# Módulo Especies

Catálogo taxonómico de referencia para las plantas. Una especie puede quedar
desactivada aunque existan plantas que la referencien.

Tabla: `especie` · Modelo: `app/models/Especie.php` · Controlador:
`app/controllers/EspeciesController.php` · JS:
`public/assets/js/dashboard/especies.js`

| Operación | Permiso |
|---|---|
| Ver vista | `especies:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `especies:crear` |
| Editar | `especies:editar` |
| Eliminar (lógico) | `especies:eliminar` |

Endpoints:

1. `GET /dashboard/especies` — vista con permiso
2. `GET /especies` — vista (solo sesión)
3. `GET /especies?action=get_species`
4. `POST /especies?action=add_ajax`
5. `POST /especies?action=edit_ajax`
6. `POST /especies?action=delete_ajax`

---

### `GET /dashboard/especies`

**Descripción:** vista del módulo dentro del dashboard.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/especies` |
| Autenticación | Sesión |
| Permiso | `especies:ver` |
| Content-Type | `text/html` |

**Respuestas**

| Caso | Resultado |
|---|---|
| Con permiso | `200` HTML |
| Sin sesión | `302` a `/login` |
| Sin permiso | `403` HTML |

---

### `GET /especies`

**Descripción:** vista servida por el controlador del módulo.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/especies` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | `text/html` |

**Notas:** **no** valida `especies:ver`; solo exige sesión.

---

### `GET /especies?action=get_species`

**Descripción:** lista las especies activas ordenadas por nombre.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/especies?action=get_species` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/especies?action=get_species"
```

**Respuesta 200**

```json
{
  "success": true,
  "especies": [
    { "id": 2, "nombre_especie": "Ixora", "descripcion": "Ixora coccinea", "activo": 1 },
    { "id": 3, "nombre_especie": "Jazmín", "descripcion": "Jasminum officinale", "activo": 1 },
    { "id": 1, "nombre_especie": "Rosa", "descripcion": "Rosa sp.", "activo": 1 }
  ],
  "count": 3
}
```

**Notas**

- El arreglo se llama `especies`.
- El orden es alfabético (`ORDER BY nombre_especie ASC`).
- El campo `id` es el alias de `id_especie`.

---

### `POST /especies?action=add_ajax`

**Descripción:** crea una especie.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/especies?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `especies:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_especie` | string | Sí | Nombre de la especie |
| `descripcion` | string | No | Nota libre; vacío se guarda como `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre_especie=Test Especie" \
  -d "descripcion=Descripcion de prueba" \
  "http://localhost/sys-inescolara/especies?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Especie agregada correctamente",
  "especie": {
    "id": 4,
    "nombre_especie": "Test Especie",
    "descripcion": "Descripcion de prueba"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `especies:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre_especie` vacío | `El nombre de la especie es requerido.` |

**Efectos:** inserta en `especie` y registra `CREATE` en auditoría.

---

### `POST /especies?action=edit_ajax`

**Descripción:** actualiza una especie existente.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/especies?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `especies:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK de la especie |
| `nombre_especie` | string | Sí | Nombre nuevo |
| `descripcion` | string | No | Si se omite, se guarda `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=4" \
  -d "nombre_especie=Test Especie Editada" \
  -d "descripcion=Otra" \
  "http://localhost/sys-inescolara/especies?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Especie actualizada correctamente",
  "especie": {
    "id": 4,
    "nombre_especie": "Test Especie Editada",
    "descripcion": "Otra"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `especies:editar` | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la especie` |

**Notas:** semántica *replace*: enviar la ficha sin `descripcion` borra la
descripción anterior.

---

### `POST /especies?action=delete_ajax`

**Descripción:** desactiva la especie (`activo = 0`).

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/especies?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `especies:eliminar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK de la especie |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=4" \
  "http://localhost/sys-inescolara/especies?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Especie desactivada correctamente",
  "especieId": 4
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `especies:eliminar` | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la especie` |

**Notas:** no verifica plantas asociadas. Las plantas que apuntan a la especie
siguen existiendo; el `LEFT JOIN` del listado de plantas devolverá la especie
aunque esté inactiva.

---

## Deuda detectada

- La vista `/especies` no valida `especies:ver`.
- `edit_ajax` pisa `descripcion` con `NULL` si el cliente no la envía.
- El endpoint de listado no pagina ni filtra.
- No hay endpoint de restauración (`restore`) expuesto, aunque el modelo lo
  implementa.

## Casos Postman

`pruebas/collections/inventario/Especies.postman_collection.json`
