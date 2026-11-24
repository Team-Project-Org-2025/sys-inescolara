# Módulo Ampliación

Intercambios de plantas entre el vivero y terceros: registra salidas de lotes y
entradas que crean o incrementan lotes. Es el único módulo de Comercial que
acepta JSON de forma nativa.

Tablas: `movimiento_planta`, `movimiento_planta_detalle`, `lote`, `plantas` ·
Modelo: `app/models/Ampliacion.php` · Controlador:
`app/controllers/AmpliacionController.php` · JS:
`public/assets/js/dashboard/ampliacion.js`

| Operación | Permiso |
|---|---|
| Ver vista (`/dashboard/ampliacion`) | `ampliacion:ver` |
| Listar / detalle / catálogos / buscar clientes | Solo sesión |
| Crear | `ampliacion:crear` |
| Editar | `ampliacion:editar` |
| Eliminar (lógico) | `ampliacion:eliminar` |

> **Ruta:** `/ampliacion` solo atiende AJAX. Sin `X-Requested-With:
> XMLHttpRequest` redirige (`302`) a `/dashboard/ampliacion`; con el header y
> sin `action` también redirige.

Endpoints:

1. `GET /dashboard/ampliacion`
2. `GET /ampliacion?action=get_exchanges`
3. `GET /ampliacion?action=get_detail&id=<id>`
4. `GET /ampliacion?action=get_lotes`
5. `GET /ampliacion?action=get_plantas`
6. `GET /ampliacion?action=get_ubicaciones`
7. `GET /ampliacion?action=get_especies`
8. `GET /ampliacion?action=get_gestores`
9. `GET /ampliacion?action=buscar_clientes&q=<texto>`
10. `POST /ampliacion?action=add_ajax`
11. `POST /ampliacion?action=edit_ajax`
12. `POST /ampliacion?action=delete_ajax`

---

### `GET /dashboard/ampliacion`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | `ampliacion:ver` |
| Content-Type | `text/html` |

Carga clientes y trabajadores para el formulario. `302` sin sesión · `403` sin
permiso.

---

### `GET /ampliacion?action=get_exchanges`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/ampliacion?action=get_exchanges"
```

**Respuesta 200 (con datos)**

```json
{
  "success": true,
  "ampliaciones": [
    {
      "id": 6,
      "tipo_movimiento": "intercambio",
      "fecha_movimiento": "2026-11-23",
      "observacion": "Ampliacion Doc",
      "id_usuario_gestor": 1,
      "cliente_nombre": "Cliente Prueba",
      "tipo_cedula_cliente": "V",
      "cedula_cliente": "12345678",
      "total_salida": 1,
      "total_entrada": 0,
      "gestor_nombre": "admin"
    }
  ]
}
```

Sin registros activos devuelve `{"success":true,"ampliaciones":[]}`.

**Notas:** solo `activo = 1`; ordena por fecha y id descendente. `total_salida`
y `total_entrada` cuentan las líneas activas. Si el cliente está desactivado,
`cliente_nombre` cae a `"—"`.

---

### `GET /ampliacion?action=get_detail&id=<id>`

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/ampliacion?action=get_detail&id=6"
```

**Respuesta 200**

```json
{
  "success": true,
  "ampliacion": {
    "id_movimiento_planta": 6,
    "tipo_movimiento": "intercambio",
    "id_cliente": 1,
    "id_usuario_gestor": 1,
    "fecha_movimiento": "2026-11-23",
    "observacion": "Ampliacion Doc",
    "activo": 1,
    "cliente_nombre": "Cliente Prueba",
    "tipo_cedula_cliente": "V",
    "cedula_cliente": "12345678",
    "gestor_nombre": "admin",
    "detalles": [
      {
        "id_detalle_mov_planta": 13,
        "id_movimiento_planta": 6,
        "id_lote": 34,
        "tipo": "salida",
        "cantidad": 2,
        "precio_unitario": null,
        "sub_total": null,
        "activo": 1,
        "lote_stock_actual": 3,
        "id_planta": 1,
        "id_ubicacion": 1,
        "planta_nombre": "Rosa Base",
        "ubicacion_nombre": "Vivero Central"
      }
    ]
  }
}
```

**Errores:** `400 ID inválido`, `404 No se encontró el intercambio`.

**Notas:** `lote_stock_actual` es el stock **después** del movimiento. Los
detalles se ordenan por `tipo` y luego por id.

---

### Catálogos

| Endpoint | Arreglo | Uso |
|---|---|---|
| `get_lotes` | `lotes` | Lotes activos con `cantidad_actual > 0` |
| `get_plantas` | `plantas` | Plantas activas |
| `get_ubicaciones` | `ubicaciones` | Ubicaciones activas |
| `get_especies` | `especies` | Especies activas |
| `get_gestores` | `gestores` | Usuarios activos con nombre de usuario o de trabajador |
| `buscar_clientes&q=` | `clientes` | Hasta 10 clientes activos; con menos de 2 caracteres, `[]` |

**Respuesta real de `get_lotes`**

```json
{
  "success": true,
  "lotes": [
    {
      "id": 1,
      "id_lote": 1,
      "cantidad_actual": 9,
      "id_estado": 5,
      "planta_nombre": "Rosa Base",
      "ubicacion_nombre": "Vivero Central"
    }
  ]
}
```

**Respuesta real de `get_gestores` (recortada)**

```json
{
  "success": true,
  "gestores": [
    {
      "id": 1,
      "nombre_usuario": "admin",
      "correo_electronico": "admin@inecolara.gob.ve",
      "rol_id": 1,
      "nombre_rol": "Administrador",
      "estatus": "Activo",
      "nombre_trabajador": null,
      "apellido_trabajador": null,
      "cargo": null
    }
  ]
}
```

**Respuesta real de `buscar_clientes`**

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

Todos los catálogos devuelven `success: true` y no aplican permisos distintos
de la sesión.

---

### `POST /ampliacion?action=add_ajax`

| Campo | Valor |
|---|---|
| Permiso | `ampliacion:crear` |
| Content-Type | `application/x-www-form-urlencoded` o `application/json` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `id_cliente` | int | No | Cliente asociado; `0` o vacío → `NULL` |
| `id_usuario_gestor` | int | Sí | Usuario que registra el movimiento |
| `fecha_movimiento` | date | No validado | `YYYY-MM-DD`; el frontend siempre lo envía |
| `observacion` | string | No | Vacío → `NULL` |
| `salida_items` | JSON string | Condicional | Al menos una salida o una entrada |
| `entrada_items` | JSON string | Condicional | Al menos una salida o una entrada |

Cada salida: `id_lote` > 0 y `cantidad` > 0.

Cada entrada:

| Campo | Obligatorio | Descripción |
|---|---|---|
| `id_planta` | Sí, salvo `nueva_planta_nombre` | Planta existente |
| `id_ubicacion` | Sí | Ubicación destino |
| `cantidad` | Sí | Entero > 0 |
| `nueva_planta_nombre` | Condicional | Crea la planta si no hay `id_planta` |
| `nueva_planta_tecnico` | No | Nombre técnico de la planta nueva |
| `nueva_planta_id_especie` | Condicional | Obligatorio si se crea planta |

**Request (form)**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  --data-urlencode "id_cliente=1" \
  --data-urlencode "id_usuario_gestor=1" \
  --data-urlencode "fecha_movimiento=2026-11-23" \
  --data-urlencode "observacion=Ampliacion Doc" \
  --data-urlencode 'salida_items=[{"id_lote":34,"cantidad":2}]' \
  --data-urlencode "entrada_items=[]" \
  "http://localhost/sys-inescolara/ampliacion?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Ampliación de especies registrada correctamente",
  "id": 6
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `ampliacion:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | Sin gestor | `El trabajador gestor es requerido.` |
| `500` | Sin items válidos | `Debe agregar al menos un item de salida o entrada.` |
| `500` | Salida incompleta | `Complete correctamente todos los items de salida.` |
| `500` | Entrada incompleta | `Complete correctamente todos los items de entrada.` |
| `500` | Entrada sin planta | `Seleccione una planta existente o agregue una nueva.` |
| `500` | Planta nueva sin especie | `Debe seleccionar una especie para la nueva planta '…'.` |
| `500` | Salida sin stock | `Stock insuficiente en el lote seleccionado para salida.` |
| `500` | Ningún item procesado | `No se registró ningún item. Verifique los datos.` |

**Efectos**

- Inserta `movimiento_planta` con `tipo_movimiento = 'intercambio'`.
- Cada salida descuenta `lote.cantidad_actual` con guardia de stock.
- Cada entrada busca un lote activo de la misma planta y ubicación; si existe,
  lo incrementa; si no, crea un lote con estado `vivo` y origen `Ampliación`.
- Si la entrada trae `nueva_planta_nombre` sin `id_planta`, crea la planta.
- Todo transaccional; los items inválidos se descartan y, si no queda ninguno,
  revierte.
- Auditoría `CREATE` sobre `movimiento_planta`.

---

### `POST /ampliacion?action=edit_ajax`

Mismos parámetros que `add_ajax`, más `id`.

**Respuesta 200**

```json
{ "success": true, "message": "Ampliación actualizada correctamente" }
```

**Efectos**

1. Revierte el stock de todos los detalles anteriores (suma las salidas y
   resta las entradas).
2. Desactiva los detalles antiguos.
3. Actualiza la cabecera.
4. Inserta y aplica los nuevos items.

Todo transaccional. Si al revertir una entrada no hay stock suficiente, la
edición falla completa con `Stock insuficiente para revertir entrada del lote
#<id>.`

**Errores:** `403`, `500 ID inválido`, `500 No existe la ampliación`,
validaciones de `add_ajax`.

---

### `POST /ampliacion?action=delete_ajax`

| Campo | Valor |
|---|---|
| Permiso | `ampliacion:eliminar` |
| Parámetros | `id` |

**Respuesta 200**

```json
{ "success": true, "message": "Ampliación desactivada correctamente" }
```

**Errores:** `403`, `500 ID inválido`, `500 No existe la ampliación`.

**Efectos:** `movimiento_planta.activo = 0` y
`movimiento_planta_detalle.activo = 0`, en una transacción. Auditoría
`DEACTIVATE`.

> **No revierte el stock.** Las salidas descontadas y las entradas sumadas
> siguen aplicadas en `lote`. Es deuda conocida: eliminar un intercambio no
> devuelve el inventario a su estado anterior.

---

## Deuda detectada

- Eliminar una ampliación no restaura el stock de sus detalles.
- `get_detail` no filtra por `activo`; después de eliminar sigue devolviendo el
  registro con `activo: 0` y `detalles: []` (verificado).
- `restore()` existe en el modelo pero no hay endpoint que lo exponga.
- La edición revierte y reaplica stock, pero no audita el detalle anterior.
- `getAll` arma el nombre del gestor con `nombre_usuario` + `apellido_trabajador`;
  para usuarios sin apellido de trabajador el nombre queda incompleto.
- Los errores de negocio se devuelven como `500`.
- `get_gestores` incluye usuarios sin datos de trabajador; `ventas/trabajadores`
  usa el criterio contrario.
- No se valida que la fecha sea real ni que no sea futura.

## Casos Postman

`pruebas/collections/comercial/Ampliacion.postman_collection.json`

Crea un lote de 10 ejemplares, registra una salida de 2, verifica el stock (8),
edita a salida de 3 y verifica el stock (7), elimina la ampliación y el lote.
Incluye `401/403/400/500`.
