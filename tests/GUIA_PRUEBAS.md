# Guía de ejecución de pruebas — Sys Inescolara

Esta guía explica cómo ejecutar las tres suites, en qué orden se ejecutan y qué
resultado se debe esperar en cada una. La matriz de trazabilidad se actualiza
en [`TRACEABILITY.md`](TRACEABILITY.md) y [`traceability.json`](traceability.json).
Para copiar y pegar los comandos de un módulo concreto, usa
[`COMANDOS_RAPIDOS.md`](COMANDOS_RAPIDOS.md).

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

## 7. Ejecutar y verificar un solo módulo

Cada módulo tiene tres piezas que se ejecutan por separado: su clase unitaria,
su clase de integración y su caso de humo. Para verificarlo hay dos caminos.

### 7.1 Camino rápido (script incluido)

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 Planta
```

El script acepta el nombre del modelo (`Planta`), el número (`01`), el nombre
del sidebar (`Administrar planta`) o el requisito (`RF-01`). Ejecuta las tres
suites (por ruta de archivo en Unit/Integration y por ID en Smoke), guarda los
JUnit en `tests\results\module-<Modelo>-{unit,integration,smoke}.xml` y compara
contra `tests/traceability.json`:

```text
==================================================================
Modulo: Planta (RF-01) - Administrar planta
==================================================================

Suite       Ejecutadas Esperadas Pass Fail Skip Aserciones Estado
-----       ---------- --------- ---- ---- ---- ---------- ------
Unitaria            12        12   12    0    0         40 COINCIDE
Integracion         11        11   11    0    0        105 COINCIDE
Humo                 2         2    2    0    0         24 COINCIDE

RESULTADO: COINCIDE con tests/traceability.json
```

Sale con código `0` si todo coincide y `1` si algo difiere. Para recorrer los 31
módulos:

```powershell
1..31 | ForEach-Object {
    powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 ('{0:00}' -f $_)
}
```

> El script está en ASCII a propósito: Windows PowerShell 5.1 lee los `.ps1`
> UTF-8 sin BOM como ANSI y rompería los acentos.

### 7.2 Camino manual (comando por comando)

Los tres comandos, con el mismo patrón que se usó para generar la matriz. Para
Unit e Integration se pasa la **ruta del archivo de prueba**; para humo se usa
el `--filter` con el ID del caso:

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\PlantaTest.php --log-junit tests\results\module-Planta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\PlantaRepositoryTest.php --log-junit tests\results\module-Planta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-01 --log-junit tests\results\module-Planta-smoke.xml --do-not-cache-result
```

Qué esperar en la consola de cada uno:

| Comando | Salida esperada | Código de salida |
| --- | --- | --- |
| Unit `PlantaTest` | `OK (12 tests, 40 assertions)` | `0` |
| Integration `PlantaRepositoryTest` | `OK (11 tests, 105 assertions)` | `0` |
| Smoke `CP-SMOKE-01` | `OK (2 tests, 24 assertions)` | `0` |

Los mensajes `Error al guardar planta: ...` que aparecen en Unit son
`error_log()` del modelo probando el camino sin base de datos; **no son
fallos**. Lo que manda es el código de salida y la línea `OK`/`FAILURES!`.

Para otro módulo cambia solo la ruta y el ID de humo. Los tres comandos de cada
uno de los 31 módulos están listos para copiar en
[`COMANDOS_RAPIDOS.md`](COMANDOS_RAPIDOS.md).

> **Por qué la ruta y no `--filter`:** `--filter` es una coincidencia por
> subcadena. `--filter InsumoTest` también ejecuta `RegistroInsumoTest` (23
> pruebas en vez de 12) y `--filter HerramientaTest` también ejecuta
> `UsoHerramientaTest` (22 en vez de 13). Pasar el archivo evita esa ambigüedad.

Si de todas formas usas `--filter` con varios patrones, separa con `|` y usa
comillas simples y el binario directo:

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --filter 'PlantaTest|EspecieTest' --do-not-cache-result
```

Evita `vendor\bin\phpunit` cuando uses `|`: es un `.bat` que reexpande `%*` y
corta el pipe. Con `php vendor\phpunit\phpunit\phpunit` el filtro llega intacto.

### 7.3 Cómo comparar con el `.xml`

El JUnit que genera `--log-junit` tiene esta forma (la cabecera es la que
importa):

```xml
<testsuite name="...phpunit.xml" tests="12" assertions="40" errors="0" failures="0" skipped="0">
  <testsuite name="Unit" tests="12" assertions="40" errors="0" failures="0" skipped="0">
    <testsuite name="...PlantaTest" file="...PlantaTest.php" tests="12" assertions="40" errors="0" failures="0" skipped="0">
      <testcase name="valores_por_defecto" ... assertions="7"/>
```

Revisa estos atributos del nodo raíz:

| Atributo | Significado | Debe ser |
| --- | --- | --- |
| `tests` | pruebas ejecutadas | igual a `pruebas` en `traceability.json` |
| `assertions` | aserciones ejecutadas | igual al valor de referencia |
| `failures` | pruebas fallidas | `0` |
| `errors` | errores de ejecución | `0` |
| `skipped` | pruebas omitidas | igual a `skip` en `traceability.json` |

Comprobación rápida desde PowerShell:

```powershell
$xml = [xml](Get-Content tests\results\module-Planta-unit.xml -Raw)
$xml.testsuites.testsuite | Select-Object name, tests, assertions, failures, errors, skipped
```

Y contra la matriz:

```powershell
$json = Get-Content tests\traceability.json -Raw | ConvertFrom-Json
$json.modulos | Where-Object modelo -eq 'Planta' |
    Select-Object modelo, idRequisito,
        @{n='unit';e={$_.casos[0].pruebas}},
        @{n='unitPass';e={$_.casos[0].pass}},
        @{n='unitSkip';e={$_.casos[0].skip}},
        @{n='integration';e={$_.casos[1].pruebas}},
        @{n='smoke';e={$_.casos[2].pruebas}}
```

Los conteos de referencia de los 31 módulos están en el **Resumen por módulo**
de [`TRACEABILITY.md`](TRACEABILITY.md) y en `tests/traceability.json`. La
columna **Omitidos** indica cuántos `skipped` debe reportar el XML; un `SKIP`
no es un fallo, pero el número debe coincidir.

Si el módulo tiene pruebas omitidas, la consola dirá
`OK, but some tests were skipped!` y el XML tendrá `skipped="N"`. Por ejemplo:

| Módulo | Unit | Integration | Smoke |
| --- | --- | --- | --- |
| `Trazabilidad` | 15/15, 0 skip | 12/13, 1 skip | 2/2, 0 skip |
| `CuentaPagar` | 22/22, 0 skip | 20/21, 1 skip | 2/2, 0 skip |
| `Ampliacion` | 23/24, 1 skip | 18/19, 1 skip | 2/2, 0 skip |
| `SeedCollection` | 21/22, 1 skip | 15/16, 1 skip | 2/2, 0 skip |

### 7.4 Verificación final de los 31 módulos

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result
```

Debe imprimir `OK, but some tests were skipped!` con
`Tests: 949, Assertions: 11683, Skipped: 7` y código de salida `0`. Después:

```powershell
php tests\tools\generate_traceability.php
```

Debe reportar `31 requisitos, 93 casos (93 PASS, 0 FAIL, 0 SKIP, 0 sin ejecutar)`
y, en la línea siguiente, **945 pruebas de modelo** (938 PASS, 7 SKIP).

### 7.5 Tabla de referencia por módulo

Estos son los conteos que debe mostrar cada ejecución individual. `N/M` =
pruebas PASS / pruebas ejecutadas.

| # | Modelo | Unitaria | Integración | Humo |
| ---: | --- | --- | --- | --- |
| 01 | `Planta` | 12/12 | 11/11 | 2/2 |
| 02 | `Especie` | 12/12 | 12/12 | 2/2 |
| 03 | `Ubicacion` | 12/12 | 14/14 | 2/2 |
| 04 | `Inventory` | 2/2 | 10/10 | 2/2 |
| 05 | `Lote` | 18/18 | 20/20 | 2/2 |
| 06 | `Trazabilidad` | 15/15 | 12/13 (1 skip) | 2/2 |
| 07 | `Insumo` | 12/12 | 13/13 | 2/2 |
| 08 | `Herramienta` | 13/13 | 18/18 | 2/2 |
| 09 | `UnidadMedida` | 11/11 | 10/10 | 2/2 |
| 10 | `Merma` | 17/17 | 13/13 | 2/2 |
| 11 | `RegistroInsumo` | 11/11 | 12/12 | 2/2 |
| 12 | `UsoHerramienta` | 9/9 | 10/10 | 2/2 |
| 13 | `Venta` | 13/13 | 23/23 | 2/2 |
| 14 | `LotePrecio` | 7/7 | 14/14 | 2/2 |
| 15 | `Cliente` | 18/18 | 14/14 | 2/2 |
| 16 | `CuentaCobrar` | 17/17 | 17/18 (1 skip) | 2/2 |
| 17 | `CuentaPagar` | 22/22 | 20/21 (1 skip) | 2/2 |
| 18 | `Purchase` | 20/20 | 24/24 | 2/2 |
| 19 | `Ornato` | 18/18 | 17/17 | 2/2 |
| 20 | `Ampliacion` | 23/24 (1 skip) | 18/19 (1 skip) | 2/2 |
| 21 | `Proveedor` | 22/22 | 18/18 | 2/2 |
| 22 | `Tarea` | 10/10 | 15/15 | 2/2 |
| 23 | `SeedCollection` | 21/22 (1 skip) | 15/16 (1 skip) | 2/2 |
| 24 | `Reports` | 7/7 | 12/12 | 2/2 |
| 25 | `DashboardData` | 10/10 | 14/14 | 2/2 |
| 26 | `Usuario` | 21/21 | 32/32 | 2/2 |
| 27 | `Role` | 12/12 | 13/13 | 2/2 |
| 28 | `AuditLog` | 4/4 | 8/8 | 2/2 |
| 29 | `Backup` | 10/10 | 10/10 | 2/2 |
| 30 | `Notification` | 10/10 | 15/15 | 2/2 |
| 31 | `PasswordReset` | 4/4 | 9/9 | 2/2 |

Las aserciones exactas de cada módulo están en
[`TRACEABILITY.md`](TRACEABILITY.md) (columna **Evidencia**) y en
`traceability.json` (`casos[].pruebas`, `casos[].pass`, `casos[].skip`).

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
