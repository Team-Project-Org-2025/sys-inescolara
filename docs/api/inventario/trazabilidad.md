# Módulo Trazabilidad

Historial fitosanitario por lote. Registrar una cuarentena **descuenta** stock
del lote; restaurarla o eliminarla lo devuelve.

Tablas: `trazabilidad`, `lote`, `estado` · Modelo:
`app/models/Trazabilidad.php` · Controlador:
`app/controllers/TrazabilidadController.php` · JS:
`public/assets/js/dashboard/trazabilidad.js`

| Operación | Permiso |
|---|---|
| Ver vista | `trazabilidad:ver` |
| Listar | Solo sesión |
| Lotes disponibles | Solo sesión |
| Crear | `trazabilidad:crear` |
| Editar / cambiar estado / restaurar | `trazabilidad:editar` |
| Eliminar (lógico + devolver stock) | `trazabilidad:eliminar` |

Endpoints:

1. `GET /dashboard/trazabilidad`
2. `GET /trazabilidad`
3. `GET /trazabilidad?action=get_trazabilidad`
4. `GET /trazabilidad?action=get_batches`
5. `POST /trazabilidad?action=add_ajax`
6. `POST /trazabilidad?action=edit_ajax`
7. `POST /trazabilidad?action=update_estado_ajax`
8. `POST /trazabilidad?action=restore_ajax`
9. `POST /trazabilidad?action=delete_ajax`

> **Esquema:** la base actual define `trazabilidad.estado_salud` como
> `VARCHAR(30)`, no `id_estado`. El modelo detecta la columna en
> `information_schema` y guarda el **nombre** del estado. Por eso las respuestas
> traen `estado_salud` y no `id_estado`, aunque las peticiones siguen enviando
> `id_estado`.

---

### `GET /trazabilidad?action=get_trazabilidad`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/trazabilidad?action=get_trazabilidad"
```

**Respuesta 200**

```json
{
  "success": true,
  "trazabilidad": [
    {
      "id": 1,
      "id_lote": 1,
      "cantidad": 3,
      "estado_salud": "cuarentena",
      "observacion": null,
      "fecha_registro": "2026-01-20",
      "activo": 1,
      "lote_cantidad_actual": 7,
      "planta_nombre": "Rosa Base"
    }
  ]
}
```

**Notas:** el arreglo se llama `trazabilidad`; no incluye `count`.

---

### `GET /trazabilidad?action=get_batches`

**Descripción:** lotes activos con stock disponible para cuarentena.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |

**Query:** `include_id` (opcional) incluye un lote aunque ya no tenga stock.

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/trazabilidad?action=get_batches"
```

**Respuesta 200**

```json
{
  "success": true,
  "batches": [
    {
      "id": 1,
      "cantidad_actual": 7,
      "planta_nombre": "Rosa Base",
      "nombre_ubicacion": "Vivero Central"
    }
  ]
}
```

---

### `POST /trazabilidad?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Autenticación | Sesión |
| Permiso | `trazabilidad:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_lote` | int | Sí | Lote con stock |
| `cantidad` | int | Sí | Entero positivo, sin decimales ni signos |
| `id_estado` | int | Sí | Estado de salud (en esta BD se guarda su nombre) |
| `fecha_registro` | date | Sí | `YYYY-MM-DD`, no futura |
| `observacion` | string | No | Vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id_lote=1" \
  -d "cantidad=2" \
  -d "id_estado=6" \
  -d "fecha_registro=2026-11-18" \
  -d "observacion=Prueba Postman" \
  "http://localhost/sys-inescolara/trazabilidad?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Cuarentena registrada correctamente. Stock del lote actualizado.",
  "id": 2
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `trazabilidad:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | Sin lote | `Debe seleccionar un lote.` |
| `500` | Cantidad inválida | `La cantidad debe ser un número entero positivo, sin decimales ni signos.` |
| `500` | Sin estado | `El estado de salud es requerido.` |
| `500` | Sin fecha | `La fecha de cuarentena es requerida.` |
| `500` | Fecha inválida/futura | `Formato de fecha inválido…` / `La fecha de cuarentena no puede ser posterior…` |
| `500` | Stock insuficiente | `El lote solo tiene N ejemplares disponibles.` |

**Efectos**

- Inserta en `trazabilidad`.
- `UPDATE lote SET cantidad_actual = cantidad_actual - N`.
- Todo en una transacción; auditoría `CREATE`.

---

### `POST /trazabilidad?action=edit_ajax`

**Descripción:** actualiza el registro ajustando el stock del lote.

| Campo | Valor |
|---|---|
| Permiso | `trazabilidad:editar` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros:** los de `add_ajax`, más `id`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  -d "id_lote=1" \
  -d "cantidad=3" \
  -d "id_estado=6" \
  -d "fecha_registro=2026-11-18" \
  "http://localhost/sys-inescolara/trazabilidad?action=edit_ajax"
```

**Respuesta 200**

```json
{ "success": true, "message": "Monitoreo actualizado correctamente." }
```

**Reglas de stock**

| Caso | Efecto |
|---|---|
| Cambia de lote | Devuelve la cantidad anterior al lote viejo y descuenta la nueva del nuevo |
| Mismo lote, cantidad mayor | Descuenta la diferencia |
| Mismo lote, cantidad menor | Devuelve la diferencia |
| Misma cantidad | No toca stock |

**Errores:** `403`, `500 ID inválido`, `500 No existe el registro de
trazabilidad`, `500` stock insuficiente.

---

### `POST /trazabilidad?action=update_estado_ajax`

| Campo | Valor |
|---|---|
| Permiso | `trazabilidad:editar` |

**Parámetros**

| Nombre | Tipo | Obligatorio |
|---|---|---|
| `id` | int | Sí |
| `id_estado` | int | Sí |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" -d "id_estado=5" \
  "http://localhost/sys-inescolara/trazabilidad?action=update_estado_ajax"
```

**Respuesta 200**

```json
{ "success": true, "message": "Estado actualizado correctamente." }
```

**Notas:** solo cambia el estado; el stock del lote no se mueve.

---

### `POST /trazabilidad?action=restore_ajax`

**Descripción:** marca el registro como `vivo`, lo desactiva y devuelve su
cantidad al lote.

| Campo | Valor |
|---|---|
| Permiso | `trazabilidad:editar` |

**Parámetros:** `id`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  "http://localhost/sys-inescolara/trazabilidad?action=restore_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Ejemplar restaurado correctamente. Stock devuelto al lote."
}
```

**Efectos:** cambia estado a `vivo`, `activo = 0`, y suma la cantidad al lote.
Todo transaccional.

---

### `POST /trazabilidad?action=delete_ajax`

| Campo | Valor |
|---|---|
| Permiso | `trazabilidad:eliminar` |

**Parámetros:** `id`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=3" \
  "http://localhost/sys-inescolara/trazabilidad?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Registro de cuarentena desactivado correctamente. Stock del lote restaurado."
}
```

**Efectos:** `activo = 0` y devuelve la cantidad al lote. No cambia el estado.

---

## Deuda detectada

- La BD usa `estado_salud` (texto) y el controlador habla de `id_estado`; el
  modelo lo traduce consultando `information_schema` en cada operación.
- `get_trazabilidad` no devuelve `id_estado`, así que el frontend no puede
  preseleccionar el estado sin cruzar el nombre.
- `delete_ajax` y `restore_ajax` hacen casi lo mismo con permisos distintos.
- La vista `/trazabilidad` no valida `trazabilidad:ver`.
- `get_batches` devuelve `planta_nombre` pero no el id de la planta.

## Casos Postman

`pruebas/collections/inventario/Trazabilidad.postman_collection.json`

La colección crea un lote propio de 20 ejemplares, ejecuta el ciclo completo
(`add` → verificación de stock → `edit` → `update_estado` → `restore` →
`add`/`delete`) y elimina el lote al final. Así no depende del stock del seed y
puede ejecutarse repetidamente sin acumular diferencias.
