#!/usr/bin/env python3
"""Password changes on GLPI 11 with the plugin active, in a real browser.

A real browser matters: the bug fixed in 0.4.2 came from a request the page
makes on its own (the plugin stylesheet), which a plain HTTP client never
sends. Flows covered:

  self-central   tech changes their own password (front/updatepassword.php)
  self-helpdesk  post-only changes their own password (simplified interface)
  expired        normal logs in with an expired password and must change it
  admin-other    glpi changes the password of 'normal' (user form modal)
  lost           tech resets a forgotten password with the emailed token

Usage: test_passwords.py [rounds] [--sso]
"""
import re
import secrets
import sys

from playwright.sync_api import sync_playwright

import glpi as G

SUCCESS = "Your password has been successfully updated"
SUCCESS_PT = "Sua senha foi atualizada com sucesso"


def login(page, user, password, sso=False):
    page.goto(f"{G.BASE}/?noAUTO=1" + ("&bypass=1" if sso else ""))
    page.fill("#login_name", user)
    page.fill("#login_password", password)
    page.click("button[name=submit]")
    page.wait_for_load_state("networkidle")


def submit_update_password(page, current, new):
    page.fill("input[name=current_password]", current)
    page.fill("input[name=password]", new)
    page.fill("input[name=password2]", new)
    page.click("button[name=update]")
    page.wait_for_load_state("networkidle")
    return page.content()


def succeeded(html):
    return SUCCESS in html or SUCCESS_PT in html


def page_text(html):
    text = re.sub(r"<script.*?</script>|<style.*?</style>", " ", html, flags=re.S)
    text = re.sub(r"<[^>]+>", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def new_password():
    return "Nw!" + secrets.token_hex(6)


def t_self(browser, user, password, sso):
    ctx = browser.new_context()
    page = ctx.new_page()
    login(page, user, password, sso)
    page.goto(f"{G.BASE}/front/updatepassword.php")
    page.wait_for_load_state("networkidle")
    new = new_password()
    html = submit_update_password(page, password, new)
    ok = succeeded(html) and G.password_is(user, new)
    ctx.close()
    return ok, "" if ok else page_text(html)[:300]


def t_expired(browser, sso):
    G.set_config("password_expiration_delay", "30")
    G.set_password("normal", "normal", "2020-01-01 00:00:00")
    try:
        ctx = browser.new_context()
        page = ctx.new_page()
        login(page, "normal", "normal", sso)
        landed = page.url
        new = new_password()
        html = submit_update_password(page, "normal", new)
        ok = "updatepassword.php" in landed and succeeded(html) and G.password_is("normal", new)
        detail = "" if ok else f"landed={landed} | {page_text(html)[:300]}"
        if ok:
            # The new password must open a normal (complete) session.
            page.goto(f"{G.BASE}/front/logout.php?noAUTO=1")
            login(page, "normal", new, sso)
            ok = "updatepassword.php" not in page.url and page.locator("#login_name").count() == 0
            detail = "" if ok else f"re-login failed, url={page.url}"
        ctx.close()
        return ok, detail
    finally:
        G.set_config("password_expiration_delay", "-1")


def t_admin_other(browser, sso):
    ctx = browser.new_context()
    page = ctx.new_page()
    login(page, "glpi", "glpi", sso)
    page.goto(f"{G.BASE}/front/user.form.php?id={G.USERS['normal']}")
    page.wait_for_load_state("networkidle")
    page.click("[data-bs-target^='#modal_password_']")
    modal = page.locator("[id^=modal_password_]")
    modal.locator("input[name=password]").wait_for(state="visible")
    new = new_password()
    modal.locator("input[name=password]").fill(new)
    modal.locator("input[name=password2]").fill(new)
    modal.locator(".btn-close").first.click()
    page.wait_for_timeout(400)
    page.click("button[name=update]")
    page.wait_for_load_state("networkidle")
    html = page.content()
    ok = G.password_is("normal", new)
    ctx.close()
    return ok, "" if ok else page_text(html)[:300]


def t_lost(browser, sso):
    token = secrets.token_hex(20)
    G.sql("UPDATE glpi_users SET password_forget_token=%s, "
          "password_forget_token_date=DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id=" + str(G.USERS["tech"]),
          (G.encrypt(token),))
    ctx = browser.new_context()
    page = ctx.new_page()
    page.goto(f"{G.BASE}/front/lostpassword.php?password_forget_token={token}")
    page.wait_for_load_state("networkidle")
    visible = page.locator("input[name=password]").is_visible()
    new = new_password()
    page.fill("input[name=password]", new)
    page.fill("input[name=password2]", new)
    page.click("form button[name=update]")
    page.wait_for_load_state("networkidle")
    html = page.content()
    ok = visible and G.password_is("tech", new)
    ctx.close()
    return ok, "" if ok else f"fields visible={visible} | {page_text(html)[:300]}"


def main():
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    rounds = int(args[0]) if args else 3
    sso = "--sso" in sys.argv
    results = {}
    log_start = G.log_size()
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for _ in range(rounds):
            G.reset_state(sso)
            cases = {
                "self-central": lambda: t_self(browser, "tech", "tech", sso),
                "self-helpdesk": lambda: t_self(browser, "post-only", "postonly", sso),
                "expired": lambda: t_expired(browser, sso),
                "admin-other": lambda: t_admin_other(browser, sso),
                "lost": lambda: t_lost(browser, sso),
            }
            for name, case in cases.items():
                try:
                    ok, detail = case()
                except Exception as e:  # noqa: BLE001 - report and go on
                    ok, detail = False, "exception: " + str(e).splitlines()[0][:250]
                results.setdefault(name, []).append((ok, detail))
        browser.close()
    G.reset_state(False)

    failed = 0
    print(f"Password flows, {rounds} round(s), SSO mode {'on' if sso else 'off'}")
    for name, runs in results.items():
        passed = sum(1 for ok, _ in runs if ok)
        failed += len(runs) - passed
        print(f"  {'OK  ' if passed == len(runs) else 'FAIL'} {name:14s} {passed}/{len(runs)}")
        for ok, detail in runs:
            if not ok:
                print(f"         {detail}")
                G.annotate(f"password flow {name}", detail)
    errors = [line for line in G.new_log(log_start).splitlines()
              if re.search(r"\b(PHP (Warning|Notice|Deprecated|Fatal)|Uncaught|brandkit)", line, re.I)]
    if errors:
        print("New PHP errors:\n  " + "\n  ".join(errors[:30]))
        G.annotate("PHP errors", "\n".join(errors[:30]))
        failed += 1
    sys.exit(1 if failed else 0)


if __name__ == "__main__":
    main()
