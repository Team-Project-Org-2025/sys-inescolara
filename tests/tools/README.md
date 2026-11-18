# Herramientas de pruebas — Sys Inescolara

Punto de entrada único para saber **qué script ejecutar, con qué comando y
dónde quedan sus resultados**. Cada área de prueba tiene la misma estructura:

```text
tests/tools/<área>/
├── scripts/                 ← ejecutables PowerShell + Bash y su guía .md
│   ├── <script>.ps1         ← Windows
│   ├── <script>.sh          ← Linux / macOS
│   ├── lib/                 ← motor interno compartido (PHP)
│   └── <script>.md
└── formatos/
    ├── plantillas/          ← formatos vacíos (estructura a seguir)
    └── resultados/          ← formatos llenos (salida de las pruebas)
```

Reglas del estándar:

1. **`scripts/`** contiene los ejecutables y su documentación. Cada ejecutor
   existe en **PowerShell** y en **Bash**; nunca se guardan resultados ahí.
2. **`scripts/lib/`** contiene los motores internos en PHP. No se ejecutan
   directamente: los invocan los wrappers `.ps1` y `.sh`.
3. **`formatos/plantillas/`** contiene el formato vacío que define columnas,
   secciones y campos obligatorios.
4. **`formatos/resultados/`** contiene el formato lleno que producen los
   scripts, respetando la plantilla. Las matrices y los JUnit viven aquí.
5. Los resultados están ignorados por git; las plantillas sí se versionan.
6. Toda ejecución se lanza **desde la raíz del proyecto**:

   ```powershell
   cd C:\xampp\htdocs\sys-inescolara
   ```

```text
tests/tools/
├── README.md
├── phpunit/
│   ├── scripts/
│   │   ├── run-module.ps1 + run-module.sh + run-module.md
│   │   ├── run-tests.ps1 + run-tests.sh + run-tests.md
│   │   └── lib/run-tests.php          ← motor Integration + Smoke
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← JUnit y logs de PHPUnit
├── integration/
│   ├── scripts/
│   │   ├── run-integration-doc.ps1 + run-integration-doc.sh + run-integration-doc.md
│   │   └── lib/integration-doc.php    ← motor del Word (Bash)
│   └── formatos/
│       ├── plantillas/Formato-Prueba-Integración.docx
│       └── resultados/                 ← copia llena, capturas y manifiesto
├── interfaz/
│   ├── scripts/
│   │   ├── run-interfaz.ps1 + run-interfaz.sh + run-interfaz.md
│   │   ├── setup-interfaz.ps1 + setup-interfaz.sh + setup-interfaz.md
│   │   └── lib/seed-interfaz.php      ← semilla interna del usuario E2E
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← JUnit, capturas y descargas
├── aceptacion/
│   ├── scripts/
│   │   └── run-aceptacion.ps1 + run-aceptacion.sh + run-aceptacion.md
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← report.xml y logs de Codeception
└── reportes/
    ├── scripts/
    │   ├── generate_traceability.ps1 + generate_traceability.sh + generate_traceability.md
    │   ├── generate_aceptacion.ps1 + generate_aceptacion.sh + generate_aceptacion.md
    │   └── lib/
    │       ├── generate_traceability.php
    │       └── generate_aceptacion.php
    └── formatos/
        ├── plantillas/
        │   ├── TRACEABILITY.plantilla.md
        │   ├── traceability.plantilla.json
        │   └── ACEPTACION.plantilla.md
        └── resultados/
            ├── TRACEABILITY.md         ← matriz llena
            ├── traceability.json       ← datos llenos
            └── ACEPTACION.md           ← matriz llena
```

En resumen: `run-module` cubre **93 casos** (31 módulos × Unit + Integration +
Smoke) y **945 pruebas**; `run-tests` cubre Integration + Smoke; y PHPUnit
directo sirve para una suite, un archivo, una clase o un método concreto.
`run-integration-doc` ejecuta las 31 pruebas de integración y produce una copia
llena del formato Word con capturas nuevas.

## Windows o Linux

Todos los ejecutores tienen dos variantes equivalentes:

| Windows (PowerShell) | Linux / macOS (Bash) |
| --- | --- |
| `tests\tools\phpunit\scripts\run-module.ps1` | `./tests/tools/phpunit/scripts/run-module.sh` |
| `tests\tools\phpunit\scripts\run-tests.ps1` | `./tests/tools/phpunit/scripts/run-tests.sh` |
| `tests\tools\integration\scripts\run-integration-doc.ps1` | `./tests/tools/integration/scripts/run-integration-doc.sh` |
| `tests\tools\interfaz\scripts\run-interfaz.ps1` | `./tests/tools/interfaz/scripts/run-interfaz.sh` |
| `tests\tools\interfaz\scripts\setup-interfaz.ps1` | `./tests/tools/interfaz/scripts/setup-interfaz.sh` |
| `tests\tools\aceptacion\scripts\run-aceptacion.ps1` | `./tests/tools/aceptacion/scripts/run-aceptacion.sh` |
| `tests\tools\reportes\scripts\generate_traceability.ps1` | `./tests/tools/reportes/scripts/generate_traceability.sh` |
| `tests\tools\reportes\scripts\generate_aceptacion.ps1` | `./tests/tools/reportes/scripts/generate_aceptacion.sh` |

La guía de cada ejecutor muestra ambos comandos y sus parámetros.

## Buscar por necesidad

| Quiero… | Herramienta | Guía |
| --- | --- | --- |
| Probar los **31 módulos** (Unit + Integration + Smoke) y comparar contra la matriz | `phpunit/scripts/run-module.ps1 all` · `run-module.sh all` | [`phpunit/scripts/run-module.md`](phpunit/scripts/run-module.md) |
| Probar **un módulo completo** (sus 3 suites) | `phpunit/scripts/run-module.ps1 05` · `run-module.sh 05` | [`phpunit/scripts/run-module.md`](phpunit/scripts/run-module.md) |
| Probar **integración + humo** en Windows, Linux o macOS | `phpunit/scripts/run-tests.ps1` · `run-tests.sh` | [`phpunit/scripts/run-tests.md`](phpunit/scripts/run-tests.md) |
| Probar **una sola suite** (`Unit`, `Integration` o `Smoke`) | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Probar **un archivo, una clase o un método** concreto | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Guardar el JUnit que consume la matriz de trazabilidad | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Llenar el **formato Word de integración** con capturas nuevas | `integration/scripts/run-integration-doc.ps1` · `.sh` | [`integration/scripts/run-integration-doc.md`](integration/scripts/run-integration-doc.md) |
| Probar la **interfaz web** con Selenium | `interfaz/scripts/run-interfaz.ps1` · `.sh` | [`interfaz/scripts/run-interfaz.md`](interfaz/scripts/run-interfaz.md) |
| Preparar ChromeDriver, carpetas y usuario E2E | `interfaz/scripts/setup-interfaz.ps1` · `.sh` | [`interfaz/scripts/setup-interfaz.md`](interfaz/scripts/setup-interfaz.md) |
| Regenerar `TRACEABILITY.md` y `traceability.json` | `reportes/scripts/generate_traceability.ps1` · `.sh` | [`reportes/scripts/generate_traceability.md`](reportes/scripts/generate_traceability.md) |
| Regenerar `ACEPTACION.md` | `reportes/scripts/generate_aceptacion.ps1` · `.sh` | [`reportes/scripts/generate_aceptacion.md`](reportes/scripts/generate_aceptacion.md) |
| Pruebas de **aceptación** (Codeception + navegador) | `aceptacion/scripts/run-aceptacion.ps1` · `.sh` | [`aceptacion/scripts/run-aceptacion.md`](aceptacion/scripts/run-aceptacion.md) |
| Pruebas de **API** (Postman/Newman) | `pruebas/scripts/run.sh` | [`../../pruebas/README.md`](../../pruebas/README.md) |

## Scripts

| Script | Qué hace | Guía |
| --- | --- | --- |
| `phpunit/scripts/run-module.ps1` · `.sh` | Unit + Integration + Smoke de uno o de los 31 módulos, comparado con la matriz. | [`run-module.md`](phpunit/scripts/run-module.md) |
| `phpunit/scripts/run-tests.ps1` · `.sh` | Integration + Smoke (todos o un módulo). Envoltorio de `lib/run-tests.php`. | [`run-tests.md`](phpunit/scripts/run-tests.md) |
| `integration/scripts/run-integration-doc.ps1` · `.sh` | Ejecuta las 31 pruebas de integración y llena una copia del formato Word con capturas nuevas. | [`run-integration-doc.md`](integration/scripts/run-integration-doc.md) |
| `interfaz/scripts/run-interfaz.ps1` · `.sh` | Suite E2E de Selenium (toda, una suite o un filtro). | [`run-interfaz.md`](interfaz/scripts/run-interfaz.md) |
| `interfaz/scripts/setup-interfaz.ps1` · `.sh` | Verifica PHPUnit/php-webdriver/ChromeDriver y prepara el entorno E2E. | [`setup-interfaz.md`](interfaz/scripts/setup-interfaz.md) |
| `aceptacion/scripts/run-aceptacion.ps1` · `.sh` | Levanta la app y ChromeDriver, ejecuta Codeception y deja el JUnit de aceptación. | [`run-aceptacion.md`](aceptacion/scripts/run-aceptacion.md) |
| `reportes/scripts/generate_traceability.ps1` · `.sh` | Regenera la matriz PHPUnit desde los JUnit de `phpunit/formatos/resultados/`. | [`generate_traceability.md`](reportes/scripts/generate_traceability.md) |
| `reportes/scripts/generate_aceptacion.ps1` · `.sh` | Regenera la matriz de aceptación desde el JUnit de Codeception. | [`generate_aceptacion.md`](reportes/scripts/generate_aceptacion.md) |

Motores internos (no se ejecutan directamente):

| Motor | Lo usan |
| --- | --- |
| `phpunit/scripts/lib/run-tests.php` | `run-tests.ps1` y `run-tests.sh`. |
| `integration/scripts/lib/integration-doc.php` | `run-integration-doc.sh` (y disponible para pruebas manuales). |
| `interfaz/scripts/lib/seed-interfaz.php` | `setup-interfaz` y `run-interfaz` (y `run-aceptacion`). |
| `reportes/scripts/lib/generate_traceability.php` | `generate_traceability.ps1` y `.sh`. |
| `reportes/scripts/lib/generate_aceptacion.php` | `generate_aceptacion.ps1` y `.sh`. |

## Formatos

### Plantillas vacías

| Plantilla | Define | La llena |
| --- | --- | --- |
| `phpunit/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de PHPUnit (casos, fallos, omitidas). | PHPUnit con `--log-junit`. |
| `integration/formatos/plantillas/Formato-Prueba-Integración.docx` | Los 31 cuadros del formato Word: objetivos, técnicas, código, caso, resultado con captura y observaciones. | `run-integration-doc` (sobre una copia). |
| `interfaz/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de Selenium. | `run-interfaz`. |
| `aceptacion/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de Codeception. | `run-aceptacion`. |
| `reportes/formatos/plantillas/TRACEABILITY.plantilla.md` | Columnas y secciones de la matriz de trazabilidad. | `generate_traceability`. |
| `reportes/formatos/plantillas/traceability.plantilla.json` | Esquema de los conteos que consume `run-module`. | `generate_traceability`. |
| `reportes/formatos/plantillas/ACEPTACION.plantilla.md` | Columnas y secciones de la matriz de aceptación. | `generate_aceptacion`. |

### Resultados llenos

| Resultado | Contenido | Lo produce |
| --- | --- | --- |
| `phpunit/formatos/resultados/module-<Modelo>-{unit,integration,smoke}.xml` | JUnit por módulo y suite. | `run-module`. |
| `phpunit/formatos/resultados/junit-{unit,integration,smoke}.xml` | JUnit de la suite completa. | PHPUnit (recetas). |
| `phpunit/formatos/resultados/*.log` | Salida de consola de cada corrida. | Los ejecutores. |
| `integration/formatos/resultados/Formato-Prueba-Integración-<fecha>.docx` | Copia llena del formato Word de integración. | `run-integration-doc`. |
| `integration/formatos/resultados/capturas/captura-NN-<Modelo>.png` | Capturas nuevas de la ejecución de cada módulo. | `run-integration-doc`. |
| `integration/formatos/resultados/ultima-ejecucion.json` | Conteos, tiempos y estado por módulo. | `run-integration-doc`. |
| `interfaz/formatos/resultados/junit-<suite>.xml` | JUnit de Selenium. | `run-interfaz`. |
| `interfaz/formatos/resultados/screenshots/` | Capturas de fallos. | La suite E2E. |
| `aceptacion/formatos/resultados/report.xml` | JUnit de Codeception. | `run-aceptacion`. |
| `reportes/formatos/resultados/TRACEABILITY.md` | Matriz de trazabilidad llena. | `generate_traceability`. |
| `reportes/formatos/resultados/traceability.json` | Conteos y metadatos. | `generate_traceability`. |
| `reportes/formatos/resultados/ACEPTACION.md` | Matriz de aceptación llena. | `generate_aceptacion`. |

## Cómo se reparten las suites

| Nivel | Qué prueba | Dónde está | Cómo se ejecuta |
| --- | --- | --- | --- |
| `Unit` | Lógica pura del modelo, sin base de datos. | `tests/Unit/` | `run-module`, PHPUnit |
| `Integration` | SQL real del modelo contra SQLite en memoria. | `tests/Integration/` | `run-module`, `run-tests`, `run-integration-doc`, PHPUnit |
| `Smoke` | Autoload, contratos y arranque de los 31 modelos. | `tests/Smoke/` | `run-module`, `run-tests`, PHPUnit |
| `Interfaz` | Flujos E2E en navegador con Selenium. | `tests/Interfaz/` | `run-interfaz` |
| `Aceptación` | Recorrido funcional con Codeception. | `tests/aceptacion/` | `run-aceptacion` |
| API | Colección Postman contra la aplicación real. | `pruebas/` | `pruebas/scripts/run.sh` |

`run-module` es el único que ejecuta **las tres suites PHPUnit de un módulo**
(Unitaria + Integración + Humo). `run-tests` ejecuta **solo Integración +
Humo**; si necesitas Unit en otro sistema operativo, usa PHPUnit directamente
(ver recetas).

## Recetas PHPUnit

Comandos para ejecutar exactamente lo que necesitas, sin pasar por los scripts.
Sustituye la ruta o el filtro por el que corresponda.

### Toda la suite

```powershell
# Windows
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result
```

```bash
# Linux/macOS
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --do-not-cache-result
```

### Una sola suite

```bash
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Unit --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Integration --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Smoke --do-not-cache-result
```

### Un archivo concreto

```powershell
# Windows: unitaria, integración y humo
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\PlantaTest.php --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\PlantaRepositoryTest.php --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-05 --do-not-cache-result
```

```bash
# Linux/macOS: mismas rutas con /
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml tests/Unit/Catalogo/PlantaTest.php --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml tests/Integration/Catalogo/PlantaRepositoryTest.php --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Smoke --filter CP-SMOKE-05 --do-not-cache-result
```

### Una clase o un método

```bash
# Clase (coincidencia por subcadena)
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --filter PlantaTest --do-not-cache-result

# Método concreto: Clase::metodo
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --filter 'PlantaTest::valores_por_defecto' --do-not-cache-result
```

> `--filter` es una coincidencia por subcadena. `--filter InsumoTest` también
> ejecuta `RegistroInsumoTest`, y `--filter HerramientaTest` también ejecuta
> `UsoHerramientaTest`. Para un archivo exacto, pasa la **ruta** del archivo.

### Varios patrones a la vez

```bash
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --filter 'PlantaTest|EspecieTest' --do-not-cache-result
```

En Windows usa el binario directo (`php vendor\phpunit\phpunit\phpunit`), no
`vendor\bin\phpunit`: el `.bat` reexpande `%*` y corta el pipe.

### Ver qué se va a ejecutar sin ejecutarlo

```bash
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --list-tests
```

### Guardar JUnit

```powershell
# Windows: nombres que consume generate_traceability
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Unit        --log-junit tests\tools\phpunit\formatos\resultados\junit-unit.xml        --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Integration --log-junit tests\tools\phpunit\formatos\resultados\junit-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke       --log-junit tests\tools\phpunit\formatos\resultados\junit-smoke.xml       --do-not-cache-result
```

```bash
# Linux/macOS
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Unit        --log-junit tests/tools/phpunit/formatos/resultados/junit-unit.xml        --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Integration --log-junit tests/tools/phpunit/formatos/resultados/junit-integration.xml --do-not-cache-result
php vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --testsuite Smoke       --log-junit tests/tools/phpunit/formatos/resultados/junit-smoke.xml       --do-not-cache-result
```

> `generate_traceability` lee **exactamente** `junit-unit.xml`,
> `junit-integration.xml` y `junit-smoke.xml` de
> `tests/tools/phpunit/formatos/resultados/`. Los `module-<Modelo>-*.xml` que
> produce `run-module` no se usan para regenerar la matriz.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todo pasó (o, en `run-module`, todo coincide con la matriz). |
| `1` | Hay pruebas fallidas o el módulo no coincide con la matriz. |
| `2` | Error de uso: opción o módulo no válido. |
| `3` | Error de entorno: falta PHPUnit, PHP, el driver `pdo_sqlite`, Chrome o una plantilla. |

## Dónde quedan los resultados

| Carpeta | Contenido | Ignorado por git |
| --- | --- | --- |
| `tests/tools/phpunit/formatos/resultados/` | JUnit y logs de las suites PHPUnit. | Sí |
| `tests/tools/integration/formatos/resultados/` | Copia llena del Word, capturas y manifiesto de integración. | Sí |
| `tests/tools/interfaz/formatos/resultados/` | JUnit, capturas y descargas de Selenium. | Sí |
| `tests/tools/aceptacion/formatos/resultados/` | Reporte JUnit y logs de Codeception. | Sí |
| `tests/tools/reportes/formatos/resultados/` | Matrices `.md` y `.json` generadas. | No (son entregables) |

## Reglas comunes

- No se necesita MySQL ni Apache para `Unit`, `Integration` y `Smoke`; solo
  `pdo_sqlite`. La interfaz y la aceptación sí necesitan XAMPP/MySQL.
- Los mensajes `Error al guardar ...` de `error_log()` en `Unit` **no son
  fallos**. Lo que manda es el código de salida y la línea `OK`/`FAILURES!`.
- Usa `--do-not-cache-result` para que un resultado viejo no tape un fallo.
- No edites `app/` ni los modelos de producción para hacer pasar una prueba.
- No edites un archivo de `formatos/resultados/` a mano: cambia la plantilla o
  el generador y vuelve a ejecutar.
- La referencia manual de los 93 casos (31 módulos × 3 suites), comando por
  comando, está en [`../COMANDOS_RAPIDOS.md`](../COMANDOS_RAPIDOS.md).
- La explicación conceptual de cada suite está en
  [`../GUIA_PRUEBAS.md`](../GUIA_PRUEBAS.md).
