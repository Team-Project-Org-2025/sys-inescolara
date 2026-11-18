# Pruebas BETA — Sys Inescolara

Las **pruebas beta** validan la aplicación en un entorno real (staging o
producción controlada), primero de forma automática y después con testers
reales. Este directorio agrupa las tres pruebas en scripts independientes para
Linux/macOS y Windows.

```text
tests/tools/beta/
├── README.md
├── scripts/
│   ├── run-beta.php / .sh / .bat / .md   ← ejecutor general
│   ├── run-humo.sh / .bat / .md          ← humo HTTP automático
│   ├── run-api.sh / .bat / .md           ← colección Postman
│   └── run-checklist.sh / .bat / .md     ← checklist manual guiado
└── formatos/
    ├── plantillas/
    │   ├── checklist-beta.plantilla.md
    │   └── junit-reporte.xml
    └── resultados/                       ← JUnit, logs y actas (ignorados por git)
```

## Qué prueba cada script

| Prueba | Script Linux/macOS | Script Windows | Qué valida |
| --- | --- | --- | --- |
| `humo` | `run-humo.sh` | `run-humo.bat` | Disponibilidad, páginas públicas, login, rutas protegidas y 404. |
| `api` | `run-api.sh` | `run-api.bat` | Colección Postman contra el entorno beta. |
| `checklist` | `run-checklist.sh` | `run-checklist.bat` | Recorrido manual guiado por 12 escenarios; genera un acta. |

Cada script individual equivale a `run-beta` con `--test=<prueba>` y acepta las
mismas opciones (`--base-url`, `--usuario`, `--password`, etc.).

## Entorno

El entorno se indica con `--base-url` o con variables de entorno:

| Variable | Qué define | Valor por defecto |
| --- | --- | --- |
| `BETA_BASE_URL` | URL base del entorno beta. | `http://localhost/sys-inescolara` |
| `BETA_USUARIO` | Usuario de la colección Postman. | `admin` |
| `BETA_PASSWORD` | Contraseña de la colección Postman. | *(vacía)* |
| `BETA_MAX_MS` | Tiempo máximo por página pública, en ms. | *(sin límite)* |

También se aceptan `POSTMAN_USUARIO` y `POSTMAN_PASSWORD` como respaldo.

## Ejecución rápida

Siempre desde la raíz del proyecto.

### Linux / macOS

```bash
# Humo + API (las automáticas)
./tests/tools/beta/scripts/run-beta.sh --base-url=https://mi-app.onrender.com

# Solo humo HTTP
./tests/tools/beta/scripts/run-humo.sh --base-url=https://mi-app.onrender.com

# API con credenciales (mejor por variable de entorno)
BETA_PASSWORD='***' ./tests/tools/beta/scripts/run-api.sh \
  --base-url=https://mi-app.onrender.com --usuario=admin

# Checklist manual guiado
./tests/tools/beta/scripts/run-checklist.sh --base-url=https://mi-app.onrender.com

# Menú para elegir una, varias o todas
./tests/tools/beta/scripts/run-beta.sh --manual
```

### Windows

```bat
rem Humo + API
tests\tools\beta\scripts\run-beta.bat --base-url=https://mi-app.onrender.com

rem Solo humo
tests\tools\beta\scripts\run-humo.bat --base-url=https://mi-app.onrender.com

rem API (defina BETA_PASSWORD antes)
set BETA_PASSWORD=***
tests\tools\beta\scripts\run-api.bat --base-url=https://mi-app.onrender.com --usuario=admin

rem Checklist manual
tests\tools\beta\scripts\run-checklist.bat --base-url=https://mi-app.onrender.com
```

## Modos de ejecución

| Modo | Comando | Ejecuta |
| --- | --- | --- |
| Todo automático | `run-beta` sin argumentos o `run-beta all` | `humo` + `api`. |
| Núcleo | `run-beta core` | Solo `humo`. |
| Individual | `run-beta <prueba>` o un script `run-<prueba>` | Solo esa prueba. |
| Manual (menú) | `run-beta --manual` | Pregunta qué pruebas ejecutar. |
| Checklist | `run-beta --test=checklist` | Recorrido guiado para testers. |
| Simulación | `run-beta --dry-run` | Muestra el plan y los requisitos pendientes. |

`checklist` no forma parte de `all` a propósito: es interactivo y necesita un
tester. Para ejecutarlo, use su script o `--test=checklist`.

## Checklist manual

`run-checklist` lee
`formatos/plantillas/checklist-beta.plantilla.md`, pregunta por cada escenario
(**P**asa / **F**alla / **N**o aplica) y por las observaciones, y escribe un
acta llena en:

```text
tests/tools/beta/formatos/resultados/checklist-beta-<fecha>.md
```

El acta conserva la estructura de la plantilla y añade el JUnit
`junit-checklist.xml`. Si algún escenario falla, el script sale con `1`.

## Resultados

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/junit-humo.xml` | JUnit de las comprobaciones HTTP. |
| `formatos/resultados/junit-api.xml` | JUnit derivado de la colección Postman. |
| `formatos/resultados/junit-checklist.xml` | JUnit del checklist manual. |
| `formatos/resultados/humo-<fecha>.log` | Detalle de cada petición HTTP. |
| `formatos/resultados/api-<fecha>.log` | Salida de Postman (credenciales redactadas). |
| `formatos/resultados/checklist-beta-<fecha>.md` | Acta manual llena. |

Los resultados están ignorados por git; la plantilla y este README se versionan.

## Seguridad

- No escriba contraseñas en el README ni en el historial del shell. Use
  `BETA_PASSWORD` o un gestor de secretos.
- El ejecutor redacta las contraseñas en la consola, los logs y evita conservar
  el reporte JSON de Postman, que puede contener cuerpos de petición.
- La colección Postman **crea, edita y elimina una planta de prueba**. Úsela en
  staging o con un usuario de pruebas; no la apunte a producción con datos
  reales.
- La prueba `humo` solo hace GET y no modifica datos.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todas las pruebas ejecutadas pasaron. |
| `1` | Alguna prueba falló. |
| `2` | Error de uso: opción o prueba no válida. |
| `3` | Faltan requisitos: Postman CLI, credenciales, plantilla, curl, etc. |

## Relación con `pruebas/`

`run-api` reutiliza la colección `pruebas/collections/SYSINECOLARA API Tests.postman_collection.json`
y el environment de `pruebas/environments/`. No los modifica: solo pasa la URL y
las credenciales como variables de entorno de Postman.
