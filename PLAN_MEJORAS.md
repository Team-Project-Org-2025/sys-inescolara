# PLAN DE MEJORAS — SYSINECOLARA

Basado en el análisis del proyecto BarkiOS como referencia de patrones y arquitectura.

---

## OBJETIVO
Refactorizar módulos transaccionales, implementar borrado lógico, centralizar validación, estandarizar uso de transacciones y mejorar la legibilidad del código, preparando el sistema para los módulos de Ventas/POS, Compras y demás funcionalidades pendientes.

---

## FASE 1: INFRAESTRUCTURA Y CALIDAD DE CÓDIGO

### 1.1 Validation Helper
**Archivo nuevo:** `app/helpers/Validation.php`
**Namespace:** `SysInescolara\helpers`
**Propósito:** Centralizar la validación de datos del lado del servidor.

- Clase con métodos estáticos reutilizables.
- Patrones regex por tipo de campo: `cedula`, `rif`, `telefono`, `email`, `nombre`, `precio`, `codigo`, `fecha`, `direccion`.
- Métodos: `validateField($value, $type, $required)`, `validate($data, $rules)`, `sanitize($data)`, `validateDate($date, $format)`, `validateRange($value, $min, $max)`.

**Referencia:** BarkiOS `app/helpers/Validation.php`

### 1.2 JS Validation Helper (Client-Side)
**Archivo nuevo:** `public/assets/js/utils/validation.js`
**Propósito:** Refactorizar la validación inline que se repite en cada módulo JS hacia un helper centralizado.

- `setupRealTimeValidation($form, rules)` — vincula eventos `input`/`blur` para feedback visual en tiempo real.
- `validateForm($form, rules)` — valida todo el formulario antes de submit.
- `validateField($input, regex, errorMsg)` — valida un campo individual.
- `clearValidation($form)` — limpia estados visuales.

**Referencia:** BarkiOS `public/assets/js/utils/validation.js`

### 1.3 Shared Controller Functions (Refactor controller_helpers.php)
**Archivo existente:** `app/controllers/controller_helpers.php`
**Propósito:** Centralizar funciones que hoy se repiten en cada controlador.

- `checkModuleAuth()` ✓ (ya existía)
- `checkPermisoOrFail()` ✓ (ya existía)
- `jsonResponse()` ✓ (ya existía, ahora incluye `Content-Type: application/json; charset=utf-8`)
- `handleError()` ✓ (ya existía)
- `isAjaxRequest()` ✓ (ya existía)
- `getRequestData()` ✓ — Función que unifica la lectura de `$_POST`/`$_GET` según el Content-Type (JSON vs form data).
- `validateAndSanitize()` ✓ — Aplica `Validation::sanitize()` y `Validation::validate()` en un solo paso con excepción.
- **Refactor:** Se eliminaron las llamadas redundantes `header('Content-Type: application/json; charset=utf-8')` de 17 controladores AJAX y se migró `LoginController`, `NotificationsController` y `FrontController::renderNotFound()` a usar `jsonResponse()` en lugar de `echo json_encode` manual.

### 1.4 Estandarizar Transacciones en Módulos Existentes
**Archivos modificados/creados:** Modelos Task, Tool; controladores TasksController, ToolsController.
**Propósito:** Asegurar que toda operación que modifique múltiples tablas use transacciones atómicas.

**Nuevos modelos creados:**
- `AsignarTarea.php` — CRUD para `asignar_tarea` (PK id_asignacion, FK a trabajadores/tareas/lote)
- `ConsumoInsumo.php` — CRUD para `consumo_insumos` (FK a asignar_tarea + insumo)
- `UsoHerramienta.php` — CRUD para `uso_herramienta` (FK a asignar_tarea + herramienta)

**Métodos transaccionales agregados:**
- `Task::assignTaskWithConsumptions($assignmentData, $consumptions)` — Inserta en `asignar_tarea` + `consumo_insumos` + actualiza `insumo.stock_actual` en una sola transacción
- `Task::completeAssignment($id, $fecha, $horas)` — Actualiza estatus a completada en transacción
- `Tool::recordUsageWithStateUpdate($usageData)` — Inserta en `uso_herramienta` + actualiza `herramienta.estado` en una sola transacción

**Acciones de controlador agregadas:**
- TasksController: `assign_ajax`, `complete_ajax`, `cancel_ajax`, `get_assignments`, `get_assignment`
- ToolsController: `record_usage_ajax`, `get_usages`

**Permisos nuevos:** `TAREAS_ASSIGN` (47), `USO_HERRAMIENTA_CREATE` (48) — ver `scripts/add-permissions-1.4.sql`

---

## FASE 2: BORRADO LÓGICO (SOFT DELETES) ✅

### 2.1 Migración BD - Agregar columna `activo`
**Script:** `scripts/add-soft-deletes-2.sql`

**Tablas modificadas (11):** plantas, especie, insumo, herramienta, lote, proveedores, cliente, ubicacion, unidad_medida, tareas (+ trabajadores ya tenía activo)

### 2.2 Modelos Refactorizados (11)
Cada modelo implementa soft delete:
- `delete()` → `UPDATE SET activo = 0` (en lugar de DELETE)
- `getAll()` → agrega `WHERE activo = 1`
- `restore($id)` → nuevo, `UPDATE SET activo = 1`
- `getById()` y `exists()` sin filtro activo (para auditoría/edición)

**Modelos modificados:** Plant, Species, Supplies, Tool, Batch, Supplier, Employee, Client, Location, UnidadMedida, Task

### 2.3 Controladores Actualizados (14)
- Mensajes de éxito: "eliminado" → "desactivado"
- Auditoría: `AuditLog::record('DELETE', ...)` → `AuditLog::record('DEACTIVATE', ...)`
- Excepciones: Backups, Roles, User, Prices mantienen DELETE (hard delete)

### 2.4 Relaciones FK
- Las consultas JOIN agregan `AND tabla.activo = 1` para evitar referenciar registros desactivados
- `Location::hasAssociatedLots()` filtra por `activo = 1` al validar desactivación

---

## FASE 3: MÓDULO VENTAS / POS

Basado en BarkiOS `SaleController.php` + `Sale.php`.

### 3.1 Estructura BD (nuevas tablas)
| Tabla | Propósito | Campos clave |
|---|---|---|
| venta | Cabecera de venta | id_venta, referencia, id_cliente, id_trabajador_gestor, tipo_venta (contado/credito), estado (pendiente/completada/cancelada), monto_subtotal, monto_iva, monto_total, saldo_pendiente, fecha_venta, observaciones, activo |
| detalle_venta | Items de la venta | id_detalle_venta, id_venta, id_lote, cantidad, precio_unitario, sub_total |
| credito | Créditos asociados | id_credito, referencia_credito, id_venta, fecha_inicio |
| cuentas_cobrar | Cuotas del crédito | id_cuenta_cobrar, id_credito, monto, fecha_vencimiento, estado (pendiente/pagado/vencido) |
| pago_venta | Pagos recibidos | id_pago, id_venta, id_credito, monto, tipo_pago, referencia, banco, fecha_pago, estado (PENDIENTE/CONFIRMADO/ANULADO) |

### 3.2 Funcionalidades del POS
- Búsqueda de productos (plantas/lotes) por código/nombre en tiempo real.
- Selector de cliente (con búsqueda) + creador rápido.
- Selector de trabajador gestor.
- Cálculo automático de subtotal, IVA (16% o configurable), total.
- Venta contado: Pago inmediato, liberación de stock, PDF de factura.
- Venta crédito: Genera credito + cuentas_cobrar, seguimiento de pagos.
- Cancelación de venta: Libera stock en lote, actualiza estado.
- PDF de factura con Dompdf (ya disponible en composer.json).
- Registro de auditoría en cada paso.

### 3.3 Integración con inventario
- Al vender: `lote.cantidad_actual -= cantidad_vendida`.
- Si `cantidad_actual = 0`: opcionalmente marcar lote como `estado = 'Agotado'`.
- Validar disponibilidad antes de confirmar venta.
- Actualizar `plantas.cantidad_total` vía subquery o trigger (o no — actualmente es un campo calculado).

---

## FASE 4: MÓDULO COMPRAS

Basado en BarkiOS `PurchaseController.php` + `Purchase.php`.

### 4.1 Estructura BD (nuevas tablas)
| Tabla | Propósito | Campos clave |
|---|---|---|
| compra | Cabecera de compra | id_compra, id_proveedor, factura_numero, fecha_compra, monto_total, observaciones, activo |
| detalle_compra | Items de la compra | id_detalle_compra, id_compra, id_insumo, cantidad, costo_unitario, sub_total |
| cuentas_pagar | Cuentas por pagar | id_cuenta_pagar, id_compra, monto, fecha_vencimiento, estado (pendiente/pagado/vencido) |
| pago_compra | Pagos realizados | id_pago_compra, id_cuenta_pagar, monto, tipo_pago, referencia, banco, fecha_pago, estado (PENDIENTE/CONFIRMADO/ANULADO) |

### 4.2 Funcionalidades
- Selección de proveedor con búsqueda.
- Múltiples items (insumos) por compra.
- Validación de duplicados (factura).
- Actualización automática de stock + costo_unitario_actual en insumo.
- Cuentas por pagar con seguimiento de pagos parciales.
- Soft delete en cascada.

---

## FASE 5: EXCHANGE RATE / TASA BCV (opcional)

### 5.1 ExchangeRateService
**Archivo nuevo:** `app/services/ExchangeRateService.php`
**Propósito:** Obtener la tasa de cambio oficial Bs/USD del BCV (Banco Central de Venezuela) con caché.

```php
class ExchangeRateService {
    public static function getRate(): float;
    public static function formatBs(float $amount, ?float $rate = null): string;
    public static function convertToBs(float $usd, ?float $rate = null): float;
}
```

**Referencia:** BarkiOS `app/services/ExchangeRateService.php` + `AdminContext.php`

---

## FASE 6: MEJORAS TRANSVERSALES

### 6.1 Estandarizar Mensajes de Auditoría
- Formato consistente en `AuditLog::record()`.
- Incluir siempre `id_registro_afectado`.
- En soft deletes, registrar `accion = 'DEACTIVATE'` en lugar de DELETE.

### 6.2 Mejorar Legibilidad del Código
- Controladores: Mantener el patrón de funciones globales pero con nombres consistentes. En BarkiOS cada función tiene un prefijo del módulo (ej: `sale_...`, `purchase_...`). Nosotros usamos `supplies_...`, etc. — es consistente.
- Modelos: Usar comentarios de sección (ej: `// -- Transactions --`, `// -- Queries --`).
- JS: Estandarizar uso de `import` y evitar duplicación de lógica de validación.

### 6.3 Migrar DeletableInterface
La interfaz `DeletableInterface::delete()` actualmente espera un DELETE físico. Evaluar si conviene:
- Cambiar la interfaz para reflejar soft delete.
- O crear `SoftDeletableInterface` separada.
- O eliminar la interfaz y manejarlo directamente en cada modelo.

---

## CRONOGRAMA SUGERIDO

| Orden | Fase | Duración estimada | Dependencias |
|---|---|---|---|
| 1 | Fase 1.1 - Validation Helper | Corta | Ninguna |
| 2 | Fase 1.2 - JS Validation Helper | Corta | Fase 1.1 |
| 3 | Fase 1.3 - Refactor controller_helpers | Corta | Completado ✓ |
| 4 | Fase 1.4 - Transacciones existentes | Media | Completado ✓ |
| 5 | **Fase 2 - Soft Deletes** | **Larga** | **Completado ✓** |
| 6 | Fase 6.3 - DeletableInterface | Corta | Fase 2 |
| 7 | Fase 5 - Exchange Rate | Corta | Ninguna |
| 8 | **Fase 3 - Ventas/POS** | **Muy larga** | Fases 1, 2 |
| 9 | **Fase 4 - Compras** | **Larga** | Fases 1, 2, 7 |
| 10 | Fase 6 - Mejoras transversales | Media | Fases 1-4 |

---

## NOTAS

- Los módulos de BarkiOS (Sales, Purchases, Clients) usan cédula/RIF como PK natural. Nosotros usamos `id auto_increment` como PK — es más flexible y recomendado. Mantener este enfoque.
- BarkiOS no tiene sistema de permisos granular. Nosotros sí (vía `SysInescolara-Seguridad`). Este es un punto fuerte de nuestro proyecto.
- BarkiOS usa Docker. Nosotros XAMPP. No relevante para el código.
- BarkiOS usa `activo` como nombre de columna para soft delete. Usaremos el mismo nombre por consistencia con el estándar del mercado.
