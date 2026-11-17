# `run-aceptacion.sh` — suite de aceptación completa

> **Tipo:** Codeception + WebDriver · Bash (Linux/macOS)
>
> **Atajo:** levanta la aplicación y ChromeDriver, prepara el usuario
> `e2e_admin`, ejecuta la suite y deja el JUnit en
> `tests/tools/aceptacion/formatos/resultados/report.xml`.
>
> **Matriz que se genera después:**
> [`ACEPTACION.md`](../../reportes/formatos/resultados/ACEPTACION.md)

## Qué hace

1. Descarga `chromedriver` si falta en `tests/aceptacion/bin/`.
2. Levanta la aplicación en `http://127.0.0.1:8099` con
   `tests/aceptacion/server/router.php`.
3. Levanta ChromeDriver en el puerto `9515`.
4. Crea o actualiza el usuario de pruebas `e2e_admin`.
5. Ejecuta Codeception con reporte JUnit (`--xml`).
6. Detiene los procesos que levantó.
7. Guarda logs y `report.xml` en
   `tests/tools/aceptacion/formatos/resultados/`.

## Requisitos

- PHP 8.2+ y dependencias de Composer (`composer install`).
- MySQL con `sysinescolara` y `SysInescolara-Seguridad`.
- Google Chrome, Chromium o Brave.
- Bash (Linux/macOS). En Windows usa WSL o ejecuta Codeception directamente.

## Uso

```bash
# Toda la suite
./tests/tools/aceptacion/scripts/run-aceptacion.sh

# Un archivo concreto
./tests/tools/aceptacion/scripts/run-aceptacion.sh AutenticacionCest

# Cambiar el navegador
ACEPTACION_BROWSER=/usr/bin/chromium ./tests/tools/aceptacion/scripts/run-aceptacion.sh
```

El argumento opcional es el nombre del archivo o `Cest` que Codeception debe
ejecutar. Sin argumento corre toda la suite.

## Variables de entorno

| Variable | Valor por defecto |
| --- | --- |
| `ACEPTACION_URL` | `http://127.0.0.1:8099/` |
| `ACEPTACION_PORT` | `8099` |
| `ACEPTACION_BROWSER` | `/opt/brave-bin/brave` |
| `ACEPTACION_DRIVER_BIN` | `tests/aceptacion/bin/chromedriver` |
| `ACEPTACION_DRIVER_VERSION` | `156.0.8078.80` |
| `ACEPTACION_DRIVER_PORT` | `9515` |
| `ACEPTACION_USER` | `e2e_admin` |
| `ACEPTACION_PASSWORD` | `E2eAdmin123!` |

## Qué genera

| Archivo | Contenido |
| --- | --- |
| `tests/tools/aceptacion/formatos/resultados/report.xml` | JUnit de Codeception. |
| `tests/tools/aceptacion/formatos/resultados/php-server.log` | Salida del servidor PHP embebido. |
| `tests/tools/aceptacion/formatos/resultados/chromedriver*.log` | Salida de ChromeDriver. |

La carpeta `resultados/` está ignorada por git; la plantilla
`tests/tools/aceptacion/formatos/plantillas/junit-reporte.xml` sí se versiona.

## Regenerar la matriz

```bash
./tests/tools/aceptacion/scripts/run-aceptacion.sh
php tests/tools/reportes/scripts/generate_aceptacion.php
```

La guía del generador está en
[`generate_aceptacion.md`](../../reportes/scripts/generate_aceptacion.md).

## Ver también

- Suite de aceptación: [`tests/aceptacion/README.md`](../../../aceptacion/README.md)
- Matriz llena: [`ACEPTACION.md`](../../reportes/formatos/resultados/ACEPTACION.md)
- Configuración: [`codeception.yml`](../../../aceptacion/codeception.yml)
