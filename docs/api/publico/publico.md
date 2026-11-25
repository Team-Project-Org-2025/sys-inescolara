# Módulo Público y router

Controlador: `app/controllers/PublicController.php` (vistas) ·
`app/controllers/FrontController.php` (router).

| Ruta | Acción | Devuelve |
|---|---|---|
| `/` | `home()` | HTML |
| `/catalogo` | `catalogo()` | HTML |
| `/servicios` | `servicios()` | HTML |
| `/nosotros` | `nosotros()` | HTML |
| `/contacto` | `contacto()` | HTML |

Todas devuelven `200 text/html` sin autenticación.

### `GET /`

Página de inicio. `200`.

### `GET /catalogo`, `/servicios`, `/nosotros`, `/contacto`

Cada una renderiza su vista en `app/views/public/*.php` dentro del layout
`app/views/layouts/main.php`.

### Router (`FrontController`)

- Rutas de un segmento `catalogo|servicios|nosotros|contacto` → `PublicController`.
- Segmentos `dashboard/...` → `DashboardController`.
- Cualquier otro segmento se convierte a `CamelCase` (`/cuentas-cobrar` →
  `CuentasCobrarController`) y dispara la acción (`/x/y` → método `y`).
- Controlador o método inexistente → `404` (`JSON` si es AJAX, `HTML` si no).

## Casos Postman

`pruebas/collections/publico/Publico.postman_collection.json`
Smoke test de las cinco vistas públicas y `404` de ruta inexistente.
