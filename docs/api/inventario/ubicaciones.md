# Módulo Ubicaciones

Zonas físicas del vivero (vivero, invernadero, área de aclimatación…). Se usa en
lotes y trazabilidad.

Tabla: `ubicacion` · Modelo: `app/models/Ubicacion.php` · Controlador:
`app/controllers/UbicacionesController.php` · JS:
`public/assets/js/dashboard/ubicaciones.js`

| Operación | Permiso |
|---|---|
| Ver vista | `ubicaciones:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `ubicaciones:crear` |
| Editar | `ubicaciones:editar` |
| Eliminar (lógico) | `ubicaciones:eliminar` |

Endpoints:

1. `GET /dashboard/ubicaciones`
2. `GET /ubicaciones`
3. `GET /ubicaciones?action=get_locations`
4. `POST /ubicaciones?action=add_ajax`
5. `POST /ubicaciones?action=edit_ajax`
6. `POST /ubicaciones?action=delete_ajax`

---

### `GET /dashboard/ubicaciones`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/ubicaciones` |
| Autenticación | Sesión |
| Permiso | `ubicaciones:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /ubicaciones`

Vista servida por el controlador del módulo. Solo exige sesión; **no** valida
`ubicaciones:ver`.

---

### `GET /ubicaciones?action=get_locations`

**Descripción:** lista las ubicaciones activas ordenadas por nombre.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/ubicaciones?action=get_locations` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/ubicaciones?action=get_locations"
```

**Respuesta 200**

```json
{
  "success": true,
  "ubicaciones": [
    { "id": 2, "nombre_ubicacion": "Invernadero 1", "descripcion": "Área protegida", "tipo": "B", "activo": 1 },
    { "id": 1, "nombre_ubicacion": "Vivero Central", "descripcion": "Área principal de cultivo", "tipo": "A", "activo": 1 }
  ],
  "count": 2
}
```

**Notas**

- El arreglo se llama `ubicaciones`.
- `tipo` proviene de la columna `tipo_ubicacion` con fallback a `tipo`. El
  modelo detecta la columna existente en `information_schema` y la aliasa.

---

### `POST /ubicaciones?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/ubicaciones?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `ubicaciones:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_ubicacion` | string | Sí | Nombre de la zona |
| `descripcion` | string | No | Detalle; vacío → `NULL` |
| `tipo` | string | No | Código de tipo; vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre_ubicacion=Test Ubicacion" \
  -d "descripcion=Zona de prueba" \
  -d "tipo=C" \
  "http://localhost/sys-inescolara/ubicaciones?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Ubicación agregada correctamente",
  "ubicacion": {
    "id": 3,
    "nombre_ubicacion": "Test Ubicacion",
    "descripcion": "Zona de prueba",
    "tipo": "C"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `ubicaciones:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre_ubicacion` vacío | `El nombre de la ubicación es requerido.` |

**Efectos:** inserta en `ubicacion`; auditoría `CREATE`.

---

### `POST /ubicaciones?action=edit_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/ubicaciones?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `ubicaciones:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK |
| `nombre_ubicacion` | string | Sí | Nombre |
| `descripcion` | string | No | Omitirla la deja en `NULL` |
| `tipo` | string | No | Omitirlo lo deja en `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=3" \
  -d "nombre_ubicacion=Test Ubicacion Editada" \
  -d "tipo=D" \
  "http://localhost/sys-inescolara/ubicaciones?action=edit_ajax"
```

**Respuesta 200** (nótese `descripcion` en `null` porque no se envió)

```json
{
  "success": true,
  "message": "Ubicación actualizada correctamente",
  "ubicacion": {
    "id": 3,
    "nombre_ubicacion": "Test Ubicacion Editada",
    "descripcion": null,
    "tipo": "D"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `La ubicación que intenta editar no existe.` |

**Notas:** semántica *replace*. El JS del módulo envía siempre los tres campos,
pero una llamada manual puede borrar datos.

---

### `POST /ubicaciones?action=delete_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/ubicaciones?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `ubicaciones:eliminar` |
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
  -d "id=3" \
  "http://localhost/sys-inescolara/ubicaciones?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Ubicación desactivada correctamente",
  "ubicacionId": 3
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID de ubicación inválido` |
| `500` | No existe | `No existe la ubicación solicitada.` |
| `500` | Tiene lotes activos | `No se puede desactivar la ubicación: Existen lotes vinculados en el inventario activo.` |
| `200` | Otra FK (`success:false`) | `No se puede eliminar esta ubicación porque tiene registros asociados.` |

**Notas**

- El modelo bloquea el borrado si hay lotes activos y lanza `Exception`; el
  controlador no la captura y `handleError()` responde **500**.
- El bloque `catch (\PDOException)` del controlador (respuesta `200` con
  `type:"foreign_key"`) solo se alcanzaría con una restricción FK que el modelo
  no haya previsto.
- Auditoría `DEACTIVATE`.

---

## Deuda detectada

- Dos estrategias distintas para el mismo caso de FK (500 y 200).
- `edit_ajax` pisa `descripcion`/`tipo` con `NULL` cuando se omiten.
- La vista `/ubicaciones` no valida `ubicaciones:ver`.
- El modelo consulta `information_schema` en cada operación para descubrir la
  columna `tipo`/`tipo_ubicacion`.

## Casos Postman

`pruebas/collections/inventario/Ubicaciones.postman_collection.json`
