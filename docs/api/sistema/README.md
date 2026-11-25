# Sistema · índice

Administración y configuración del sistema.

| Módulo | Ruta | Ficha | Colección |
|---|---|---|---|
| Usuarios | `/usuarios` | [`usuarios.md`](usuarios.md) | `Usuarios.postman_collection.json` |
| Roles | `/roles` | [`roles.md`](roles.md) | `Roles.postman_collection.json` |
| Auditoría | `/auditlog` | [`auditlog.md`](auditlog.md) | `Auditlog.postman_collection.json` |
| Respaldos | `/backups` | [`backups.md`](backups.md) | `Backups.postman_collection.json` |
| Reportes | `/reports` | [`reports.md`](reports.md) | `Reports.postman_collection.json` |
| Notificaciones | `/notifications/*` | [`notifications.md`](notifications.md) | `Notifications.postman_collection.json` |
| Dashboard (vistas) | `/dashboard/*`, `/inicio` | [`dashboard.md`](dashboard.md) | `Dashboard.postman_collection.json` |

## Particularidades

- Usuarios y Roles usan `?action=`. Notificaciones usa rutas por segmento
  (`/notifications/<accion>`). Reportes usa `?action=`, excepto
  `generate_pdf` que responde binario.
- **`add_ajax` de Usuarios y Roles devuelve el id de auditoría**, no el del
  registro creado, por leer `lastInsertId()` después de escribir el log.
- Los CRUD de Usuarios/Roles escriben en la base de seguridad
  (`SysInescolara-Seguridad`).
