#!/usr/bin/env bash
#
# Pruebas ALFA — Sys Inescolara
# Envoltura para Linux/macOS de tests/tools/alfa/scripts/run-alfa.php
#
# Uso:
#   ./tests/tools/alfa/scripts/run-alfa.sh
#   ./tests/tools/alfa/scripts/run-alfa.sh core
#   ./tests/tools/alfa/scripts/run-alfa.sh auth
#   ./tests/tools/alfa/scripts/run-alfa.sh --manual
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
RUNNER="${SCRIPT_DIR}/run-alfa.php"

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
