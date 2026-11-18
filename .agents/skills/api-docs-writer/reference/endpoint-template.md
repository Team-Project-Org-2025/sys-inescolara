# Plantilla de ficha de endpoint

Copia este bloque por cada acción documentada.

````markdown
### `POST /plantas?action=add_ajax`

**Descripción:** crea una planta y registra la acción en auditoría.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/plantas?action=add_ajax` |
| Autenticación | Sesión (`checkModuleAuth`) |
| Permiso | `plantas:crear` |
| Content-Type | `multipart/form-data` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción | Ejemplo |
|---|---|---|---|---|
| `nombre_comun` | string | Sí | Nombre común de la planta | `Rosa canaria` |
| `especie_id` | int | No | FK a `especie` | `1` |
| `imagen` | file | No | Imagen JPG/PNG/WebP | `rosa.jpg` |

**Request**

```bash
curl -X POST "http://localhost/sys-inescolara/plantas?action=add_ajax" \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -F "nombre_comun=Rosa canaria" \
  -F "especie_id=1"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Planta agregada correctamente.",
  "id": 42
}
```

**Errores**

| Código | Cuándo | Cuerpo |
|---|---|---|
| `400` | Acción o método inválido | `{"success":false,"message":"Acción AJAX inválida"}` |
| `401` | Sin sesión | `{"success":false,"message":"No autorizado","redirect":"..."}` |
| `403` | Sin permiso | `{"success":false,"message":"No tienes permiso para realizar esta acción."}` |
| `500` | Validación o SQL | `{"success":false,"message":"El nombre común es requerido."}` |

**Efectos secundarios**

- Inserta en `plantas` (borrado lógico vía `activo`).
- `AuditLog::record('CREATE', 'plantas', <id>, null, <nuevo>)`.
- Sube la imagen a `public/assets/uploads/plants/`.

**Notas**

- El envelope de este endpoint (`id` en la raíz) difiere del de otros módulos
  que devuelven `data.id`.
- No valida CSRF.

**Casos Postman:** `02 · Crear` en
`pruebas/collections/inventario/Plantas.postman_collection.json`.
````

## Variantes

**Acción por segmento** (`/notifications/get_unread`): la ruta no lleva query
string; el método HTTP y el segmento identifican la acción.

**Ventas**: usa `?accion=` en lugar de `?action=`.

**Respuesta DataTables**: sustituye el bloque de respuesta por la estructura
real (`data`, `recordsTotal`, etc.) y documenta el parámetro `draw` si aplica.

**Descarga de archivo** (`?action=comprobante`, `?action=download_backup`):
documenta `Content-Type: application/pdf` o `application/octet-stream` y el
`Content-Disposition`, no un JSON.
