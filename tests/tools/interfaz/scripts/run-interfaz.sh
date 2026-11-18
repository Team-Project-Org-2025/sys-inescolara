#!/usr/bin/env bash
#
# run-interfaz.sh — suite de pruebas de interfaz con Selenium.
#
# Equivalente Bash de run-interfaz.ps1. Requiere Apache, MySQL y ChromeDriver
# disponibles. Si no se indica --suite, ejecuta todas las suites. Guarda el
# JUnit en tests/tools/interfaz/formatos/resultados/junit-<suite>.xml.
#
# Uso:
#   ./tests/tools/interfaz/scripts/run-interfaz.sh
#   ./tests/tools/interfaz/scripts/run-interfaz.sh --suite Inventario
#   ./tests/tools/interfaz/scripts/run-interfaz.sh --filter VentasTest --headless
#   ./tests/tools/interfaz/scripts/run-interfaz.sh --skip-seed
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
PHPUNIT="${ROOT_DIR}/vendor/phpunit/phpunit/phpunit"

SUITE="all"
FILTER=""
HEADLESS=0
SKIP_SEED=0

usage() {
    sed -n '2,14p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --suite) SUITE="${2:-}"; shift 2 ;;
        --suite=*) SUITE="${1#*=}"; shift ;;
        --filter) FILTER="${2:-}"; shift 2 ;;
        --filter=*) FILTER="${1#*=}"; shift ;;
        --headless) HEADLESS=1; shift ;;
        --skip-seed) SKIP_SEED=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *)
            echo "ERROR: opción desconocida: $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
    echo "ERROR: no se encontró '${PHP_BIN}' en el PATH." >&2
    exit 3
fi

if [[ ! -f "${PHPUNIT}" ]]; then
    echo "ERROR: falta PHPUnit en ${PHPUNIT}. Ejecuta: composer install" >&2
    exit 3
fi

cd "${ROOT_DIR}"

if [[ "${HEADLESS}" -eq 1 ]]; then
    export INTERFAZ_HEADLESS=1
fi

if [[ "${SKIP_SEED}" -eq 0 ]]; then
    if ! "${PHP_BIN}" 'tests/tools/interfaz/scripts/lib/seed-interfaz.php'; then
        echo "ERROR: no se pudo preparar el usuario de prueba." >&2
        exit 1
    fi
fi

RESULTS_DIR="${ROOT_DIR}/tests/tools/interfaz/formatos/resultados"
mkdir -p "${RESULTS_DIR}"

if [[ -n "${FILTER}" ]]; then
    LOG_NAME="filter-$(printf '%s' "${FILTER}" | sed 's/[^A-Za-z0-9_-]/_/g')"
else
    LOG_NAME="$(printf '%s' "${SUITE}" | tr '[:upper:]' '[:lower:]')"
fi
LOG_PATH="${RESULTS_DIR}/junit-${LOG_NAME}.xml"

ARGS=(-c 'tests/Interfaz/phpunit.xml' --do-not-cache-result --log-junit "${LOG_PATH}")
if [[ -n "${FILTER}" ]]; then
    ARGS+=(--filter "${FILTER}")
elif [[ "${SUITE}" != "all" ]]; then
    ARGS+=(--testsuite "${SUITE}")
fi

echo "==> Ejecutando pruebas de interfaz (${LOG_NAME})"
set +e
"${PHP_BIN}" "${PHPUNIT}" "${ARGS[@]}"
CODE=$?
set -e

echo
echo "Resultados: ${LOG_PATH}"
echo "Capturas:   tests/tools/interfaz/formatos/resultados/screenshots"
exit "${CODE}"
