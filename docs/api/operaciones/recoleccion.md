# Módulo Recolección

Tablas: `recoleccion_semillas`, `recoleccion_semillas_detalle`, `insumo` ·
Modelo: `app/models/SeedCollection.php` · Controlador:
`app/controllers/RecoleccionController.php`.

| Operación | Permiso |
|---|---|
| Ver vista | Solo sesión (redirige a `/dashboard/seed-collection`) |
| Listar / detalle | Solo sesión |
| Crear | `seed_collection:crear` |
| Editar / completar / registrar insumo | `seed_collection:editar` |
| Eliminar | `seed_collection:eliminar` |

> Sin `X-Requested-With` (o sin `action`) redirige a
> `/dashboard/seed-collection`.

Endpoints (`?action=`): `get_recolecciones`, `get_details&id=`, `add_ajax`,
`edit_ajax`, `delete_ajax`, `completar_ajax`, `registrar_insumo_ajax`,
`registrar_y_completar_ajax`.

### `GET /recoleccion?action=get_recolecciones`

```json
{
  "success": true,
  "recolecciones": [
    { "id": 1, "id_usuario": 1, "id_ubicacion": 1, "fecha_asignacion": "2026-11-24", "estatus": "Pendiente", "total_detalles": 0, "nombre_ubicacion": "Vivero Central", "trabajador_nombre": "admin" }
  ]
}
```

### `GET /recoleccion?action=get_details&id=<id>`

```json
{
  "success": true,
  "recoleccion": { "id_recoleccion": 1, "estatus": "Realizada", "fecha_recoleccion": "2026-11-24" },
  "detalles": [
    { "id_recoleccion_detalle": 1, "nombre_semilla": "Semilla Postman", "id_unidad_medida": 1, "id_insumo": 12, "cantidad": "5.00", "insumo_nombre": "Semilla Postman" }
  ]
}
```

`400 ID inválido`, `404 No existe la recolección`.

### `POST /recoleccion?action=add_ajax`

`id_usuario`, `id_ubicacion`, `fecha_asignacion` (`YYYY-MM-DD`), `observacion`.

```json
{ "success": true, "message": "Recolección registrada correctamente", "id": 1 }
```

### `POST /recoleccion?action=edit_ajax`

Mismos campos más `id`.

### `POST /recoleccion?action=completar_ajax`

`id`, `fecha_recoleccion` (default hoy). Solo `Pendiente` → `Realizada`.

### `POST /recoleccion?action=registrar_insumo_ajax`

Requiere recolección `Realizada`. `items` (JSON string) con:
`id_insumo` (existente) **o** `nombre_insumo` + `id_unidad_medida`, más
`cantidad` (> 0).

```json
{ "success": true, "message": "1 tipo(s) de semilla registrado(s) correctamente" }
```

Efecto: crea o incrementa el insumo (categoría `Semillas`) y registra el
detalle.

### `POST /recoleccion?action=registrar_y_completar_ajax`

Igual que `registrar_insumo_ajax` pero además completa la recolección si está
`Pendiente`.

### `POST /recoleccion?action=delete_ajax`

Desactiva la recolección (lógico). Si hay registros asociados, devuelve
`foreign_key`. **No elimina los detalles** ni revierte los insumos creados.

## Deuda detectada

- `delete_ajax` no limpia `recoleccion_semillas_detalle` ni revierte insumos.
- Los insumos creados por recolección quedan activos.
- Errores de negocio como `500`.

## Casos Postman

`pruebas/collections/operaciones/Recoleccion.postman_collection.json`
Crea, edita, completa, registra insumo y usa `registrar_y_completar` en una
segunda recolección; luego elimina ambas. Incluye negativos `401/403/400/500`.
