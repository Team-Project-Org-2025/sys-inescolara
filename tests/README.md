# Pruebas — Sys Inescolara

- **Instrucciones paso a paso:** [`GUIA_PRUEBAS.md`](GUIA_PRUEBAS.md)
- **Comandos listos para copiar (3 por módulo):** [`COMANDOS_RAPIDOS.md`](COMANDOS_RAPIDOS.md)
- **Matriz de trazabilidad:** [`TRACEABILITY.md`](TRACEABILITY.md) · [`traceability.json`](traceability.json)

Suite PHPUnit 11.5 con tres niveles:

| Suite | Carpeta | Base de datos | Objetivo |
| --- | --- | --- | --- |
| `Unit` | `tests/Unit` | No | Getters, setters, `fill`, validaciones y lógica pura |
| `Integration` | `tests/Integration` | SQLite en memoria | SQL real de los modelos, CRUD y flujos entre tablas |
| `Smoke` | `tests/Smoke` | No | Autoload, contratos de clases, archivos y arranque |

## Comandos

Siempre desde la raíz del proyecto:

```powershell
# Toda la suite
vendor\bin\phpunit -c tests\phpunit.xml

# Por nivel
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Unit
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Integration
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Smoke

# Un modelo
vendor\bin\phpunit -c tests\phpunit.xml --filter Especie

# Un módulo completo (Unit + Integration + Smoke) y comparación con la matriz
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 Planta

# Los 31 módulos, con resumen y comparación automática
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 all
```

## Estructura

```text
tests/
├── phpunit.xml
├── bootstrap.php
├── GUIA_PRUEBAS.md
├── COMANDOS_RAPIDOS.md
├── TRACEABILITY.md
├── traceability.json
├── Support/
│   ├── NoDbTrait.php
│   ├── SqliteTrait.php
│   ├── SqliteTestCase.php
│   ├── NoDb<Modelo>.php
│   └── Sqlite<Modelo>.php
├── Unit/<Dominio>/<Modelo>Test.php
├── Integration/<Dominio>/<Modelo>RepositoryTest.php
├── Smoke/ModelsSmokeTest.php
├── tools/generate_traceability.php
├── tools/run-module.ps1
└── results/junit-*.xml
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
