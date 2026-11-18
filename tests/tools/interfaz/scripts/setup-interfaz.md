# `setup-interfaz` — preparar el entorno E2E

> **Tipo:** preparación · PowerShell y Bash
>
> **Ejecutores:** `windows/setup-interfaz.ps1` (Windows) y `linux/setup-interfaz.sh` (Linux/macOS)
>
> **Atajo:** verifica PHP, PHPUnit, php-webdriver y ChromeDriver, crea las
> carpetas de resultados y deja listo el usuario de prueba.

## Qué hace

1. Comprueba que `php` esté disponible.
2. Instala dependencias con Composer si falta `vendor/php-webdriver` o si se
   fuerza la instalación.
3. Verifica que exista PHPUnit.
4. Verifica que exista ChromeDriver y, si falta, lo **descarga y coloca**
   automáticamente con la versión compatible con el navegador instalado
   (Chrome, Chromium o Brave).
5. Crea `tests/tools/interfaz/formatos/resultados/`, `screenshots/` y `downloads/`.
6. Ejecuta `lib/seed-interfaz.php` para crear/actualizar `e2e_admin`.
7. Avisa si la aplicación no responde en
   `http://localhost/sys-inescolara/login` y si el `.env` usa una clave
   reCAPTCHA real (incompatible con la automatización).

No inicia Apache, MySQL ni ChromeDriver: solo comprueba y prepara.

## Requisitos

- PHP 8.2+ en el `PATH`, con la extensión `zip` (o `unzip` en el sistema).
- Composer disponible.
- Un navegador compatible instalado: Google Chrome, Chromium o Brave.
  - No hace falta descargar ChromeDriver a mano: el script lo instala en
    `tests/Interfaz/bin/` (`chromedriver.exe` en Windows, `chromedriver` en
    Linux/macOS).
  - Solo se necesita conexión a Internet la primera vez (o con `--force`).
- MySQL con las bases del proyecto para crear el usuario.
- Apache iniciado para la comprobación final (opcional, solo avisa).

## ChromeDriver y navegador

`lib/install-chromedriver.php` es el instalador compartido por ambos sistemas.
Detecta el navegador disponible, consulta el catálogo de
[Chrome for Testing](https://googlechromelabs.github.io/chrome-for-testing/) y
descarga el ChromeDriver de la misma versión mayor.

```bash
# Instalación manual o reinstalación forzada
php tests/tools/interfaz/scripts/lib/install-chromedriver.php
php tests/tools/interfaz/scripts/lib/install-chromedriver.php --force
php tests/tools/interfaz/scripts/lib/install-chromedriver.php --browser=/usr/bin/brave
php tests/tools/interfaz/scripts/lib/install-chromedriver.php --output=/tmp/chromedriver
```

| Variable | Uso |
| --- | --- |
| `INTERFAZ_DRIVER` | Ruta del binario si no se usa `tests/Interfaz/bin/`. |
| `INTERFAZ_BROWSER_BIN` / `CHROME_BIN` | Navegador a controlar (Chrome, Chromium o Brave). |

Si hay varios navegadores, se usa el primero detectado en este orden: Chrome,
Chromium, Brave. Para forzar otro, exporta `INTERFAZ_BROWSER_BIN`.

## reCAPTCHA en las pruebas

Las pruebas inyectan un token ficticio, por lo que el servidor debe validarlo
con las **claves de prueba de Google** (aceptan cualquier token). En el `.env`
de la raíz:

```dotenv
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

Con las claves reales, el login y la recuperación de contraseña responden
`Verificación de seguridad fallida` y la suite falla. Las claves de prueba solo
sirven para desarrollo local: no las uses en producción.

Para aplicarlas y revertirlas sin editar el archivo a mano:

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -TestRecaptcha
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -RestoreRecaptcha
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --test-recaptcha
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --restore-recaptcha
```

El respaldo queda en `tests/Interfaz/.env.interfaz.bak` (ignorado por git).

## Uso

### Windows (PowerShell)

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 [-SkipSeed] [-Install] [-TestRecaptcha] [-RestoreRecaptcha]
```

### Linux / macOS (Bash)

```bash
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh [--skip-seed] [--install] [--test-recaptcha] [--restore-recaptcha]
```

### Parámetros

| PowerShell | Bash | Descripción |
| --- | --- | --- |
| `-SkipSeed` | `--skip-seed` | No crea/actualiza el usuario de prueba. |
| `-Install` | `--install` | Fuerza `composer install` aunque `vendor/php-webdriver` ya exista. |
| `-TestRecaptcha` | `--test-recaptcha` | Respalda el `.env` en `tests/Interfaz/.env.interfaz.bak` y aplica las claves de prueba de Google. |
| `-RestoreRecaptcha` | `--restore-recaptcha` | Restaura el `.env` desde ese respaldo y lo elimina. |

## Ejemplos

### Preparación normal

```powershell
# Windows
composer install
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1
```

```bash
# Linux/macOS
composer install
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh
```

### Sin tocar el usuario

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -SkipSeed
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --skip-seed
```

### Forzar dependencias

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -Install
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --install
```

## Salida esperada

```text
==> Verificando PHP
==> Verificando dependencias de Composer
==> Verificando ChromeDriver
==> Preparando carpetas de resultados
==> Creando/actualizando usuario de prueba
==> Comprobando la aplicación web
Aplicación disponible (HTTP 200).

Entorno de interfaz listo.
Ejecute: .../run-interfaz.ps1  (o run-interfaz.sh en Linux/macOS)
```

## Usuario de prueba (semilla interna)

`lib/seed-interfaz.php` es el motor compartido por `setup-interfaz` y
`run-interfaz`; no es un ejecutor aparte. Crea o actualiza el rol y el usuario
`e2e_admin` en la base de seguridad. Es idempotente.

La contraseña y el usuario salen de `tests/Interfaz/Support/Config`, que lee el
`.env` de la raíz y permite sobrescribirlos con variables de entorno:

| Variable | Valor por defecto |
| --- | --- |
| `INTERFAZ_USER` | `e2e_admin` |
| `INTERFAZ_PASSWORD` | `E2eAdmin123!` |

Para ejecutarlo de forma aislada:

```powershell
# Windows
php tests\tools\interfaz\scripts\lib\seed-interfaz.php
```

```bash
# Linux/macOS
php tests/tools/interfaz/scripts/lib/seed-interfaz.php
```

Si cambias `INTERFAZ_PASSWORD`, vuelve a ejecutar la semilla para actualizar el
hash. **Solo para desarrollo:** escribe en la base configurada en `.env`.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `PHP no está disponible en PATH` | PHP no está en el `PATH`. | Añade `C:\xampp\php` al `PATH`. |
| `PHPUnit no está instalado` | Falta `vendor/`. | `composer install --dev`. |
| `php-webdriver no está instalado` | Falta la dependencia de desarrollo. | `composer require --dev php-webdriver/webdriver`. |
| `No se encontró ChromeDriver` | Falta el binario y la descarga automática falló (sin red). | Descárgalo desde <https://googlechromelabs.github.io/chrome-for-testing/> y colócalo en `tests/Interfaz/bin`, o ejecuta `php tests/tools/interfaz/scripts/lib/install-chromedriver.php --force`. |
| `Verificación de seguridad fallida` en el login | El `.env` usa claves reCAPTCHA reales. | Usa las claves de prueba de Google (ver arriba). |
| `Chrome no abre` / versión incompatible | ChromeDriver y navegador no coinciden. | Reinstala con `install-chromedriver.php --force`. |
| `No se pudo acceder a .../login` | Apache apagado. | Inicia Apache en XAMPP. |
| `No se pudo crear el usuario` | MySQL apagado o `.env` incorrecto. | Inicia MySQL y revisa las credenciales. |

## Ver también

- Ejecutar la suite: [`run-interfaz.md`](run-interfaz.md)
- Documentación de la suite: [`Interfaz/README.md`](../../../Interfaz/README.md)
