# Módulo Usuarios

Tabla: `SysInescolara-Seguridad.usuarios` · Modelo: `app/models/Usuario.php` ·
Controlador: `app/controllers/UsuariosController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/usuarios`) | Solo sesión |
| Listar | Solo sesión |
| Crear / editar / eliminar | `usuarios:ver` (reutiliza `ver` para escritura) |

Endpoints (`?action=`): `get_users`, `add_ajax`, `edit_ajax`, `delete_ajax`.

### `GET /usuarios?action=get_users`

```json
{
  "success": true,
  "users": [
    { "id": 1, "nombre_usuario": "admin", "correo_electronico": "admin@inecolara.gob.ve", "rol_id": 1, "nombre_rol": "Administrador", "estatus": "Activo", "permisos": [] }
  ],
  "count": 1
}
```

- Para `rol_id !== 1` incluye `permisos`.

### `POST /usuarios?action=add_ajax`

**Parámetros:** `nombre_usuario`, `password` (obligatorio en add, 8-30 con
mayúscula/minúscula/número/símbolo), `rol_id` (default 1), `correo_electronico`,
`permisos[]` (opcional, `modulo:permiso`), `avatar` (multipart).

**Respuesta 200**

```json
{ "success": true, "message": "Usuario agregado", "user": { "id": 1084, "nombre_usuario": "postman_user1", "rol_id": 2, "permisos": [] } }
```

> **Bug:** el `id` devuelto es el de la auditoría (`1084`), no el del usuario
> (`7`). Para editar/eliminar hay que buscar el id real en `get_users`.

**Errores:** `403` sin permiso; `500` nombre/password/rol/email inválidos; `500`
nombre duplicado.

### `POST /usuarios?action=edit_ajax`

Mismos campos más `id`. Si se cambia la contraseña de la cuenta propia (o un
admin), exige `current_password`.

### `POST /usuarios?action=delete_ajax`

`id` + `current_password` (la del usuario logueado). No permite eliminar el
superusuario `id = 1`.

```json
{ "success": true, "message": "Usuario desactivado", "userId": 7 }
```

Errores: `400` superusuario o sin contraseña; `403` contraseña incorrecta;
`500` si tiene registros asociados.

## Deuda detectada

- `add_ajax` devuelve un id incorrecto.
- Los permisos de escritura reutilizan `usuarios:ver`.
- No existe validación de duplicado de correo.

## Casos Postman

`pruebas/collections/sistema/Usuarios.postman_collection.json`
Crea (con nombre aleatorio), localiza el id real en el listado, edita y elimina.
Incluye negativos `401/403/400`.
