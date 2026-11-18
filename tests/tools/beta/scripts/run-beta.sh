#!/usr/bin/env bash
#
# Pruebas BETA — Sys Inescolara
# Envoltura para Linux/macOS de tests/tools/beta/scripts/run-beta.php
#
# Uso:
#   ./tests/tools/beta/scripts/run-beta.sh
#   ./tests/tools/beta/scripts/run-beta.sh humo
#   ./tests/tools/beta/scripts/run-beta.sh humo --base-url=https://mi-app.onrender.com
#   ./tests/tools/beta/scripts/run-beta.sh api --usuario=admin
#   ./tests/tools/beta/scripts/run-beta.sh checklist
#   ./tests/tools/beta/scripts/run-beta.sh --manual
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
RUNNER="${SCRIPT_DIR}/run-beta.php"

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
