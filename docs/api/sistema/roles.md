# Módulo Roles

Tabla: `SysInescolara-Seguridad.roles` · Modelo: `app/models/Role.php` ·
Controlador: `app/controllers/RolesController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/roles`) | Solo sesión |
| Listar | Solo sesión |
| Crear | `roles:crear` |
| Editar | `roles:editar` |
| Eliminar | `roles:eliminar` |

Endpoints (`?action=`): `get_roles`, `add_ajax`, `edit_ajax`, `delete_ajax`.

### `GET /roles?action=get_roles`

```json
{
  "success": true,
  "roles": [
    { "id": 1, "nombre_rol": "Administrador", "descripcion_rol": "Acceso total al sistema" },
    { "id": 2, "nombre_rol": "Trabajador", "descripcion_rol": "Acceso a inventario, plantas, clientes y ventas" },
    { "id": 3, "nombre_rol": "Prueba", "descripcion_rol": null }
  ],
  "count": 3
}
```

### `POST /roles?action=add_ajax`

`nombre_rol` (solo letras/espacios), `descripcion_rol`.

```json
{ "success": true, "message": "Rol creado correctamente", "role": { "id": 1086, "nombre_rol": "Rol Postman" } }
```

> **Bug:** el `id` devuelto es el de la auditoría, no el del rol creado.

**Errores:** `500` nombre duplicado, `500` nombre inválido.

### `POST /roles?action=edit_ajax`

Mismos campos más `id`. No permite editar el rol `id = 1` (Administrador).

### `POST /roles?action=delete_ajax`

No permite eliminar roles `id <= 2`.

```json
{ "success": true, "message": "Rol desactivado correctamente", "roleId": 4 }
```

## Deuda detectada

- `add_ajax` devuelve id incorrecto.
- Sin endpoint para gestionar permisos del rol (solo nombre/descripción).

## Casos Postman

`pruebas/collections/sistema/Roles.postman_collection.json`
Crea (nombre aleatorio solo letras), localiza id real, edita y elimina. Incluye
negativos `401/403/500`.
