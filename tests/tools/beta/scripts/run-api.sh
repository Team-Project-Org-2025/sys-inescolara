#!/usr/bin/env bash
#
# Prueba BETA independiente: API con Postman.
# Equivale a: run-beta.sh --test=api
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "${SCRIPT_DIR}/run-beta.sh" --test=api "$@"
