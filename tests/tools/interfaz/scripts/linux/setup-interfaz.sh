#!/usr/bin/env bash
#
# setup-interfaz.sh — prepara el entorno de las pruebas de interfaz.
#
# Equivalente Bash de setup-interfaz.ps1. Verifica PHP, PHPUnit y
# php-webdriver, instala ChromeDriver si falta (según el navegador detectado),
# crea la semilla del usuario de prueba y prepara las carpetas de resultados.
#
# Uso:
#   ./tests/tools/interfaz/scripts/linux/setup-interfaz.sh
#   ./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --skip-seed
#   ./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --install
#   ./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --test-recaptcha
#   ./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --restore-recaptcha
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../../../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"

SKIP_SEED=0
INSTALL=0
TEST_RECAPTCHA=0
RESTORE_RECAPTCHA=0

usage() {
    sed -n '2,15p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --skip-seed) SKIP_SEED=1; shift ;;
        --install) INSTALL=1; shift ;;
        --test-recaptcha) TEST_RECAPTCHA=1; shift ;;
        --restore-recaptcha) RESTORE_RECAPTCHA=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *)
            echo "ERROR: opción desconocida: $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

cd "${ROOT_DIR}"

ENV_FILE='.env'
ENV_BACKUP='tests/Interfaz/.env.interfaz.bak'
RECAPTCHA_TEST_SITE='6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'
RECAPTCHA_TEST_SECRET='6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'

set_env_value() {
    local key="$1"
    local value="$2"
    local file="$3"

    awk -v key="${key}" -v value="${value}" '
        $0 ~ "^" key "=" { print key "=" value; found = 1; next }
        { print }
        END { if (!found) print key "=" value }
    ' "${file}" > "${file}.tmp"
    # Se copia sobre el archivo original para conservar sus permisos y dueño.
    cat "${file}.tmp" > "${file}"
    rm -f "${file}.tmp"
}

if [[ "${RESTORE_RECAPTCHA}" -eq 1 ]]; then
    if [[ -f "${ENV_BACKUP}" ]]; then
        cp -p "${ENV_BACKUP}" "${ENV_FILE}"
        rm -f "${ENV_BACKUP}"
        echo 'Claves de reCAPTCHA originales restauradas desde el respaldo.'
    else
        echo 'AVISO: no hay respaldo de reCAPTCHA que restaurar.' >&2
    fi
fi

if [[ "${TEST_RECAPTCHA}" -eq 1 ]]; then
    if [[ ! -f "${ENV_FILE}" ]]; then
        echo "ERROR: no existe ${ENV_FILE}; no se pudieron aplicar las claves de prueba." >&2
        exit 3
    fi
    if [[ ! -f "${ENV_BACKUP}" ]]; then
        cp -p "${ENV_FILE}" "${ENV_BACKUP}"
    fi
    set_env_value 'RECAPTCHA_SITE_KEY' "${RECAPTCHA_TEST_SITE}" "${ENV_FILE}"
    set_env_value 'RECAPTCHA_SECRET_KEY' "${RECAPTCHA_TEST_SECRET}" "${ENV_FILE}"
    echo "Claves de prueba de reCAPTCHA aplicadas. Respaldo: ${ENV_BACKUP}"
fi

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
case "$(uname -s 2>/dev/null)" in
    MINGW*|MSYS*|CYGWIN*) DRIVER_NAME='chromedriver.exe' ;;
    *) DRIVER_NAME='chromedriver' ;;
esac
DRIVER="${INTERFAZ_DRIVER:-tests/Interfaz/bin/${DRIVER_NAME}}"

if [[ ! -f "${DRIVER}" ]]; then
    echo "     No se encontró ${DRIVER}; descargando la versión compatible…"
    if ! "${PHP_BIN}" 'tests/tools/interfaz/scripts/lib/install-chromedriver.php' --output="${DRIVER}"; then
        echo "ERROR: no se pudo instalar ChromeDriver automáticamente." >&2
        echo "       Descárguelo desde https://googlechromelabs.github.io/chrome-for-testing/ y" >&2
        echo "       colóquelo en ${DRIVER}, o defina INTERFAZ_DRIVER con su ruta." >&2
        exit 3
    fi
fi

if [[ ! -x "${DRIVER}" ]]; then
    chmod +x "${DRIVER}" 2>/dev/null || true
fi

if [[ ! -f "${DRIVER}" ]]; then
    echo "ERROR: no se encontró ChromeDriver en ${DRIVER}." >&2
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

echo '==> Comprobando reCAPTCHA para las pruebas'
RECAPTCHA_SECRET=''
if [[ -f '.env' ]]; then
    RECAPTCHA_SECRET="$(grep -E '^RECAPTCHA_SECRET_KEY=' '.env' 2>/dev/null | tail -n 1 | cut -d= -f2- | tr -d '\r')"
    RECAPTCHA_SECRET="${RECAPTCHA_SECRET%\"}"; RECAPTCHA_SECRET="${RECAPTCHA_SECRET#\"}"
    RECAPTCHA_SECRET="${RECAPTCHA_SECRET%\'}"; RECAPTCHA_SECRET="${RECAPTCHA_SECRET#\'}"
fi
if [[ -n "${RECAPTCHA_SECRET}" && "${RECAPTCHA_SECRET}" != "${RECAPTCHA_TEST_SECRET}" ]]; then
    echo 'AVISO: el .env usa una clave reCAPTCHA real; las pruebas de login y de' >&2
    echo '       recuperación de contraseña no pueden resolver el reto.' >&2
    echo '       Para la suite E2E usa las claves de prueba de Google en el .env:' >&2
    echo '         RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' >&2
    echo "         RECAPTCHA_SECRET_KEY=${RECAPTCHA_TEST_SECRET}" >&2
    echo '       O ejecuta este script con --test-recaptcha (reversible con --restore-recaptcha).' >&2
else
    echo 'Claves de reCAPTCHA compatibles con la automatización.'
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
echo 'Ejecute: ./tests/tools/interfaz/scripts/linux/run-interfaz.sh'
