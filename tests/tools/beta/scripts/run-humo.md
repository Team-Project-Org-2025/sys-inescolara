# `run-humo` — humo HTTP del entorno beta

Comprueba que el entorno beta está disponible y responde como se espera, sin
modificar datos. Solo hace peticiones `GET`.

```bash
# Linux / macOS
./tests/tools/beta/scripts/run-humo.sh --base-url=https://mi-app.onrender.com

# Windows
tests\tools\beta\scripts\run-humo.bat --base-url=https://mi-app.onrender.com
```

Equivale a:

```bash
./tests/tools/beta/scripts/run-beta.sh --test=humo
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Inicio público `/` | HTTP 200 y el texto del vivero. |
| 2 | Catálogo `/catalogo` | HTTP 200 y el encabezado del catálogo. |
| 3 | Servicios `/servicios` | HTTP 200 y el encabezado de servicios. |
| 4 | Nosotros `/nosotros` | HTTP 200 y el encabezado institucional. |
| 5 | Contacto `/contacto` | HTTP 200 y el encabezado de contacto. |
| 6 | Login `/login` | HTTP 200, campo de contraseña y reCAPTCHA. |
| 7 | Dashboard `/dashboard` sin sesión | Redirección (301/302/303/307/308) a `login`. |
| 8 | Endpoint AJAX `/plantas/get_plants` sin sesión | HTTP 401 y `success:false`. |
| 9 | Ruta inexistente | HTTP 404. |

La comprobación 7 no sigue redirecciones para poder validar `Location`. El resto
sí las sigue, de modo que un despliegue HTTP→HTTPS no genera falsos fallos.

## Opciones

| Opción | Efecto |
| --- | --- |
| `--base-url=URL` | Entorno a probar. También `BETA_BASE_URL`. |
| `--max-ms=N` | Falla si una página tarda más de N ms. |
| `--insecure` | No verifica TLS (solo pruebas). |
| `--dry-run` | Muestra el plan. |
| `--no-junit` | No genera JUnit. |

## Resultados

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/junit-humo.xml` | JUnit con un caso por comprobación. |
| `formatos/resultados/humo-<fecha>.log` | Estado, tiempo y URL de cada petición. |

## Requisitos

- PHP 8.2+ con la extensión `curl`.
- Acceso de red al entorno beta.

Si el entorno no responde, la prueba **falla** (código `1`): en beta, una caída
es un hallazgo, no un problema de configuración local.
