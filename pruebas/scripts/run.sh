#!/usr/bin/env bash
# ============================================================
# SYSINECOLARA · Ejecutor local de la colección Postman
#
# Uso:
#   POSTMAN_USUARIO=admin POSTMAN_PASSWORD='***' ./pruebas/scripts/run.sh
#   POSTMAN_USUARIO=admin POSTMAN_PASSWORD='***' ./pruebas/scripts/run.sh --csv
#
# Variables opcionales:
#   POSTMAN_BASE_URL   Sobrescribe baseUrl (ej. Render/staging)
# ============================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COLLECTION="$ROOT/pruebas/collections/SYSINECOLARA API Tests.postman_collection.json"
ENVIRONMENT="$ROOT/pruebas/environments/SysInescolara Local.postman_environment.json"
DATA="$ROOT/pruebas/data/plantas.csv"

if ! command -v postman >/dev/null 2>&1; then
  echo "ERROR: el Postman CLI no está instalado. Ver pruebas/README.md" >&2
  exit 127
fi

ARGS=("$COLLECTION" -e "$ENVIRONMENT")

if [[ -n "${POSTMAN_USUARIO:-}" ]]; then
  ARGS+=(--env-var "usuario=$POSTMAN_USUARIO")
fi

if [[ -n "${POSTMAN_PASSWORD:-}" ]]; then
  ARGS+=(--env-var "password=$POSTMAN_PASSWORD")
fi

if [[ -n "${POSTMAN_BASE_URL:-}" ]]; then
  ARGS+=(--env-var "baseUrl=$POSTMAN_BASE_URL")
fi

if [[ "${1:-}" == "--csv" ]]; then
  ARGS+=(-d "$DATA")
fi

echo "Ejecutando: postman collection run ${ARGS[*]}"
postman collection run "${ARGS[@]}"
