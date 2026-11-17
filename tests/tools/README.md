# Herramientas de pruebas — Sys Inescolara

Punto de entrada único para saber **qué script ejecutar, con qué comando y
dónde quedan sus resultados**. Cada área de prueba tiene la misma estructura:

```text
tests/tools/<área>/
├── scripts/                 ← ejecutables + su guía .md
│   ├── <script>
│   └── <script>.md
└── formatos/
    ├── plantillas/          ← formatos vacíos (estructura a seguir)
    └── resultados/          ← formatos llenos (salida de las pruebas)
```

Reglas del estándar:

1. **`scripts/`** contiene el ejecutable y su documentación. Nunca se guardan
   resultados ahí.
2. **`formatos/plantillas/`** contiene el formato vacío que define columnas,
   secciones y campos obligatorios.
3. **`formatos/resultados/`** contiene el formato lleno que producen los
   scripts, respetando la plantilla. Las matrices y los JUnit viven aquí.
4. Los resultados están ignorados por git; las plantillas sí se versionan.
5. Toda ejecución se lanza **desde la raíz del proyecto**:

   ```powershell
   cd C:\xampp\htdocs\sys-inescolara
   ```

```text
tests/tools/
├── README.md
├── phpunit/
│   ├── scripts/
│   │   ├── run-module.ps1 + run-module.md
│   │   └── run-tests.php/.sh/.bat + run-tests.md
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← JUnit y logs de PHPUnit
├── interfaz/
│   ├── scripts/
│   │   ├── run-interfaz.ps1 + run-interfaz.md
│   │   ├── setup-interfaz.ps1 + setup-interfaz.md
│   │   └── seed-interfaz.php + seed-interfaz.md
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← JUnit, capturas y descargas
├── aceptacion/
│   ├── scripts/
│   │   └── run-aceptacion.sh + run-aceptacion.md
│   └── formatos/
│       ├── plantillas/junit-reporte.xml
│       └── resultados/                 ← report.xml y logs de Codeception
└── reportes/
    ├── scripts/
    │   ├── generate_traceability.php + generate_traceability.md
    │   └── generate_aceptacion.php + generate_aceptacion.md
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

En resumen: `run-module.ps1` cubre **93 casos** (31 módulos × Unit + Integration
+ Smoke) y **945 pruebas**; `run-tests.php` cubre Integration + Smoke; y PHPUnit
directo sirve para una suite, un archivo, una clase o un método concreto.

## Buscar por necesidad

| Quiero… | Herramienta | Guía |
| --- | --- | --- |
| Probar los **31 módulos** (Unit + Integration + Smoke) y comparar contra la matriz | `phpunit/scripts/run-module.ps1 all` | [`phpunit/scripts/run-module.md`](phpunit/scripts/run-module.md) |
| Probar **un módulo completo** (sus 3 suites) | `phpunit/scripts/run-module.ps1 05` | [`phpunit/scripts/run-module.md`](phpunit/scripts/run-module.md) |
| Probar **integración + humo** en Windows, Linux o macOS | `phpunit/scripts/run-tests.php` | [`phpunit/scripts/run-tests.md`](phpunit/scripts/run-tests.md) |
| Probar **una sola suite** (`Unit`, `Integration` o `Smoke`) | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Probar **un archivo, una clase o un método** concreto | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Guardar el JUnit que consume la matriz de trazabilidad | PHPUnit | [Recetas PHPUnit](#recetas-phpunit) |
| Probar la **interfaz web** con Selenium | `interfaz/scripts/run-interfaz.ps1` | [`interfaz/scripts/run-interfaz.md`](interfaz/scripts/run-interfaz.md) |
| Preparar ChromeDriver, carpetas y usuario E2E | `interfaz/scripts/setup-interfaz.ps1` | [`interfaz/scripts/setup-interfaz.md`](interfaz/scripts/setup-interfaz.md) |
| Crear o actualizar el usuario `e2e_admin` | `interfaz/scripts/seed-interfaz.php` | [`interfaz/scripts/seed-interfaz.md`](interfaz/scripts/seed-interfaz.md) |
| Regenerar `TRACEABILITY.md` y `traceability.json` | `reportes/scripts/generate_traceability.php` | [`reportes/scripts/generate_traceability.md`](reportes/scripts/generate_traceability.md) |
| Regenerar `ACEPTACION.md` | `reportes/scripts/generate_aceptacion.php` | [`reportes/scripts/generate_aceptacion.md`](reportes/scripts/generate_aceptacion.md) |
| Pruebas de **aceptación** (Codeception + navegador) | `aceptacion/scripts/run-aceptacion.sh` | [`aceptacion/scripts/run-aceptacion.md`](aceptacion/scripts/run-aceptacion.md) |
| Pruebas de **API** (Postman/Newman) | `pruebas/scripts/run.sh` | [`../../pruebas/README.md`](../../pruebas/README.md) |

## Scripts

| Script | Tipo | Qué hace | Guía |
| --- | --- | --- | --- |
| `phpunit/scripts/run-module.ps1` | PowerShell | Unit + Integration + Smoke de uno o de los 31 módulos, comparado con la matriz. | [`run-module.md`](phpunit/scripts/run-module.md) |
| `phpunit/scripts/run-tests.php` | PHP | Integration + Smoke (todos o un módulo) en cualquier sistema operativo. | [`run-tests.md`](phpunit/scripts/run-tests.md) |
| `phpunit/scripts/run-tests.sh` | Bash | Atajo de `run-tests.php` para Linux/macOS. | [`run-tests.md`](phpunit/scripts/run-tests.md) |
| `phpunit/scripts/run-tests.bat` | CMD | Atajo de `run-tests.php` para Windows. | [`run-tests.md`](phpunit/scripts/run-tests.md) |
| `interfaz/scripts/run-interfaz.ps1` | PowerShell | Suite E2E de Selenium (toda, una suite o un filtro). | [`run-interfaz.md`](interfaz/scripts/run-interfaz.md) |
| `interfaz/scripts/setup-interfaz.ps1` | PowerShell | Verifica PHPUnit/php-webdriver/ChromeDriver y prepara el entorno E2E. | [`setup-interfaz.md`](interfaz/scripts/setup-interfaz.md) |
| `interfaz/scripts/seed-interfaz.php` | PHP | Crea o actualiza el rol y el usuario de pruebas `e2e_admin`. | [`seed-interfaz.md`](interfaz/scripts/seed-interfaz.md) |
| `aceptacion/scripts/run-aceptacion.sh` | Bash | Levanta la app y ChromeDriver, ejecuta Codeception y deja el JUnit de aceptación. | [`run-aceptacion.md`](aceptacion/scripts/run-aceptacion.md) |
| `reportes/scripts/generate_traceability.php` | PHP | Regenera la matriz PHPUnit desde los JUnit de `phpunit/formatos/resultados/`. | [`generate_traceability.md`](reportes/scripts/generate_traceability.md) |
| `reportes/scripts/generate_aceptacion.php` | PHP | Regenera la matriz de aceptación desde el JUnit de Codeception. | [`generate_aceptacion.md`](reportes/scripts/generate_aceptacion.md) |

## Formatos

### Plantillas vacías

| Plantilla | Define | La llena |
| --- | --- | --- |
| `phpunit/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de PHPUnit (casos, fallos, omitidas). | PHPUnit con `--log-junit`. |
| `interfaz/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de Selenium. | `run-interfaz.ps1`. |
| `aceptacion/formatos/plantillas/junit-reporte.xml` | Estructura JUnit de Codeception. | `tests/tools/aceptacion/scripts/run-aceptacion.sh`. |
| `reportes/formatos/plantillas/TRACEABILITY.plantilla.md` | Columnas y secciones de la matriz de trazabilidad. | `generate_traceability.php`. |
| `reportes/formatos/plantillas/traceability.plantilla.json` | Esquema de los conteos que consume `run-module.ps1`. | `generate_traceability.php`. |
| `reportes/formatos/plantillas/ACEPTACION.plantilla.md` | Columnas y secciones de la matriz de aceptación. | `generate_aceptacion.php`. |

### Resultados llenos

| Resultado | Contenido | Lo produce |
| --- | --- | --- |
| `phpunit/formatos/resultados/module-<Modelo>-{unit,integration,smoke}.xml` | JUnit por módulo y suite. | `run-module.ps1`. |
| `phpunit/formatos/resultados/junit-{unit,integration,smoke}.xml` | JUnit de la suite completa. | PHPUnit (recetas). |
| `phpunit/formatos/resultados/*.log` | Salida de consola de cada corrida. | Los ejecutores. |
| `interfaz/formatos/resultados/junit-<suite>.xml` | JUnit de Selenium. | `run-interfaz.ps1`. |
| `interfaz/formatos/resultados/screenshots/` | Capturas de fallos. | La suite E2E. |
| `aceptacion/formatos/resultados/report.xml` | JUnit de Codeception. | `tests/tools/aceptacion/scripts/run-aceptacion.sh`. |
| `reportes/formatos/resultados/TRACEABILITY.md` | Matriz de trazabilidad llena. | `generate_traceability.php`. |
| `reportes/formatos/resultados/traceability.json` | Conteos y metadatos. | `generate_traceability.php`. |
| `reportes/formatos/resultados/ACEPTACION.md` | Matriz de aceptación llena. | `generate_aceptacion.php`. |

## Cómo se reparten las suites

| Nivel | Qué prueba | Dónde está | Cómo se ejecuta |
| --- | --- | --- | --- |
| `Unit` | Lógica pura del modelo, sin base de datos. | `tests/Unit/` | `run-module.ps1`, PHPUnit |
| `Integration` | SQL real del modelo contra SQLite en memoria. | `tests/Integration/` | `run-module.ps1`, `run-tests.php`, PHPUnit |
| `Smoke` | Autoload, contratos y arranque de los 31 modelos. | `tests/Smoke/` | `run-module.ps1`, `run-tests.php`, PHPUnit |
| `Interfaz` | Flujos E2E en navegador con Selenium. | `tests/Interfaz/` | `run-interfaz.ps1` |
| `Aceptación` | Recorrido funcional con Codeception. | `tests/aceptacion/` | `tests/tools/aceptacion/scripts/run-aceptacion.sh` |
| API | Colección Postman contra la aplicación real. | `pruebas/` | `pruebas/scripts/run.sh` |

`run-module.ps1` es el único que ejecuta **las tres suites PHPUnit de un módulo**
(Unitaria + Integración + Humo). `run-tests.php` ejecuta **solo Integración +
Humo**; si necesitas Unit en otro sistema operativo, usa PHPUnit directamente
(ver recetas).

## Recetas PHPUnit

Comandos para ejecutar exactamente lo que necesitas, sin pasar por los scripts.
Sustituye la ruta o el filtro por el que corresponda.

### Toda la suite

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result
```

### Una sola suite

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Unit --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Integration --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --do-not-cache-result
```

### Un archivo concreto

```powershell
# Unitaria de un modelo
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\PlantaTest.php --do-not-cache-result

# Integración de un modelo
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\PlantaRepositoryTest.php --do-not-cache-result

# Un caso de humo
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-05 --do-not-cache-result
```

### Una clase o un método

```powershell
# Clase (coincidencia por subcadena)
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --filter PlantaTest --do-not-cache-result

# Método concreto: Clase::metodo
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --filter 'PlantaTest::valores_por_defecto' --do-not-cache-result
```

> `--filter` es una coincidencia por subcadena. `--filter InsumoTest` también
> ejecuta `RegistroInsumoTest`, y `--filter HerramientaTest` también ejecuta
> `UsoHerramientaTest`. Para un archivo exacto, pasa la **ruta** del archivo.

### Varios patrones a la vez

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --filter 'PlantaTest|EspecieTest' --do-not-cache-result
```

Con `|` usa el binario directo (`php vendor\phpunit\phpunit\phpunit`), no
`vendor\bin\phpunit`: el `.bat` reexpande `%*` y corta el pipe.

### Ver qué se va a ejecutar sin ejecutarlo

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --list-tests
```

### Guardar JUnit

```powershell
# Nombres que consume generate_traceability.php
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Unit        --log-junit tests\tools\phpunit\formatos\resultados\junit-unit.xml        --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Integration --log-junit tests\tools\phpunit\formatos\resultados\junit-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke       --log-junit tests\tools\phpunit\formatos\resultados\junit-smoke.xml       --do-not-cache-result
```

> `generate_traceability.php` lee **exactamente** `junit-unit.xml`,
> `junit-integration.xml` y `junit-smoke.xml` de
> `tests/tools/phpunit/formatos/resultados/`. Los `module-<Modelo>-*.xml` que
> produce `run-module.ps1` no se usan para regenerar la matriz.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todo pasó (o, en `run-module.ps1`, todo coincide con la matriz). |
| `1` | Hay pruebas fallidas o el módulo no coincide con la matriz. |
| `2` | Error de uso: opción o módulo no válido. |
| `3` | Error de entorno: falta PHPUnit, PHP, el driver `pdo_sqlite` o una plantilla. |

## Dónde quedan los resultados

| Carpeta | Contenido | Ignorado por git |
| --- | --- | --- |
| `tests/tools/phpunit/formatos/resultados/` | JUnit y logs de las suites PHPUnit. | Sí |
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
