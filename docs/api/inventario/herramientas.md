# Módulo Herramientas

Inventario de herramientas (palas, tijeras, carretillas…) con control de estado
y registro transaccional de uso. Los usos se vinculan a una asignación de tarea
(`asignar_tarea`).

Tablas: `herramienta`, `uso_herramienta` · Modelo: `app/models/Herramienta.php`
· Controlador: `app/controllers/HerramientasController.php` · JS:
`public/assets/js/dashboard/herramientas.js`

| Operación | Permiso |
|---|---|
| Ver vista | `herramientas:ver` |
| Listar (AJAX) | Solo sesión |
| Crear | `herramientas:crear` |
| Editar | `herramientas:editar` |
| Eliminar (lógico) | `herramientas:eliminar` |
| Registrar uso | `herramientas:crear` |
| Listar usos | Solo sesión |

Endpoints:

1. `GET /dashboard/herramientas`
2. `GET /herramientas`
3. `GET /herramientas?action=get_tools`
4. `POST /herramientas?action=add_ajax`
5. `POST /herramientas?action=edit_ajax`
6. `POST /herramientas?action=delete_ajax`
7. `POST /herramientas?action=record_usage_ajax`
8. `GET /herramientas?action=get_usages&id_herramienta=<id>`

---

### `GET /dashboard/herramientas`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/dashboard/herramientas` |
| Autenticación | Sesión |
| Permiso | `herramientas:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /herramientas`

Vista servida por el controlador. Solo exige sesión.

---

### `GET /herramientas?action=get_tools`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/herramientas?action=get_tools` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/herramientas?action=get_tools"
```

**Respuesta 200**

```json
{
  "success": true,
  "tools": [
    {
      "id": 3,
      "nombre_herramienta": "Herramienta Demo",
      "cantidad": 1,
      "estado": "disponible",
      "fecha_ultimo_mantenimiento": null,
      "observacion": null,
      "activo": 1
    }
  ],
  "count": 1
}
```

**Notas**

- El arreglo se llama `tools`.
- `cantidad` es entero; `fecha_ultimo_mantenimiento` es `null` o `YYYY-MM-DD`.
- Orden alfabético por `nombre_herramienta`.

---

### `POST /herramientas?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/herramientas?action=add_ajax` |
| Autenticación | Sesión |
| Permiso | `herramientas:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_herramienta` | string | Sí | 1-50 caracteres |
| `cantidad` | int | No | Mínimo 1; por defecto 1 |
| `estado` | string | No | Por defecto `disponible` |
| `fecha_ultimo_mantenimiento` | date | No | `YYYY-MM-DD`, no futura |
| `observacion` | string | No | Vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre_herramienta=Test Herramienta" \
  -d "cantidad=2" \
  -d "estado=disponible" \
  "http://localhost/sys-inescolara/herramientas?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Herramienta agregada correctamente",
  "herramienta": {
    "id": 1,
    "nombre_herramienta": "Test Herramienta",
    "cantidad": 2,
    "estado": "disponible"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `herramientas:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `nombre_herramienta` vacío | `El nombre de la herramienta es requerido.` |
| `500` | Nombre inválido | `Nombre inválido (1-50 caracteres)` |
| `500` | Fecha con formato incorrecto | `Formato de fecha de último mantenimiento inválido.` |
| `500` | Fecha futura | `La fecha de último mantenimiento no puede ser posterior al día de hoy.` |

**Efectos:** inserta en `herramienta`; auditoría `CREATE`.

---

### `POST /herramientas?action=edit_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/herramientas?action=edit_ajax` |
| Autenticación | Sesión |
| Permiso | `herramientas:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK |
| `nombre_herramienta` | string | Sí | 1-50 caracteres |
| `cantidad` | int | No | Mínimo 1 |
| `estado` | string | No | Si se omite, vuelve a `disponible` |
| `fecha_ultimo_mantenimiento` | date | No | `YYYY-MM-DD`, no futura |
| `observacion` | string | No | Vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=1" \
  -d "nombre_herramienta=Test Herramienta Editada" \
  -d "cantidad=3" \
  -d "estado=mantenimiento" \
  "http://localhost/sys-inescolara/herramientas?action=edit_ajax"
```

**Respuesta 200** (solo devuelve el id)

```json
{
  "success": true,
  "message": "Herramienta actualizada correctamente",
  "herramienta": { "id": 1 }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la herramienta solicitada.` |

**Notas:** para comprobar el cambio hay que releer el listado. `estado` se
sobrescribe con `disponible` si no se envía.

---

### `POST /herramientas?action=delete_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/herramientas?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | `herramientas:eliminar` |
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
  "http://localhost/sys-inescolara/herramientas?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Herramienta desactivada correctamente",
  "herramientaId": 1
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe la herramienta` |

**Notas:** no valida usos históricos. Los registros de `uso_herramienta`
conservan el `id_herramienta`.

---

### `POST /herramientas?action=record_usage_ajax`

**Descripción:** registra un uso y actualiza el estado de la herramienta en una
sola transacción.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/herramientas?action=record_usage_ajax` |
| Autenticación | Sesión |
| Permiso | `herramientas:crear` |
| Content-Type | `application/json` o `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_asignacion` | int | Sí | FK a `asignar_tarea.id_asignacion` |
| `id_herramienta` | int | Sí | FK a `herramienta.id_herramienta` |
| `fecha_uso` | date | No | Por defecto la fecha actual |
| `observacion` | string | No | Nota libre |
| `estado_herramienta_post_uso` | string | No | Por defecto `disponible` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"id_asignacion":1,"id_herramienta":1,"fecha_uso":"2026-11-18","estado_herramienta_post_uso":"mantenimiento"}' \
  "http://localhost/sys-inescolara/herramientas?action=record_usage_ajax"
```

**Respuesta 200** (según código; requiere una asignación existente)

```json
{ "success": true, "message": "Uso de herramienta registrado", "id_uso": 1 }
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `400` | Falta `id_asignacion` o `id_herramienta` | `Se requieren asignación y herramienta.` |
| `403` | Sin permiso | `No tienes permiso…` |
| `500` | FK inválida | Mensaje crudo del motor SQL |

**Efectos**

- Inserta en `uso_herramienta`.
- `UPDATE herramienta SET estado = <estado_herramienta_post_uso>`.
- Auditoría `CREATE` sobre `uso_herramienta`.
- Todo dentro de una transacción con `rollBack()` en caso de error.

**Notas**

- El controlador no acepta `cantidad`; el modelo usa `1` por defecto, así que
  `cantidad_usada` siempre queda en `1.00` desde este endpoint.
- No se validan los estados permitidos: cualquier string se guarda.
- No se probó con respuesta real porque el seed no crea filas en
  `asignar_tarea`; la ficha está extraída del código.

---

### `GET /herramientas?action=get_usages&id_herramienta=<id>`

**Descripción:** histórico de usos de una herramienta.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/herramientas?action=get_usages&id_herramienta=<id>` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/herramientas?action=get_usages&id_herramienta=1"
```

**Respuesta 200**

```json
{ "success": true, "usages": [], "count": 0 }
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `400` | `id_herramienta` ausente o ≤ 0 | `ID de herramienta inválido` |
| `401` | Sin sesión | `No autorizado` |

---

## Deuda detectada

- `edit_ajax` devuelve solo el id y pisa `estado` con `disponible` si se omite.
- `record_usage_ajax` fija `cantidad_usada = 1` y no valida el estado.
- `get_usages` no pagina.
- La vista `/herramientas` no valida `herramientas:ver`.
- El seed no incluye una asignación de tarea, por lo que el flujo de uso no se
  puede probar de extremo a extremo sin preparar datos adicionales.

## Casos Postman

`pruebas/collections/inventario/Herramientas.postman_collection.json`
