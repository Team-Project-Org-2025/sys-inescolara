# Autenticación y sesión

Endpoints de acceso, cierre de sesión y recuperación de contraseña.

| Aspecto | Implementación |
|---|---|
| Almacén | Sesión PHP nativa (`PHPSESSID`) |
| Hash | `password_hash()` / `password_verify()` (bcrypt) |
| CAPTCHA | reCAPTCHA v2 en login y recuperación |
| Regeneración | `session_regenerate_id(true)` al iniciar sesión |
| Permisos | Por usuario en `usuario_modulo_permiso`; rol 1 = bypass |
| CSRF | Solo se valida en recuperación de contraseña |

Endpoints:

1. `GET /login`
2. `POST /login`
3. `GET /login/logout`
4. `POST /login/logout_ajax`
5. `GET /login/check_session`
6. `GET /login/dashboard`
7. `GET /recuperar-password`
8. `POST /recuperar-password/enviar`
9. `GET /recuperar-password/cambiar`
10. `POST /recuperar-password/restablecer`

---

### `GET /login`

**Descripción:** muestra el formulario de acceso. Si ya hay sesión, redirige
al dashboard.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/login` |
| Autenticación | Pública |
| Permiso | — |
| Content-Type | `text/html` |

**Respuesta 200:** HTML del login (`app/views/auth/login.php`), con el campo
oculto `_csrf_token`.

**Redirección:** `302` a `/dashboard` si `Auth::check()` es verdadero.

---

### `POST /login`

**Descripción:** autentica al usuario y crea la sesión.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/login` |
| Autenticación | Pública |
| Permiso | — |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `nombre_usuario` | string | Sí | Usuario o correo (`email` también se acepta) |
| `password` | string | Sí | Contraseña en claro |
| `g-recaptcha-response` | string | Sí | Token de reCAPTCHA v2 |

**Request**

```bash
curl -i -c cookies.txt -b cookies.txt \
  -d "nombre_usuario=admin" \
  -d "password=Admin123!" \
  -d "g-recaptcha-response=PASSED" \
  "http://localhost/sys-inescolara/login"
```

**Respuesta 302** (éxito)

```http
HTTP/1.1 302 Found
Set-Cookie: PHPSESSID=376j8f13u2r1pra14o935dfetf; path=/
Location: /sys-inescolara/dashboard
```

**Errores** (siempre `200` con HTML y `#loginError`)

| Mensaje | Cuándo |
|---|---|
| `Por favor, complete todos los campos.` | Usuario o contraseña vacíos |
| `Por favor, completa la verificación de seguridad.` | Falta el token reCAPTCHA |
| `Verificación de seguridad fallida. Intenta de nuevo.` | Google rechaza el token |
| `Usuario o contraseña incorrectos.` | Credenciales inválidas |
| `Tu cuenta está bloqueada…` | `estatus` distinto de `Activo` |

**Efectos secundarios**

- `session_regenerate_id(true)`.
- Actualiza `ultimo_acceso` e `intentos_fallidos`.
- `AuditLog::record('LOGIN', 'usuarios', ...)`.

**Notas**

- Con las llaves de prueba de Google cualquier token es válido.
- El login **no** valida el `_csrf_token` del formulario.

---

### `GET /login/logout`

**Descripción:** cierra la sesión y redirige al login.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/login/logout` |
| Autenticación | Sesión (opcional) |
| Permiso | — |

**Respuesta:** `302` a `/login`. Registra `LOGOUT` en auditoría si había sesión.

---

### `POST /login/logout_ajax`

**Descripción:** cierre de sesión para el frontend, devuelve JSON.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/login/logout_ajax` |
| Autenticación | Sesión |
| Permiso | — |
| Content-Type | ninguno (sin body) |

**Request**

```bash
curl -X POST \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt \
  "http://localhost/sys-inescolara/login/logout_ajax"
```

**Respuesta 200**

```json
{
  "success": true,
  "message": "Sesión cerrada correctamente",
  "redirect": "/sys-inescolara/login"
}
```

**Errores**

| Código | Cuándo |
|---|---|
| `400` | Método distinto de POST o falta `X-Requested-With` |

---

### `GET /login/check_session`

**Descripción:** indica si la sesión sigue activa. Lo usa el frontend para
detectar expiración.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/login/check_session` |
| Autenticación | Sesión (no falla si no hay) |
| Permiso | — |

**Request**

```bash
curl -H "X-Requested-With: XMLHttpRequest" -b cookies.txt \
  "http://localhost/sys-inescolara/login/check_session"
```

**Respuesta 200** (autenticado)

```json
{
  "active": true,
  "user_id": 1,
  "user_email": "admin@inecolara.gob.ve",
  "user_nombre": "admin",
  "user_avatar": null,
  "user_rol_id": 1
}
```

**Respuesta 200** (sin sesión): `{"active":false,"user_id":null,...}`

**Errores**

| Código | Cuándo |
|---|---|
| `400` | Falta `X-Requested-With` (sin cuerpo) |

---

### `GET /login/dashboard`

**Descripción:** alias protegido que redirige al dashboard.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/login/dashboard` |
| Autenticación | Sesión obligatoria |
| Permiso | — |

**Respuestas:** `302` a `/dashboard` si hay sesión; `302` a `/login` si no.

---

### `GET /recuperar-password`

**Descripción:** formulario para solicitar el enlace de recuperación.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/recuperar-password` |
| Autenticación | Pública |
| Permiso | — |

**Query:** `success=1` muestra el aviso de envío correcto.

**Respuesta 200:** HTML con el campo `_csrf_token`.

---

### `POST /recuperar-password/enviar`

**Descripción:** valida el correo, verifica reCAPTCHA y envía el enlace.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/recuperar-password/enviar` |
| Autenticación | Pública |
| Permiso | — |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `correo` | string | Sí | Correo registrado |
| `_csrf_token` | string | **Sí** | Token de la sesión actual |
| `g-recaptcha-response` | string | Sí | Token reCAPTCHA |

**Errores** (`200` con HTML y alerta)

| Mensaje | Cuándo |
|---|---|
| `Token de seguridad inválido. Intenta de nuevo.` | CSRF ausente o incorrecto |
| `Ingresa un correo electrónico válido.` | Formato inválido |
| `Por favor, completa la verificación de seguridad.` | Falta reCAPTCHA |
| `Verificación de seguridad fallida. Intenta de nuevo.` | Google rechaza |
| `Ya enviaste una solicitud recientemente…` | Rate limit de 5 minutos |
| `No encontramos una cuenta con ese correo electrónico.` | Correo no registrado |
| `Ocurrió un error al enviar el correo…` | Falla SMTP |

**Respuesta 302** (éxito): `/recuperar-password?success=1`.

**Efectos:** crea fila en `password_resets`, registra auditoría y envía correo
con enlace `…/recuperar-password/cambiar?token=…`.

**Notas:** es el **único** flujo que valida CSRF. El token debe extraerse del
HTML del formulario.

---

### `GET /recuperar-password/cambiar`

**Descripción:** valida el token y muestra el formulario de nueva contraseña.

| Campo | Valor |
|---|---|
| Método | `GET` |
| Ruta | `/recuperar-password/cambiar` |
| Autenticación | Pública |
| Permiso | — |

**Query:** `token` (obligatorio).

**Respuestas**

| Caso | Resultado |
|---|---|
| Token válido | `200` con `reset-password.php` |
| Token vacío | `302` a `/recuperar-password` |
| Token inválido o expirado | `200` con alerta `El enlace de recuperación es inválido o ha expirado…` |

---

### `POST /recuperar-password/restablecer`

**Descripción:** guarda la nueva contraseña usando el token.

| Campo | Valor |
|---|---|
| Método | `POST` |
| Ruta | `/recuperar-password/restablecer` |
| Autenticación | Pública |
| Permiso | — |
| Content-Type | `application/x-www-form-urlencoded` |

**Parámetros**

| Nombre | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `token` | string | Sí | Token del enlace |
| `password` | string | Sí | Nueva contraseña |
| `password2` | string | Sí | Confirmación |
| `_csrf_token` | string | Sí | Token de sesión |

**Respuesta 302** (éxito): `/login?reset=ok`.

**Errores** (`200` con HTML): token inválido, contraseñas no coinciden,
contraseña débil (`isPasswordStrong`), CSRF inválido.

**Notas**

- Bug conocido: en servidores con zona horaria distinta a `America/Caracas`
  el token expira de inmediato (comparación `NOW()` de MySQL vs hora de PHP).
- No cubierto por la colección Postman por depender de correo.

---

## Uso en Postman

1. Request `POST {{baseUrl}}/login` con los tres campos.
2. No esperes JSON: valida `302` y la cookie `PHPSESSID`.
3. Los requests siguientes heredan la cookie del jar.
4. Para el caso `401`, limpia cookies con `pm.cookies.clear()`.
5. Para `403`, inicia sesión con `postman_viewer`.

Scripts listos en
`.agents/skills/postman-api-testing/reference/php-session-auth.md`.
