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

- `checkModuleAuth()` ✓ (ya existe)
- `checkPermisoOrFail()` ✓ (ya existe)
- `jsonResponse()` ✓ (ya existe)
- `handleError()` ✓ (ya existe)
- `isAjaxRequest()` ✓ (ya existe)
- **Agregar:** Función `getRequestData()` que unifique la lectura de `$_POST`/`$_GET` según el Content-Type (JSON vs form data). BarkiOS no lo tiene, pero sería una mejora.
- **Agregar:** Función `validateAndSanitize()` que aplique `Validation::sanitize()` y `Validation::validate()` en un solo paso.

### 1.4 Estandarizar Transacciones en Módulos Existentes
**Archivos a modificar:** Modelos que realizan operaciones multi-paso.

Asegurar que toda operación que modifique múltiples tablas use:
```php
$this->db->beginTransaction();
try {
    // operaciones
    $this->db->commit();
} catch (\Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

Módulos a revisar:
- Inventory (✓ ya implementado)
- Sales (cuando se construya)
- Purchases (cuando se construya)
- Tasks (asignación + consumo de insumos)
- Tools (uso de herramientas + desgaste)

---

## FASE 2: BORRADO LÓGICO (SOFT DELETES)

### 2.1 Migración BD - Agregar columna `activo`
**Tablas a modificar:** Agregar columna `activo` TINYINT(1) NOT NULL DEFAULT 1 en todas las tablas que actualmente permiten DELETE físico.

| Tabla | PK | Estado Actual |
|---|---|---|
| plantas | id_planta | Hard delete |
| especie | id_especie | Hard delete |
| insumo | id_insumo | Hard delete |
| herramienta | id_herramienta | Hard delete |
| lote | id_lote | Hard delete |
| proveedores | id_proveedor | Hard delete |
| trabajadores | id_trabajador | Hard delete |
| cliente | id_cliente | Hard delete |
| ubicacion | id_ubicacion | Hard delete |
| unidad_medida | id_unidad_medida | Hard delete |

**Tablas que NO necesitan soft delete (tablas transaccionales/históricas):**
- movimiento_planta / movimiento_planta_detalle
- movimiento_insumo / movimiento_insumo_detalle
- calculo_precio
- planta_precio_vigente
- trazabilidad
- ajuste_inventario
- asistencia

### 2.2 Refactorizar Modelos - Soft Delete
Para cada modelo con `DeletableInterface`:
- `delete()`: Cambiar `DELETE FROM` por `UPDATE SET activo = 0`.
- `getAll()`: Agregar `WHERE activo = 1` a todas las consultas de listado.
- `exists()`: Considerar si debe validar existencia de registros activos.
- Agregar `restore($id)` para restaurar registros eliminados.
- Agregar `getDeleted()` para listar registros eliminados (opcional).

### 2.3 Refactorizar Controladores - Soft Delete
- Los métodos `delete_ajax` deben reflejar que ahora es un soft delete.
- Mensajes de confirmación: Cambiar "eliminado" por "desactivado" (o similar).
- La auditoría (`AuditLog::record`) debe registrar que se trata de un soft delete.

### 2.4 Manejo de Relaciones FK
- Validar que al desactivar un registro padre (ej: especie), los hijos (ej: plantas) puedan seguir existiendo.
- La UI debe indicar cuando un registro está desactivado (color gris, badge "Inactivo").

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
| 3 | Fase 1.3 - Refactor controller_helpers | Corta | Fase 1.1 |
| 4 | Fase 1.4 - Transacciones existentes | Media | Fase 1.3 |
| 5 | **Fase 2 - Soft Deletes** | **Larga** | Fase 1.4 |
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
