#!/usr/bin/env python3
"""Admin pages, uploads and access control of the plugin on GLPI 11."""
import io
import re
import struct
import sys
import zlib

import requests

import glpi as G

P = G.PLUGIN_URL
results = []


def check(name, cond, info=""):
    results.append((name, bool(cond), info))


def png_bytes(width=300, height=100):
    """A valid red PNG, built by hand (no imaging library needed)."""
    def chunk(kind, data):
        return struct.pack(">I", len(data)) + kind + data + struct.pack(">I", zlib.crc32(kind + data) & 0xFFFFFFFF)
    raw = b"".join(b"\x00" + b"\xff\x00\x00" * width for _ in range(height))
    return (b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", struct.pack(">IIBBBBB", width, height, 8, 2, 0, 0, 0))
            + chunk(b"IDAT", zlib.compress(raw)) + chunk(b"IEND", b""))


def ico_bytes(png):
    """A real one-image ICO wrapping a PNG (Vista+ format)."""
    return struct.pack("<HHH", 0, 1, 1) + struct.pack("<BBBBHHII", 32, 32, 0, 0, 1, 32, len(png), 22) + png


def uploaded(r):
    return "successfully" in r.text or "com sucesso" in r.text


def denied(r):
    return (r.status_code in (302, 401, 403)
            or any(s in r.text for s in ("Access denied", "Acesso negado", "not allowed", "não é permitida")))


G.reset_state()
admin = G.http_login("glpi", "glpi")
users = {
    "anonymous": requests.Session(),
    "technician": G.http_login("tech", "tech"),       # no "config: update" right
    "self-service": G.http_login("post-only", "postonly"),
}


def upload(target, filename, content, mime):
    token = G.csrf(admin.get(P + "/index.php?step=1").text)
    return admin.post(P + "/logo_upload.php", data={"brandkit_action": "upload_logo", "logo_target": target,
                      "_glpi_csrf_token": token}, files={"brandkit_logo": (filename, content, mime)})


def clear(target):
    token = G.csrf(admin.get(P + "/index.php?step=1").text)
    admin.post(P + "/logo_upload.php", data={"brandkit_action": "clear_logo", "logo_target": target,
               "_glpi_csrf_token": token})


log_start = G.log_size()
try:
    # Config pages
    r = admin.get(P + "/index.php?step=1")
    check("admin opens the configuration page", r.status_code == 200 and "logo_upload.php" in r.text, r.status_code)
    r = admin.get(P + "/index.php?step=2")
    check("admin opens the login page settings", r.status_code == 200 and "brandkit-login-settings-form" in r.text,
          r.status_code)

    # Access control
    for who, s in users.items():
        for path in ("/index.php", "/logo_preview.php?name=logo-GLPI-100-white.png", "/logo_upload.php"):
            r = s.get(P + path, allow_redirects=False)
            check(f"{who}: GET {path.split('?')[0]} denied", denied(r), r.status_code)
        r = s.post(P + "/logo_upload.php", data={"brandkit_action": "clear_logo", "logo_target": "favicon.ico",
                   "_glpi_csrf_token": "forged"}, allow_redirects=False)
        check(f"{who}: POST logo_upload.php denied", denied(r), r.status_code)
    r = admin.post(P + "/logo_upload.php", data={"brandkit_action": "clear_logo", "logo_target": "favicon.ico"},
                   allow_redirects=False)
    check("admin: POST without CSRF token refused", denied(r), r.status_code)

    # Logo uploads
    check("valid PNG logo accepted", uploaded(upload("logo-GLPI-100-white.png", "logo.png", png_bytes(), "image/png")))
    r = admin.get(P + "/logo_preview.php?name=logo-GLPI-100-white.png")
    check("preview returns the logo as PNG with nosniff",
          r.status_code == 200 and r.content[:8] == b"\x89PNG\r\n\x1a\n"
          and r.headers.get("X-Content-Type-Options") == "nosniff", r.headers.get("Content-Type"))
    r = admin.get(P + "/logo_preview.php?name=../../../config/config_db.php")
    check("preview refuses path traversal", r.status_code == 404 and "dbpassword" not in r.text, r.status_code)
    check("PHP script as logo refused",
          not uploaded(upload("logo-GLPI-100-white.png", "shell.php", b"<?php system($_GET['c']);", "image/png")))
    check("PNG signature followed by PHP refused",
          not uploaded(upload("logo-GLPI-100-white.png", "evil.png", b"\x89PNG\r\n\x1a\n<?php system('id');", "image/png")))
    check("target outside the logo list refused",
          not uploaded(upload("../../index.php", "logo.png", png_bytes(), "image/png")))

    # Favicon (stored as uploaded, published as GLPI's favicon)
    fake_ico = b"\x00\x00\x01\x00\x01\x00" + b"<html><script>alert(document.domain)</script></html>" * 3
    check("fake ICO (icon header + HTML) refused",
          not uploaded(upload("favicon.ico", "favicon.ico", fake_ico, "image/x-icon")))
    real_ico = ico_bytes(png_bytes(32, 32))
    check("real ICO accepted", uploaded(upload("favicon.ico", "favicon.ico", real_ico, "image/x-icon")))
    r = requests.get(G.BASE + "/pics/favicon.ico")
    check("published favicon is the uploaded one", r.content == real_ico, len(r.content))

    # Settings with hostile text: HTML, CSS and SCSS injection attempts
    token = G.csrf(admin.get(P + "/index.php?step=2").text)
    r = admin.post(P + "/index.php?step=2", data={
        "_glpi_csrf_token": token, "brandkit_action": "save_login_settings",
        "login_sso_mode": "1",
        "login_sso_title": '<img src=x onerror=alert(1)>"</style>',
        "login_sso_button_label": "\"'; } body { display:none } #{1+1} /*",
        "login_wallpaper_position": "center; } body { display:none",
        "login_panel_color": "red;}",
    })
    check("settings saved", r.status_code == 200)
    page = requests.get(G.BASE + "/?noAUTO=1").text
    check("login page renders", "login_name" in page)
    check("SSO title is HTML-escaped", "<img src=x" not in page)
    m = re.search(r'href="([^"]*css\.php\?file=[^"]*brandkit-login[^"]*)"', page)
    css = ""
    if m:
        try:
            css = requests.get(G.BASE + m.group(1).replace("&amp;", "&"), timeout=120).text
        except requests.RequestException as e:
            check("login stylesheet request answered", False, f"{m.group(1)}: {e}")
    check("generated login stylesheet compiles", m is not None and len(css) > 1000, len(css))
    outside_strings = re.sub(r"""content: ("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*');""", "content: X;", css)
    check("no CSS injection through text settings",
          "display:none" not in outside_strings and "center; }" not in css and "red;}" not in css)

    # Public logo stylesheet: no session, cacheable
    r = requests.get(P + "/brandkit.css.php")
    check("logo stylesheet is public CSS without session cookie",
          r.status_code == 200 and r.headers.get("Content-Type", "").startswith("text/css")
          and "set-cookie" not in {k.lower() for k in r.headers}, r.status_code)
    etag = r.headers.get("ETag")
    r2 = requests.get(P + "/brandkit.css.php", headers={"If-None-Match": etag or ""})
    check("logo stylesheet answers 304 when unchanged", etag and r2.status_code == 304, r2.status_code)
finally:
    token = G.csrf(admin.get(P + "/index.php?step=2").text)
    admin.post(P + "/index.php?step=2", data={"_glpi_csrf_token": token, "brandkit_action": "reset_login_defaults"})
    for target in ("favicon.ico", "logo-GLPI-100-white.png"):
        clear(target)
    G.reset_state()

errors = [line for line in G.new_log(log_start).splitlines()
          if re.search(r"\b(PHP (Warning|Notice|Deprecated|Fatal)|Uncaught|brandkit)", line, re.I)]
check("no new PHP errors in GLPI logs", not errors, errors[:5])

failed = [r for r in results if not r[1]]
print("Admin pages, uploads and access control")
for name, ok, info in results:
    print(f"  {'OK  ' if ok else 'FAIL'} {name}" + (f"  [{info}]" if not ok and info != "" else ""))
    if not ok:
        G.annotate(name, info)
print(f"  {len(results) - len(failed)}/{len(results)} passed")
sys.exit(1 if failed else 0)
