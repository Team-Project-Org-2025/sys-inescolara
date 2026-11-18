# `run-auth` — autenticación y acceso

Prueba alfa independiente del grupo `auth`. Verifica páginas públicas, login,
sesión, control de acceso y rutas inexistentes.

- **Linux/macOS:** `run-auth.sh`
- **Windows:** `run-auth.bat`
- **Núcleo:** `run-alfa.php --test=auth`

## Uso

```bash
./tests/tools/alfa/scripts/run-auth.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-auth.bat [opciones]
```

Acepta las mismas opciones que [`run-alfa`](run-alfa.md) (`--dry-run`,
`--verbose`, `--no-junit`, `--skip-seed`, etc.).

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Inicio público | HTTP 200 y contenido del vivero. |
| 2 | Catálogo público | HTTP 200 y texto del catálogo. |
| 3 | Formulario de login | HTTP 200 con email, password y reCAPTCHA. |
| 4 | Login sin reCAPTCHA | HTTP 200 con el aviso de verificación. |
| 5 | Dashboard sin sesión | Redirección al login. |
| 6 | Login con sesión | Redirección al dashboard. |
| 7 | Dashboard autenticado | HTTP 200 con el título del inicio. |
| 8 | AJAX protegido sin sesión | HTTP 401 con `success:false`. |
| 9 | `check_session` con sesión | HTTP 200 con `active:true`. |
| 10 | Logout AJAX inválido | HTTP 400. |
| 11 | Ruta inexistente | HTTP 404 con la página de error. |

## Resultado

Genera `formatos/resultados/junit-auth.xml` y `auth-<fecha>.log`. Si alguna
comprobación falla, el script sale con `1`.
