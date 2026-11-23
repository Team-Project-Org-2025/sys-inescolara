# Módulo Mermas

Registro de pérdidas sobre ejemplares en cuarentena. Cada merma descuenta stock
de la trazabilidad y calcula su impacto económico con el costo unitario del
lote.

Tablas: `mermas_historico`, `trazabilidad`, `lote` · Modelo:
`app/models/Merma.php` · Controlador: `app/controllers/MermasController.php` ·
JS: `public/assets/js/dashboard/mermas.js`

| Operación | Permiso |
|---|---|
| Ver vista | `mermas:ver` |
| Listar mermas | Solo sesión |
| Listar cuarentena disponible | Solo sesión |
| Crear | `mermas:crear` |
| Eliminar (lógico) | **Ninguno** (`checkPermisoOrFail` no se invoca) |

Endpoints:

1. `GET /dashboard/mermas`
2. `GET /mermas`
3. `GET /mermas?action=get_mermas`
4. `GET /mermas?action=get_quarantine`
5. `POST /mermas?action=add_ajax`
6. `POST /mermas?action=delete_ajax`

---

### `GET /mermas?action=get_mermas`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/mermas?action=get_mermas"
```

**Respuesta 200**

```json
{
  "success": true,
  "mermas": [
    {
      "id": 1,
      "id_merma": 1,
      "id_trazabilidad": 1,
      "id_lote": 1,
      "cantidad": 1,
      "motivo": "plaga",
      "descripcion": "Hoja con plaga",
      "fecha_merma": "2026-11-18",
      "impacto_economico": "6.00",
      "id_usuario_registra": 1,
      "activo": 1,
      "created_at": "2026-11-18 18:45:35",
      "planta_nombre": "Rosa Base",
      "estado_salud": "cuarentena",
      "fecha_cuarentena": "2026-01-20",
      "usuario_registra": null
    }
  ]
}
```

**Notas**

- El arreglo se llama `mermas`; no incluye `count`.
- `impacto_economico` es string decimal.
- `usuario_registra` siempre viene en `null`: el `SELECT` lo fuerza así.
- `estado_salud` y `fecha_cuarentena` vienen del registro de trazabilidad.

---

### `GET /mermas?action=get_quarantine`

**Descripción:** registros de trazabilidad con cantidad disponible para dar de
baja.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/mermas?action=get_quarantine"
```

**Respuesta 200**

```json
{
  "success": true,
  "quarantine": [
    {
      "id": 1,
      "id_lote": 1,
      "cantidad": 3,
      "estado_salud": "cuarentena",
      "fecha_registro": "2026-01-20",
      "planta_nombre": "Rosa Base",
      "lote_stock": 7,
      "nombre_ubicacion": "Vivero Central",
      "precio_unitario": "5.00"
    }
  ]
}
```

**Notas**

- Solo incluye registros activos con `cantidad > 0` y estado distinto de
  `Vivo`/`Activo`/`Sano`.
- `lote_stock` es `lote.cantidad_actual`, que **no** cambia al registrar una
  merma; la merma descuenta `trazabilidad.cantidad`.

---

### `POST /mermas?action=add_ajax`

| Campo | Valor |
|---|---|
| Método | `POST` |
| Autenticación | Sesión |
| Permiso | `mermas:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_trazabilidad` | int | Sí | Registro en cuarentena |
| `cantidad` | int | Sí | > 0 y ≤ cantidad disponible |
| `motivo` | string | Sí | `plaga`, `daño_mecanico`, `factor_climatico`, `enfermedad`, `otro` |
| `fecha_merma` | date | Sí | `YYYY-MM-DD` |
| `descripcion` | string | No | Vacío → `NULL` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id_trazabilidad=1" \
  -d "cantidad=1" \
  -d "motivo=plaga" \
  -d "descripcion=Hoja con plaga" \
  -d "fecha_merma=2026-11-18" \
  "http://localhost/sys-inescolara/mermas?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Merma registrada correctamente. Stock de cuarentena actualizado.",
  "id": 1
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `mermas:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | `id_trazabilidad` ≤ 0 | `Debe seleccionar un registro de cuarentena.` |
| `500` | `cantidad` ≤ 0 | `La cantidad debe ser mayor a cero.` |
| `500` | Motivo fuera de la lista | `Motivo de merma inválido.` |
| `500` | Sin fecha | `La fecha de merma es requerida.` |
| `500` | Sin sesión válida | `Usuario no autenticado.` |
| `500` | Cantidad > disponible | `Esta cuarentena solo tiene N ejemplares disponibles.` |

**Efectos**

- Calcula `impacto_economico = cantidad × lote.costo_unitario`.
- Inserta en `mermas_historico` con `id_usuario_registra`.
- Descuenta `trazabilidad.cantidad`.
- Auditoría `CREATE` sobre `mermas_historico`.
- Transaccional.

**Notas**

- El controlador valida `daño_mecanico` (con `ñ`) y el `ENUM` de la BD define
  `dano_mecanico`. MySQL lo acepta por la colación acento-insensible, pero el
  valor almacenado es `dano_mecanico`.
- En esta BD no existe `trazabilidad.id_estado`, así que el paso “marcar como
  muerto al llegar a 0” no se ejecuta. El registro queda con `cantidad = 0` y
  desaparece de `get_quarantine`.
- `fecha_merma` no se valida como fecha; se inserta tal cual.

---

### `POST /mermas?action=delete_ajax`

**Descripción:** desactiva el registro de merma. **No devuelve stock** a la
cuarentena.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/mermas?action=delete_ajax` |
| Autenticación | Sesión |
| Permiso | — (solo sesión) |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id` | int | Sí | PK de `mermas_historico` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=1" \
  "http://localhost/sys-inescolara/mermas?action=delete_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Registro de merma desactivado correctamente."
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `500` | `id` ≤ 0 | `ID inválido` |
| `500` | No existe | `No existe el registro de merma` |

**Notas:** cualquier usuario autenticado puede desactivar una merma, incluido
un viewer sin permisos de módulo.

---

## Deuda detectada

- `delete_ajax` no valida `mermas:eliminar` (ni ningún permiso).
- El borrado no restaura el stock de cuarentena; la merma queda anulada pero el
  stock perdido.
- `get_mermas` fuerza `usuario_registra = NULL`.
- `lote_stock` en `get_quarantine` no refleja el descuento de mermas.
- Inconsistencia `daño_mecanico` (PHP) vs `dano_mecanico` (ENUM).
- La vista `/mermas` no valida `mermas:ver`.

## Casos Postman

`pruebas/collections/inventario/Mermas.postman_collection.json`
