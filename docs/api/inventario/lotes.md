# Módulo Lotes

Cada lote agrupa una cantidad de plantas de una especie, sembradas en una
ubicación y fecha, con su costo y porcentaje de ganancia. Es la unidad que
consumen Precios, Trazabilidad, Mermas y Ventas.

Tabla: `lote` · Modelo: `app/models/Lote.php` · Controlador:
`app/controllers/LotesController.php` · JS:
`public/assets/js/dashboard/lotes.js`

| Operación | Permiso |
|---|---|
| Ver vista | `lotes:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `lotes:crear` |
| Editar | `lotes:editar` |
| Eliminar (lógico) | `lotes:eliminar` |

Endpoints:

1. `GET /dashboard/lotes`
2. `GET /lotes`
3. `GET /lotes?action=get_batches`
4. `POST /lotes?action=add_ajax`
5. `POST /lotes?action=edit_ajax`
6. `POST /lotes?action=delete_ajax`

---

### `GET /dashboard/lotes`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | `lotes:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /lotes`

Vista servida por el controlador. Carga plantas, ubicaciones y los catálogos
`estado`, `categoria` y `origen`. Solo exige sesión.

---

### `GET /lotes?action=get_batches`

**Descripción:** lista los lotes activos con datos resueltos de planta, especie,
ubicación y catálogos.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/lotes?action=get_batches"
```

**Respuesta 200**

```json
{
  "success": true,
  "lotes": [
    {
      "id": 1,
      "id_planta": 1,
      "id_ubicacion": 1,
      "fecha_siembra": "2026-01-15",
      "cantidad_inicial": 10,
      "cantidad_actual": 7,
      "costo_unitario": "5.00",
      "id_estado": 5,
      "id_categoria": 1,
      "id_origen": 1,
      "observacion": null,
      "imagen": null,
      "activo": 1,
      "porcentaje_ganancia": "30.00",
      "planta_nombre": "Rosa Base",
      "especie_nombre": "Rosa",
      "ubicacion_nombre": "Vivero Central",
      "estado_nombre": "vivo",
      "categoria_nombre": "germinado",
      "origen_nombre": "Siembra"
    }
  ],
  "count": 1
}
```

**Notas**

- El arreglo se llama `lotes`.
- `costo_unitario` y `porcentaje_ganancia` llegan como string decimal.
- `cantidad_actual` es la que descuentan Trazabilidad y Ventas.

---

### `POST /lotes?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/lotes?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `lotes:crear` |
| Content-Type | `multipart/form-data` (con imagen) o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_planta` | int | Sí | FK a `plantas` |
| `id_ubicacion` | int | Sí | FK a `ubicacion` |
| `fecha_siembra` | date | Sí | `YYYY-MM-DD` |
| `cantidad_inicial` | int | Sí | > 0 |
| `cantidad_actual` | int | Sí | ≥ 0 |
| `id_estado` | int | No | Por defecto el estado `vivo` |
| `id_categoria` | int | No | Catálogo `categoria` |
| `id_origen` | int | No | Por defecto `Siembra` |
| `costo_unitario` | decimal | No | Por defecto 0 |
| `porcentaje_ganancia` | decimal | No | Por defecto 30 |
| `observacion` | string | No | Vacío → `NULL` |
| `imagen` | file | No | Se guarda en `assets/uploads/lotes` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id_planta=1" \
  -d "id_ubicacion=1" \
  -d "fecha_siembra=2026-11-18" \
  -d "cantidad_inicial=5" \
  -d "cantidad_actual=5" \
  -d "id_estado=5" \
  -d "id_categoria=1" \
  -d "id_origen=1" \
  -d "costo_unitario=4" \
  -d "porcentaje_ganancia=25" \
  "http://localhost/sys-inescolara/lotes?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Lote agregado correctamente",
  "lote": { "id": 2, "id_planta": 1, "imagen": null }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `lotes:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `id_planta` ≤ 0 | `Selecciona una planta.` |
| `500` | `id_ubicacion` ≤ 0 | `Selecciona una ubicación.` |
| `500` | `fecha_siembra` vacía | `La fecha de siembra es requerida.` |
| `500` | `cantidad_inicial` ≤ 0 | `La cantidad inicial debe ser mayor a 0.` |
| `500` | `cantidad_actual` < 0 | `La cantidad actual no puede ser negativa.` |
| `500` | Imagen inválida | Errores del `ImageUploader` |

**Efectos:** inserta en `lote`; auditoría `CREATE`; sube la imagen si se envía.

**Notas**

- El controlador **ignora** el valor de retorno de `Lote::add()`. Si la
  validación del modelo o el `INSERT` fallan, la respuesta puede ser
  `success:true` con `lote.id = 0`.
- `costo_unitario` y `porcentaje_ganancia` se castean con `(float)` antes de
  guardar; el modelo los valida con `^\d+(\.\d{1,2})?$`.

---

### `POST /lotes?action=edit_ajax`

| Campo | Valor |
|---|---|
| Permiso | `lotes:editar` |
| Content-Type | `multipart/form-data` o `application/x-www-form-urlencoded` |

**Parámetros:** los mismos que `add_ajax`, más `id`. Si no se envía `imagen`,
se conserva la actual.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  -d "id_planta=1" \
  -d "id_ubicacion=2" \
  -d "fecha_siembra=2026-11-18" \
  -d "cantidad_inicial=5" \
  -d "cantidad_actual=4" \
  -d "id_estado=5" \
  -d "id_categoria=2" \
  -d "id_origen=1" \
  -d "costo_unitario=4.5" \
  -d "porcentaje_ganancia=25" \
  "http://localhost/sys-inescolara/lotes?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Lote actualizado correctamente",
  "lote": { "id": 2, "imagen": null }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe el lote con ID: <id>` |

**Notas:** no devuelve los campos editados; hay que releer el listado. Si se
envía una imagen nueva, borra la anterior.

---

### `POST /lotes?action=delete_ajax`

| Campo | Valor |
|---|---|
| Permiso | `lotes:eliminar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  "http://localhost/sys-inescolara/lotes?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Lote desactivado correctamente",
  "loteId": 2
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe el lote` |

**Notas:** no comprueba trazabilidad, ventas ni mermas asociadas. Borra la
imagen física si existía.

---

## Deuda detectada

- `add_ajax`/`edit_ajax` no verifican el resultado del modelo: un fallo de
  validación puede devolver `success:true` con id `0`.
- `fecha_siembra` no se valida como fecha; se inserta tal cual.
- El borrado lógico no protege lotes con movimientos.
- La vista `/lotes` no valida `lotes:ver`.

## Casos Postman

`pruebas/collections/inventario/Lotes.postman_collection.json`
