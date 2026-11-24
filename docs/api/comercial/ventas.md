# Módulo Ventas

Registro de ventas de plantas (lotes) e insumos, con detalle, pagos, IVA y
comprobante. Descuenta stock al guardar y lo devuelve al cancelar.

Tablas: `venta`, `detalle_venta`, `pago_venta`, `lote`, `insumo` · Modelo:
`app/models/Venta.php` · Controlador: `app/controllers/VentasController.php` ·
JS: `public/assets/js/dashboard/ventas.js`

| Operación | Permiso |
|---|---|
| Ver vista (`/dashboard/ventas`) | `ventas:ver` |
| Ver vista (`/ventas`) | Solo sesión |
| Listar / detalles / catálogos | Solo sesión |
| Crear venta | `ventas:crear` |
| Cancelar venta | `ventas:eliminar` |
| Actualizar precio del lote | **Ninguno** (solo sesión) |
| Comprobante PDF | `ventas:ver` |

> **Parámetro:** este módulo usa `?accion=`, no `?action=`.

Endpoints:

1. `GET /dashboard/ventas`
2. `GET /ventas`
3. `GET /ventas?accion=listar`
4. `POST /ventas?accion=guardar`
5. `GET /ventas?accion=detalles&id=<id>`
6. `POST /ventas?accion=cancelar`
7. `GET /ventas?accion=buscar_lotes&q=<texto>`
8. `GET /ventas?accion=precio_lote&id_lote=<id>`
9. `GET /ventas?accion=buscar_clientes&q=<texto>`
10. `GET /ventas?accion=trabajadores`
11. `POST /ventas?accion=actualizar_precio_lote`
12. `GET /ventas?accion=comprobante&id=<id>` (PDF)

---

### `GET /ventas?accion=listar`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/ventas?accion=listar"
```

**Respuesta 200**

```json
{
  "success": true,
  "ventas": [
    {
      "id": 12,
      "id_venta": 12,
      "referencia": "VEN-20261123-011",
      "id_cliente": 1,
      "id_usuario": 1,
      "tipo_venta": "contado",
      "estado": "completada",
      "iva_porcentaje": "16.00",
      "fecha_venta": "2026-11-23 09:00:00",
      "observaciones": null,
      "activo": 1,
      "nombre_cliente": "Cliente Prueba",
      "tipo_cedula_cliente": "V",
      "cedula_cliente": "12345678",
      "apellido_cliente": "Prueba",
      "nombre_trabajador": null,
      "apellido_trabajador": null,
      "nombre_usuario": "admin",
      "monto_subtotal": "10.00",
      "monto_sin_iva": 8.62,
      "total_pagado": "10.00",
      "monto_iva": 1.38,
      "monto_total": 10
    }
  ],
  "total": 1
}
```

**Notas**

- Solo lista ventas con `activo = 1`; las canceladas desaparecen.
- El arreglo se llama `ventas`; el contador, `total`.
- `monto_sin_iva`, `monto_iva` y `monto_total` los recalcula el controlador a
  partir de `monto_subtotal`; `monto_total` termina siendo igual al subtotal.
- No pagina ni filtra.

---

### `POST /ventas?accion=guardar`

| Campo | Valor |
|---|---|
| Permiso | `ventas:crear` |
| Content-Type | `application/x-www-form-urlencoded` (o JSON vía `getRequestData`) |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_cliente` | int | Sí | Cliente activo |
| `id_usuario` | int | Sí | Vendedor |
| `tipo_venta` | string | Sí | `contado` o `credito` |
| `fecha_venta` | datetime | No | Si falta, usa la fecha actual |
| `observaciones` | string | No | Vacío → `NULL` |
| `productos` | JSON string | Sí | Al menos uno |
| `pagos` | JSON string | No | Arreglo de pagos |

Cada producto:

| Campo | Descripción |
|---|---|
| `tipo_item` | `planta` (por defecto) o `insumo` |
| `id_lote` | Obligatorio si es planta |
| `id_insumo` | Obligatorio si es insumo |
| `cantidad` | Entero > 0 |
| `precio_unitario` | Número ≥ 0 |

Cada pago: `metodo` (`efectivo`, `transferencia`, `pago_movil`, `punto`),
`monto` > 0 y `referencia` para los métodos que no son efectivo/punto
(máximo 6 dígitos; se limpian los no numéricos).

**Request (contado)**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  --data-urlencode "id_cliente=1" \
  --data-urlencode "id_usuario=1" \
  --data-urlencode "tipo_venta=contado" \
  --data-urlencode "fecha_venta=2026-11-23 10:00:00" \
  --data-urlencode 'productos=[{"tipo_item":"planta","id_lote":21,"cantidad":2,"precio_unitario":10}]' \
  --data-urlencode 'pagos=[{"metodo":"efectivo","monto":20}]' \
  "http://localhost/sys-inescolara/ventas?accion=guardar"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Venta registrada correctamente",
  "id": 4,
  "referencia": "VEN-20261123-003"
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `ventas:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | Sin cliente | `El cliente es requerido.` |
| `500` | Sin vendedor | `El vendedor es requerido.` |
| `500` | Tipo inválido | `El tipo de venta debe ser contado o crédito.` |
| `500` | Sin productos | `Debe agregar al menos un producto.` |
| `500` | Producto inválido | `Cada planta debe tener un lote seleccionado.` / `Cada insumo debe tener un ID seleccionado.` |
| `500` | Cantidad/precio inválidos | `La cantidad debe ser mayor a cero en todos los productos.` / `El precio unitario es requerido en todos los productos.` |
| `500` | Pago inválido | `Método de pago inválido.` / `La referencia es obligatoria para …` |
| `500` | Stock insuficiente | `Stock insuficiente de insumo…` (para lotes no se valida antes) |
| `500` | Pago no coincide | `El total de pagos (N) no coincide con el total de la venta (M).` |

**Efectos**

- Inserta `venta` con referencia `VEN-YYYYMMDD-NNN`.
- `contado` → `estado = completada`; `credito` → `estado = pendiente` y
  `fecha_vencimiento = fecha_venta + 30 días`.
- Inserta `detalle_venta` y descuenta `lote.cantidad_actual` /
  `insumo.stock_actual`.
- Inserta `pago_venta`.
- Todo transaccional. Si el total de pagos de una venta de contado no coincide,
  revierte la venta completa.

---

### `GET /ventas?accion=detalles&id=<id>`

**Respuesta 200 (resumen)**

```json
{
  "success": true,
  "venta": {
    "id_venta": 4,
    "referencia": "VEN-20261123-003",
    "tipo_venta": "contado",
    "estado": "completada",
    "nombre_cliente": "Cliente Prueba",
    "nombre_usuario": "admin",
    "monto_subtotal": "20.00",
    "total_pagado": "20.00",
    "detalles": [
      {
        "id_detalle_venta": 3,
        "tipo_item": "planta",
        "id_lote": 21,
        "cantidad": 2,
        "precio_unitario": "10.00",
        "sub_total": "20.00",
        "cantidad_actual": "4.00",
        "planta_nombre": "Rosa Base",
        "especie_nombre": "Rosa"
      }
    ],
    "pagos": [
      { "id_pago": 3, "metodo": "efectivo", "monto": "20.00", "estado_pago": "registrado" }
    ],
    "monto_sin_iva": 17.24137931034483,
    "monto_iva": 2.7586206896551726,
    "monto_total": 20
  }
}
```

**Notas:** `monto_sin_iva` y `monto_iva` no se redondean en el detalle. Los
importes de detalle y pago son strings.

**Errores:** `500 ID inválido`, `500 Venta no encontrada.`

---

### `POST /ventas?accion=cancelar`

| Campo | Valor |
|---|---|
| Permiso | `ventas:eliminar` |
| Parámetros | `id` |

**Respuesta 200**

```json
{ "success": true, "message": "Venta cancelada correctamente", "id": 4 }
```

**Errores:** `403`, `500 ID inválido`, `500 No existe la venta solicitada.`,
`500 La venta ya se encuentra cancelada.`

**Efectos:**

- Devuelve cantidades a `lote.cantidad_actual` e `insumo.stock_actual`.
- `venta.activo = 0`, `estado = 'cancelada'`.
- Auditoría `DEACTIVATE`.
- No elimina ni reembolsa las filas de `pago_venta`.

---

### `GET /ventas?accion=buscar_lotes&q=<texto>`

Devuelve plantas e insumos con stock. Ejemplo real:

```json
{
  "success": true,
  "lotes": [
    {
      "id_lote": 1,
      "tipo_item": "planta",
      "cantidad_actual": 9,
      "planta_nombre": "Rosa Base",
      "nombre": "Rosa Base",
      "especie_nombre": "Rosa",
      "detalle": "Rosa",
      "costo_unitario": "5.00",
      "porcentaje_ganancia": "30.00",
      "precio_unitario": "6.50",
      "unidad_simbolo": null,
      "id_insumo": null
    }
  ]
}
```

Con `q` vacío devuelve todo; con texto, hasta 20 coincidencias.

---

### `GET /ventas?accion=precio_lote&id_lote=<id>`

```json
{
  "success": true,
  "precio": 6.5,
  "costo_unitario": 5,
  "porcentaje_ganancia": 30,
  "total_insumos": 0
}
```

Si el lote no existe o no tiene stock devuelve
`{"success":false,"message":"Lote no encontrado"}` con **200**.

---

### `GET /ventas?accion=buscar_clientes&q=<texto>`

Devuelve hasta 10 clientes activos. Si `q` tiene menos de 2 caracteres,
`clientes` viene vacío.

### `GET /ventas?accion=trabajadores`

Devuelve usuarios activos **con `nombre_trabajador` no nulo**. En la base local
actual la respuesta es `{"success":true,"trabajadores":[]}`.

### `POST /ventas?accion=actualizar_precio_lote`

**Parámetros:** `id_lote`, `costo_unitario` (> 0 para actualizar),
`porcentaje_ganancia`.

```json
{ "success": true, "message": "Precio del lote actualizado" }
```

**No valida permiso**: cualquier sesión autenticada puede cambiar el costo y el
margen de un lote.

### `GET /ventas?accion=comprobante&id=<id>`

Genera un PDF con `PdfHelper` (`application/pdf`,
`Content-Disposition: attachment`). `400 ID inválido`, `404 Venta no encontrada`,
`500 Error al generar el comprobante PDF.`

---

## Deuda detectada

- `actualizar_precio_lote` no valida permiso.
- `trabajadores` filtra por `nombre_trabajador IS NOT NULL`, por lo que los
  usuarios sin datos de trabajador no aparecen aunque estén activos.
- El listado no pagina ni filtra y recalcula importes que ya vienen de la BD.
- `getById` usa el nombre de columna de seguridad de forma fija; si se renombra
  la base de seguridad, falla.
- La cancelación no borra pagos.
- No existe edición de venta: solo crear y cancelar.
- Las validaciones de negocio devuelven `500`.

## Casos Postman

`pruebas/collections/comercial/Ventas.postman_collection.json`

Crea un lote de 6 ejemplares, registra una venta de contado con pago exacto,
verifica detalle y stock, cancela y elimina el lote. Incluye `401/403/400/500`.
