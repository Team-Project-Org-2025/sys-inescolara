#!/usr/bin/env bash
#
# run-module.sh — Unit + Integration + Smoke de uno o de los 31 módulos.
#
# Equivalente Bash de run-module.ps1. Compara los conteos reales contra
# tests/tools/reportes/formatos/resultados/traceability.json.
#
# Uso:
#   ./tests/tools/phpunit/scripts/linux/run-module.sh
#   ./tests/tools/phpunit/scripts/linux/run-module.sh all
#   ./tests/tools/phpunit/scripts/linux/run-module.sh Planta
#   ./tests/tools/phpunit/scripts/linux/run-module.sh 01
#   ./tests/tools/phpunit/scripts/linux/run-module.sh "Cuenta por cobrar"
#
# Códigos de salida:
#   0  Todos los módulos coinciden con la matriz.
#   1  Al menos un módulo no coincide.
#   2  El filtro no corresponde a ningún módulo o la opción no es válida.
#   3  Error de entorno (PHP, PHPUnit, configuración o matriz ausente).
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
PHPUNIT="${ROOT_DIR}/vendor/phpunit/phpunit/phpunit"
CONFIG="${ROOT_DIR}/tests/phpunit.xml"
MATRIX="${ROOT_DIR}/tests/tools/reportes/formatos/resultados/traceability.json"
RESULTS="${ROOT_DIR}/tests/tools/phpunit/formatos/resultados"
MODULE="${1:-all}"

if [[ "${MODULE}" == "-h" || "${MODULE}" == "--help" ]]; then
    sed -n '2,20p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
    exit 0
fi

if [[ $# -gt 1 ]]; then
    echo "ERROR: solo se admite un argumento (módulo o 'all')." >&2
    exit 2
fi

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
    echo "ERROR: no se encontró '${PHP_BIN}' en el PATH." >&2
    exit 3
fi

if [[ ! -f "${PHPUNIT}" ]]; then
    echo "ERROR: falta PHPUnit en ${PHPUNIT}. Ejecuta: composer install" >&2
    exit 3
fi

if [[ ! -f "${CONFIG}" ]]; then
    echo "ERROR: falta la configuración ${CONFIG}." >&2
    exit 3
fi

if [[ ! -f "${MATRIX}" ]]; then
    echo "ERROR: falta la matriz ${MATRIX}." >&2
    echo "       Genera primero: php tests/tools/reportes/scripts/lib/generate_traceability.php" >&2
    exit 3
fi

mkdir -p "${RESULTS}"
TMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/run-module.XXXXXX")"
trap 'rm -rf "${TMP_DIR}"' EXIT

if [[ -t 1 ]]; then
    C_CYAN=$'\033[36m'; C_GREEN=$'\033[32m'; C_RED=$'\033[31m'
    C_YELLOW=$'\033[33m'; C_GRAY=$'\033[90m'; C_RESET=$'\033[0m'
else
    C_CYAN=""; C_GREEN=""; C_RED=""; C_YELLOW=""; C_GRAY=""; C_RESET=""
fi

# ---------------------------------------------------------------------------
# Catálogo de los 31 módulos (una línea TSV por módulo)
# ---------------------------------------------------------------------------
read -r -d '' CATALOG_PHP <<'PHP' || true
$data = json_decode(file_get_contents($argv[1]), true);
if (!is_array($data) || !isset($data['modulos'])) {
    fwrite(STDERR, "La matriz no contiene 'modulos'.\n");
    exit(3);
}
foreach ($data['modulos'] as $m) {
    $unit = $m['casos'][0] ?? [];
    $integration = $m['casos'][1] ?? [];
    $smoke = $m['casos'][2] ?? [];
    preg_match('/CP-SMOKE-\d{2}/', (string)($smoke['evidencia'] ?? ''), $match);
    printf(
        "%d\t%s\t%s\t%s\t%s\t%s\t%s\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\n",
        (int)$m['numero'], $m['modelo'], $m['idRequisito'], $m['moduloSidebar'],
        (string)($unit['evidencia'] ?? ''), (string)($integration['evidencia'] ?? ''),
        $match[0] ?? '',
        (int)($unit['pruebas'] ?? 0), (int)($unit['pass'] ?? 0),
        (int)($unit['fail'] ?? 0), (int)($unit['skip'] ?? 0),
        (int)($integration['pruebas'] ?? 0), (int)($integration['pass'] ?? 0),
        (int)($integration['fail'] ?? 0), (int)($integration['skip'] ?? 0),
        (int)($smoke['pruebas'] ?? 0), (int)($smoke['pass'] ?? 0),
        (int)($smoke['fail'] ?? 0), (int)($smoke['skip'] ?? 0)
    );
}
PHP

CATALOG="$("${PHP_BIN}" -r "${CATALOG_PHP}" "${MATRIX}")"

case "${MODULE}" in
    all|todos|todo|'*'|-all) RUN_ALL=1 ;;
    *) RUN_ALL=0 ;;
esac

if [[ "${RUN_ALL}" -eq 1 ]]; then
    SELECTED="${CATALOG}"
else
    SELECTED="$(printf '%s\n' "${CATALOG}" | awk -F'\t' -v q="${MODULE}" '
        BEGIN { ql = tolower(q); found = 0 }
        tolower($2) == ql || tolower($3) == ql || tolower($4) == ql || $1 == q || sprintf("%02d", $1) == q {
            print
            found = 1
        }
        END { if (!found) exit 1 }
    ')" || {
        echo "ERROR: no se encontró el módulo '${MODULE}'." >&2
        echo "       Usa el nombre (Planta), el número (01), el nombre del sidebar o 'all'." >&2
        exit 2
    }
fi

# ---------------------------------------------------------------------------
# Utilidades
# ---------------------------------------------------------------------------
fqcn_to_path() {
    printf '%s' "$1" \
        | sed 's/^SysInescolara\\Tests\\//' \
        | sed 's/\\/\//g' \
        | sed 's|^|tests/|; s|$|.php|'
}

read_junit() {
    "${PHP_BIN}" -r '
        $xml = @simplexml_load_file($argv[1]);
        if ($xml === false) {
            fwrite(STDERR, "No se pudo leer el JUnit: {$argv[1]}\n");
            exit(1);
        }
        $suite = isset($xml->testsuite[0]) ? $xml->testsuite[0] : $xml;
        printf("%d %d %d %d %d %s\n",
            (int)$suite["tests"], (int)$suite["assertions"],
            (int)$suite["failures"], (int)$suite["errors"],
            (int)$suite["skipped"], (string)$suite["time"]);
    ' "$1"
}

RUN_EXIT=0
RUN_TESTS=0
RUN_ASSERTIONS=0
RUN_FAILURES=0
RUN_ERRORS=0
RUN_SKIPPED=0

run_suite() {
    local suite="$1"
    local test_path="$2"
    local filter="$3"
    local log_name="$4"
    local verbose="$5"
    local log="${RESULTS}/${log_name}"
    local out="${TMP_DIR}/${log_name}.out"

    rm -f "${log}" "${out}"

    local args=(-c "${CONFIG}")
    if [[ -n "${test_path}" ]]; then
        args+=("${test_path}")
    else
        args+=(--testsuite "${suite}" --filter "${filter}")
    fi
    args+=(--log-junit "${log}" --do-not-cache-result)

    set +e
    "${PHP_BIN}" "${PHPUNIT}" "${args[@]}" >"${out}" 2>&1
    RUN_EXIT=$?
    set -e

    if [[ "${verbose}" -eq 1 || "${RUN_EXIT}" -ne 0 ]]; then
        cat "${out}"
    fi

    if [[ ! -f "${log}" ]]; then
        echo "ERROR: PHPUnit no generó el JUnit ${log}." >&2
        return 3
    fi

    read -r RUN_TESTS RUN_ASSERTIONS RUN_FAILURES RUN_ERRORS RUN_SKIPPED _time < <(read_junit "${log}")
}

status_cell() {
    local pass="$1" total="$2" skip="$3"
    printf '%s/%s' "${pass}" "${total}"
    if [[ "${skip}" -gt 0 ]]; then
        printf ' (%s skip)' "${skip}"
    fi
}

# ---------------------------------------------------------------------------
# Ejecución
# ---------------------------------------------------------------------------
TOTAL_MODULES=0
TOTAL_OK=0
TOTAL_TESTS=0
TOTAL_ASSERTIONS=0
TOTAL_FAILS=0
TOTAL_SKIPS=0
FAILED_MODULES=()
STARTED_AT=$(date +%s)

if [[ "${RUN_ALL}" -eq 1 ]]; then
    echo
    echo "${C_YELLOW}Ejecutando los 31 módulos (Unit + Integration + Smoke)...${C_RESET}"
    echo "${C_GRAY}Directorio: ${ROOT_DIR}${C_RESET}"
fi

while IFS=$'\t' read -r num model req sidebar unit_ev int_ev smoke_id \
        u_tests u_pass u_fail u_skip \
        i_tests i_pass i_fail i_skip \
        s_tests s_pass s_fail s_skip; do

    [[ -z "${num}" ]] && continue

    unit_path="$(fqcn_to_path "${unit_ev}")"
    int_path="$(fqcn_to_path "${int_ev}")"

    if [[ -z "${smoke_id}" ]]; then
        echo "ERROR: no se pudo obtener el ID de humo del módulo ${model}." >&2
        exit 3
    fi

    verbose=0
    if [[ "${RUN_ALL}" -eq 0 ]]; then
        verbose=1
        echo
        echo "${C_CYAN}>>> ${model} :: ${unit_path}${C_RESET}"
    fi
    run_suite "Unit" "${unit_path}" "" "module-${model}-unit.xml" "${verbose}"
    unit=("${RUN_EXIT}" "${RUN_TESTS}" "${RUN_ASSERTIONS}" "${RUN_FAILURES}" "${RUN_ERRORS}" "${RUN_SKIPPED}")

    if [[ "${RUN_ALL}" -eq 0 ]]; then
        echo "${C_CYAN}>>> ${model} :: ${int_path}${C_RESET}"
    fi
    run_suite "Integration" "${int_path}" "" "module-${model}-integration.xml" "${verbose}"
    integration=("${RUN_EXIT}" "${RUN_TESTS}" "${RUN_ASSERTIONS}" "${RUN_FAILURES}" "${RUN_ERRORS}" "${RUN_SKIPPED}")

    if [[ "${RUN_ALL}" -eq 0 ]]; then
        echo "${C_CYAN}>>> ${model} :: --filter ${smoke_id}${C_RESET}"
    fi
    run_suite "Smoke" "" "${smoke_id}" "module-${model}-smoke.xml" "${verbose}"
    smoke=("${RUN_EXIT}" "${RUN_TESTS}" "${RUN_ASSERTIONS}" "${RUN_FAILURES}" "${RUN_ERRORS}" "${RUN_SKIPPED}")

    module_ok=1
    declare -a row_names=("Unitaria" "Integracion" "Humo")
    declare -a row_logs=("module-${model}-unit.xml" "module-${model}-integration.xml" "module-${model}-smoke.xml")
    declare -a row_data=()
    row_data+=("${unit[*]}")
    row_data+=("${integration[*]}")
    row_data+=("${smoke[*]}")
    declare -a expected_tests=("${u_tests}" "${i_tests}" "${s_tests}")
    declare -a expected_pass=("${u_pass}" "${i_pass}" "${s_pass}")
    declare -a expected_fail=("${u_fail}" "${i_fail}" "${s_fail}")
    declare -a expected_skip=("${u_skip}" "${i_skip}" "${s_skip}")

    if [[ "${RUN_ALL}" -eq 0 ]]; then
        echo
        echo "${C_YELLOW}==================================================================${C_RESET}"
        echo "${C_YELLOW}Modulo: ${model} (${req}) - ${sidebar}${C_RESET}"
        echo "${C_YELLOW}==================================================================${C_RESET}"
        printf '%-13s %-12s %-12s %-6s %-6s %-6s %s\n' \
            "Suite" "Ejecutadas" "Esperadas" "Pass" "Fail" "Skip" "Estado"
    fi

    for idx in 0 1 2; do
        IFS=' ' read -r r_exit r_tests r_assertions r_failures r_errors r_skipped <<< "${row_data[$idx]}"
        r_pass=$((r_tests - r_failures - r_errors - r_skipped))
        e_tests="${expected_tests[$idx]}"
        e_pass="${expected_pass[$idx]}"
        e_fail="${expected_fail[$idx]}"
        e_skip="${expected_skip[$idx]}"

        row_ok=1
        if [[ "${r_exit}" -ne 0 || "${r_tests}" -ne "${e_tests}" || "${r_pass}" -ne "${e_pass}" \
              || "${r_failures}" -ne "${e_fail}" || "${r_errors}" -ne 0 || "${r_skipped}" -ne "${e_skip}" ]]; then
            row_ok=0
            module_ok=0
        fi

        if [[ "${RUN_ALL}" -eq 0 ]]; then
            estado="COINCIDE"
            color="${C_GREEN}"
            if [[ "${row_ok}" -eq 0 ]]; then
                estado="NO COINCIDE"
                color="${C_RED}"
            fi
            printf '%-13s %-12s %-12s %-6s %-6s %-6s %s%s%s\n' \
                "${row_names[$idx]}" "${r_tests}" "${e_tests}" "${r_pass}" "${r_failures}" "${r_skipped}" \
                "${color}" "${estado}" "${C_RESET}"
            echo "${C_GRAY}  Log: ${RESULTS}/${row_logs[$idx]}${C_RESET}"
        fi

        TOTAL_TESTS=$((TOTAL_TESTS + r_tests))
        TOTAL_ASSERTIONS=$((TOTAL_ASSERTIONS + r_assertions))
        TOTAL_FAILS=$((TOTAL_FAILS + r_failures))
        TOTAL_SKIPS=$((TOTAL_SKIPS + r_skipped))
    done

    TOTAL_MODULES=$((TOTAL_MODULES + 1))
    if [[ "${module_ok}" -eq 1 ]]; then
        TOTAL_OK=$((TOTAL_OK + 1))
    else
        FAILED_MODULES+=("$(printf '%02d %s' "${num}" "${model}")")
    fi

    if [[ "${RUN_ALL}" -eq 1 ]]; then
        estado="COINCIDE"
        color="${C_GREEN}"
        if [[ "${module_ok}" -eq 0 ]]; then
            estado="NO COINCIDE"
            color="${C_RED}"
        fi
        printf '%s[%02d/%02d] %-16s %s%s\n' "${color}" "${num}" "${TOTAL_MODULES}" "${model}" "${estado}" "${C_RESET}"
    fi

    unset row_names row_logs row_data expected_tests expected_pass expected_fail expected_skip
done <<< "${SELECTED}"

# ---------------------------------------------------------------------------
# Resumen
# ---------------------------------------------------------------------------
echo
echo "${C_YELLOW}==================================================================${C_RESET}"
echo "${C_YELLOW}RESUMEN${C_RESET}"
echo "${C_YELLOW}==================================================================${C_RESET}"
echo "Modulos: ${TOTAL_MODULES} | COINCIDE: ${TOTAL_OK} | NO COINCIDE: $((TOTAL_MODULES - TOTAL_OK))"
echo "Pruebas: ${TOTAL_TESTS} | Aserciones: ${TOTAL_ASSERTIONS} | FAIL: ${TOTAL_FAILS} | SKIP: ${TOTAL_SKIPS}"
echo "Tiempo: $(( $(date +%s) - STARTED_AT )) s"
echo "XML por modulo: ${RESULTS}"
echo

if [[ "${#FAILED_MODULES[@]}" -eq 0 ]]; then
    echo "${C_GREEN}RESULTADO: COINCIDE con tests/tools/reportes/formatos/resultados/traceability.json${C_RESET}"
    exit 0
fi

echo "${C_RED}RESULTADO: NO COINCIDE con tests/tools/reportes/formatos/resultados/traceability.json${C_RESET}"
for item in "${FAILED_MODULES[@]}"; do
    echo "${C_RED}  - ${item}${C_RESET}"
done
exit 1
