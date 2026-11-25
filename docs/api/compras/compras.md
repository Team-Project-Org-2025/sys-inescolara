# Módulo Compras

Tablas: `compra`, `compra_detalle`, `cuentas_pagar`, `lote`, `insumo`,
`herramienta`, `plantas` · Modelo: `app/models/Purchase.php` · Controlador:
`app/controllers/ComprasController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/compras`) | Solo sesión |
| Listar / detalles | Solo sesión |
| Crear / editar / recibir / cancelar | `compras:crear` / `compras:editar` |
| Eliminar | `compras:eliminar` |
| Rápidos (`agregar_*_rapido`) | Solo sesión |
| `actualizar_costo_lote` | Solo sesión |

> **Ruta por segmento vs `?action=`.** Las funciones con chequeo de permiso solo
> se invocan si la URL es `/compras/<accion>` (por ejemplo
> `POST /compras/agregar_ajax`). El despachador `?action=` llama directamente a
> los manejadores internos, que **no** validan permiso.

Endpoints (`?action=`):

1. `GET obtener_compras`
2. `GET obtener_detalles&id_compra=<id>`
3. `POST agregar_ajax`
4. `POST editar_ajax`
5. `POST eliminar_ajax`
6. `POST recibir_ajax`
7. `POST cancelar_ajax`
8. `POST agregar_planta_rapido`
9. `POST agregar_insumo_rapido`
10. `POST agregar_herramienta_rapido`
11. `POST actualizar_costo_lote`

### `GET /compras?action=obtener_compras`

```json
{
  "success": true,
  "compras": [
    {
      "id": 1,
      "id_compra": 1,
      "id_proveedor": 1,
      "fecha_compra": "2026-11-23",
      "tipo_comprobante": "Factura",
      "numero_comprobante": "TEST-001",
      "subtotal": "15.00",
      "iva": "2.40",
      "total": "17.40",
      "estado": "recibida",
      "observacion": "Compra Postman Editada",
      "proveedor_nombre": "Proveedor Prueba",
      "rif_proveedor": "J-00000000-0",
      "items_count": 1,
      "pagos_count": 0
    }
  ],
  "count": 1
}
```

### `GET /compras?action=obtener_detalles&id_compra=<id>`

```json
{
  "success": true,
  "details": [
    {
      "id_detalle": 1,
      "tipo_item": "planta",
      "id_planta": 1,
      "cantidad": "2.00",
      "costo_unitario": "5.00",
      "subtotal": "10.0000",
      "categoria_lote": "germinado",
      "id_ubicacion": 1,
      "item_nombre": "Rosa Base"
    }
  ],
  "compra": { "id_compra": 1, "estado": "pendiente", "total": "11.60" }
}
```

`400 ID de compra inválido.` si `id_compra <= 0`.

### `POST /compras?action=agregar_ajax`

**Parámetros** (form-urlencoded): `id_proveedor` (obligatorio), `fecha_compra`
(`YYYY-MM-DD`, no futura), `tipo_comprobante` (por defecto `Factura`),
`numero_comprobante`, `subtotal`, `iva`, `total` (> 0), `observacion`, `items`
(JSON string con al menos un item).

Cada item: `id_insumo` / `id_herramienta` / `id_planta` (exactamente uno),
`cantidad`, `costo_unitario`, `categoria_lote`, `id_ubicacion` (para plantas).

**Respuesta 200**

```json
{ "success": true, "message": "Compra registrada correctamente.", "id": 1 }
```

**Efectos:** inserta `compra` en estado `pendiente`, `compra_detalle` y crea la
`cuentas_pagar` asociada. Transaccional.

**Errores:** `500` proveedor/items/total/fecha inválidos.

### `POST /compras?action=editar_ajax`

Mismos parámetros más `id`. Borra y reinserta detalles; actualiza la cuenta por
pagar (o la crea). **No ajusta stock** ya aplicado si la compra fue recibida.

### `POST /compras?action=recibir_ajax`

```json
{ "success": true, "message": "Compra recibida y stock actualizado." }
```

Aplica stock: para plantas crea un lote (`observacion = Ingresado por compra
#<id>`, `id_origen = 4` “Compra”) e incrementa `plantas.cantidad_total`; para
insumos suma stock y actualiza costo; para herramientas pone estado
`disponible`. Marca `estado = recibida` y `fecha_recepcion = CURDATE()`.

### `POST /compras?action=cancelar_ajax`

Solo compras `pendiente`; marca `estado = cancelada` y desactiva la cuenta por
pagar. No revierte stock (aún no aplicado).

### `POST /compras?action=eliminar_ajax`

Rechaza si la compra tiene pagos activos
(`No se puede eliminar una compra que ya tiene pagos registrados.`). Desactiva
`compra` y la cuenta por pagar. **No revierte el stock** de una compra recibida.

### Rápidos

| Endpoint | Parámetros | Respuesta |
|---|---|---|
| `agregar_insumo_rapido` | `nombre_insumo`, `id_unidad_medida` | `{success, insumo:{id,nombre_insumo}}` |
| `agregar_herramienta_rapido` | `nombre_herramienta` | `{success, herramienta:{id,nombre_herramienta}}` |
| `agregar_planta_rapido` | `nombre_comun` | **roto**: llama a `Planta::add()` (inexistente) → `404` con `Call to undefined method` |

### `POST /compras?action=actualizar_costo_lote`

`id_lote`, `costo_unitario`. Actualiza el costo del lote vía
`LotePrecio::actualizarCostoUnitario`. **Sin permiso** (solo sesión).

## Deuda detectada

- Los endpoints `?action=` de Compras no validan permisos de escritura.
- `agregar_planta_rapido` está roto (404).
- `actualizar_costo_lote` y los rápidos no validan permisos.
- `editar_ajax` y `eliminar_ajax` no revierten el stock ya aplicado.
- `recibir_ajax` no es transaccional entre `aplicarStock` y `marcarRecibida`.
- Errores de negocio como `500`.

## Casos Postman

`pruebas/collections/compras/Compras.postman_collection.json`
Crea planta/insumo/herramienta propios, registra una compra de planta, la edita,
la recibe (verifica lote creado), actualiza costo del lote y limpia todo.
Incluye negativos `401/403/400/500` y el caso del endpoint roto.
