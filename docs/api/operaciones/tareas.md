# Módulo Tareas

Tablas: `asignar_tarea`, `uso_herramienta`, `registro_insumo` · Modelo:
`app/models/Tarea.php` · Controlador: `app/controllers/TareasController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/tareas`) | Solo sesión |
| Listar / detalle / catálogos | Solo sesión |
| Asignar | `tareas:crear` |
| Editar / completar | `tareas:editar` |
| Cancelar | `tareas:eliminar` |

Endpoints (`?action=`): `get_assignments`, `get_assignment&id=`,
`get_tools_refresh`, `get_insumos_refresh`, `assign_ajax`, `edit_ajax`,
`complete_ajax`, `cancel_ajax`.

### `GET /tareas?action=get_assignments`

```json
{
  "success": true,
  "assignments": [
    { "id_asignacion": 1, "nombre_tarea": "Tarea Postman", "fecha_asignacion": "2026-11-24", "estatus_tarea": "pendiente", "nombre_usuario": "admin" }
  ],
  "count": 1
}
```

No filtra: devuelve todas las asignaciones (incluso canceladas).

### `GET /tareas?action=get_assignment&id=<id>`

```json
{
  "success": true,
  "assignment": { "id_asignacion": 1, "nombre_tarea": "Tarea Postman", "estatus_tarea": "pendiente" },
  "consumptions": [],
  "tool_usages": []
}
```

`400 ID inválido`, `404 Asignación no encontrada`.

### `POST /tareas?action=assign_ajax`

**Parámetros** (`getRequestData()`): `nombre_tarea` (obligatorio), `descripcion`,
`id_usuario` (obligatorio), `fecha_asignacion` (default hoy), `tools` (array).

```json
{ "success": true, "message": "Tarea asignada correctamente", "id_asignacion": 1, "herramientas": [ ... ] }
```

Efecto: inserta en `asignar_tarea` (`estatus_tarea = pendiente`), crea una
notificación al usuario y devuelve el listado fresco de herramientas. Si se
envían herramientas valida disponibilidad.

### `POST /tareas?action=edit_ajax`

Mismos campos más `id_asignacion`. Borra y reinserta `uso_herramienta`.

### `POST /tareas?action=complete_ajax`

`id`, `fecha_cumplimiento`, `horas_dedicadas`, `consumptions` (array),
`tool_estados` (array).

```json
{ "success": true, "message": "Tarea completada correctamente" }
```

Efecto: `estatus_tarea = completada`, registra consumos de insumo (desc cuenta
stock) y estados de herramientas.

> **Bug:** si se envían `consumptions`/`tool_estados` como `application/x-www-
> form-urlencoded`, `getRequestData()` devuelve string y `completeAssignment()`
> lanza un `TypeError` (`Argument #5 … must be of type array`). Con form hay que
> omitir ambos campos; para consumos reales usar `application/json`.

### `POST /tareas?action=cancel_ajax`

`id`. Marca `estatus_tarea = cancelada` (funciona incluso tras completar).

### Catálogos

`get_tools_refresh` → `{ success, tools: [...] }` (con `disponibles`).
`get_insumos_refresh` → `{ success, insumos: [...] }`.

## Deuda detectada

- `get_assignments` no pagina ni filtra estado.
- `complete_ajax` revienta con form-urlencoded si se pasan los arrays.
- `cancel_ajax` no valida que la tarea esté pendiente.
- No hay endpoint para eliminar una tarea (solo cancelar).

## Casos Postman

`pruebas/collections/operaciones/Tareas.postman_collection.json`
Asigna, consulta, edita, completa (omitiendo arrays), cancela y verifica.
Incluye catálogos y negativos `401/403/400`.
