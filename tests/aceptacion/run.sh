#!/usr/bin/env bash
#
# Ejecuta la suite de aceptación (Codeception + WebDriver) de Sys Inescolara.
#
#   ./tests/aceptacion/run.sh              # toda la suite
#   ./tests/aceptacion/run.sh AutenticacionCest
#   ACEPTACION_BROWSER=/usr/bin/chromium ./tests/aceptacion/run.sh
#
# El script levanta, si no están ya activos:
#   - la aplicación en un servidor PHP embebido (tests/aceptacion/server)
#   - chromedriver en el puerto 9515
# y los detiene al terminar.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ACE_DIR="$ROOT/tests/aceptacion"
APP_PORT="${ACEPTACION_PORT:-8099}"
DRIVER_PORT="${ACEPTACION_DRIVER_PORT:-9515}"
APP_URL="${ACEPTACION_URL:-http://127.0.0.1:${APP_PORT}/}"
BROWSER_BIN="${ACEPTACION_BROWSER:-/opt/brave-bin/brave}"
DRIVER_BIN="${ACEPTACION_DRIVER_BIN:-$ACE_DIR/bin/chromedriver}"
DRIVER_VERSION="${ACEPTACION_DRIVER_VERSION:-156.0.8078.80}"
RESULT_DIR="$ACE_DIR/_output"

APP_PID=""
DRIVER_PID=""

cleanup() {
    if [[ -n "$APP_PID" ]] && kill -0 "$APP_PID" 2>/dev/null; then
        kill "$APP_PID" 2>/dev/null || true
        wait "$APP_PID" 2>/dev/null || true
    fi
    if [[ -n "$DRIVER_PID" ]] && kill -0 "$DRIVER_PID" 2>/dev/null; then
        kill "$DRIVER_PID" 2>/dev/null || true
        wait "$DRIVER_PID" 2>/dev/null || true
    fi
}
trap cleanup EXIT

port_open() {
    (exec 3<>"/dev/tcp/127.0.0.1/$1") >/dev/null 2>&1
}

wait_for_port() {
    local port="$1" timeout="${2:-30}" i=0
    while ! port_open "$port"; do
        i=$((i + 1))
        if [[ "$i" -ge $((timeout * 10)) ]]; then
            echo "ERROR: el puerto $port no respondió en ${timeout}s" >&2
            return 1
        fi
        sleep 0.1
    done
}

ensure_driver() {
    if [[ -x "$DRIVER_BIN" ]]; then
        return
    fi

    echo ">>> Descargando ChromeDriver $DRIVER_VERSION..."
    mkdir -p "$(dirname "$DRIVER_BIN")"
    local tmp
    tmp="$(mktemp -d)"
    curl -fsSL -o "$tmp/chromedriver.zip" \
        "https://storage.googleapis.com/chrome-for-testing-public/${DRIVER_VERSION}/linux64/chromedriver-linux64.zip"
    unzip -q -o "$tmp/chromedriver.zip" -d "$tmp"
    cp "$tmp/chromedriver-linux64/chromedriver" "$DRIVER_BIN"
    chmod +x "$DRIVER_BIN"
    rm -rf "$tmp"
}

command -v php >/dev/null || { echo "ERROR: PHP no está instalado." >&2; exit 1; }
[[ -x "$ROOT/vendor/bin/codecept" ]] || { echo "ERROR: falta Codeception. Ejecute composer install." >&2; exit 1; }
[[ -x "$BROWSER_BIN" ]] || { echo "ERROR: no se encontró el navegador $BROWSER_BIN (use ACEPTACION_BROWSER)." >&2; exit 1; }

mkdir -p "$RESULT_DIR"
ensure_driver

if port_open "$APP_PORT"; then
    echo ">>> Servidor de aplicación ya activo en el puerto $APP_PORT"
else
    echo ">>> Levantando aplicación en $APP_URL"
    ACEPTACION_URL="$APP_URL" php -S "127.0.0.1:${APP_PORT}" -t "$ROOT" \
        "$ACE_DIR/server/router.php" >"$RESULT_DIR/php-server.log" 2>&1 &
    APP_PID=$!
    wait_for_port "$APP_PORT" 30
fi

if port_open "$DRIVER_PORT"; then
    echo ">>> ChromeDriver ya activo en el puerto $DRIVER_PORT"
else
    echo ">>> Levantando ChromeDriver en el puerto $DRIVER_PORT"
    "$DRIVER_BIN" --port="$DRIVER_PORT" --log-path="$RESULT_DIR/chromedriver.log" \
        >"$RESULT_DIR/chromedriver.out" 2>&1 &
    DRIVER_PID=$!
    wait_for_port "$DRIVER_PORT" 30
fi

echo ">>> Preparando usuario de pruebas (e2e_admin)"
php "$ROOT/tests/tools/seed-interfaz.php"

echo ">>> Ejecutando Codeception"
cd "$ROOT"
export ACEPTACION_URL="$APP_URL"
export ACEPTACION_BROWSER="$BROWSER_BIN"
export ACEPTACION_DRIVER_PORT="$DRIVER_PORT"

set +e
if [[ -n "${1:-}" ]]; then
    vendor/bin/codecept -c tests/aceptacion run "$1" --xml --no-interaction
else
    vendor/bin/codecept -c tests/aceptacion run --xml --no-interaction
fi
STATUS=$?
set -e

echo ">>> Reporte JUnit: $RESULT_DIR/report.xml"
exit "$STATUS"
