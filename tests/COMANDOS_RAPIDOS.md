# Comandos rápidos de pruebas — Sys Inescolara

Esta es la **referencia manual** de los **93 casos** (31 módulos × 3 suites:
unitaria, integración y humo), comando por comando. Si prefieres automatizarlo,
usa [`tools/phpunit/scripts/run-module.ps1`](tools/phpunit/scripts/run-module.md)
(Windows) o `run-module.sh` (Linux/macOS), que ejecutan esos mismos
comandos y comparan los conteos con la matriz.

Copia y pega los comandos de cada módulo, en orden. Cada bloque ejecuta las
tres pruebas del módulo: unitaria, integración y humo.

- Índice de ejecutores: [`tools/README.md`](tools/README.md)
- Guía del script: [`tools/phpunit/scripts/run-module.md`](tools/phpunit/scripts/run-module.md)
- Matriz de referencia: [`TRACEABILITY.md`](tools/reportes/formatos/resultados/TRACEABILITY.md) · [`traceability.json`](tools/reportes/formatos/resultados/traceability.json)
- Instrucciones completas: [`GUIA_PRUEBAS.md`](GUIA_PRUEBAS.md)
- Los XML se guardan en `tests\tools\phpunit\formatos\resultados\` (ignorados por git).

## Antes de empezar

```powershell
cd C:\xampp\htdocs\sys-inescolara
```

Cada comando debe terminar con código de salida `0` y la línea `OK` o
`OK, but some tests were skipped!`. Los mensajes de `error_log()` que aparecen
en Unit (por ejemplo `Error al guardar ...`) no son fallos.

> Atajo: el script recibe el número o el nombre del módulo y ejecuta sus tres
> suites. `... run-module.ps1 05` prueba solo Lote; `... run-module.ps1 all`
> (o sin argumento) prueba los 31 y compara todo contra la matriz:
>
> `powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 all`

## Ejecutor multiplataforma (integración + humo)

> Guía completa: [`tools/phpunit/scripts/run-tests.md`](tools/phpunit/scripts/run-tests.md).

Los ejecutores `tests/tools/phpunit/scripts/run-tests.ps1` (Windows) y
`run-tests.sh` (Linux/macOS) envuelven al motor `lib/run-tests.php`. Ejecutan
las suites de integración y humo, muestran un resumen por suite y guardan el log
y el XML JUnit en `tests/tools/phpunit/formatos/resultados/`.

```bash
# Ambas suites, los 31 módulos
php tests/tools/phpunit/scripts/lib/run-tests.php

# Solo una suite
php tests/tools/phpunit/scripts/lib/run-tests.php --suite=integration
php tests/tools/phpunit/scripts/lib/run-tests.php --suite=smoke

# Un módulo por número o por nombre
php tests/tools/phpunit/scripts/lib/run-tests.php --module=13
php tests/tools/phpunit/scripts/lib/run-tests.php --module=Venta --suite=smoke

# Utilidades
php tests/tools/phpunit/scripts/lib/run-tests.php --list       # enumera los 31 módulos
php tests/tools/phpunit/scripts/lib/run-tests.php --dry-run    # muestra los comandos sin ejecutar
php tests/tools/phpunit/scripts/lib/run-tests.php --help
```

Atajos por sistema operativo:

```bash
# Linux / macOS
./tests/tools/phpunit/scripts/run-tests.sh
./tests/tools/phpunit/scripts/run-tests.sh --suite=integration --module=13
```

```powershell
# Windows (PowerShell)
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1 --suite=smoke --module=13
```

Si `pdo_sqlite` no está habilitado globalmente, indique la ruta de la
extensión (repetible):

```bash
php tests/tools/phpunit/scripts/lib/run-tests.php --suite=integration \
  --extension=/usr/lib/php/modules/sqlite3.so \
  --extension=/usr/lib/php/modules/pdo_sqlite.so
```

## Índice

| # | Modelo | # | Modelo | # | Modelo |
| ---: | --- | ---: | --- | ---: | --- |
| 01 | `Planta` | 02 | `Especie` | 03 | `Ubicacion` |
| 04 | `Inventory` | 05 | `Lote` | 06 | `Trazabilidad` |
| 07 | `Insumo` | 08 | `Herramienta` | 09 | `UnidadMedida` |
| 10 | `Merma` | 11 | `RegistroInsumo` | 12 | `UsoHerramienta` |
| 13 | `Venta` | 14 | `LotePrecio` | 15 | `Cliente` |
| 16 | `CuentaCobrar` | 17 | `CuentaPagar` | 18 | `Purchase` |
| 19 | `Ornato` | 20 | `Ampliacion` | 21 | `Proveedor` |
| 22 | `Tarea` | 23 | `SeedCollection` | 24 | `Reports` |
| 25 | `DashboardData` | 26 | `Usuario` | 27 | `Role` |
| 28 | `AuditLog` | 29 | `Backup` | 30 | `Notification` |
| 31 | `PasswordReset` |

## Comandos por módulo

### 01 · Planta — Administrar planta (RF-01)

Esperado: **Unit 12/12 PASS** · **Integration 11/11 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\PlantaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Planta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\PlantaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Planta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-01 --log-junit tests\tools\phpunit\formatos\resultados\module-Planta-smoke.xml --do-not-cache-result
```

### 02 · Especie — Administrar especie (RF-02)

Esperado: **Unit 12/12 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\EspecieTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Especie-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\EspecieRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Especie-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-02 --log-junit tests\tools\phpunit\formatos\resultados\module-Especie-smoke.xml --do-not-cache-result
```

### 03 · Ubicacion — Administrar ubicación (RF-03)

Esperado: **Unit 12/12 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\UbicacionTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ubicacion-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\UbicacionRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ubicacion-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-03 --log-junit tests\tools\phpunit\formatos\resultados\module-Ubicacion-smoke.xml --do-not-cache-result
```

### 04 · Inventory — Inventario (RF-04)

Esperado: **Unit 2/2 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\InventoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Inventory-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\InventoryRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Inventory-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-04 --log-junit tests\tools\phpunit\formatos\resultados\module-Inventory-smoke.xml --do-not-cache-result
```

### 05 · Lote — Lote (RF-05)

Esperado: **Unit 18/18 PASS** · **Integration 20/20 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\LoteTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Lote-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\LoteRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Lote-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-05 --log-junit tests\tools\phpunit\formatos\resultados\module-Lote-smoke.xml --do-not-cache-result
```

### 06 · Trazabilidad — Trazabilidad (RF-06)

Esperado: **Unit 15/15 PASS** · **Integration 12/13 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\TrazabilidadTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Trazabilidad-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\TrazabilidadRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Trazabilidad-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-06 --log-junit tests\tools\phpunit\formatos\resultados\module-Trazabilidad-smoke.xml --do-not-cache-result
```

### 07 · Insumo — Insumos (RF-07)

Esperado: **Unit 12/12 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\InsumoTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Insumo-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\InsumoRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Insumo-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-07 --log-junit tests\tools\phpunit\formatos\resultados\module-Insumo-smoke.xml --do-not-cache-result
```

### 08 · Herramienta — Herramientas (RF-08)

Esperado: **Unit 13/13 PASS** · **Integration 18/18 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\HerramientaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Herramienta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\HerramientaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Herramienta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-08 --log-junit tests\tools\phpunit\formatos\resultados\module-Herramienta-smoke.xml --do-not-cache-result
```

### 09 · UnidadMedida — Unidad de medida (RF-09)

Esperado: **Unit 11/11 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\UnidadMedidaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-UnidadMedida-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\UnidadMedidaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-UnidadMedida-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-09 --log-junit tests\tools\phpunit\formatos\resultados\module-UnidadMedida-smoke.xml --do-not-cache-result
```

### 10 · Merma — Mermas (RF-10)

Esperado: **Unit 17/17 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\MermaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Merma-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\MermaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Merma-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-10 --log-junit tests\tools\phpunit\formatos\resultados\module-Merma-smoke.xml --do-not-cache-result
```

### 11 · RegistroInsumo — Registro de insumos (soporte) (RF-11)

Esperado: **Unit 11/11 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\RegistroInsumoTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-RegistroInsumo-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\RegistroInsumoRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-RegistroInsumo-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-11 --log-junit tests\tools\phpunit\formatos\resultados\module-RegistroInsumo-smoke.xml --do-not-cache-result
```

### 12 · UsoHerramienta — Uso de herramientas (soporte) (RF-12)

Esperado: **Unit 9/9 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\UsoHerramientaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-UsoHerramienta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\UsoHerramientaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-UsoHerramienta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-12 --log-junit tests\tools\phpunit\formatos\resultados\module-UsoHerramienta-smoke.xml --do-not-cache-result
```

### 13 · Venta — Procesar venta (RF-13)

Esperado: **Unit 13/13 PASS** · **Integration 23/23 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\VentaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Venta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\VentaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Venta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-13 --log-junit tests\tools\phpunit\formatos\resultados\module-Venta-smoke.xml --do-not-cache-result
```

### 14 · LotePrecio — Gestionar precio (RF-14)

Esperado: **Unit 7/7 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\LotePrecioTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-LotePrecio-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\LotePrecioRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-LotePrecio-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-14 --log-junit tests\tools\phpunit\formatos\resultados\module-LotePrecio-smoke.xml --do-not-cache-result
```

### 15 · Cliente — Gestionar cliente (RF-15)

Esperado: **Unit 18/18 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\ClienteTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Cliente-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\ClienteRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Cliente-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-15 --log-junit tests\tools\phpunit\formatos\resultados\module-Cliente-smoke.xml --do-not-cache-result
```

### 16 · CuentaCobrar — Cuenta por cobrar (RF-16)

Esperado: **Unit 17/17 PASS** · **Integration 17/18 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\CuentaCobrarTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaCobrar-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\CuentaCobrarRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaCobrar-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-16 --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaCobrar-smoke.xml --do-not-cache-result
```

### 17 · CuentaPagar — Cuentas por pagar (RF-17)

Esperado: **Unit 22/22 PASS** · **Integration 20/21 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\CuentaPagarTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaPagar-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\CuentaPagarRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaPagar-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-17 --log-junit tests\tools\phpunit\formatos\resultados\module-CuentaPagar-smoke.xml --do-not-cache-result
```

### 18 · Purchase — Gestionar compra (RF-18)

Esperado: **Unit 20/20 PASS** · **Integration 24/24 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\PurchaseTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Purchase-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\PurchaseRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Purchase-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-18 --log-junit tests\tools\phpunit\formatos\resultados\module-Purchase-smoke.xml --do-not-cache-result
```

### 19 · Ornato — Gestionar ornatos (RF-19)

Esperado: **Unit 18/18 PASS** · **Integration 17/17 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\OrnatoTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ornato-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\OrnatoRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ornato-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-19 --log-junit tests\tools\phpunit\formatos\resultados\module-Ornato-smoke.xml --do-not-cache-result
```

### 20 · Ampliacion — Gestionar ampliación (RF-20)

Esperado: **Unit 23/24 PASS · 1 SKIP** · **Integration 18/19 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\AmpliacionTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ampliacion-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\AmpliacionRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Ampliacion-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-20 --log-junit tests\tools\phpunit\formatos\resultados\module-Ampliacion-smoke.xml --do-not-cache-result
```

### 21 · Proveedor — Gestionar proveedor (RF-21)

Esperado: **Unit 22/22 PASS** · **Integration 18/18 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\ProveedorTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Proveedor-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\ProveedorRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Proveedor-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-21 --log-junit tests\tools\phpunit\formatos\resultados\module-Proveedor-smoke.xml --do-not-cache-result
```

### 22 · Tarea — Asignar tarea (RF-22)

Esperado: **Unit 10/10 PASS** · **Integration 15/15 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\TareaTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Tarea-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\TareaRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Tarea-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-22 --log-junit tests\tools\phpunit\formatos\resultados\module-Tarea-smoke.xml --do-not-cache-result
```

### 23 · SeedCollection — Gestionar recolección (RF-23)

Esperado: **Unit 21/22 PASS · 1 SKIP** · **Integration 15/16 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\SeedCollectionTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-SeedCollection-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\SeedCollectionRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-SeedCollection-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-23 --log-junit tests\tools\phpunit\formatos\resultados\module-SeedCollection-smoke.xml --do-not-cache-result
```

### 24 · Reports — Reportes (RF-24)

Esperado: **Unit 7/7 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\ReportsTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Reports-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\ReportsRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Reports-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-24 --log-junit tests\tools\phpunit\formatos\resultados\module-Reports-smoke.xml --do-not-cache-result
```

### 25 · DashboardData — Inicio / Dashboard (RF-25)

Esperado: **Unit 10/10 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\DashboardDataTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-DashboardData-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\DashboardDataRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-DashboardData-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-25 --log-junit tests\tools\phpunit\formatos\resultados\module-DashboardData-smoke.xml --do-not-cache-result
```

### 26 · Usuario — Usuario (RF-26)

Esperado: **Unit 21/21 PASS** · **Integration 32/32 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\UsuarioTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Usuario-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\UsuarioRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Usuario-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-26 --log-junit tests\tools\phpunit\formatos\resultados\module-Usuario-smoke.xml --do-not-cache-result
```

### 27 · Role — Roles (RF-27)

Esperado: **Unit 12/12 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\RoleTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Role-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\RoleRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Role-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-27 --log-junit tests\tools\phpunit\formatos\resultados\module-Role-smoke.xml --do-not-cache-result
```

### 28 · AuditLog — Bitácora (RF-28)

Esperado: **Unit 4/4 PASS** · **Integration 8/8 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\AuditLogTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-AuditLog-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\AuditLogRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-AuditLog-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-28 --log-junit tests\tools\phpunit\formatos\resultados\module-AuditLog-smoke.xml --do-not-cache-result
```

### 29 · Backup — Respaldo (RF-29)

Esperado: **Unit 10/10 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\BackupTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Backup-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\BackupRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Backup-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-29 --log-junit tests\tools\phpunit\formatos\resultados\module-Backup-smoke.xml --do-not-cache-result
```

### 30 · Notification — Notificaciones (soporte) (RF-30)

Esperado: **Unit 10/10 PASS** · **Integration 15/15 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\NotificationTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Notification-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\NotificationRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-Notification-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-30 --log-junit tests\tools\phpunit\formatos\resultados\module-Notification-smoke.xml --do-not-cache-result
```

### 31 · PasswordReset — Recuperación de contraseña (soporte) (RF-31)

Esperado: **Unit 4/4 PASS** · **Integration 9/9 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\PasswordResetTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-PasswordReset-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\PasswordResetRepositoryTest.php --log-junit tests\tools\phpunit\formatos\resultados\module-PasswordReset-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-31 --log-junit tests\tools\phpunit\formatos\resultados\module-PasswordReset-smoke.xml --do-not-cache-result
```

## Verificación global

```powershell
# Suite completa: 949 tests, 11683 assertions, 7 skipped, exit 0
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result

# Regenerar la matriz desde los JUnit
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\reportes\scripts\generate_traceability.ps1
```

En Linux/macOS: `./tests/tools/reportes/scripts/generate_traceability.sh`.

Guía del generador: [`tools/reportes/scripts/generate_traceability.md`](tools/reportes/scripts/generate_traceability.md).

Resultado esperado de la matriz: `31 requisitos, 93 casos (93 PASS, 0 FAIL, 0 SKIP, 0 sin ejecutar)`
y `945 pruebas de modelo (938 PASS, 7 SKIP)`.

## Cómo revisar un XML

```powershell
$xml = [xml](Get-Content tests\tools\phpunit\formatos\resultados\module-Planta-unit.xml -Raw)
$xml.testsuites.testsuite | Select-Object name, tests, assertions, failures, errors, skipped
```

Debe mostrar `failures=0`, `errors=0` y `skipped` igual a la columna de
omitidos de la matriz.

