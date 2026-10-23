#!/usr/bin/env bash
# Install a clean GLPI with this plugin active and serve it with PHP's
# built-in server. Used by GitHub Actions; runs the same way locally.
#
# Environment (defaults in brackets):
#   GLPI_VERSION [11.0.11]   GLPI_DIR [./glpi]   GLPI_PORT [8080]
#   DB_HOST [127.0.0.1] DB_PORT [3306] DB_USER [glpi] DB_PASSWORD [glpi] DB_NAME [glpi]
#   GLPI_TGZ      local GLPI archive to use instead of downloading it
#   PHP_SESSION_REDIS  "tcp://host:port" to keep PHP sessions in Redis
#                      (phpredis, no locking: the setup used in production)
set -euo pipefail

PLUGIN_SRC="$(cd "$(dirname "$0")/../.." && pwd)"
GLPI_VERSION="${GLPI_VERSION:-11.0.11}"
GLPI_DIR="${GLPI_DIR:-$PWD/glpi}"
GLPI_PORT="${GLPI_PORT:-8080}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-glpi}"
DB_PASSWORD="${DB_PASSWORD:-glpi}"
DB_NAME="${DB_NAME:-glpi}"

console() {
    local extra=()
    [ "$(id -u)" = "0" ] && extra=(--allow-superuser)
    php "$GLPI_DIR/bin/console" "${extra[@]}" --no-interaction "$@"
}

echo "::group::GLPI $GLPI_VERSION"
rm -rf "$GLPI_DIR"
mkdir -p "$GLPI_DIR"
if [ -n "${GLPI_TGZ:-}" ]; then
    tar -xzf "$GLPI_TGZ" -C "$GLPI_DIR" --strip-components=1
else
    curl -fsSL "https://github.com/glpi-project/glpi/releases/download/${GLPI_VERSION}/glpi-${GLPI_VERSION}.tgz" \
        | tar -xz -C "$GLPI_DIR" --strip-components=1
fi
grep -q "^${GLPI_VERSION}" "$GLPI_DIR/version" 2>/dev/null || cat "$GLPI_DIR/version" 2>/dev/null || true
echo "::endgroup::"

echo "::group::Database"
console database:install -H "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_PASSWORD" -d "$DB_NAME" \
    --default-language=en_GB --no-telemetry --force
echo "::endgroup::"

echo "::group::Plugin"
mkdir -p "$GLPI_DIR/plugins/brandkit"
tar -C "$PLUGIN_SRC" --exclude=.git --exclude=./glpi -cf - . | tar -C "$GLPI_DIR/plugins/brandkit" -xf -
console plugin:install --username=glpi brandkit
console plugin:activate brandkit
console plugin:list | grep -i brandkit
echo "::endgroup::"

echo "::group::Web server on port $GLPI_PORT"
php_ini=(-d display_errors=0 -d log_errors=1 -d error_log="$GLPI_DIR/files/_log/php-server.log")
if [ -n "${PHP_SESSION_REDIS:-}" ]; then
    php_ini+=(-d session.save_handler=redis -d "session.save_path=${PHP_SESSION_REDIS}" -d redis.session.locking_enabled=0)
fi
# Several workers, so the browser's parallel requests really run in parallel.
PHP_CLI_SERVER_WORKERS=4 nohup php "${php_ini[@]}" -S "127.0.0.1:${GLPI_PORT}" -t "$GLPI_DIR/public" \
    "$GLPI_DIR/public/index.php" > "$GLPI_DIR/files/_log/php-server-access.log" 2>&1 &
for _ in $(seq 1 30); do
    if curl -fsS -o /dev/null "http://127.0.0.1:${GLPI_PORT}/?noAUTO=1"; then
        echo "GLPI is up"
        echo "::endgroup::"
        exit 0
    fi
    sleep 1
done
echo "GLPI did not start" >&2
tail -50 "$GLPI_DIR/files/_log/"*.log >&2 || true
exit 1
