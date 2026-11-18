# `run-tests` — integración + humo multiplataforma

> **Tipo:** PHPUnit · Integration + Smoke · PowerShell y Bash
>
> **Ejecutores:** `windows/run-tests.ps1` (Windows) y `linux/run-tests.sh` (Linux/macOS)
>
> **Motor interno:** `lib/run-tests.php`

## Qué hace

Ejecuta las suites **Integration** y **Smoke** de todos los módulos o de uno
solo. Muestra el comando real, el detalle testdox, un resumen por suite y guarda
log + JUnit en `tests/tools/phpunit/formatos/resultados/`.

> **No ejecuta `Unit`.** Para las tres suites de un módulo usa
> [`run-module`](run-module.md). Para Unit en Linux/macOS usa PHPUnit
> directamente (ver [`README.md#recetas-phpunit`](../../README.md#recetas-phpunit)).

## Requisitos

- PHP 8.2 o superior en el `PATH`.
- PHPUnit instalado en `vendor/` (`composer install`).
- Para integración: driver `pdo_sqlite`. El script intenta detectarlo y, si no
  lo encuentra, explica cómo instalarlo.
- Para humo no hace falta base de datos.
- PowerShell 5.1+ en Windows, o Bash en Linux/macOS.

## Uso

### Windows (PowerShell)

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\windows\run-tests.ps1 [opciones]
```

### Linux / macOS (Bash)

```bash
./tests/tools/phpunit/scripts/linux/run-tests.sh [opciones]
```

Los dos ejecutores solo comprueban que `php` exista y reenvían todos los
argumentos al motor `lib/run-tests.php`. El motor también puede invocarse
directamente:

```bash
php tests/tools/phpunit/scripts/lib/run-tests.php [opciones]
```

## Opciones

| Opción | Valor | Descripción |
| --- | --- | --- |
| `--suite` | `all` (por defecto), `integration`, `smoke` | Suite a ejecutar. |
| `--module` | `01`–`31` o nombre | Ejecuta solo ese módulo. |
| `--extension` | ruta | Carga una extensión PHP (repetible). |
| `--php-arg` | argumento | Argumento adicional para el PHP hijo (repetible). |
| `--results-dir` | ruta | Directorio de logs y XML (por defecto `tests/tools/phpunit/formatos/resultados`). |
| `--no-testdox` | — | No muestra el detalle testdox. |
| `--no-junit` | — | No genera reportes JUnit XML. |
| `--dry-run` | — | Muestra los comandos sin ejecutarlos. |
| `--list` | — | Enumera los 31 módulos disponibles. |
| `--verbose` | — | Muestra información adicional. |
| `-h`, `--help` | — | Muestra la ayuda integrada. |

`--module` acepta el número (`13`), el nombre (`Venta`), mayúsculas/minúsculas
y acentos; la búsqueda normaliza ambos lados.

## Ejemplos

### Ambas suites, todos los módulos

```powershell
# Windows
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\windows\run-tests.ps1
```

```bash
# Linux/macOS
./tests/tools/phpunit/scripts/linux/run-tests.sh
```

### Una sola suite

```powershell
# Windows
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\windows\run-tests.ps1 --suite=integration
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\windows\run-tests.ps1 --suite=smoke
```

```bash
# Linux/macOS
./tests/tools/phpunit/scripts/linux/run-tests.sh --suite=integration
./tests/tools/phpunit/scripts/linux/run-tests.sh --suite=smoke
```

### Un módulo

```bash
# Integración + humo de Venta
./tests/tools/phpunit/scripts/linux/run-tests.sh --module=13

# Solo el humo de Venta
./tests/tools/phpunit/scripts/linux/run-tests.sh --module=Venta --suite=smoke
```

### Ver antes de ejecutar

```bash
./tests/tools/phpunit/scripts/linux/run-tests.sh --list
./tests/tools/phpunit/scripts/linux/run-tests.sh --dry-run
./tests/tools/phpunit/scripts/linux/run-tests.sh --help
```

### SQLite manual

Si `pdo_sqlite` no está habilitado globalmente, indica la ruta de la extensión
(repetible):

```bash
./tests/tools/phpunit/scripts/linux/run-tests.sh --suite=integration \
  --extension=/usr/lib/php/modules/sqlite3.so \
  --extension=/usr/lib/php/modules/pdo_sqlite.so
```

En Windows:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\windows\run-tests.ps1 `
  --suite=integration --extension=C:\xampp\php\ext\php_pdo_sqlite.dll
```

## Qué genera

Con los valores por defecto, en `tests/tools/phpunit/formatos/resultados/`:

| Archivo | Contenido |
| --- | --- |
| `integration-<slug>-<fecha>.xml` | JUnit de integración. |
| `integration-<slug>-<fecha>.log` | Salida completa del proceso. |
| `smoke-<slug>-<fecha>.xml` | JUnit de humo. |
| `smoke-<slug>-<fecha>.log` | Salida completa del proceso. |

`<slug>` es `all` o `NN-Modelo` (por ejemplo `13-Venta`). La fecha tiene el
formato `YYYYMMDD-HHMMSS`.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todas las ejecuciones terminaron bien. |
| `1` | Al menos una suite falló. |
| `2` | Error de uso: opción desconocida, valor inválido o módulo inexistente. |
| `3` | Error de entorno: falta PHPUnit, `pdo_sqlite` o no se pudo crear la carpeta de resultados. |

Si `--suite=all` o `integration` y falta `pdo_sqlite`, la integración se marca
como `NO EJECUTADA`, el humo sí corre y el script termina con `3`.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `La suite de integración requiere el driver PDO SQLite` | `pdo_sqlite` deshabilitado. | Habilítalo en `php.ini` o usa `--extension=...`. |
| `Módulo no encontrado` | Número fuera de 01–31 o nombre mal escrito. | Ejecuta `--list`. |
| `Opción no reconocida` | Argumento con formato incorrecto. | Usa `--opcion=valor` o `--opcion valor`. |
| `No se encontró PHPUnit` | Falta `vendor/`. | Ejecuta `composer install`. |
| Quiero Unit también | El script no lo incluye por diseño. | Usa `run-module` o PHPUnit. |

## Ver también

- Tres suites por módulo: [`run-module.md`](run-module.md)
- Comandos manuales de los 93 casos:
  [`COMANDOS_RAPIDOS.md`](../../../COMANDOS_RAPIDOS.md)
- Recetas PHPUnit: [`README.md#recetas-phpunit`](../../README.md#recetas-phpunit)
