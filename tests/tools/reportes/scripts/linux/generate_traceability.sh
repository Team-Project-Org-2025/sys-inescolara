#!/usr/bin/env bash
#
# generate_traceability.sh — regenera la matriz de trazabilidad.
#
# Envoltura para Linux/macOS de
# tests/tools/reportes/scripts/lib/generate_traceability.php.
# Lee los JUnit de tests/tools/phpunit/formatos/resultados/ y escribe
# TRACEABILITY.md y traceability.json en tests/tools/reportes/formatos/resultados/.
#
# Uso:
#   ./tests/tools/reportes/scripts/linux/generate_traceability.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
RUNNER="${SCRIPT_DIR}/../lib/generate_traceability.php"

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
