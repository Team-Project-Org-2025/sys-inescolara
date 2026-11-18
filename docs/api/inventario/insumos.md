# Módulo Insumos

Materiales consumibles del vivero (sustratos, fertilizantes, etc.), medidos en
una unidad de medida. El stock y el costo se editan directamente aquí; los
movimientos detallados viven en `registro_insumo` (aún sin módulo propio).

Tabla: `insumo` · Modelo: `app/models/Insumo.php` · Controlador:
`app/controllers/InsumosController.php` · JS:
`public/assets/js/dashboard/insumos.js`

| Operación | Permiso |
|---|---|
| Ver vista | `insumos:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `insumos:crear` |
| Editar | `insumos:editar` |
| Eliminar (lógico) | `insumos:eliminar` |

Endpoints:

1. `GET /dashboard/insumos`
2. `GET /insumos`
3. `GET /insumos?action=get_supplies`
4. `POST /insumos?action=add_ajax`
5. `POST /insumos?action=edit_ajax`
6. `POST /insumos?action=delete_ajax`

---

### `GET /dashboard/insumos`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/insumos` |
| Autenticación | Sesión |
| Permiso | `insumos:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /insumos`

Vista servida por el controlador. Carga las unidades de medida activas y solo
exige sesión.

---

### `GET /insumos?action=get_supplies`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/insumos?action=get_supplies` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/insumos?action=get_supplies"
```

**Respuesta 200**

```json
{
  "success": true,
  "supplies": [
    {
      "id": 3,
      "id_insumo": 3,
      "nombre_insumo": "Insumo Demo",
      "id_unidad_medida": 1,
      "categoria": null,
      "stock_actual": "5.00",
      "costo_unitario_actual": "1.25",
      "activo": 1,
      "nombre_unidad_medida": "Unidad",
      "simbolo": "ud"
    }
  ],
  "count": 1
}
```

**Notas**

- El arreglo se llama `supplies`.
- `id` y `id_insumo` traen el mismo valor (alias duplicado en el `SELECT`).
- `stock_actual` y `costo_unitario_actual` llegan como **string decimal**
  (`"5.00"`, `"1.25"`), no como número.
- `categoria` puede ser `null`.
- `nombre_unidad_medida` y `simbolo` vienen del `LEFT JOIN` con
  `unidad_medida`; si la unidad está inactiva, llegan en `null`.

---

### `POST /insumos?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/insumos?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `insumos:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_insumo` | string | Sí | 1-50 caracteres |
| `id_unidad_medida` | int | Sí | FK a `unidad_medida` |
| `categoria` | string | No | Vacío → `NULL` |
| `stock_actual` | decimal | Sí* | Formato `0.00` |
| `costo_unitario_actual` | decimal | Sí* | Formato `0.00` |

\* El controlador usa `isset()`, así que un valor vacío (`""`) pasa la
comprobación y se convierte en `0.0` con `floatval()`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre_insumo=Test Insumo" \
  -d "id_unidad_medida=1" \
  -d "stock_actual=10" \
  -d "costo_unitario_actual=1.5" \
  -d "categoria=Prueba" \
  "http://localhost/sys-inescolara/insumos?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Insumo agregado correctamente",
  "supply": {
    "id": 1,
    "nombre_insumo": "Test Insumo",
    "id_unidad_medida": 1,
    "stock_actual": 10,
    "costo_unitario_actual": 1.5
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `insumos:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre_insumo` vacío | `El nombre del insumo es requerido.` |
| `500` | `id_unidad_medida` ≤ 0 | `La unidad de medida es requerida.` |
| `500` | Faltan `stock_actual`/`costo_unitario_actual` | `El stock actual es requerido.` / `El costo unitario es requerido.` |
| `500` | Valor no numérico o negativo | `Precio inválido (formato: 0.00)` |
| `500` | Nombre > 50 caracteres o con símbolos | `Nombre inválido (1-50 caracteres)` |

**Valores reales del entorno local** (`pruebas/data/seed.sql`): `unidad_medida`
1 = Unidad.

**Efectos:** inserta en `insumo`; auditoría `CREATE`.

---

### `POST /insumos?action=edit_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/insumos?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `insumos:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK |
| `nombre_insumo` | string | Sí | 1-50 caracteres |
| `id_unidad_medida` | int | Sí | FK |
| `categoria` | string | No | Vacío → `NULL` |
| `stock_actual` | decimal | Sí* | — |
| `costo_unitario_actual` | decimal | Sí* | — |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=1" \
  -d "nombre_insumo=Test Insumo Editado" \
  -d "id_unidad_medida=1" \
  -d "stock_actual=12" \
  -d "costo_unitario_actual=2" \
  "http://localhost/sys-inescolara/insumos?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Insumo actualizado correctamente",
  "supply": {
    "id": 1,
    "nombre_insumo": "Test Insumo Editado",
    "id_unidad_medida": 1,
    "stock_actual": 12,
    "costo_unitario_actual": 2
  }
}
```

**Errores:** los mismos de `add_ajax`, más:

| Código | Cuándo | Mensaje |
|---|---|---|
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe el insumo solicitado.` |

**Notas:** sobrescribe `stock_actual` y `costo_unitario_actual`; no hay ajuste
por diferencia ni registro en `registro_insumo` desde este endpoint.

---

### `POST /insumos?action=delete_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/insumos?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `insumos:eliminar` |
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
  -d "id=1" \
  "http://localhost/sys-inescolara/insumos?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Insumo desactivado correctamente",
  "supplyId": 1
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe el insumo` |

**Notas:** no verifica `registro_insumo` ni consumos asociados; el insumo
desaparece del listado pero los registros históricos siguen apuntando a su id.

---

## Deuda detectada

- `stock_actual` y `costo_unitario_actual` se devuelven como string en el
  listado y como número en `add`/`edit`.
- `id` e `id_insumo` duplicados en el listado.
- Los campos vacíos de stock/costo se guardan como `0` en vez de rechazarse.
- El módulo no genera movimientos en `registro_insumo`; editar el stock no deja
  trazabilidad.
- La vista `/insumos` no valida `insumos:ver`.

## Casos Postman

`pruebas/collections/inventario/Insumos.postman_collection.json`
