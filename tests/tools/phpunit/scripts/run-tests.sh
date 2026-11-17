#!/usr/bin/env bash
#
# Ejecutor multiplataforma de pruebas — Sys Inescolara
# Envoltura para Linux/macOS de tests/tools/phpunit/scripts/run-tests.php
#
# Uso:
#   ./tests/tools/phpunit/scripts/run-tests.sh
#   ./tests/tools/phpunit/scripts/run-tests.sh --suite=integration
#   ./tests/tools/phpunit/scripts/run-tests.sh --suite=smoke
#   ./tests/tools/phpunit/scripts/run-tests.sh --module=13
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
RUNNER="${SCRIPT_DIR}/run-tests.php"

if ! command -v php >/dev/null 2>&1; then
    echo "[ERROR] No se encontró el ejecutable 'php' en el PATH." >&2
    echo "        Instale PHP 8.2+ y vuelva a intentarlo." >&2
    exit 3
fi

if [ ! -f "${RUNNER}" ]; then
    echo "[ERROR] No se encontró el ejecutor: ${RUNNER}" >&2
    exit 3
fi

cd "${ROOT_DIR}"
exec php "${RUNNER}" "$@"
