# Módulo Reportes

Modelo: `app/models/Reports.php` · Controlador:
`app/controllers/ReportsController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/reports`) | Solo sesión |
| Módulos / filtros / datos | Solo sesión |
| PDF | Solo sesión |

Endpoints (`?action=`): `get_modules`, `get_filters&module=`,
`get_report_data&module=`, `generate_pdf&module=`.

> La autenticación es `reports_checkAuth()`: sin sesión redirige a `/login`
> (no devuelve JSON 401).

### `GET /reports?action=get_modules`

```json
{
  "success": true,
  "modules": [
    { "id": "plantas", "nombre": "Plantas", "icono": "fa-seedling", "has_chart": true },
    { "id": "ventas", "nombre": "Ventas", "icono": "fa-shopping-cart", "has_chart": true }
  ]
}
```

### `GET /reports?action=get_filters&module=plantas`

```json
{
  "success": true,
  "filters": [
    { "field": "activo", "label": "Estado", "type": "select", "column": "p.activo", "options": [ { "value": "1", "label": "Activo" } ] },
    { "field": "nombre_comun", "label": "Nombre común", "type": "text", "column": "p.nombre_comun" }
  ]
}
```

`400 Módulo no especificado` si falta `module`.

### `GET /reports?action=get_report_data&module=plantas`

```json
{
  "success": true,
  "columns": ["ID", "Nombre Común", "Nombre Técnico", "Especie", "Stock en Lotes"],
  "keys": ["id_planta", "nombre_comun", "nombre_tecnico", "especie", "stock_lotes"],
  "rows": [ { "id_planta": 1, "nombre_comun": "Rosa Base", "stock_lotes": "292" } ],
  "chart": { "type": "bar", "labels": ["Rosa"], "values": [294], "label": "Stock x Especie" },
  "module": "plantas"
}
```

Los filtros se pasan como query params (`fecha_desde`, `fecha_hasta`, o campos
específicos).

### `GET /reports?action=generate_pdf&module=plantas`

Devuelve `application/pdf` con `Content-Disposition: inline`.

## Deuda detectada

- Sin validación de permisos por módulo de reporte (solo sesión).
- `generate_pdf` y `get_report_data` no capturan excepciones de `Reports` como
  JSON en todos los casos.

## Casos Postman

`pruebas/collections/sistema/Reports.postman_collection.json`
Módulos, filtros, datos y PDF de `plantas`, más negativos `400`.
