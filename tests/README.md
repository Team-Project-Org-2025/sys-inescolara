# Pruebas — Sys Inescolara

- **Índice de ejecutores y comandos:** [`tools/README.md`](tools/README.md)
- **Instrucciones paso a paso:** [`GUIA_PRUEBAS.md`](GUIA_PRUEBAS.md)
- **Comandos manuales por módulo (93 casos):** [`COMANDOS_RAPIDOS.md`](COMANDOS_RAPIDOS.md)
- **Matriz de trazabilidad:** [`TRACEABILITY.md`](tools/reportes/formatos/resultados/TRACEABILITY.md) · [`traceability.json`](tools/reportes/formatos/resultados/traceability.json)
- **Pruebas de interfaz (Selenium):** [`Interfaz/README.md`](Interfaz/README.md)

Suite PHPUnit 11.5 con tres niveles:

| Suite | Carpeta | Base de datos | Objetivo |
| --- | --- | --- | --- |
| `Unit` | `tests/Unit` | No | Getters, setters, `fill`, validaciones y lógica pura |
| `Integration` | `tests/Integration` | SQLite en memoria | SQL real de los modelos, CRUD y flujos entre tablas |
| `Smoke` | `tests/Smoke` | No | Autoload, contratos de clases, archivos y arranque |
| `Interfaz` | `tests/Interfaz` | MySQL real | Flujos E2E en navegador con Selenium |
| `Aceptación` | `tests/aceptacion` | MySQL real | Aceptación del sistema con Codeception + WebDriver |

## Comandos

Cada ejecutor tiene su propia guía con parámetros, ejemplos, archivos que
genera y códigos de salida. Empieza por [`tools/README.md`](tools/README.md).

| Necesito… | Comando | Guía |
| --- | --- | --- |
| Un módulo completo (Unit + Integration + Smoke) | `powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 05` | [`tools/phpunit/scripts/run-module.md`](tools/phpunit/scripts/run-module.md) |
| Los 31 módulos y comparación con la matriz | `powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 all` | [`tools/phpunit/scripts/run-module.md`](tools/phpunit/scripts/run-module.md) |
| Integration + Smoke en cualquier sistema operativo | `php tests/tools/phpunit/scripts/run-tests.php` | [`tools/phpunit/scripts/run-tests.md`](tools/phpunit/scripts/run-tests.md) |
| Una suite, archivo, clase o método concreto | `php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml ...` | [Recetas PHPUnit](tools/README.md#recetas-phpunit) |
| Interfaz E2E con Selenium | `powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1` | [`tools/interfaz/scripts/run-interfaz.md`](tools/interfaz/scripts/run-interfaz.md) |
| Preparar ChromeDriver y el usuario E2E | `powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1` | [`tools/interfaz/scripts/setup-interfaz.md`](tools/interfaz/scripts/setup-interfaz.md) |
| Aceptación (Codeception) | `./tests/tools/aceptacion/scripts/run-aceptacion.sh` | [`aceptacion/README.md`](aceptacion/README.md) |
| Regenerar la matriz PHPUnit | `php tests\tools\reportes\scripts\generate_traceability.php` | [`tools/reportes/scripts/generate_traceability.md`](tools/reportes/scripts/generate_traceability.md) |

Comandos esenciales (siempre desde la raíz del proyecto):

```powershell
# Toda la suite PHPUnit
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result

# Un módulo: Unit + Integration + Smoke, comparado con la matriz
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 Planta

# Los 31 módulos
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 all

# Integración + humo, todos o un módulo (Linux, macOS y Windows)
php tests/tools/phpunit/scripts/run-tests.php
php tests/tools/phpunit/scripts/run-tests.php --module=13

# Interfaz: preparar y ejecutar
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -Suite Comercial

# Aceptación y su matriz
./tests/tools/aceptacion/scripts/run-aceptacion.sh
php tests/tools/reportes/scripts/generate_aceptacion.php
php tests/tools/reportes/scripts/generate_traceability.php
```

## Estructura

```text
tests/
├── phpunit.xml
├── bootstrap.php
├── GUIA_PRUEBAS.md
├── COMANDOS_RAPIDOS.md
├── Support/
│   ├── NoDbTrait.php
│   ├── SqliteTrait.php
│   ├── SqliteTestCase.php
│   ├── NoDb<Modelo>.php
│   └── Sqlite<Modelo>.php
├── Unit/<Dominio>/<Modelo>Test.php
├── Integration/<Dominio>/<Modelo>RepositoryTest.php
├── Smoke/ModelsSmokeTest.php
├── Interfaz/                         # suite E2E Selenium
├── aceptacion/                       # suite Codeception (runner en tools/)
└── tools/
    ├── README.md                     # índice + estándar scripts/formatos
    ├── phpunit/
    │   ├── scripts/
    │   │   ├── run-module.ps1/.md    # Unit + Integration + Smoke por módulo
    │   │   └── run-tests.php/.md/.sh/.bat
    │   └── formatos/
    │       ├── plantillas/junit-reporte.xml
    │       └── resultados/           # JUnit llenos (ignorados por git)
    ├── integration/
    │   ├── scripts/
    │   │   └── run-integration-doc.ps1/.md
    │   └── formatos/
    │       ├── plantillas/Formato-Prueba-Integración.docx
    │       └── resultados/           # copia llena, capturas y manifiesto
    ├── interfaz/
    │   ├── scripts/
    │   │   ├── run-interfaz.ps1/.md
    │   │   ├── setup-interfaz.ps1/.md
    │   │   └── seed-interfaz.php/.md
    │   └── formatos/
    │       ├── plantillas/junit-reporte.xml
    │       └── resultados/           # JUnit, capturas y descargas
    ├── aceptacion/
    │   └── formatos/
    │       ├── plantillas/junit-reporte.xml
    │       └── resultados/           # report.xml de Codeception
    └── reportes/
        ├── scripts/
        │   ├── generate_traceability.php/.md
        │   └── generate_aceptacion.php/.md
        └── formatos/
            ├── plantillas/           # matrices vacías
            └── resultados/           # matrices llenas (versionadas)
```

El orden de ejecución de `Unit` e `Integration` está fijado en
`tests/phpunit.xml` y sigue el orden del sidebar (Planta → Especie → Ubicación →
Inventario → ... → PasswordReset).

`tests/bootstrap.php` registra el autoload `SysInescolara\Tests\` y la sesión
CLI. No abre MySQL ni carga `.env`.

## Dobles

Los modelos extienden `Database` y abren PDO en el constructor. Para probarlos
sin MySQL se usan dos dobles por modelo:

- `NoDb<Modelo>`: no llama a `parent::__construct()` y `db()` lanza
  `RuntimeException`. Ideal para unitarias.
- `Sqlite<Modelo>`: inyecta un `PDO` SQLite en memoria mediante
  `Sqlite<Modelo>::boot($pdo)`. Conserva el SQL real del modelo.

Ambos viven en `tests/Support` y se generan a partir de la firma real del
constructor. No modificar el modelo de producción.

## Integración con SQLite

`SqliteTestCase` crea un `PDO('sqlite::memory:')`, activa las foreign keys y
registra equivalentes de funciones MySQL usadas por los modelos:
`NOW()`, `CURDATE()`, `CONCAT()`, `MONTH()`, `YEAR()`, `DATEDIFF()`,
`DATE_FORMAT()`, `IF()`, `GREATEST()` y `LEAST()`.

Para consultas que usan la base de seguridad:

```php
$this->attachSecurityDatabase();
$this->exec('CREATE TABLE "SysInescolara-Seguridad".usuarios (...)');
```

Cada prueba crea solo las tablas que necesita en `setUp()`. No se comparte
estado entre pruebas: SQLite en memoria se destruye al terminar.

## Convenciones

- Namespace `SysInescolara\Tests\Unit\<Dominio>`,
  `SysInescolara\Tests\Integration\<Dominio>` y `SysInescolara\Tests\Smoke`.
- Clases `final`, métodos con `#[Test]` (PHPUnit 11.5).
- Nombres de prueba en español y orientados al comportamiento.
- `assertSame` para valores exactos; SQLite devuelve enteros y strings.
- No usar `@test`, `@dataProvider`, `@covers` ni `expectErrorLog()` (no existe
  en PHPUnit 11.5).
- No tocar `app/`, `composer.json` ni la base de datos de desarrollo.
- Si un método no es portable a SQLite (por ejemplo `information_schema`),
  probar el camino portable o marcar la prueba como `markTestSkipped()` con
  una razón concreta.
