#!/usr/bin/env bash
#
# generate_aceptacion.sh — regenera la matriz de aceptación.
#
# Envoltura para Linux/macOS de
# tests/tools/reportes/scripts/lib/generate_aceptacion.php.
# Lee el JUnit de Codeception en
# tests/tools/aceptacion/formatos/resultados/report.xml y escribe ACEPTACION.md
# en tests/tools/reportes/formatos/resultados/.
#
# Uso:
#   ./tests/tools/reportes/scripts/linux/generate_aceptacion.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
RUNNER="${SCRIPT_DIR}/../lib/generate_aceptacion.php"

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
    echo "[ERROR] No se encontró '${PHP_BIN}' en el PATH." >&2
    exit 3
fi

if [[ ! -f "${RUNNER}" ]]; then
    echo "[ERROR] No se encontró el generador: ${RUNNER}" >&2
    exit 3
fi

cd "${ROOT_DIR}"
exec "${PHP_BIN}" "${RUNNER}" "$@"
