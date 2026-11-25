# Módulo Auditoría

Tabla: `SysInescolara-Seguridad.auditoria_logs` · Modelo:
`app/models/AuditLog.php` · Controlador: `app/controllers/AuditlogController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/auditlog`) | Solo sesión |
| Listar | `auditlog:ver` |

Endpoint: `GET /auditlog?action=get_auditlogs`.

### `GET /auditlog?action=get_auditlogs`

```json
{
  "success": true,
  "auditlogs": [
    {
      "id_log": 1079,
      "id_usuario": 1,
      "accion": "UPDATE",
      "tabla_afectada": "recoleccion_semillas",
      "id_registro_afectado": 1,
      "valor_anterior": "{...}",
      "valor_nuevo": "{...}",
      "endpoint_solicitado": "/sys-inescolara/recoleccion?action=edit_ajax",
      "fecha_accion": "2026-11-24 20:54:43",
      "nombre_usuario": "admin"
    }
  ],
  "count": 1
}
```

Solo lectura; no hay filtros ni paginación. `403` si el rol no tiene
`auditlog:ver`.

## Casos Postman

`pruebas/collections/sistema/Auditlog.postman_collection.json`
Listado + negativos `401/403/400`.
