"""Helpers shared by the end-to-end tests: GLPI instance, database, users.

Everything is configured through environment variables, so the same tests
run on a developer machine and in GitHub Actions:

  GLPI_URL      base URL of GLPI            (default http://127.0.0.1:8080)
  GLPI_DIR      GLPI install directory      (default ./glpi)
  DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME   (default 127.0.0.1, 3306, glpi, glpi, glpi)
  PHP_CMD       command running PHP as the web server user (default "php")
  REDIS_FLUSH   "1" to empty Redis (PHP sessions) between test rounds
"""
import os
import re
import shlex
import shutil
import subprocess
from pathlib import Path

import requests

BASE = os.environ.get("GLPI_URL", "http://127.0.0.1:8080").rstrip("/")
GLPI_DIR = Path(os.environ.get("GLPI_DIR", "glpi")).resolve()
PHP_CMD = shlex.split(os.environ.get("PHP_CMD", "php"))
PLUGIN_URL = BASE + "/plugins/brandkit/front"
HERE = Path(__file__).resolve().parent

# Users created by a fresh GLPI install.
USERS = {"glpi": 2, "post-only": 3, "tech": 4, "normal": 5}
DEFAULT_PASSWORDS = {"glpi": "glpi", "post-only": "postonly", "tech": "tech", "normal": "normal"}

PHP_LOG = GLPI_DIR / "files" / "_log" / "php-errors.log"


def _mysql_args():
    return [
        "mysql",
        "-h", os.environ.get("DB_HOST", "127.0.0.1"),
        "-P", os.environ.get("DB_PORT", "3306"),
        "-u", os.environ.get("DB_USER", "glpi"),
        f"-p{os.environ.get('DB_PASSWORD', 'glpi')}",
        os.environ.get("DB_NAME", "glpi"),
        "-N", "-B",
    ]


def sql(query, params=()):
    """Run a query; %s placeholders are filled with escaped string literals."""
    if params:
        escaped = tuple("'" + str(p).replace("\\", "\\\\").replace("'", "\\'") + "'" for p in params)
        query = query % escaped
    return subprocess.run(_mysql_args(), input=query, check=True,
                          capture_output=True, text=True).stdout.strip()


def php_eval(code, *args):
    return subprocess.run(["php", "-r", code, "--", *args], check=True,
                          capture_output=True, text=True).stdout


def password_is(user, password):
    stored = sql("SELECT password FROM glpi_users WHERE id=%d" % USERS[user])
    return php_eval('echo password_verify($argv[1], $argv[2]) ? "yes" : "no";', password, stored) == "yes"


def set_password(user, password, last_update=None):
    hashed = php_eval('echo password_hash($argv[1], PASSWORD_DEFAULT);', password)
    if last_update is None:
        sql("UPDATE glpi_users SET password=%s, password_last_update=NOW(), password_forget_token='', "
            "password_forget_token_date=NULL WHERE id=" + str(USERS[user]), (hashed,))
    else:
        sql("UPDATE glpi_users SET password=%s, password_last_update=%s, password_forget_token='', "
            "password_forget_token_date=NULL WHERE id=" + str(USERS[user]), (hashed, last_update))


def set_config(name, value, context="core"):
    sql("DELETE FROM glpi_configs WHERE context=%s AND name=%s", (context, name))
    sql("INSERT INTO glpi_configs (context, name, value) VALUES (%s, %s, %s)", (context, name, value))
    clear_cache()


def clear_cache():
    cache = GLPI_DIR / "files" / "_cache"
    if cache.is_dir():
        for entry in cache.iterdir():
            if entry.is_dir():
                shutil.rmtree(entry, ignore_errors=True)
            else:
                try:
                    entry.unlink()
                except OSError:
                    pass


def encrypt(value):
    """Encrypt like GLPIKey::encrypt() (needs read access to glpicrypt.key)."""
    return subprocess.run(PHP_CMD + [str(HERE / "encrypt.php"), str(GLPI_DIR), value],
                          check=True, capture_output=True, text=True).stdout


def reset_state(sso=False):
    for user, password in DEFAULT_PASSWORDS.items():
        set_password(user, password)
    set_config("password_expiration_delay", "-1")
    # The lost password page needs password-forget notifications enabled
    # (the token is set directly in the database: no email is sent).
    set_config("use_notifications", "1")
    set_config("notifications_mailing", "1")
    set_config("login_sso_mode", "1" if sso else "0", "plugin:brandkit")
    if os.environ.get("REDIS_FLUSH") == "1":
        subprocess.run(["redis-cli", "FLUSHALL"], check=False, capture_output=True)


def log_size(path=PHP_LOG):
    try:
        return path.stat().st_size
    except FileNotFoundError:
        return 0


def new_log(since, path=PHP_LOG):
    try:
        with open(path, "rb") as f:
            f.seek(since)
            return f.read().decode(errors="replace")
    except FileNotFoundError:
        return ""


def csrf(html):
    m = re.search(r'name="_glpi_csrf_token" value="([^"]+)"', html)
    return m.group(1) if m else None


def http_login(user, password):
    s = requests.Session()
    r = s.get(f"{BASE}/?noAUTO=1")
    s.post(f"{BASE}/front/login.php", data={"_glpi_csrf_token": csrf(r.text), "noAUTO": "1",
           "login_name": user, "login_password": password, "submit": ""})
    return s
