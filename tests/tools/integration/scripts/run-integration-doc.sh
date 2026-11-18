#!/usr/bin/env bash
#
# run-integration-doc.sh — ejecuta las pruebas de integración y llena el Word.
#
# Equivalente Bash de run-integration-doc.ps1. Para cada módulo ejecuta su
# archivo de integración con PHPUnit, genera una captura PNG con Chrome
# headless y rellena una COPIA del formato Word. El original nunca se modifica.
#
# Uso:
#   ./tests/tools/integration/scripts/run-integration-doc.sh
#   ./tests/tools/integration/scripts/run-integration-doc.sh --module=29
#   ./tests/tools/integration/scripts/run-integration-doc.sh --module=Backup --output=/tmp/cuadro-29.docx
#   CHROME_BIN=/usr/bin/chromium ./tests/tools/integration/scripts/run-integration-doc.sh
#
# Requisitos: PHP 8.2+, PHPUnit en vendor/, Chrome o Chromium (CHROME_BIN).
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
ENGINE="${SCRIPT_DIR}/lib/integration-doc.php"

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
    echo "[ERROR] No se encontró '${PHP_BIN}' en el PATH." >&2
    exit 3
fi

if [[ ! -f "${ENGINE}" ]]; then
    echo "[ERROR] No se encontró el motor: ${ENGINE}" >&2
    exit 3
fi

cd "${ROOT_DIR}"
exec "${PHP_BIN}" "${ENGINE}" "$@"
