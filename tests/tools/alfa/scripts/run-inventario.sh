#!/usr/bin/env bash
#
# Prueba ALFA independiente: Inventario.
# Equivale a: run-alfa.sh --test=inventario
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "${SCRIPT_DIR}/run-alfa.sh" --test=inventario "$@"
