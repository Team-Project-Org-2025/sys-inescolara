#!/usr/bin/env bash
#
# Prueba BETA independiente: checklist manual guiado.
# Equivale a: run-beta.sh --test=checklist
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "${SCRIPT_DIR}/run-beta.sh" --test=checklist "$@"
