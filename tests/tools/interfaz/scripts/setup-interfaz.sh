#!/usr/bin/env bash
#
# setup-interfaz.sh — prepara el entorno de las pruebas de interfaz.
#
# Equivalente Bash de setup-interfaz.ps1. Verifica PHP, PHPUnit y
# php-webdriver, comprueba ChromeDriver, crea la semilla del usuario de prueba
# y prepara las carpetas de resultados.
#
# Uso:
#   ./tests/tools/interfaz/scripts/setup-interfaz.sh
#   ./tests/tools/interfaz/scripts/setup-interfaz.sh --skip-seed
#   ./tests/tools/interfaz/scripts/setup-interfaz.sh --install
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"

SKIP_SEED=0
INSTALL=0

usage() {
    sed -n '2,12p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --skip-seed) SKIP_SEED=1; shift ;;
        --install) INSTALL=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *)
            echo "ERROR: opción desconocida: $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

cd "${ROOT_DIR}"

echo '==> Verificando PHP'
if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
    echo 'ERROR: PHP no está disponible en el PATH.' >&2
    exit 3
fi
"${PHP_BIN}" -v | head -n 1

echo '==> Verificando dependencias de Composer'
if [[ "${INSTALL}" -eq 1 || ! -d 'vendor/php-webdriver/webdriver' ]]; then
    composer install --no-interaction
fi

if [[ ! -f 'vendor/phpunit/phpunit/phpunit' ]]; then
    echo 'ERROR: PHPUnit no está instalado. Ejecute: composer install --dev' >&2
    exit 3
fi

if [[ ! -d 'vendor/php-webdriver/webdriver' ]]; then
    echo 'ERROR: php-webdriver no está instalado. Ejecute: composer require --dev php-webdriver/webdriver' >&2
    exit 3
fi

echo '==> Verificando ChromeDriver'
DRIVER='tests/Interfaz/bin/chromedriver'
if [[ ! -x "${DRIVER}" ]]; then
    if [[ -f "${DRIVER}" ]]; then
        chmod +x "${DRIVER}" 2>/dev/null || true
    fi
fi
if [[ ! -f "${DRIVER}" ]]; then
    echo "ERROR: no se encontró ChromeDriver en ${DRIVER}." >&2
    echo '       Descargue la versión compatible con su Chrome en' >&2
    echo '       https://googlechromelabs.github.io/chrome-for-testing/ y colóquela en tests/Interfaz/bin.' >&2
    exit 3
fi
"${DRIVER}" --version || true

echo '==> Preparando carpetas de resultados'
mkdir -p \
    'tests/tools/interfaz/formatos/resultados' \
    'tests/tools/interfaz/formatos/resultados/screenshots' \
    'tests/tools/interfaz/formatos/resultados/downloads'

if [[ "${SKIP_SEED}" -eq 0 ]]; then
    echo '==> Creando/actualizando usuario de prueba'
    if ! "${PHP_BIN}" 'tests/tools/interfaz/scripts/lib/seed-interfaz.php'; then
        echo 'ERROR: no se pudo crear el usuario de prueba.' >&2
        exit 1
    fi
fi

echo '==> Comprobando la aplicación web'
if command -v curl >/dev/null 2>&1; then
    if curl -fsS -o /dev/null --max-time 15 'http://localhost/sys-inescolara/login'; then
        echo 'Aplicación disponible.'
    else
        echo 'AVISO: no se pudo acceder a http://localhost/sys-inescolara/login.' >&2
        echo '       Inicie Apache y MySQL en XAMPP antes de ejecutar las pruebas.' >&2
    fi
else
    echo 'AVISO: curl no está disponible; no se pudo comprobar la aplicación web.' >&2
fi

echo
echo 'Entorno de interfaz listo.'
echo 'Ejecute: ./tests/tools/interfaz/scripts/run-interfaz.sh'
