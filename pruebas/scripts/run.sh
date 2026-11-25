#!/usr/bin/env bash
# ============================================================
# SYSINECOLARA · Ejecutor de colecciones Postman con Newman
#
# Uso:
#   ./pruebas/scripts/run.sh                              # corre las 29 colecciones
#   ./pruebas/scripts/run.sh "pruebas/collections/...json" # corre una sola
#
# Variables opcionales:
#   POSTMAN_BASE_URL         Sobrescribe baseUrl (p. ej. Render/staging)
#   POSTMAN_PASSWORD         Contraseña del administrador (admin)
#   POSTMAN_VIEWER_PASSWORD  Contraseña de postman_viewer
# ============================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV="$ROOT/pruebas/environments/SysInescolara Local.postman_environment.json"

if ! command -v newman >/dev/null 2>&1; then
  echo "ERROR: newman no está instalado. Ejecuta: npm install -g newman" >&2
  exit 127
fi

BASE_URL="${POSTMAN_BASE_URL:-http://localhost/sys-inescolara}"

run_one() {
  local collection="$1"
  echo "=== $(basename "$collection" .postman_collection.json) ==="
  newman run "$collection" \
    -e "$ENV" \
    --env-var "baseUrl=$BASE_URL" \
    --env-var "usuario=${POSTMAN_USUARIO:-admin}" \
    --env-var "password=${POSTMAN_PASSWORD:-}" \
    --env-var "viewerPassword=${POSTMAN_VIEWER_PASSWORD:-}"
}

if [[ "${1:-}" == *.json ]]; then
  run_one "$1"
  exit 0
fi

# Sin argumento: recorrer todas las colecciones por módulo.
shopt -s nullglob
COLLECTIONS=("$ROOT"/pruebas/collections/*/*.postman_collection.json)
if [[ ${#COLLECTIONS[@]} -eq 0 ]]; then
  echo "No se encontraron colecciones en pruebas/collections/*/" >&2
  exit 1
fi

for collection in "${COLLECTIONS[@]}"; do
  run_one "$collection"
done
