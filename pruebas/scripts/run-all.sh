#!/usr/bin/env bash
# ============================================================
# SYSINECOLARA · Correr TODAS las colecciones y exportar JUnit
#
# Uso:
#   ./pruebas/scripts/run-all.sh
#
# Salida: results/*.xml (un JUnit por colección).
# ============================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV="$ROOT/pruebas/environments/SysInescolara Local.postman_environment.json"
OUT="$ROOT/results"

if ! command -v newman >/dev/null 2>&1; then
  echo "ERROR: newman no está instalado. Ejecuta: npm install -g newman" >&2
  exit 127
fi

BASE_URL="${POSTMAN_BASE_URL:-http://localhost/sys-inescolara}"
mkdir -p "$OUT"

shopt -s nullglob
COLLECTIONS=("$ROOT"/pruebas/collections/*/*.postman_collection.json)
if [[ ${#COLLECTIONS[@]} -eq 0 ]]; then
  echo "No se encontraron colecciones en pruebas/collections/*/" >&2
  exit 1
fi

for collection in "${COLLECTIONS[@]}"; do
  name="$(basename "$collection" .postman_collection.json)"
  echo "=== $name ==="
  newman run "$collection" \
    -e "$ENV" \
    --env-var "baseUrl=$BASE_URL" \
    --env-var "usuario=${POSTMAN_USUARIO:-admin}" \
    --env-var "password=${POSTMAN_PASSWORD:-}" \
    --env-var "viewerPassword=${POSTMAN_VIEWER_PASSWORD:-}" \
    --reporters cli,junit \
    --reporter-junit-export "$OUT/$name.xml"
done

echo "Resultados JUnit en: $OUT"
