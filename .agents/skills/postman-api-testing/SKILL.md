---
name: postman-api-testing
description: Pruebas de integración de backend con Postman para SYS INESCOLARA. Úsala cuando haya que crear, ejecutar o depurar colecciones Postman contra los endpoints JSON del sistema (login con sesión PHP, listados, CRUD, casos 401/403/400), preparar corridas data-driven con CSV o integrar la suite en CI.
license: MIT
metadata:
  domain: testing
  scope: api-integration
  stack: PHP 8.2 · MariaDB · Postman/Newman
---

# Postman API Testing · SYS INESCOLARA

Guía operativa para probar el backend real de SYS INESCOLARA con Postman.
No asumas REST puro: este backend es PHP nativo con sesión, rutas por query
string y respuestas heterogéneas.

## Regla de oro

**Primero lee el controlador, después escribe el request.**
Cada endpoint se comporta distinto: nombre del parámetro (`action` o
`accion`), envelope, content-type y permiso requerido cambian por módulo.

## Flujo de trabajo

1. **Identifica la ruta real**
   - Vista: `GET /{modulo}` → HTML (no sirve para la colección).
   - AJAX: `GET|POST /{modulo}?action={accion}` → JSON.
   - Acción por segmento: `GET /notifications/get_unread` → JSON.
   - Excepción: Ventas usa `?accion=` en lugar de `?action=`.

2. **Lee el dispatcher del controlador** (`app/controllers/{Modulo}Controller.php`)
   - Busca el `match ($_SERVER['REQUEST_METHOD'] . '_' . $action)`.
   - Ahí están los métodos y acciones válidas; lo que no esté devuelve 400.

3. **Verifica autenticación y permisos**
   - `checkModuleAuth()` → 401 JSON si no hay sesión.
   - `checkPermisoOrFail('modulo:accion')` → 403 si el rol no lo tiene.
   - Admin (`id_rol = 1`) pasa todos los chequeos.

4. **Añade el header AJAX obligatorio**
   ```
   X-Requested-With: XMLHttpRequest
   ```
   Sin él, el controlador devuelve la vista o redirige al login.

5. **Arma el body según el controlador**
   - `$_POST` → `x-www-form-urlencoded` o `multipart/form-data` (si hay archivos).
   - `getRequestData()` → acepta JSON si `Content-Type: application/json`.

6. **Captura la respuesta real** y úsala en las aserciones. No inventes claves.

7. **Añade siempre los casos negativos**: 401 sin cookie, 403 con usuario sin
   permiso, 400 con acción inexistente.

## Autenticación

El login es un POST de formulario, no JSON:

```
POST {{baseUrl}}/login
Content-Type: application/x-www-form-urlencoded

nombre_usuario={{usuario}}
password={{password}}
g-recaptcha-response={{recaptchaToken}}
```

- Éxito: `302` + `Set-Cookie: PHPSESSID=...`
- Postman conserva la cookie en su cookie jar por dominio; el resto de
  requests de la corrida ya van autenticados.
- En local usa las llaves de prueba de Google en `.env`; cualquier valor de
  `g-recaptcha-response` (por ejemplo `PASSED`) es válido.
- Sin sesión, un endpoint AJAX responde `401` con
  `{"success":false,"message":"No autorizado","redirect":"..."}`.

Detalle completo en [`reference/php-session-auth.md`](reference/php-session-auth.md).

## Convenciones de la colección

- Nombre: `<Modulo>.postman_collection.json` en `pruebas/collections/<dominio>/`.
- Carpetas fijas: `00 · Auth`, `01 · Listado`, `02 · Crear`, `03 · Editar`,
  `04 · Eliminar`, `05 · Negativos`.
- Variables: `baseUrl`, `usuario`, `password`, `recaptchaToken`,
  `<modulo>Id`, `adminCookie`, `viewerCookie`.
- Cada request con tests; nunca dejes un request sin aserción.
- Los IDs se guardan en variables de colección, no se hardcodean.

## Comandos

La suite se ejecuta con **newman** (`npm install -g newman`), no con el Postman
CLI:

```bash
# Todas las colecciones por módulo (29)
./pruebas/scripts/run.sh

# Una sola colección
newman run "pruebas/collections/inventario/Plantas.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "usuario=admin" --env-var "password=Admin123!"

# Correr todo y exportar JUnit a results/
./pruebas/scripts/run-all.sh            # Linux/macOS
pwsh -File pruebas\scripts\run-all.ps1  # Windows

# Data-driven con CSV (solo la suite monolítica anterior)
newman run <coleccion> -e <entorno> -d pruebas/data/plantas.csv -n 3
```

Más detalle en [`reference/cli-and-ci.md`](reference/cli-and-ci.md).

## Errores frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| HTML en vez de JSON | Falta `X-Requested-With` | Añadir el header |
| `401` con login correcto | Cookie jar desactivado o dominio distinto | Revisar cookie jar / `baseUrl` |
| `400 Acción AJAX inválida` | Acción o método incorrecto | Verificar el `match` del controlador |
| `403` siendo admin | Sesión de otro usuario | Revisar `id_rol` |
| `500` con SQL | Tabla o columna inexistente | Revisar esquema / migraciones |
| Login falla solo en local | Llaves reCAPTCHA reales en `.env` | Usar llaves de prueba |

## Referencias

- [`reference/php-session-auth.md`](reference/php-session-auth.md) — login, cookie jar y guardias
- [`reference/assertions-cookbook.md`](reference/assertions-cookbook.md) — patrones de aserción
- [`reference/cli-and-ci.md`](reference/cli-and-ci.md) — CLI, CSV y CI
- `pruebas/README.md` — conceptos de la suite existente
- `docs/api/` — contrato documentado de cada endpoint
