# Módulo Respaldos

Archivos SQL en `_backups/` · Modelo: `app/models/Backup.php` · Controlador:
`app/controllers/BackupsController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/backups`) | Solo sesión |
| Listar | Solo sesión |
| Crear | `backups:crear` |
| Restaurar / eliminar | `backups:eliminar` |
| Descargar | `backups:crear` (ruta no-AJAX) |

Endpoints (`?action=`): `get_backups`, `create_backup`, `restore_backup`,
`delete_backup`; descarga vía `GET /backups?action=download_backup&file=…`
(sin `X-Requested-With`).

### `GET /backups?action=get_backups`

```json
{
  "success": true,
  "backups": [
    { "filename": "Datos_sysinescolara_20261124_205541.sql", "filepath": "C:\\xampp\\...", "db_label": "Datos_sysinescolara", "size": 52499, "size_formatted": "51.27 KB" }
  ],
  "count": 38
}
```

### `POST /backups?action=create_backup`

Sin parámetros. Genera un archivo por cada base configurada.

```json
{
  "success": true,
  "message": "Respaldo completo creado exitosamente.",
  "files": ["Datos_sysinescolara_20261124_205541.sql", "Seguridad_SysInescolara-Seguridad_20261124_205542.sql"],
  "total_size": 366485
}
```

### `POST /backups?action=restore_backup`

`filename`. Devuelve el resultado del modelo `{ success, message }`.

### `POST /backups?action=delete_backup`

`filename`.

```json
{ "success": true, "message": "Respaldo eliminado correctamente." }
```

`400 Nombre de archivo requerido.` si falta `filename`.

## Deuda detectada

- `restore_backup` puede dejar la BD en un estado inconsistente si el archivo es
  inválido; no hay confirmación transaccional.
- La descarga no se sirve como JSON (binario, requiere quitar el header AJAX).

## Casos Postman

`pruebas/collections/sistema/Backups.postman_collection.json`
Lista, crea un respaldo completo, verifica los archivos y los elimina. Incluye
negativos `401/403/400`.
