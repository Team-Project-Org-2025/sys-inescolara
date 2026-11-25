# Módulo Notificaciones

Tabla: `notificaciones` (base principal) · Modelo:
`app/models/Notification.php` · Controlador:
`app/controllers/NotificationsController.php`.

> Ruta **por segmento**: `/notifications/<accion>` (no usa `?action=`). No exige
> `X-Requested-With`; sin sesión, `Auth::id()` es 0 y responde
> `{success:false,count:0}` en lugar de 401.

Endpoints: `get_unread`, `mark_read`, `mark_all_read`, `delete_notification`,
`create_notification`.

### `GET /notifications/get_unread`

```json
{
  "success": true,
  "count": 5,
  "notifications": [
    { "id_notificacion": 3, "titulo": "Stock bajo: Rosa Base", "mensaje": "Solo quedan 3 unidades", "tipo": "warning", "leida": 0, "link": "dashboard/inventario", "fecha_creacion": "2026-11-24 20:54:51" }
  ]
}
```

**Efecto secundario:** genera avisos de stock bajo (lotes e insumos) si no
existen y marca como leídas las alertas `warning` previas. Por eso `count`
cambia entre corridas.

### `POST /notifications/create_notification`

`user_id`, `titulo` (obligatorios), `mensaje`, `tipo` (default `info`), `link`.

```json
{ "success": true, "message": "Notificación creada" }
```

### `POST /notifications/mark_read`

`id` (de la notificación del usuario).

```json
{ "success": true }
```

### `POST /notifications/mark_all_read`

Sin parámetros. `{ "success": true }`.

### `POST /notifications/delete_notification`

`id`. `{ "success": true }`.

## Deuda detectada

- No hay control de permisos (cualquier sesión puede crear/borrar sus
  notificaciones y las ajenas si conoce el id).
- `get_unread` muta estado (crea y marca leídas notificaciones de stock).

## Casos Postman

`pruebas/collections/sistema/Notifications.postman_collection.json`
Crea, lista, marca leída, elimina y marca todas. Incluye negativo de creación
sin `user_id`.
