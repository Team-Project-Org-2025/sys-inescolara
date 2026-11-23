# Módulo Inventario

Vista consolidada de existencias: plantas, lotes, insumos y herramientas en una
sola respuesta. Es de solo lectura; no modifica nada.

Tablas: `plantas`, `lote`, `insumo`, `herramienta` · Modelo:
`app/models/Inventory.php` · Controlador: `app/controllers/InventarioController.php`
· JS: `public/assets/js/dashboard/inventario.js`

| Operación | Permiso |
|---|---|
| Ver vista | `inventario:ver` |
| Consolidado (AJAX) | Solo sesión |

Endpoints:

1. `GET /dashboard/inventario`
2. `GET /inventario`
3. `GET /inventario?action=get_consolidated`

---

### `GET /dashboard/inventario`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | `inventario:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /inventario`

Vista servida por el controlador. Solo exige sesión.

---

### `GET /inventario?action=get_consolidated`

**Descripción:** une plantas, lotes, insumos y herramientas activos en una lista
heterogénea.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/inventario?action=get_consolidated` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/inventario?action=get_consolidated"
```

**Respuesta 200**

```json
{
  "success": true,
  "data": [
    {
      "id": "HERRAMIENTA_3",
      "nombre": "Herramienta Demo",
      "tipo": "Herramienta",
      "stock": null,
      "unidad": null,
      "ubicacion": null,
      "precio": null,
      "item_id": 3
    },
    {
      "id": "INSUMO_3",
      "nombre": "Insumo Demo",
      "tipo": "Insumo",
      "stock": "5.00",
      "unidad": "Unidad",
      "ubicacion": null,
      "precio": "1.25",
      "item_id": 3
    },
    {
      "id": "LOTE_1",
      "nombre": "Rosa Base (Lote #1)",
      "tipo": "Lote",
      "stock": "7.00",
      "unidad": "unidades",
      "ubicacion": "Vivero Central",
      "precio": "5.00",
      "item_id": 1
    },
    {
      "id": "PLANTA_1",
      "nombre": "Rosa Base",
      "tipo": "Planta",
      "stock": "7.00",
      "unidad": "unidades",
      "ubicacion": null,
      "precio": "5.00",
      "item_id": 1
    }
  ],
  "count": 4
}
```

**Notas**

- El arreglo se llama `data` y **no** es DataTables: no trae `recordsTotal` ni
  `draw`.
- `id` es un identificador compuesto (`TIPO_item_id`), no la PK real; la PK va
  en `item_id`.
- `stock` y `precio` son string decimal para planta, lote e insumo, y `null`
  para herramientas.
- El stock de la planta se calcula sumando `cantidad_actual` de sus lotes
  activos; el precio de la planta es el del lote más reciente.
- El orden lo define el `UNION` del modelo, no un `ORDER BY` explícito.

**Errores**

| Código | Cuándo |
|---|---|
| `401` | Sin sesión |
| `500` | Error SQL |

---

## Deuda detectada

- No hay filtros ni paginación: devuelve todo el inventario.
- La herramienta no reporta stock, aunque tiene columna `cantidad`.
- Los importes mezclan string y `null` según el tipo.
- La vista `/inventario` no valida `inventario:ver`.

## Casos Postman

`pruebas/collections/inventario/Inventario.postman_collection.json`
