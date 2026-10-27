# Comandos rápidos de pruebas — Sys Inescolara

Copia y pega los comandos de cada módulo, en orden. Cada bloque ejecuta las
tres pruebas del módulo: unitaria, integración y humo.

- Matriz de referencia: [`TRACEABILITY.md`](TRACEABILITY.md) · [`traceability.json`](traceability.json)
- Instrucciones completas: [`GUIA_PRUEBAS.md`](GUIA_PRUEBAS.md)
- Los XML se guardan en `tests\results\` (ignorados por git).

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
> `powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\run-module.ps1 all`

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
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\PlantaTest.php --log-junit tests\results\module-Planta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\PlantaRepositoryTest.php --log-junit tests\results\module-Planta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-01 --log-junit tests\results\module-Planta-smoke.xml --do-not-cache-result
```

### 02 · Especie — Administrar especie (RF-02)

Esperado: **Unit 12/12 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\EspecieTest.php --log-junit tests\results\module-Especie-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\EspecieRepositoryTest.php --log-junit tests\results\module-Especie-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-02 --log-junit tests\results\module-Especie-smoke.xml --do-not-cache-result
```

### 03 · Ubicacion — Administrar ubicación (RF-03)

Esperado: **Unit 12/12 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\UbicacionTest.php --log-junit tests\results\module-Ubicacion-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\UbicacionRepositoryTest.php --log-junit tests\results\module-Ubicacion-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-03 --log-junit tests\results\module-Ubicacion-smoke.xml --do-not-cache-result
```

### 04 · Inventory — Inventario (RF-04)

Esperado: **Unit 2/2 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\InventoryTest.php --log-junit tests\results\module-Inventory-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\InventoryRepositoryTest.php --log-junit tests\results\module-Inventory-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-04 --log-junit tests\results\module-Inventory-smoke.xml --do-not-cache-result
```

### 05 · Lote — Lote (RF-05)

Esperado: **Unit 18/18 PASS** · **Integration 20/20 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\LoteTest.php --log-junit tests\results\module-Lote-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\LoteRepositoryTest.php --log-junit tests\results\module-Lote-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-05 --log-junit tests\results\module-Lote-smoke.xml --do-not-cache-result
```

### 06 · Trazabilidad — Trazabilidad (RF-06)

Esperado: **Unit 15/15 PASS** · **Integration 12/13 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\TrazabilidadTest.php --log-junit tests\results\module-Trazabilidad-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\TrazabilidadRepositoryTest.php --log-junit tests\results\module-Trazabilidad-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-06 --log-junit tests\results\module-Trazabilidad-smoke.xml --do-not-cache-result
```

### 07 · Insumo — Insumos (RF-07)

Esperado: **Unit 12/12 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\InsumoTest.php --log-junit tests\results\module-Insumo-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\InsumoRepositoryTest.php --log-junit tests\results\module-Insumo-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-07 --log-junit tests\results\module-Insumo-smoke.xml --do-not-cache-result
```

### 08 · Herramienta — Herramientas (RF-08)

Esperado: **Unit 13/13 PASS** · **Integration 18/18 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\HerramientaTest.php --log-junit tests\results\module-Herramienta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\HerramientaRepositoryTest.php --log-junit tests\results\module-Herramienta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-08 --log-junit tests\results\module-Herramienta-smoke.xml --do-not-cache-result
```

### 09 · UnidadMedida — Unidad de medida (RF-09)

Esperado: **Unit 11/11 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Catalogo\UnidadMedidaTest.php --log-junit tests\results\module-UnidadMedida-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Catalogo\UnidadMedidaRepositoryTest.php --log-junit tests\results\module-UnidadMedida-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-09 --log-junit tests\results\module-UnidadMedida-smoke.xml --do-not-cache-result
```

### 10 · Merma — Mermas (RF-10)

Esperado: **Unit 17/17 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\MermaTest.php --log-junit tests\results\module-Merma-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\MermaRepositoryTest.php --log-junit tests\results\module-Merma-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-10 --log-junit tests\results\module-Merma-smoke.xml --do-not-cache-result
```

### 11 · RegistroInsumo — Registro de insumos (soporte) (RF-11)

Esperado: **Unit 11/11 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\RegistroInsumoTest.php --log-junit tests\results\module-RegistroInsumo-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\RegistroInsumoRepositoryTest.php --log-junit tests\results\module-RegistroInsumo-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-11 --log-junit tests\results\module-RegistroInsumo-smoke.xml --do-not-cache-result
```

### 12 · UsoHerramienta — Uso de herramientas (soporte) (RF-12)

Esperado: **Unit 9/9 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\UsoHerramientaTest.php --log-junit tests\results\module-UsoHerramienta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\UsoHerramientaRepositoryTest.php --log-junit tests\results\module-UsoHerramienta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-12 --log-junit tests\results\module-UsoHerramienta-smoke.xml --do-not-cache-result
```

### 13 · Venta — Procesar venta (RF-13)

Esperado: **Unit 13/13 PASS** · **Integration 23/23 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\VentaTest.php --log-junit tests\results\module-Venta-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\VentaRepositoryTest.php --log-junit tests\results\module-Venta-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-13 --log-junit tests\results\module-Venta-smoke.xml --do-not-cache-result
```

### 14 · LotePrecio — Gestionar precio (RF-14)

Esperado: **Unit 7/7 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Inventario\LotePrecioTest.php --log-junit tests\results\module-LotePrecio-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Inventario\LotePrecioRepositoryTest.php --log-junit tests\results\module-LotePrecio-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-14 --log-junit tests\results\module-LotePrecio-smoke.xml --do-not-cache-result
```

### 15 · Cliente — Gestionar cliente (RF-15)

Esperado: **Unit 18/18 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\ClienteTest.php --log-junit tests\results\module-Cliente-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\ClienteRepositoryTest.php --log-junit tests\results\module-Cliente-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-15 --log-junit tests\results\module-Cliente-smoke.xml --do-not-cache-result
```

### 16 · CuentaCobrar — Cuenta por cobrar (RF-16)

Esperado: **Unit 17/17 PASS** · **Integration 17/18 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\CuentaCobrarTest.php --log-junit tests\results\module-CuentaCobrar-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\CuentaCobrarRepositoryTest.php --log-junit tests\results\module-CuentaCobrar-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-16 --log-junit tests\results\module-CuentaCobrar-smoke.xml --do-not-cache-result
```

### 17 · CuentaPagar — Cuentas por pagar (RF-17)

Esperado: **Unit 22/22 PASS** · **Integration 20/21 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\CuentaPagarTest.php --log-junit tests\results\module-CuentaPagar-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\CuentaPagarRepositoryTest.php --log-junit tests\results\module-CuentaPagar-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-17 --log-junit tests\results\module-CuentaPagar-smoke.xml --do-not-cache-result
```

### 18 · Purchase — Gestionar compra (RF-18)

Esperado: **Unit 20/20 PASS** · **Integration 24/24 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\PurchaseTest.php --log-junit tests\results\module-Purchase-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\PurchaseRepositoryTest.php --log-junit tests\results\module-Purchase-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-18 --log-junit tests\results\module-Purchase-smoke.xml --do-not-cache-result
```

### 19 · Ornato — Gestionar ornatos (RF-19)

Esperado: **Unit 18/18 PASS** · **Integration 17/17 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Ventas\OrnatoTest.php --log-junit tests\results\module-Ornato-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Ventas\OrnatoRepositoryTest.php --log-junit tests\results\module-Ornato-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-19 --log-junit tests\results\module-Ornato-smoke.xml --do-not-cache-result
```

### 20 · Ampliacion — Gestionar ampliación (RF-20)

Esperado: **Unit 23/24 PASS · 1 SKIP** · **Integration 18/19 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\AmpliacionTest.php --log-junit tests\results\module-Ampliacion-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\AmpliacionRepositoryTest.php --log-junit tests\results\module-Ampliacion-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-20 --log-junit tests\results\module-Ampliacion-smoke.xml --do-not-cache-result
```

### 21 · Proveedor — Gestionar proveedor (RF-21)

Esperado: **Unit 22/22 PASS** · **Integration 18/18 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Compras\ProveedorTest.php --log-junit tests\results\module-Proveedor-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Compras\ProveedorRepositoryTest.php --log-junit tests\results\module-Proveedor-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-21 --log-junit tests\results\module-Proveedor-smoke.xml --do-not-cache-result
```

### 22 · Tarea — Asignar tarea (RF-22)

Esperado: **Unit 10/10 PASS** · **Integration 15/15 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\TareaTest.php --log-junit tests\results\module-Tarea-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\TareaRepositoryTest.php --log-junit tests\results\module-Tarea-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-22 --log-junit tests\results\module-Tarea-smoke.xml --do-not-cache-result
```

### 23 · SeedCollection — Gestionar recolección (RF-23)

Esperado: **Unit 21/22 PASS · 1 SKIP** · **Integration 15/16 PASS · 1 SKIP** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Operaciones\SeedCollectionTest.php --log-junit tests\results\module-SeedCollection-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Operaciones\SeedCollectionRepositoryTest.php --log-junit tests\results\module-SeedCollection-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-23 --log-junit tests\results\module-SeedCollection-smoke.xml --do-not-cache-result
```

### 24 · Reports — Reportes (RF-24)

Esperado: **Unit 7/7 PASS** · **Integration 12/12 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\ReportsTest.php --log-junit tests\results\module-Reports-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\ReportsRepositoryTest.php --log-junit tests\results\module-Reports-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-24 --log-junit tests\results\module-Reports-smoke.xml --do-not-cache-result
```

### 25 · DashboardData — Inicio / Dashboard (RF-25)

Esperado: **Unit 10/10 PASS** · **Integration 14/14 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\DashboardDataTest.php --log-junit tests\results\module-DashboardData-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\DashboardDataRepositoryTest.php --log-junit tests\results\module-DashboardData-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-25 --log-junit tests\results\module-DashboardData-smoke.xml --do-not-cache-result
```

### 26 · Usuario — Usuario (RF-26)

Esperado: **Unit 21/21 PASS** · **Integration 32/32 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\UsuarioTest.php --log-junit tests\results\module-Usuario-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\UsuarioRepositoryTest.php --log-junit tests\results\module-Usuario-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-26 --log-junit tests\results\module-Usuario-smoke.xml --do-not-cache-result
```

### 27 · Role — Roles (RF-27)

Esperado: **Unit 12/12 PASS** · **Integration 13/13 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\RoleTest.php --log-junit tests\results\module-Role-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\RoleRepositoryTest.php --log-junit tests\results\module-Role-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-27 --log-junit tests\results\module-Role-smoke.xml --do-not-cache-result
```

### 28 · AuditLog — Bitácora (RF-28)

Esperado: **Unit 4/4 PASS** · **Integration 8/8 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\AuditLogTest.php --log-junit tests\results\module-AuditLog-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\AuditLogRepositoryTest.php --log-junit tests\results\module-AuditLog-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-28 --log-junit tests\results\module-AuditLog-smoke.xml --do-not-cache-result
```

### 29 · Backup — Respaldo (RF-29)

Esperado: **Unit 10/10 PASS** · **Integration 10/10 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Analitica\BackupTest.php --log-junit tests\results\module-Backup-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Analitica\BackupRepositoryTest.php --log-junit tests\results\module-Backup-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-29 --log-junit tests\results\module-Backup-smoke.xml --do-not-cache-result
```

### 30 · Notification — Notificaciones (soporte) (RF-30)

Esperado: **Unit 10/10 PASS** · **Integration 15/15 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\NotificationTest.php --log-junit tests\results\module-Notification-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\NotificationRepositoryTest.php --log-junit tests\results\module-Notification-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-30 --log-junit tests\results\module-Notification-smoke.xml --do-not-cache-result
```

### 31 · PasswordReset — Recuperación de contraseña (soporte) (RF-31)

Esperado: **Unit 4/4 PASS** · **Integration 9/9 PASS** · **Smoke 2/2 PASS**

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Unit\Seguridad\PasswordResetTest.php --log-junit tests\results\module-PasswordReset-unit.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml tests\Integration\Seguridad\PasswordResetRepositoryTest.php --log-junit tests\results\module-PasswordReset-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke --filter CP-SMOKE-31 --log-junit tests\results\module-PasswordReset-smoke.xml --do-not-cache-result
```

## Verificación global

```powershell
# Suite completa: 949 tests, 11683 assertions, 7 skipped, exit 0
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --do-not-cache-result

# Regenerar la matriz desde los JUnit
php tests\tools\generate_traceability.php
```

Resultado esperado de la matriz: `31 requisitos, 93 casos (93 PASS, 0 FAIL, 0 SKIP, 0 sin ejecutar)`
y `945 pruebas de modelo (938 PASS, 7 SKIP)`.

## Cómo revisar un XML

```powershell
$xml = [xml](Get-Content tests\results\module-Planta-unit.xml -Raw)
$xml.testsuites.testsuite | Select-Object name, tests, assertions, failures, errors, skipped
```

Debe mostrar `failures=0`, `errors=0` y `skipped` igual a la columna de
omitidos de la matriz.

