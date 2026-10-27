# Guía de ejecución de pruebas — Sys Inescolara

Esta guía explica cómo ejecutar las tres suites, en qué orden se ejecutan y qué
resultado se debe esperar en cada una. La matriz de trazabilidad se actualiza
en [`TRACEABILITY.md`](TRACEABILITY.md) y [`traceability.json`](traceability.json).

## 1. Requisitos

- PHP 8.2 o superior (`php -v`).
- PHPUnit 11.5 instalado en `vendor/` (`vendor\bin\phpunit --version`).
- No se necesita MySQL, XAMPP ni una base de datos levantada.
- Ejecutar siempre desde la raíz del proyecto:

```powershell
cd C:\xampp\htdocs\sys-inescolara
```

## 2. Orden de los módulos

Las pruebas están ordenadas igual que el sidebar:

```text
INVENTARIO
  01 Planta            02 Especie           03 Ubicacion
  04 Inventory         05 Lote              06 Trazabilidad
  07 Insumo            08 Herramienta       09 UnidadMedida
  10 Merma             11 RegistroInsumo    12 UsoHerramienta
COMERCIAL
  13 Venta             14 LotePrecio        15 Cliente
  16 CuentaCobrar      17 CuentaPagar       18 Purchase
  19 Ornato            20 Ampliacion        21 Proveedor
OPERACIONES
  22 Tarea             23 SeedCollection
HERRAMIENTAS
  24 Reports           25 DashboardData
SISTEMA
  26 Usuario           27 Role              28 AuditLog
  29 Backup            30 Notification      31 PasswordReset
```

`RegistroInsumo`, `UsoHerramienta`, `DashboardData`, `Notification` y
`PasswordReset` no tienen entrada propia en el sidebar: se ubican al final de su
sección como modelos de soporte.

El orden se fuerza en `tests/phpunit.xml` listando los archivos explícitamente
en ese orden. No dependas del orden alfabético del sistema de archivos.

## 3. Suite de humo (Smoke)

**Objetivo:** comprobar que el proyecto arranca y que cada modelo cumple su
contrato mínimo. No toca base de datos.

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Smoke --do-not-cache-result
```

Qué valida por cada uno de los 31 modelos:

1. El autoload PSR-4 resuelve la clase.
2. La clase es instanciable y su archivo existe en `app/models`.
3. Extiende `SysInescolara\core\Database` (excepto `Backup`).
4. Implementa las interfaces declaradas (`ReadableInterface`,
   `DeletableInterface`).
5. Expone los métodos públicos esperados del módulo.
6. Existe su doble `NoDb<Modelo>` y se puede instanciar sin MySQL.

**Resultado esperado:** `OK (63 tests, 644 assertions)`.
Son 31 casos de contrato + 31 casos de doble + 1 verificación de cobertura.

**Si falla:** casi siempre indica un modelo nuevo sin doble, un método público
renombrado o un archivo mal ubicado. Corrige la prueba o el doble, no el modelo
de producción.

## 4. Suite unitaria (Unit)

**Objetivo:** probar la lógica pura del modelo sin abrir conexiones.

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Unit --do-not-cache-result
```

Cada archivo `tests/Unit/<Dominio>/<Modelo>Test.php` usa el doble
`SysInescolara\Tests\Support\NoDb<Modelo>`. Ese doble no llama a
`parent::__construct()` y hace que `db()` lance una excepción.

Qué se espera por modelo:

- Valores por defecto de propiedades y getters.
- Setters fluidos, normalización y `fill()` respetando `fillable`/`guarded`.
- Validaciones con `InvalidArgumentException` y mensajes del `ValidationTrait`.
- Degradación segura cuando no hay base de datos: `save()` devuelve `false`,
  listados devuelven `[]`, etc.
- Cálculos puros (por ejemplo `Inventory`, `Reports`, `Backup`).

**Resultado esperado:** todos los tests en verde. Los mensajes
`Error al guardar ...` que aparecen en la consola provienen de `error_log()` del
modelo y **no** son fallos: son ruido de stderr. El código de salida debe ser
`0`.

**Si falla:** revisa el mensaje de la aserción. Un fallo unitario suele ser una
regla de validación mal asumida o un setter que no normaliza como creías.

## 5. Suite de integración (Integration)

**Objetivo:** ejecutar el SQL real de cada modelo contra SQLite en memoria.

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Integration --do-not-cache-result
```

Cada archivo `tests/Integration/<Dominio>/<Modelo>RepositoryTest.php` extiende
`SysInescolara\Tests\Support\SqliteTestCase`, crea solo las tablas que necesita
y llama `Sqlite<Modelo>::boot($this->pdo)` en `setUp()` y `boot(null)` en
`tearDown()`.

Qué se espera por modelo:

- `save()`/`add()` inserta y asigna ID; una segunda llamada actualiza.
- `find()`, `all()`, `getAll()`, `getById()`, `exists()`, `where()`,
  `loadById()` devuelven lo esperado.
- Baja lógica (`delete`) y `restore`.
- Métodos propios: ventas con detalles/pagos, compras y cuentas, tareas,
  trazabilidad, recolección de semillas, reportes, etc.
- Flujos entre tablas con claves foráneas y transacciones.

`SqliteTestCase` emula las funciones MySQL usadas por los modelos:
`NOW()`, `CURDATE()`, `CONCAT()`, `MONTH()`, `YEAR()`, `DATEDIFF()`,
`DATE_FORMAT()`, `IF()`, `GREATEST()` y `LEAST()`. Para consultas que usan la
base de seguridad:

```php
$this->attachSecurityDatabase();
$this->exec('CREATE TABLE "SysInescolara-Seguridad".usuarios (...)');
```

**Resultado esperado:** todos los tests en verde, con los `markTestSkipped()`
documentados en la matriz. Un test omitido no es un fallo, pero debe tener una
razón concreta (por ejemplo SQL exclusivo de MySQL).

**Si falla:** suele ser una columna que no existe en el esquema de prueba, una
función MySQL no emulada o una FK mal sembrada. Ajusta la prueba, nunca el
modelo de producción.

## 6. Suite completa

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --do-not-cache-result
```

**Resultado esperado:** `OK` con los tres niveles en verde y los omitidos
justificados. Este es el comando que se usa para llenar la matriz.

Resultados de referencia de la última ejecución (2026-10-27):

| Suite | Archivos de modelo | Pruebas | Aserciones | Omitidas | Resultado |
| --- | ---: | ---: | ---: | ---: | --- |
| `Unit` | 31 | 418 | 1119 | 2 | OK |
| `Integration` | 31 | 468 | 9920 | 5 | OK |
| `Smoke` | 31 casos | 63 | 644 | 0 | OK |
| **Total** | **93 casos** | **949** | **11683** | **7** | **OK** |

Las 7 pruebas omitidas son limitaciones documentadas de SQLite frente a MySQL
(por ejemplo `HAVING` sin `GROUP BY`, `UPDATE ... LIMIT`, `information_schema`
o métodos que instancian modelos que abren MySQL real). Están listadas en
[`TRACEABILITY.md`](TRACEABILITY.md#pruebas-omitidas-skip).

La matriz contabiliza **945 pruebas ligadas directamente a los 31 modelos**.
Las 4 restantes de la suite completa son 3 pruebas de autoload
(`AutoloadTest`) y 1 control de cobertura de modelos.

## 7. Ejecutar un solo módulo

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --filter Planta --do-not-cache-result
```

En PowerShell, si el filtro usa `|`, usa comillas simples:

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --filter 'Planta|Especie' --do-not-cache-result
```

Si el `.bat` de Windows se atora con el pipe, invoca PHPUnit directamente:

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --filter 'Planta|Especie' --do-not-cache-result
```

## 8. Regenerar la matriz de trazabilidad

1. Ejecuta las tres suites guardando JUnit:

```powershell
New-Item -ItemType Directory -Force tests\results | Out-Null
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Unit        --log-junit tests\results\junit-unit.xml        --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Integration --log-junit tests\results\junit-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke       --log-junit tests\results\junit-smoke.xml       --do-not-cache-result
```

2. Regenera los documentos:

```powershell
php tests\tools\generate_traceability.php
```

Esto sobrescribe `tests/TRACEABILITY.md` y `tests/traceability.json` con el
estado real (`PASS`, `FAIL`, `SKIP` o `NO EJECUTADO`) de cada caso.

## 9. Convenciones para agregar pruebas

- Un archivo unitario por modelo: `tests/Unit/<Dominio>/<Modelo>Test.php`.
- Un archivo de integración por modelo:
  `tests/Integration/<Dominio>/<Modelo>RepositoryTest.php`.
- Un caso de humo por modelo en el data provider de
  `tests/Smoke/ModelsSmokeTest.php`.
- Namespace `SysInescolara\Tests\<Suite>\<Dominio>`.
- Clases `final`, métodos con `#[Test]`, nombres en español.
- No usar `@test`, `@dataProvider`, `@covers` ni `expectErrorLog()` (no existe
  en PHPUnit 11.5).
- No modificar `app/`, `composer.json` ni la base de datos de desarrollo.
- No crear archivos en la raíz del proyecto.
