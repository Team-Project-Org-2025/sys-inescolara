# Módulo Proveedores

Tabla: `proveedores` · Modelo: `app/models/Proveedor.php` · Controlador:
`app/controllers/ProveedoresController.php`.

| Operación | Permiso |
|---|---|
| Ver vista (`/proveedores`) | Solo sesión |
| Listar | Solo sesión |
| Crear | `proveedores:crear` |
| Editar | `proveedores:editar` |
| Eliminar (lógico) | `proveedores:eliminar` |

Endpoints: `GET /proveedores?action=get_suppliers`, `POST …/add_ajax`,
`POST …/edit_ajax`, `POST …/delete_ajax`.

### `GET /proveedores?action=get_suppliers`

```json
{
  "success": true,
  "suppliers": [
    {
      "id": 1,
      "nombre_proveedor": "Proveedor Prueba",
      "rif_proveedor": "J-00000000-0",
      "contacto_vendedor": "Contacto Prueba",
      "telefono_proveedor": "0212-0000000",
      "activo": 1
    }
  ],
  "count": 0
}
```

- El arreglo se llama `suppliers`.
- `count` viene **fijo en 0** (deuda).
- Solo lista `activo = 1`.

### `POST /proveedores?action=add_ajax`

**Parámetros** (form-urlencoded): `nombre_proveedor` (obligatorio, solo letras y
espacios 2-50), `rif_proveedor` (formato `J-00000000-0`), `contacto_vendedor`
(solo letras), `telefono_proveedor` (11 dígitos).

**Respuesta 200**

```json
{
  "success": true,
  "message": "Proveedor agregado correctamente",
  "proveedor": {
    "id": 4,
    "nombre_proveedor": "Proveedor Postman",
    "rif_proveedor": "J-55555555-5",
    "contacto_vendedor": "Contacto Postman",
    "telefono_proveedor": "04141234567"
  }
}
```

**Errores:** `403` sin `proveedores:crear`; `500` nombre vacío
(`El nombre del proveedor es requerido.`); `500` RIF inválido; `500` RIF
duplicado. Si el RIF existe pero está inactivo, la BD lanza
`SQLSTATE[23000] … Duplicate entry` por la restricción única (no se reutiliza).

### `POST /proveedores?action=edit_ajax`

Mismos parámetros más `id`.

```json
{ "success": true, "message": "Proveedor actualizado correctamente", "proveedor": { "id": 4, "nombre_proveedor": "Proveedor Postman Editado", "rif_proveedor": "J-55555555-5", "contacto_vendedor": "Contacto Postman", "telefono_proveedor": "04141234567" } }
```

### `POST /proveedores?action=delete_ajax`

```json
{ "success": true, "message": "Proveedor desactivado correctamente", "supplierId": 4 }
```

Efecto: `UPDATE proveedores SET activo = 0`.

## Deuda detectada

- `count` fijo en 0.
- El RIF de un proveedor inactivo no se puede reutilizar (restricción única).
- Los errores de validación llegan como `500` y sin indicar el campo.
- `exists()` no filtra por `activo`, por lo que eliminar dos veces responde
  éxito.

## Casos Postman

`pruebas/collections/compras/Proveedores.postman_collection.json`
Genera un RIF aleatorio por corrida, crea, edita, verifica y elimina el
proveedor; incluye `401/403/400/500`.
