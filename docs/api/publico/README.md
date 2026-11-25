# Público · índice

Vistas públicas (sin sesión) y el router frontal.

| Módulo | Ruta | Ficha | Colección |
|---|---|---|---|
| Público | `/`, `/catalogo`, `/servicios`, `/nosotros`, `/contacto` | [`publico.md`](publico.md) | `Publico.postman_collection.json` |
| Router | `FrontController` | [`publico.md`](publico.md) | — |

Las rutas públicas de un segmento están mapeadas en `FrontController::$routes` a
`PublicController`; el resto se resuelve dinámicamente como
`<Controlador>Controller`.
