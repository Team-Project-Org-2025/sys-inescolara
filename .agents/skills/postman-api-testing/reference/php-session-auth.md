# Autenticación por sesión PHP en Postman

## 1. Cómo funciona en este proyecto

- `LoginController::login()` valida `nombre_usuario` (o `email`), `password` y
  `g-recaptcha-response`.
- Usa `password_verify()` y `session_regenerate_id(true)`.
- La sesión vive en la cookie `PHPSESSID`.
- Los endpoints AJAX llaman a `checkModuleAuth()`, que responde `401` JSON si
  no hay sesión (en vez de redirigir).

## 2. Request de login

```
POST {{baseUrl}}/login
Content-Type: application/x-www-form-urlencoded

nombre_usuario={{usuario}}
password={{password}}
g-recaptcha-response={{recaptchaToken}}
```

Body en formato Postman (`raw` no funciona; es un formulario):

```json
[
  { "key": "nombre_usuario", "value": "{{usuario}}", "type": "text" },
  { "key": "password", "value": "{{password}}", "type": "text" },
  { "key": "g-recaptcha-response", "value": "{{recaptchaToken}}", "type": "text" }
]
```

Respuestas posibles:

| Caso | Status | Cuerpo |
|---|---|---|
| Éxito | `302` | `Location: {{baseUrl}}dashboard` + `Set-Cookie: PHPSESSID` |
| Campos vacíos | `200` | HTML del login con `#loginError` |
| Credenciales inválidas | `200` | HTML con mensaje de error |
| reCAPTCHA inválido | `200` | HTML con "Verificación de seguridad fallida" |

> El login **no** devuelve JSON. No asserts `response.json()` aquí.

## 3. Script de test del login

```js
pm.test('Login responde redirección o dashboard', () => {
  pm.expect([200, 302]).to.include(pm.response.code);
});

pm.test('La sesión quedó establecida', () => {
  const cookies = pm.cookies.all();
  pm.expect(cookies).to.have.property('PHPSESSID');
  pm.collectionVariables.set('authCookie', cookies.PHPSESSID);
});

pm.test('No hay mensaje de error en el HTML', () => {
  if (pm.response.code === 200) {
    pm.expect(pm.response.text()).to.not.include('id="loginError"');
  }
});
```

## 4. Cookie jar

- Postman mantiene cookies por dominio automáticamente si el **cookie jar**
  está activo (`Settings → General → Automatically persist variable values` no
  aplica; el jar es del dominio de `baseUrl`).
- Todas las peticiones deben usar el mismo host y puerto que el login.
- Si usas `localhost` en el login y `127.0.0.1` en los demás requests, la
  cookie no viaja: **sé consistente**.
- Para forzar la cookie manualmente:

```js
pm.request.headers.add({
  key: 'Cookie',
  value: `PHPSESSID=${pm.collectionVariables.get('authCookie')}`
});
```

- En Newman/Postman CLI el jar funciona dentro de la misma corrida. Si partes
  la corrida en dos procesos, pasa la cookie con `--env-var authCookie=...` y
  añádela a mano.

## 5. Header AJAX obligatorio

Todos los endpoints de módulo exigen:

```
X-Requested-With: XMLHttpRequest
```

Sin él:
- `checkModuleAuth()` hace `header('Location: .../login')`.
- El controlador sirve la vista HTML en lugar del JSON.

En Postman se configura a nivel de colección (`event: prerequest` o headers de
carpeta) para no repetirlo:

```js
pm.request.headers.upsert({ key: 'X-Requested-With', value: 'XMLHttpRequest' });
```

## 6. Cerrar sesión

```http
POST {{baseUrl}}/login/logout_ajax
X-Requested-With: XMLHttpRequest
```

Devuelve JSON y destruye la sesión. Úsalo al final de la colección para no
dejar sesiones abiertas (`sesiones_activas`).

## 7. CSRF

`Csrf.php` genera el token y los formularios lo renderizan, pero
`checkCsrf()` **no se invoca** en ningún controlador y los AJAX están exentos.
Conclusión práctica: **no necesitas enviar token CSRF** en las pruebas.
Si en el futuro se activa, el patrón sería:

1. `GET /login` → extraer `name="_csrf_token" value="..."` con regex del HTML.
2. Guardarlo en variable de entorno.
3. Enviarlo en cada POST de formulario.

## 8. reCAPTCHA en local

`.env` debe tener las llaves de prueba de Google:

```
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

Con ellas, Google acepta cualquier token (`PASSED`, `test`, etc.). Si el login
devuelve "Verificación de seguridad fallida", revisa que `.env` no tenga las
llaves reales.
