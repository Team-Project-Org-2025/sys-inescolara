# Pruebas de aceptación — Sys Inescolara

Suite de aceptación con **Codeception 5** + **WebDriver** que recorre la
aplicación real en un navegador (Brave/Chrome headless) y valida los 31
requisitos funcionales del sistema.

- **Matriz de resultados:** [`ACEPTACION.md`](../tools/reportes/formatos/resultados/ACEPTACION.md)
- **Ejecutor de la suite:** [`../tools/aceptacion/scripts/run-aceptacion.md`](../tools/aceptacion/scripts/run-aceptacion.md)
- **Guía del generador de la matriz:** [`../tools/reportes/scripts/generate_aceptacion.md`](../tools/reportes/scripts/generate_aceptacion.md)
- **Configuración:** [`codeception.yml`](codeception.yml) · [`Acceptance.suite.yml`](Acceptance.suite.yml)
- **Servidor de pruebas:** [`server/`](server) (PHP embebido, sin tocar Apache)
- **Reporte JUnit:** `tests/tools/aceptacion/formatos/resultados/report.xml`

## Requisitos

- PHP 8.2+ y dependencias de Composer (`composer install`).
- MySQL con las bases `sysinescolara` y `SysInescolara-Seguridad`.
- Google Chrome / Chromium / Brave. En Linux/macOS se usa
  `/opt/brave-bin/brave`; en Windows se detecta Chrome o Edge.
- `chromedriver` compatible. El script lo descarga automáticamente en
  `tests/aceptacion/bin/` si no existe.

## Ejecución

```bash
# Linux/macOS — toda la suite
./tests/tools/aceptacion/scripts/run-aceptacion.sh

# Linux/macOS — un archivo concreto
./tests/tools/aceptacion/scripts/run-aceptacion.sh AutenticacionCest
```

```powershell
# Windows — toda la suite
powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\run-aceptacion.ps1

# Windows — un archivo concreto
powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\run-aceptacion.ps1 AutenticacionCest
```

El script:

1. Descarga `chromedriver` si falta.
2. Levanta la aplicación en `http://127.0.0.1:8099` con
   `tests/aceptacion/server/router.php`.
3. Levanta `chromedriver` en el puerto `9515`.
4. Crea/actualiza el usuario `e2e_admin` (`e2e_admin` / `E2eAdmin123!`).
5. Ejecuta Codeception y guarda `tests/tools/aceptacion/formatos/resultados/report.xml`.
6. Detiene los procesos que levantó.

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

## Aislamiento

`tests/aceptacion/server/index.php` sirve la aplicación real y solo cambia
las claves de reCAPTCHA por las **claves de prueba oficiales de Google**,
necesarias para automatizar el login. No modifica `index.php`, `.env` ni la
configuración de Apache. No apunte esta suite a producción.

Las pruebas CRUD crean registros con prefijo `ACE` y los eliminan al final.
El usuario `e2e_admin` se crea/actualiza en la base de seguridad.
