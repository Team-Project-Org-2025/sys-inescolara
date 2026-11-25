# Módulo Dashboard (vistas) e Inicio

Vistas HTML servidas por `app/controllers/DashboardController.php` (funciones
legacy) e `InicioController`.

| Ruta | Función | Permiso |
|---|---|---|
| `/dashboard` | `index()` | sesión |
| `/dashboard/<modulo>` | función homónima | `dashboardCheckPermiso('<modulo>:ver')` |
| `/inicio` | `index()` (redirect a `/`) | — |

La mayoría de las rutas de módulo exigen permiso de vista. Las rutas principales
(`/plantas`, `/ventas`, etc.) sirven la vista sin chequear `:ver` (solo sesión);
la variante `/dashboard/<modulo>` sí lo hace.

### `GET /dashboard`

Sin sesión redirige a `/login` (302). Con sesión, HTML `200`.

### `GET /dashboard/plantas`, `/usuarios`, `/roles`, `/backups`, `/reports`, `/auditlog`, …

Devuelven HTML `200` con sesión y permiso; `403` sin el permiso `:ver`.

### `GET /inicio`

Redirige (302) a `/` (`InicioController::index()`).

## Casos Postman

`pruebas/collections/sistema/Dashboard.postman_collection.json`
Login + smoke test de varias vistas del dashboard y el redirect de `/inicio`.
