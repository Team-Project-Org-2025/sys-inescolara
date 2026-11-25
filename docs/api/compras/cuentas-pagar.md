# Módulo Cuentas por pagar

Tablas: `cuentas_pagar`, `pago_compra`, `compra`, `proveedores` · Modelo:
`app/models/CuentaPagar.php` · Controlador:
`app/controllers/CuentasPagarController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/cuentas-pagar`) | Solo sesión |
| Listar / detalle / pagos | Solo sesión |
| Registrar pago | `cuentas_pagar:editar` |
| Anular pago | `cuentas_pagar:eliminar` |

Endpoints (`?action=`): `obtener_cuentas`, `obtener_detalle`, `obtener_pagos`,
`registrar_pago`, `anular_pago`.

### `GET /cuentas-pagar?action=obtener_cuentas`

```json
{
  "success": true,
  "cuentas": [
    {
      "id": 2,
      "id_cuenta_pagar": 2,
      "id_compra": 2,
      "monto_total": "20.00",
      "saldo_pendiente": "20.00",
      "fecha_vencimiento": null,
      "estado": "pendiente",
      "fecha_compra": "2026-11-23",
      "compra_total": "20.00",
      "proveedor_nombre": "Proveedor Prueba",
      "rif_proveedor": "J-00000000-0",
      "pagos_count": 0
    }
  ],
  "count": 1
}
```

- `saldo_pendiente` y `estado` se calculan a partir de los pagos activos.
- Excluye compras `cancelada`.

### `GET /cuentas-pagar?action=obtener_detalle&id_cuenta=<id>`

```json
{
  "success": true,
  "cuenta": { "id_cuenta_pagar": 2, "monto_total": "20.00", "saldo_pendiente": "15.00", "estado": "parcial", "fecha_compra": "2026-11-23", "nombre_proveedor": "Proveedor Prueba" },
  "pagos": [
    { "id_pago_compra": 1, "monto": "5.00", "tipo_pago": "Efectivo", "fecha_pago": "2026-11-23", "estado": "registrado" }
  ]
}
```

`400 ID de cuenta inválido.` si `id_cuenta <= 0`.

### `GET /cuentas-pagar?action=obtener_pagos&id_cuenta=<id>`

```json
{ "success": true, "pagos": [ { "id_pago_compra": 1, "monto": "5.00", "tipo_pago": "Efectivo", "estado": "registrado" } ] }
```

### `POST /cuentas-pagar?action=registrar_pago`

**Parámetros:** `id` (cuenta), `monto` (> 0), `fecha_pago` (default hoy),
`tipo_pago` (default `Efectivo`), `referencia`, `observacion`.

```json
{ "success": true, "message": "Pago registrado correctamente." }
```

**Validaciones:** fecha no anterior a la compra; monto no mayor al saldo;
cuenta no pagada. Errores como `500`.

**Efecto:** inserta en `pago_compra`; recalcula estado de la cuenta
(computado). Transaccional.

### `POST /cuentas-pagar?action=anular_pago`

**Parámetro:** `id_pago`.

```json
{ "success": true, "message": "Pago anulado correctamente." }
```

Efecto: `pago_compra.estado = 'anulado'`, `activo = 0`.

## Deuda detectada

- `anular_pago` no restaura `compra.estado` si ya estaba `pagada`.
- La columna `cuentas_pagar.saldo_pendiente` existe pero no se usa: el saldo se
  calcula siempre a partir de los pagos.
- No hay endpoint para editar o eliminar una cuenta por pagar directamente.
- Errores de negocio como `500`.

## Casos Postman

`pruebas/collections/compras/CuentasPagar.postman_collection.json`
Crea una compra a pagar, lista la cuenta, registra un abono (saldo 15), salda
(saldo 0, estado `pagada`), verifica el rechazo de pago extra, anula ambos pagos
y elimina la compra. Incluye `401/403/400/500`.
