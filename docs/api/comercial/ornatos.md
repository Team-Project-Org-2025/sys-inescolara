# Módulo Ornatos

Arreglos ornamentales vendidos o donados. Cada ornato tiene un cliente, una
fecha, un monto y un detalle de lotes. **No mueve stock**: el detalle es
descriptivo.

Tablas: `ornatos`, `detalle_ornatos`, `cliente`, `lote` · Modelo:
`app/models/Ornato.php` · Controlador: `app/controllers/OrnatosController.php` ·
JS: `public/assets/js/dashboard/ornatos.js`

| Operación | Permiso |
|---|---|
| Ver vista (`/dashboard/ornatos`) | `ornatos:ver` |
| Listar / detalles / buscar clientes | Solo sesión |
| Crear | `ornatos:crear` |
| Editar | `ornatos:editar` |
| Eliminar (lógico) | `ornatos:eliminar` |

> **Parámetro:** este módulo usa `?accion=`, no `?action=`. La URL `/ornatos`
> sin `accion` responde `400` JSON (`Solicitud inválida`), no sirve la vista.

Endpoints:

1. `GET /dashboard/ornatos`
2. `GET /ornatos?accion=listar`
3. `POST /ornatos?accion=guardar`
4. `POST /ornatos?accion=actualizar`
5. `GET /ornatos?accion=detalles&id=<id>`
6. `POST /ornatos?accion=eliminar`
7. `GET /ornatos?accion=buscar_clientes&q=<texto>`

---

### `GET /dashboard/ornatos`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | `ornatos:ver` |
| Content-Type | `text/html` |

Carga clientes y lotes para el formulario. `302` sin sesión, `403` sin permiso.

---

### `GET /ornatos?accion=listar`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/ornatos?accion=listar"
```

**Respuesta 200**

```json
{ "success": true, "ornatos": [], "total": 0 }
```

Con datos, cada fila incluye:

```json
{
  "id": 2,
  "id_ornato": 2,
  "id_cliente": 1,
  "tipo_ornato": "Venta",
  "descripcion": "Arreglo Postman",
  "ubicacion": "Vivero Central",
  "monto_total": "15.00",
  "fecha": "2026-11-23",
  "activo": 1,
  "nombre_cliente": "Cliente Prueba",
  "tipo_cedula_cliente": "V",
  "cedula_cliente": "12345678"
}
```

**Notas:** solo `activo = 1`; ordena por fecha descendente. El arreglo se llama
`ornatos` y el contador `total`.

---

### `POST /ornatos?accion=guardar`

| Campo | Valor |
|---|---|
| Permiso | `ornatos:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_cliente` | int | Sí | Cliente existente |
| `tipo_ornato` | string | Sí | `Venta` o `Donacion` (sin tilde en el valor) |
| `descripcion` | string | No | Vacío → `NULL` |
| `ubicacion` | string | No | Vacío → `NULL` |
| `fecha` | date | Sí | `YYYY-MM-DD` |
| `monto_total` | decimal | No | ≥ 0; por defecto 0 |
| `items` | JSON string | No | Detalle de lotes |

Cada item: `id_lote` > 0, `cantidad` > 0, `precio_unitario`, `sub_total`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  --data-urlencode "id_cliente=1" \
  --data-urlencode "tipo_ornato=Venta" \
  --data-urlencode "descripcion=Arreglo Postman" \
  --data-urlencode "ubicacion=Vivero Central" \
  --data-urlencode "fecha=2026-11-23" \
  --data-urlencode "monto_total=15.00" \
  --data-urlencode 'items=[{"id_lote":1,"cantidad":1,"precio_unitario":15,"sub_total":15}]' \
  "http://localhost/sys-inescolara/ornatos?accion=guardar"
```

**Respuesta 200**

```json
{ "success": true, "message": "Ornato registrado correctamente", "id": 2 }
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `ornatos:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | Sin cliente | `El cliente es requerido.` |
| `500` | Tipo inválido | `El tipo de ornato debe ser Venta o Donación.` |
| `500` | Fecha inválida | `La fecha es requerida y debe tener formato válido.` |
| `500` | Monto negativo | `El monto total no puede ser negativo.` |
| `500` | Item sin lote | `Cada detalle debe tener un lote seleccionado.` |
| `500` | Cantidad ≤ 0 | `La cantidad debe ser mayor a cero en todos los detalles.` |

**Efectos:** inserta en `ornatos` y, si hay items, en `detalle_ornatos`.
Auditoría `CREATE` sobre `ornatos`. **No descuenta stock del lote.**

---

### `GET /ornatos?accion=detalles&id=<id>`

**Respuesta 200**

```json
{
  "success": true,
  "detalles": [
    {
      "id_detalle_ornato": 3,
      "id_ornato": 2,
      "id_lote": 1,
      "cantidad": 1,
      "precio_unitario": "15.00",
      "sub_total": "15.00",
      "cantidad_actual": 9,
      "planta_nombre": "Rosa Base",
      "especie_nombre": "Rosa"
    }
  ]
}
```

**Errores:** `500 ID inválido`. Si el ornato no tiene detalles devuelve `[]`.

---

### `POST /ornatos?accion=actualizar`

Mismos parámetros que `guardar`, más `id`.

**Respuesta 200**

```json
{ "success": true, "message": "Ornato actualizado correctamente", "id": 2 }
```

**Efectos:** actualiza la cabecera y **reemplaza** los detalles (borra e
inserta). Auditoría `UPDATE`.

**Errores:** `403`, `500 ID inválido`, `500 Error al actualizar…`, validaciones
de `guardar`.

---

### `POST /ornatos?accion=eliminar`

| Campo | Valor |
|---|---|
| Permiso | `ornatos:eliminar` |
| Parámetros | `id` |

**Respuesta 200**

```json
{ "success": true, "message": "Ornato eliminado correctamente", "id": 2 }
```

**Errores:** `403`, `500 ID inválido`,
`500 No existe el ornato solicitado.`

**Efectos:** `ornatos.activo = 0`; los detalles **no** se desactivan. Auditoría
`DEACTIVATE`.

---

### `GET /ornatos?accion=buscar_clientes&q=<texto>`

```json
{
  "success": true,
  "clientes": [
    {
      "id_cliente": 1,
      "nombre_cliente": "Cliente Prueba",
      "tipo_cedula_cliente": "V",
      "cedula_cliente": "12345678",
      "apellido_cliente": "Prueba",
      "contacto_cliente": "0414-0000000"
    }
  ]
}
```

Con menos de 2 caracteres devuelve `clientes: []`.

---

## Deuda detectada

- Guardar y actualizar no validan stock ni precios de lote; el detalle puede
  referenciar un lote sin stock.
- Eliminar no limpia `detalle_ornatos`.
- `POST` de actualización usa `DELETE` físico de detalles, sin auditoría de los
  mismos.
- La validación de `tipo_ornato` confunde el valor `Donacion` con el texto
  mostrado `Donación`.
- No existe endpoint de restauración.
- `/ornatos` sin `accion` devuelve JSON 400 en vez de la vista.

## Casos Postman

`pruebas/collections/comercial/Ornatos.postman_collection.json`

Cubre listado, búsqueda de clientes, creación con detalle, consulta de detalle,
actualización a donación, eliminación y verificación. No altera stock.
