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
4. Verifica que exista ChromeDriver y muestra su versión.
5. Crea `tests/tools/interfaz/formatos/resultados/`, `screenshots/` y `downloads/`.
6. Ejecuta `lib/seed-interfaz.php` para crear/actualizar `e2e_admin`.
7. Avisa si la aplicación no responde en
   `http://localhost/sys-inescolara/login`.

No inicia Apache, MySQL ni ChromeDriver: solo comprueba y prepara.

## Requisitos

- PHP 8.2+ en el `PATH`.
- Composer disponible.
- Google Chrome y ChromeDriver compatible en `tests/Interfaz/bin/`.
  - Windows: `chromedriver.exe`.
  - Linux/macOS: `chromedriver` con permiso de ejecución.
- MySQL con las bases del proyecto para crear el usuario.
- Apache iniciado para la comprobación final (opcional, solo avisa).

## Uso

### Windows (PowerShell)

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 [-SkipSeed] [-Install]
```

### Linux / macOS (Bash)

```bash
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh [--skip-seed] [--install]
```

### Parámetros

| PowerShell | Bash | Descripción |
| --- | --- | --- |
| `-SkipSeed` | `--skip-seed` | No crea/actualiza el usuario de prueba. |
| `-Install` | `--install` | Fuerza `composer install` aunque `vendor/php-webdriver` ya exista. |

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
| `No se encontró ChromeDriver` | Falta el binario. | Descárgalo desde <https://googlechromelabs.github.io/chrome-for-testing/> y colócalo en `tests/Interfaz/bin`. |
| `No se pudo acceder a .../login` | Apache apagado. | Inicia Apache en XAMPP. |
| `No se pudo crear el usuario` | MySQL apagado o `.env` incorrecto. | Inicia MySQL y revisa las credenciales. |

## Ver también

- Ejecutar la suite: [`run-interfaz.md`](run-interfaz.md)
- Documentación de la suite: [`Interfaz/README.md`](../../../Interfaz/README.md)
