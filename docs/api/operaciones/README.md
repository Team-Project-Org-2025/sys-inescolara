# Operaciones · índice

Tareas de campo y recolección de semillas.

| Módulo | Ruta | Ficha | Colección |
|---|---|---|---|
| Tareas | `/tareas` | [`tareas.md`](tareas.md) | `Tareas.postman_collection.json` |
| Recolección | `/recoleccion` | [`recoleccion.md`](recoleccion.md) | `Recoleccion.postman_collection.json` |

Ambos usan `?action=`. Tareas usa `getRequestData()` (acepta JSON o form);
Recolección redirige a `/dashboard/seed-collection` cuando no es AJAX.
