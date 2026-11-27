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

# El reporter junitfull da conteos correctos, pero es un paquete aparte. Si no
# está instalado, se usa el reporter JUnit integrado para no romper la corrida.
has_reporter() {
  local name="$1" root
  for root in "$ROOT/node_modules" "$(npm root -g 2>/dev/null || true)"; do
    [[ -n "$root" && -d "$root/$name" ]] && return 0
  done
  return 1
}

if has_reporter newman-reporter-junitfull; then
  REPORTERS="cli,junitfull"
  JUNIT_FLAG="--reporter-junitfull-export"
else
  REPORTERS="cli,junit"
  JUNIT_FLAG="--reporter-junit-export"
  echo "AVISO: newman-reporter-junitfull no está instalado; se usará el reporter JUnit integrado." >&2
  echo "       Para conteos exactos: npm install -g newman-reporter-junitfull" >&2
fi

shopt -s nullglob
COLLECTIONS=("$ROOT"/pruebas/collections/*/*.postman_collection.json)
if [[ ${#COLLECTIONS[@]} -eq 0 ]]; then
  echo "No se encontraron colecciones en pruebas/collections/*/" >&2
  exit 1
fi

FAILED=()
for collection in "${COLLECTIONS[@]}"; do
  name="$(basename "$collection" .postman_collection.json)"
  echo "=== $name ==="
  if ! newman run "$collection" \
    -e "$ENV" \
    --env-var "baseUrl=$BASE_URL" \
    --env-var "usuario=${POSTMAN_USUARIO:-admin}" \
    --env-var "password=${POSTMAN_PASSWORD:-}" \
    --env-var "viewerPassword=${POSTMAN_VIEWER_PASSWORD:-}" \
    --reporters "$REPORTERS" \
    "$JUNIT_FLAG" "$OUT/$name.xml"; then
    FAILED+=("$name")
  fi
  [[ -f "$OUT/$name.xml" ]] || FAILED+=("$name (sin JUnit)")
done

if [[ ${#FAILED[@]} -gt 0 ]]; then
  echo "Colecciones con fallo: ${FAILED[*]}" >&2
  exit 1
fi

echo "Resultados JUnit en: $OUT"
