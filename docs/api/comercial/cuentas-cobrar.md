# Módulo Cuentas por cobrar

Seguimiento de ventas a crédito y registro de abonos. Se apoya en `venta`,
`detalle_venta` y `pago_venta`.

Tablas: `venta`, `detalle_venta`, `pago_venta`, `cliente` · Modelo:
`app/models/CuentaCobrar.php` · Controlador:
`app/controllers/Cuentas_cobrarController.php` · JS:
`public/assets/js/dashboard/cuentas-cobrar.js`

| Operación | Permiso |
|---|---|
| Ver vista (`/dashboard/cuentas_cobrar`) | `cuentas_cobrar:ver` |
| Listar / estadísticas / detalle / pagos / clientes | Solo sesión |
| Registrar pago | `cuentas_cobrar:editar` |

> **Ruta:** la URL correcta es `/cuentas_cobrar` (guion bajo). El controlador se
> llama `Cuentas_cobrarController`; el router convierte `cuentas-cobrar` en
> `CuentasCobrarController` y falla con
> `El controlador 'CuentasCobrar' no existe.`

Endpoints:

1. `GET /dashboard/cuentas_cobrar`
2. `GET /cuentas_cobrar?action=obtener_lista`
3. `GET /cuentas_cobrar?action=obtener_estadisticas`
4. `GET /cuentas_cobrar?action=obtener_detalle&id=<id_venta>`
5. `GET /cuentas_cobrar?action=obtener_pagos&id=<id_venta>`
6. `GET /cuentas_cobrar?action=obtener_clientes`
7. `POST /cuentas_cobrar?action=registrar_pago`

---

### `GET /cuentas_cobrar?action=obtener_lista`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Query:** `draw`, `start`, `length`, `search[value]`, `estado`
(`pagado`, `vencido`, `vigente`).

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/cuentas_cobrar?action=obtener_lista&draw=1&start=0&length=10"
```

**Respuesta 200**

```json
{
  "draw": 1,
  "recordsTotal": 1,
  "recordsFiltered": 1,
  "data": [
    {
      "id_venta": 5,
      "referencia": "VEN-20261123-004",
      "fecha_venta": "2026-11-23 10:30:00",
      "fecha_vencimiento": "2026-12-23",
      "observaciones": null,
      "id_cliente": 1,
      "nombre_cliente": "Cliente Prueba",
      "tipo_cedula_cliente": "V",
      "cedula_cliente": "12345678",
      "contacto": "0414-0000000",
      "monto_total": "20.00",
      "total_pagado": "0.00",
      "saldo_pendiente": "20.00",
      "estado_cuenta": "vigente"
    }
  ]
}
```

**Notas**

- Formato DataTables server-side (`draw`, `recordsTotal`, `recordsFiltered`,
  `data`).
- Solo ventas `tipo_venta = 'credito'` y `activo = 1`.
- `estado_cuenta` se calcula: `pagado`, `vencido` o `vigente`.
- Importes como string decimal.

---

### `GET /cuentas_cobrar?action=obtener_estadisticas`

**Respuesta 200**

```json
{
  "success": true,
  "data": {
    "total_cuentas": 1,
    "total_por_cobrar": "20.00",
    "total_pagadas": 0,
    "total_vencidas": 0,
    "total_vigentes": 1,
    "monto_vigente": "20.00",
    "monto_vencido": "0.00",
    "cobrado_mes": 35
  }
}
```

---

### `GET /cuentas_cobrar?action=obtener_detalle&id=<id_venta>`

**Respuesta 200**

```json
{
  "success": true,
  "data": {
    "id_venta": 5,
    "referencia": "VEN-20261123-004",
    "tipo_venta": "credito",
    "estado": "pendiente",
    "fecha_venta": "2026-11-23 10:30:00",
    "fecha_vencimiento": "2026-12-23",
    "nombre_cliente": "Cliente Prueba",
    "trabajador": "admin",
    "monto_total": "20.00",
    "total_pagado": "0.00",
    "saldo_pendiente": "20.00",
    "detalles": [
      {
        "id_detalle_venta": 4,
        "tipo_item": "planta",
        "id_lote": 22,
        "cantidad": 2,
        "precio_unitario": "10.00",
        "producto": "Rosa Base (Lote #22)"
      }
    ],
    "pagos": []
  }
}
```

**Errores:** `400 ID de venta invalido`, `404 Venta no encontrada`.

---

### `GET /cuentas_cobrar?action=obtener_pagos&id=<id_venta>`

```json
{
  "success": true,
  "data": [
    {
      "id_pago": 4,
      "id_venta": 5,
      "metodo": "efectivo",
      "monto": "5.00",
      "referencia": null,
      "fecha_pago": "2026-11-23 00:00:00",
      "estado_pago": "registrado",
      "banco": null,
      "id_usuario": 1,
      "observaciones": "Abono Postman",
      "cobrador": "admin"
    }
  ]
}
```

**Errores:** `400 ID de venta invalido`.

---

### `GET /cuentas_cobrar?action=obtener_clientes`

```json
{
  "success": true,
  "data": [
    { "id_cliente": 1, "nombre_cliente": "Cliente Prueba", "contacto": "0414-0000000" }
  ]
}
```

---

### `POST /cuentas_cobrar?action=registrar_pago`

| Campo | Valor |
|---|---|
| Permiso | `cuentas_cobrar:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | Venta a crédito |
| `monto` | decimal | Sí | > 0 |
| `metodo` | string | Sí | `efectivo`, `transferencia`, `punto`, `pago_movil`, `otro` |
| `fecha_pago` | date | Sí | `YYYY-MM-DD`, no futura ni anterior a la venta |
| `id_usuario` | int | Sí | Usuario que cobra |
| `referencia` | string | Condicional | 6 dígitos para transferencia/pago móvil |
| `banco` | string | Condicional | Obligatorio para transferencia/pago móvil |
| `observaciones` | string | No | Vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=5" \
  -d "monto=5" \
  -d "metodo=efectivo" \
  -d "fecha_pago=2026-11-23" \
  -d "id_usuario=1" \
  -d "observaciones=Abono Postman" \
  "http://localhost/sys-inescolara/cuentas_cobrar?action=registrar_pago"
```

**Respuesta 200**

```json
{ "success": true, "message": "Pago registrado correctamente", "id": 0 }
```

> **Bug:** `id` siempre vuelve en `0`. El modelo inserta correctamente (el pago
> aparece en `obtener_pagos`), pero el controlador devuelve la última inserción
> después de ejecutar el `UPDATE` de estado de la venta.

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `cuentas_cobrar:editar` | `No tienes permiso para realizar esta acción.` |
| `500` | `id` ≤ 0 | `ID de venta invalido.` |
| `500` | `monto` ≤ 0 | `El monto debe ser mayor a cero.` |
| `500` | Sin usuario | `Debe seleccionar el usuario que cobro.` |
| `500` | Método inválido | `Metodo de pago invalido.` |
| `500` | Sin fecha / formato | `La fecha de pago es requerida.` / `Formato de fecha de pago inválido.` |
| `500` | Fecha futura | `La fecha de pago no puede ser posterior al día de hoy.` |
| `500` | Fecha anterior a la venta | `La fecha de pago no puede ser anterior a la fecha de venta (YYYY-MM-DD).` |
| `500` | Venta inexistente | `Venta no encontrada.` |
| `500` | Transferencia sin datos | `La referencia es requerida…` / `La referencia debe tener exactamente 6 digitos numericos.` / `El banco es requerido…` |

**Efectos**

- Inserta en `pago_venta`.
- Recalcula `venta.estado`: `completada` si el total pagado cubre el total,
  `pendiente` si no.
- Auditoría `CREATE` sobre `pago_venta`.
- Transaccional.

---

## Deuda detectada

- `/cuentas-cobrar` (guion medio) está roto: el controlador usa guion bajo.
- `registrar_pago` devuelve `id = 0`.
- No hay endpoint para anular o corregir un pago.
- Al cancelar la venta, los pagos siguen contando en reportes de caja.
- La lista no valida `cuentas_cobrar:ver`; solo la vista del dashboard.
- Los errores de negocio devuelven `500`.
- `fecha_pago` se guarda como `YYYY-MM-DD 00:00:00`.

## Casos Postman

`pruebas/collections/comercial/CuentasCobrar.postman_collection.json`

Crea un lote y una venta a crédito, lista la cuenta, consulta estadísticas,
detalle, pagos y clientes, registra un abono de 5, verifica el saldo de 15 y
cancela la venta al final. Incluye `401/403/400/500`.
