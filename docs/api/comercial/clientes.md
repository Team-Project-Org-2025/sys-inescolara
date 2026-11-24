# Módulo Clientes

Personas o entidades que compran plantas e insumos. Es la base de Ventas,
Ornatos y Cuentas por cobrar.

Tabla: `cliente` · Modelo: `app/models/Cliente.php` · Controlador:
`app/controllers/ClientesController.php` · JS:
`public/assets/js/dashboard/clientes.js`

| Operación | Permiso |
|---|---|
| Ver vista (`/dashboard/clientes`) | `clientes:ver` |
| Ver vista (`/clientes`) | Solo sesión |
| Listar | Solo sesión |
| Crear | `clientes:crear` |
| Editar | `clientes:editar` |
| Eliminar (lógico) | `clientes:eliminar` |

Endpoints:

1. `GET /dashboard/clientes`
2. `GET /clientes`
3. `GET /clientes?action=get_clients`
4. `POST /clientes?action=add_ajax`
5. `POST /clientes?action=edit_ajax`
6. `POST /clientes?action=delete_ajax`

---

### `GET /dashboard/clientes`

| Campo | Valor |
|---|---|
| Autenticación | Sesión |
| Permiso | `clientes:ver` |
| Content-Type | `text/html` |

`200` con permiso · `302` sin sesión · `403` sin permiso.

---

### `GET /clientes`

Vista servida por `ClientesController::index()`. A diferencia de
`/dashboard/clientes`, solo exige sesión: no valida `clientes:ver`.

---

### `GET /clientes?action=get_clients`

| Campo | Valor |
|---|---|
| Método | `GET` |
| Autenticación | Sesión |
| Permiso | Solo sesión |
| Content-Type | JSON |

**Request**

```bash
curl -b cookies.txt -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost/sys-inescolara/clientes?action=get_clients"
```

**Respuesta 200**

```json
{
  "success": true,
  "clientes": [
    {
      "id": 1,
      "tipo_cedula_cliente": "V",
      "cedula_cliente": "12345678",
      "cedula_completa": "V-12345678",
      "nombre_cliente": "Cliente",
      "apellido_cliente": "Prueba",
      "nombre_completo": "Cliente Prueba",
      "contacto_cliente": "0414-0000000",
      "activo": 1
    }
  ],
  "count": 0
}
```

**Notas**

- El arreglo se llama `clientes`.
- `count` viene **siempre en 0**: el controlador lo escribe literalmente. No
  sirve como total (deuda conocida).
- `nombre_completo` y `cedula_completa` los calcula el modelo.
- Solo lista `activo = 1`.

---

### `POST /clientes?action=add_ajax`

| Campo | Valor |
|---|---|
| Permiso | `clientes:crear` |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Validación |
|---|---|---|---|
| `nombre_cliente` | string | Sí | Solo letras y espacios, 2-50 |
| `apellido_cliente` | string | No | Solo letras y espacios, 2-50 |
| `tipo_cedula_cliente` | string | No | `V`, `E`, `J`, `G` o `P` |
| `cedula_cliente` | string | No | 7-10 dígitos (V/E: 7-8; J/G/P: 9-10) |
| `contacto_cliente` | string | No | Sin validación de formato |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "nombre_cliente=Cliente Postman" \
  -d "apellido_cliente=Comercial" \
  -d "tipo_cedula_cliente=V" \
  -d "cedula_cliente=87654321" \
  -d "contacto_cliente=0414-1234567" \
  "http://localhost/sys-inescolara/clientes?action=add_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Cliente agregado correctamente",
  "client": {
    "id": 2,
    "nombre_cliente": "Cliente Postman",
    "apellido_cliente": "Comercial",
    "nombre_completo": "Cliente Postman Comercial",
    "tipo_cedula_cliente": "V",
    "cedula_cliente": "87654321",
    "cedula_completa": "V-87654321",
    "contacto_cliente": "0414-1234567"
  }
}
```

**Errores**

| Código | Cuándo | Mensaje |
|---|---|---|
| `403` | Sin `clientes:crear` | `No tienes permiso para realizar esta acción.` |
| `500` | Nombre vacío | `El nombre del cliente es requerido.` |
| `500` | Formato inválido | `Debe contener solo letras (2-50 caracteres)` (sin indicar el campo) |
| `500` | Cédula inválida | `Número de C.I. inválido (V/E: 7-8 dígitos, J/G: 9-10 dígitos)` |

**Efectos:** inserta en `cliente` con `activo = 1` y auditoría `CREATE`.

---

### `POST /clientes?action=edit_ajax`

Mismos parámetros que `add_ajax`, más `id`.

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  -d "nombre_cliente=Cliente Postman Editado" \
  -d "apellido_cliente=Comercial" \
  -d "tipo_cedula_cliente=V" \
  -d "cedula_cliente=87654321" \
  -d "contacto_cliente=0414-1234567" \
  "http://localhost/sys-inescolara/clientes?action=edit_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Cliente actualizado correctamente",
  "client": {
    "id": 2,
    "nombre_cliente": "Cliente Postman Editado",
    "apellido_cliente": "Comercial",
    "nombre_completo": "Cliente Postman Editado Comercial",
    "tipo_cedula_cliente": "V",
    "cedula_cliente": "87654321",
    "cedula_completa": "V-87654321",
    "contacto_cliente": "0414-1234567"
  }
}
```

**Errores:** `403`, `500 ID inválido`, `500 No existe el cliente solicitado.`,
`500` de validación.

**Efectos:** actualiza la fila y registra auditoría `UPDATE`.

---

### `POST /clientes?action=delete_ajax`

| Campo | Valor |
|---|---|
| Permiso | `clientes:eliminar` |
| Parámetros | `id` |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  -d "id=2" \
  "http://localhost/sys-inescolara/clientes?action=delete_ajax"
```

**Respuesta 200**

```json
{ "success": true, "message": "Cliente desactivado correctamente", "clientId": 2 }
```

**Errores:** `403`, `500 ID inválido`, `500 No existe el cliente`,
`500 Error al desactivar el cliente.`

**Efectos:** `UPDATE cliente SET activo = 0` y auditoría `DEACTIVATE`. La fila
permanece en BD y desaparece del listado.

---

## Deuda detectada

- `get_clients` devuelve `count: 0` fijo.
- Los errores de validación llegan como `500` y sin el nombre del campo.
- `/clientes` (vista) no valida `clientes:ver`; solo `/dashboard/clientes`.
- La validación de nombre excluye números y signos, lo que impide razones
  sociales habituales.
- No hay verificación de cédula duplicada.

## Casos Postman

`pruebas/collections/comercial/Clientes.postman_collection.json`

Cubre listado, creación, edición, eliminación, verificación en el listado y
negativos `401/403/400/500`. Crea y desactiva su propio cliente.
