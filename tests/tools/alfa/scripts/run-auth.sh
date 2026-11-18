#!/usr/bin/env bash
#
# Prueba ALFA independiente: Autenticación y acceso.
# Equivale a: run-alfa.sh --test=auth
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "${SCRIPT_DIR}/run-alfa.sh" --test=auth "$@"
