# Convenciones de la API

Reglas transversales del backend. Aplican a todos los módulos.

## 1. URL base

```
http://localhost/sys-inescolara
```

Sin slash final. El frontend lo expone como `window.BASE_URL`.

En producción el prefijo cambia (`APP_URL`), por eso las fichas usan rutas
relativas (`/plantas`) y las colecciones usan `{{baseUrl}}`.

## 2. Tipos de ruta

| Patrón | Ejemplo | Devuelve |
|---|---|---|
| Vista de módulo | `GET /plantas` | HTML |
| Vista de dashboard | `GET /dashboard/plantas` | HTML (con chequeo de permiso) |
| Acción AJAX | `GET\|POST /plantas?action=get_plants` | JSON |
| Acción por segmento | `GET /notifications/get_unread` | JSON |
| Pública | `GET /catalogo` | HTML |

> **Excepción:** Ventas y Ornatos usan `?accion=` en lugar de `?action=`. Está
> así en el controlador y en el JS; cualquier colección nueva debe respetarlo.

## 3. Header AJAX

Todos los endpoints de módulo exigen:

```http
X-Requested-With: XMLHttpRequest
```

Sin este header:
- `checkModuleAuth()` redirige al login (302) en vez de devolver 401.
- El controlador sirve la vista HTML en vez del JSON.

`/notifications/*` no valida el header, pero sus acciones asumen sesión.

## 4. Content-Type

| Entrada | Content-Type |
|---|---|
| Formularios y CRUD estándar | `application/x-www-form-urlencoded` |
| Formularios con imagen | `multipart/form-data` |
| Módulos que usan `getRequestData()` | `application/json` también válido |

Módulos que aceptan JSON: Ampliación, Compras, Precios, Recolección, Tareas,
Herramientas y Ventas (`guardar`).

## 5. Envelopes de respuesta

No hay un envelope único. Los más frecuentes:

**Éxito simple**
```json
{ "success": true, "message": "Planta agregada correctamente" }
```

**Éxito con datos**
```json
{
  "success": true,
  "plantas": [ { "id": 2, "nombre_comun": "Rosa Postman" } ],
  "count": 1
}
```

**Error controlado**
```json
{ "success": false, "message": "No tienes permiso para realizar esta acción." }
```

**Error de sesión (401)**
```json
{
  "success": false,
  "message": "No autorizado",
  "redirect": "http://localhost/sys-inescolara/login"
}
```

**DataTables** (algunos listados)
```json
{ "data": [], "recordsTotal": 0, "recordsFiltered": 0 }
```

Regla: **el nombre del arreglo se toma del controlador**, no se asume.

## 6. Códigos de estado

| Código | Cuándo |
|---|---|
| `200` | Operación correcta (también en errores de validación) |
| `302` | Redirección de vistas o login correcto |
| `400` | Acción o método no contemplado en el `match` |
| `401` | AJAX sin sesión |
| `403` | Sesión válida sin el permiso requerido |
| `404` | Controlador o acción inexistente (render HTML) |
| `500` | Excepción no controlada; el mensaje se devuelve crudo |

> Las validaciones de negocio (campo vacío, id inválido) lanzan `\Exception`
> y terminan en **500**, no en 422. Es comportamiento actual, no ideal.

## 7. Autenticación y permisos

- Sesión PHP (`PHPSESSID`) creada en `POST /login`.
- `checkModuleAuth()` → exige sesión.
- `checkPermisoOrFail('modulo:accion')` → exige permiso.
- El rol **1 (Administrador)** omite todos los chequeos.
- Los permisos efectivos salen de `usuario_modulo_permiso`
  (`modulos.nombre_modulo` + `permisos.nombre_permiso`), no del rol.

Ver [`01-autenticacion.md`](01-autenticacion.md).

## 8. CSRF

- `Csrf::render()` pinta `_csrf_token` en login, recuperación y reset.
- Solo `RecuperarPasswordController::enviar()` valida el token.
- `checkCsrf()` existe pero **no se invoca** en ningún controlador.
- Los AJAX están exentos por diseño.

Consecuencia para pruebas: no se envía token CSRF, salvo en el flujo de
recuperación de contraseña.

## 9. Borrado lógico

La mayoría de modelos desactivan con `activo = 0` en lugar de borrar:

| Modelo | Bandera | Observación |
|---|---|---|
| Planta, Especie, Ubicación, Lote, Insumo, Herramienta | `activo` | El registro sigue en BD |
| Merma | `activo` | `MermasController` no valida permiso de borrado |
| Trazabilidad | `activo` | Tiene `restore_ajax` |

Los listados suelen filtrar `activo = 1`; verificar la eliminación contra el
listado, no contra la fila.

## 10. Auditoría

Las operaciones de escritura registran en `SysInescolara-Seguridad.auditoria_logs`
vía `AuditLog::record(accion, tabla, id, anterior, nuevo)`.

Acciones observadas: `CREATE`, `UPDATE`, `DEACTIVATE`, `LOGIN`, `LOGOUT`.

## 11. Errores internos

`handleError()` y varios controladores devuelven `$e->getMessage()` al cliente.
En desarrollo es útil; en producción filtra detalles (rutas SQL, nombres de
tabla). Documentado como deuda en `_cobertura.md`.

## 12. Convención de documentación

Cada endpoint lleva ficha con: método, ruta, auth, permiso, content-type,
parámetros, request reproducible, respuesta real, errores, efectos y notas.
Plantilla en `.agents/skills/api-docs-writer/reference/endpoint-template.md`.
