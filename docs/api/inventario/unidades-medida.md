# Módulo Unidades de medida

Catálogo de unidades para insumos (gramo, litro, bulto…). La tabla no tiene
columna de símbolo; el `simbolo` que aparece en Insumos viene del `JOIN` y en la
base actual es `NULL` salvo que se cargue por SQL.

Tabla: `unidad_medida` · Modelo: `app/models/UnidadMedida.php` · Controlador:
`app/controllers/UnidadesMedidaController.php` · JS:
`public/assets/js/dashboard/unidades-medida.js`

| Operación | Permiso |
|---|---|
| Ver vista | `unidades_medida:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `unidades_medida:crear` |
| Editar | `unidades_medida:editar` |
| Eliminar (lógico) | `unidades_medida:eliminar` |

> El nombre del módulo en permisos usa guion bajo (`unidades_medida`), pero la
> ruta usa guion (`/unidades-medida`).

Endpoints:

1. `GET /dashboard/unidades-medida`
2. `GET /unidades-medida`
3. `GET /unidades-medida?action=get_units`
4. `POST /unidades-medida?action=add_ajax`
5. `POST /unidades-medida?action=edit_ajax`
6. `POST /unidades-medida?action=delete_ajax`

---

### `GET /dashboard/unidades-medida`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/unidades-medida` |
| Autenticación | Sesión |
| Permiso | `unidades_medida:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /unidades-medida`

Vista servida por el controlador. Solo exige sesión.

---

### `GET /unidades-medida?action=get_units`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/unidades-medida?action=get_units` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/unidades-medida?action=get_units"
```

**Respuesta 200**

```json
{
  "success": true,
  "units": [
    { "id": 8, "nombre_unidad_medida": "Bulto", "activo": 1 },
    { "id": 3, "nombre_unidad_medida": "Gramo", "activo": 1 },
    { "id": 9, "nombre_unidad_medida": "Guacal", "activo": 1 },
    { "id": 2, "nombre_unidad_medida": "Kilogramo", "activo": 1 },
    { "id": 4, "nombre_unidad_medida": "Litro", "activo": 1 },
    { "id": 6, "nombre_unidad_medida": "Metro", "activo": 1 },
    { "id": 7, "nombre_unidad_medida": "Metro Cuadrado", "activo": 1 },
    { "id": 5, "nombre_unidad_medida": "Mililitro", "activo": 1 },
    { "id": 1, "nombre_unidad_medida": "Unidad", "activo": 1 }
  ],
  "count": 9
}
```

**Notas**

- El arreglo se llama `units` (inglés), no `unidades`.
- El campo del formulario se llama `nombre`, pero la columna y la respuesta usan
  `nombre_unidad_medida`.

---

### `POST /unidades-medida?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/unidades-medida?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `unidades_medida:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre` | string | Sí | Nombre de la unidad |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre=Test Unidad" \
  "http://localhost/sys-inescolara/unidades-medida?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Unidad agregada correctamente",
  "unit": { "id": 10, "nombre_unidad_medida": "Test Unidad" }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre` vacío | `El nombre de la unidad es requerido.` |

**Efectos:** inserta en `unidad_medida`; auditoría `CREATE`.

---

### `POST /unidades-medida?action=edit_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/unidades-medida?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `unidades_medida:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK |
| `nombre` | string | Sí | Nombre nuevo |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=10" \
  -d "nombre=Test Unidad Editada" \
  "http://localhost/sys-inescolara/unidades-medida?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Unidad actualizada correctamente",
  "unit": { "id": 10, "nombre_unidad_medida": "Test Unidad Editada" }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la unidad de medida solicitada.` |

---

### `POST /unidades-medida?action=delete_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/unidades-medida?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `unidades_medida:eliminar` |
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
  -d "id=10" \
  "http://localhost/sys-inescolara/unidades-medida?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Unidad desactivada correctamente",
  "unitId": 10
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la unidad de medida` |

**Notas**

- El `catch (\PDOException)` del controlador es **código muerto**: el modelo
  atrapa `Throwable`, hace `UPDATE activo = 0` y devuelve `false`. Como el
  borrado es lógico, una unidad en uso por insumos se desactiva sin error.
- Los insumos que la referencian pierden el nombre de la unidad en el `JOIN`
  (`LEFT JOIN … AND u.activo = 1`), pero el insumo sigue visible.

---

## Deuda detectada

- El mensaje `No se puede eliminar la unidad porque está siendo usada por uno o
  más insumos.` nunca se muestra.
- Desactivar una unidad deja insumos activos sin nombre de unidad.
- La vista `/unidades-medida` no valida `unidades_medida:ver`.
- Mezcla de español (`nombre`) e inglés (`unit`) en el mismo endpoint.

## Casos Postman

`pruebas/collections/inventario/UnidadesMedida.postman_collection.json`
