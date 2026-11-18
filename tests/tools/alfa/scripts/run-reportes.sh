#!/usr/bin/env bash
#
# Prueba ALFA independiente: Reportes.
# Equivale a: run-alfa.sh --test=reportes
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "${SCRIPT_DIR}/run-alfa.sh" --test=reportes "$@"
