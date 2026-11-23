# Módulo Precios

Calcula el precio final de cada lote a partir del costo unitario y el
porcentaje de ganancia, y expone el detalle de insumos usados.

Tablas: `lote`, `registro_insumo` · Modelo: `app/models/LotePrecio.php` ·
Controlador: `app/controllers/PreciosController.php` · JS:
`public/assets/js/dashboard/precios.js`

| Operación | Permiso |
|---|---|
| Ver vista | `precios:ver` |
| Listar precios | Solo sesión |
| Ver detalle de insumos | Solo sesión |
| Listar lotes/insumos | Solo sesión |
| Actualizar costo | `precios:editar` |
| Actualizar ganancia | `precios:editar` |

Endpoints:

1. `GET /dashboard/precios`
2. `GET /precios`
3. `GET /precios?action=get_prices`
4. `GET /precios?action=get_detalle&id_lote=<id>`
5. `GET /precios?action=get_lotes`
6. `GET /precios?action=get_insumos`
7. `POST /precios?action=actualizar_precio_ajax`
8. `POST /precios?action=actualizar_ganancia_ajax`

> Este módulo acepta cuerpo JSON porque usa `getRequestData()`.

---

### `GET /precios?action=get_prices`

**Descripción:** precio final y valor del inventario por lote.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/precios?action=get_prices"
```

**Respuesta 200**

```json
{
  "success": true,
  "prices": [
    {
      "id": 1,
      "id_lote": 1,
      "id_planta": 1,
      "costo_unitario": "5.00",
      "porcentaje_ganancia": "30.00",
      "cantidad_actual": 7,
      "estado_nombre": "vivo",
      "planta_nombre": "Rosa Base",
      "especie_nombre": "Rosa",
      "precio_final": "6.50",
      "valor_total_inventario": "45.50"
    }
  ],
  "count": 1
}
```

**Fórmulas**

```text
precio_final           = costo_unitario × (1 + porcentaje_ganancia / 100)
valor_total_inventario = precio_final × cantidad_actual
```

Todos los importes llegan como **string decimal** desde MySQL.

---

### `GET /precios?action=get_detalle&id_lote=<id>`

**Descripción:** insumos registrados para el lote y su costo.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |

**Query:** `id_lote` (obligatorio).

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/precios?action=get_detalle&id_lote=1"
```

**Respuesta 200** (lote sin insumos registrados)

```json
{ "success": true, "detalles": [] }
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `400` | `id_lote` ausente o ≤ 0 | `ID de lote inválido` |

---

### `GET /precios?action=get_lotes`

**Descripción:** listado simplificado de lotes con el precio ya calculado en
PHP.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |

**Respuesta 200**

```json
{
  "success": true,
  "lotes": [
    {
      "id_lote": 1,
      "id_planta": 1,
      "planta_nombre": "Rosa Base",
      "categoria": "germinado",
      "cantidad_actual": 7,
      "costo_unitario": 5,
      "porcentaje_ganancia": 30,
      "precio_final": 6.5
    }
  ]
}
```

**Notas:** aquí los importes sí son números (se castean con `(float)`), a
diferencia de `get_prices`. No incluye `count`.

---

### `GET /precios?action=get_insumos`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |

**Respuesta 200**

```json
{
  "success": true,
  "insumos": [
    {
      "id_insumo": 3,
      "nombre_insumo": "Insumo Demo",
      "simbolo": "ud",
      "costo_unitario": 1.25
    }
  ]
}
```

---

### `POST /precios?action=actualizar_precio_ajax`

**Descripción:** actualiza el costo unitario de un lote.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Autenticación | Sesión |
| Permiso | `precios:editar` |
| Content-Type | `application/json` o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_lote` | int | Sí | Lote activo |
| `costo_unitario` | decimal | Sí | > 0 |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"id_lote":1,"costo_unitario":6}' \
  "http://localhost/sys-inescolara/precios?action=actualizar_precio_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Costo unitario actualizado correctamente"
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `400` | `id_lote` ≤ 0 | `ID de lote inválido` |
| `400` | `costo_unitario` ≤ 0 | `El costo unitario debe ser mayor a cero` |
| `403` | Sin `precios:editar` | `No tienes permiso para realizar esta acción.` |
| `200` | Lote inexistente o inactivo | `success:false`, `Error al actualizar el costo unitario` |

**Efectos:** `UPDATE lote SET costo_unitario`. El cambio se refleja en
`get_prices` y `get_lotes`.

---

### `POST /precios?action=actualizar_ganancia_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Permiso | `precios:editar` |
| Content-Type | `application/json` o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_lote` | int | Sí | Lote activo |
| `porcentaje_ganancia` | decimal | Sí | ≥ 0 |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"id_lote":1,"porcentaje_ganancia":35}' \
  "http://localhost/sys-inescolara/precios?action=actualizar_ganancia_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Porcentaje de ganancia actualizado correctamente"
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `400` | `id_lote` ≤ 0 | `ID de lote inválido` |
| `400` | Porcentaje negativo | `El porcentaje de ganancia no puede ser negativo` |
| `403` | Sin permiso | `No tienes permiso…` |
| `200` | Lote inexistente | `success:false`, `Error al actualizar el porcentaje de ganancia` |

---

## Deuda detectada

- `get_prices` devuelve importes como string y `get_lotes` como número.
- No hay historial de cambios de precio; solo el valor vigente.
- `get_detalle` no distingue entre lote inexistente y lote sin insumos: ambos
  devuelven `detalles: []`.
- La vista `/precios` no valida `precios:ver`.

## Casos Postman

`pruebas/collections/inventario/Precios.postman_collection.json`
