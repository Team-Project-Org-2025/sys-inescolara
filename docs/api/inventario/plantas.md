# Módulo Plantas

Catálogo base del vivero. Cada planta pertenece opcionalmente a una especie y
se relaciona con lotes, trazabilidad y ventas.

| Operación | Permiso |
|---|---|
| Ver vista | `plantas:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `plantas:crear` |
| Editar | `plantas:editar` |
| Eliminar (lógico) | `plantas:eliminar` |

Tabla: `plantas` · Modelo: `app/models/Planta.php` · Controlador:
`app/controllers/PlantasController.php` · JS:
`public/assets/js/dashboard/plantas.js`

Endpoints:

1. `GET /dashboard/plantas` — vista
2. `GET /plantas` — vista
3. `GET /plantas?action=get_plants`
4. `POST /plantas?action=add_ajax`
5. `POST /plantas?action=edit_ajax`
6. `POST /plantas?action=delete_ajax`

---

### `GET /dashboard/plantas`

**Descripción:** vista del módulo dentro del dashboard, con menú y permisos.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/plantas` |
| Autenticación | Sesión |
| Permiso | `plantas:ver` |
| Content-Type | `text/html` |

**Respuestas**

| Caso | Resultado |
|---|---|
| Con permiso | `200` HTML |
| Sin sesión | `302` a `/login` |
| Sin permiso | `403` HTML con tarjeta "Acceso denegado" |

---

### `GET /plantas`

**Descripción:** variante de la vista servida por el controlador del módulo.
También carga las especies para el formulario.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/plantas` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | `text/html` |

**Notas:** convive con `/dashboard/plantas` y **no** valida `plantas:ver`.

---

### `GET /plantas?action=get_plants`

**Descripción:** lista todas las plantas activas con su especie, stock de lotes
y precio vigente.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/plantas?action=get_plants` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/plantas?action=get_plants"
```

**Respuesta 200**

```json
{
  "success": true,
  "plantas": [
    {
      "id": 2,
      "nombre_comun": "Rosa Postman",
      "nombre_tecnico": "Rosa canina",
      "especie_id": 1,
      "imagen": null,
      "activo": 1,
      "cantidad_total": 0,
      "especie_nombre": "Rosa",
      "stock_lotes": "0",
      "precio_vigente": null
    }
  ],
  "count": 1
}
```

**Errores**

| Código | Cuándo |
|---|---|
| `401` | Sin sesión |
| `500` | Error SQL |

**Notas**

- El arreglo se llama `plantas` (no `data`).
- `stock_lotes` llega como string.
- `precio_vigente` es `null` si el lote no tiene precio.

---

### `POST /plantas?action=add_ajax`

**Descripción:** crea una planta y la registra en auditoría.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/plantas?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `plantas:crear` |
| Content-Type | `multipart/form-data` o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_comun` | string | Sí | Nombre común |
| `nombre_tecnico` | string | No | Nombre científico |
| `especie_id` | int | Sí* | FK a `especie` (el modelo lo exige) |
| `imagen` | file | No | JPG/PNG/WebP; se sube a `assets/uploads/plants` |

\* El controlador lo trata como opcional, pero la validación del modelo lo
exige; sin él la inserción falla.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -F "nombre_comun=Rosa Postman" \
  -F "nombre_tecnico=Rosa canina" \
  -F "especie_id=1" \
  "http://localhost/sys-inescolara/plantas?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Planta agregada correctamente",
  "plant": { "id": 2, "nombre_comun": "Rosa Postman", "imagen": null }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `plantas:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre_comun` vacío | `El nombre común es requerido.` |
| `500` | FK inválida | `Error al guardar la planta.` |
| `500` | Imagen inválida | Errores del `ImageUploader` |

**Efectos secundarios**

- Inserta en `plantas`.
- `AuditLog::record('CREATE', 'plantas', <id>, null, <datos>)`.
- Sube la imagen si se envía.

**Notas**

- El id viene en `plant.id`, no en `data.id`.
- El `precio` que incluye `pruebas/data/plantas.csv` **no** se usa aquí: el
  precio se gestiona en el módulo Precios/Lotes.

---

### `POST /plantas?action=edit_ajax`

**Descripción:** actualiza una planta existente.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/plantas?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `plantas:editar` |
| Content-Type | `multipart/form-data` o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK de la planta |
| `nombre_comun` | string | Sí | Nombre común |
| `nombre_tecnico` | string | No | Nombre científico |
| `especie_id` | int | Sí* | FK a `especie` |
| `imagen` | file | No | Si se omite, conserva la actual |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  -d "nombre_comun=Rosa Postman Editada" \
  -d "nombre_tecnico=Rosa canina" \
  -d "especie_id=1" \
  "http://localhost/sys-inescolara/plantas?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Planta actualizada correctamente",
  "plant": { "id": 2, "imagen": null }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `plantas:editar` | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la planta` |

**Notas:** la respuesta **no** incluye `nombre_comun`; para verificar el cambio
hay que releer el listado.

---

### `POST /plantas?action=delete_ajax`

**Descripción:** desactiva la planta (`activo = 0`). No borra la fila.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/plantas?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `plantas:eliminar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK de la planta |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  "http://localhost/sys-inescolara/plantas?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Planta desactivada correctamente",
  "plantId": 2
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `plantas:eliminar` | `No tienes permiso…` |
| `500` | `id` inválido | `ID inválido` |
| `200` | Tiene lotes asociados | `success:false`, `type:"foreign_key"` |

**Notas**

- El caso de FK se devuelve con **HTTP 200** y `success:false`.
- Borra la imagen física si existía.

---

## Deuda detectada

- `/plantas` (vista) y `/dashboard/plantas` tienen chequeos distintos.
- `checkCsrf()` no se aplica.
- El listado AJAX no pagina: devuelve todas las filas.

## Casos Postman

`pruebas/collections/inventario/Plantas.postman_collection.json`
