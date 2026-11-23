# Inventario · índice

Módulos base del vivero. No dependen de otros módulos, salvo Insumos (unidad de
medida) y Herramientas (asignación de tareas para el uso).

| Módulo | Ficha | Colección |
|---|---|---|
| Plantas | [`plantas.md`](plantas.md) | `Plantas.postman_collection.json` |
| Especies | [`especies.md`](especies.md) | `Especies.postman_collection.json` |
| Ubicaciones | [`ubicaciones.md`](ubicaciones.md) | `Ubicaciones.postman_collection.json` |
| Unidades de medida | [`unidades-medida.md`](unidades-medida.md) | `UnidadesMedida.postman_collection.json` |
| Insumos | [`insumos.md`](insumos.md) | `Insumos.postman_collection.json` |
| Herramientas | [`herramientas.md`](herramientas.md) | `Herramientas.postman_collection.json` |
| Lotes | [`lotes.md`](lotes.md) | `Lotes.postman_collection.json` |
| Precios | [`precios.md`](precios.md) | `Precios.postman_collection.json` |
| Inventario | [`inventario.md`](inventario.md) | `Inventario.postman_collection.json` |
| Trazabilidad | [`trazabilidad.md`](trazabilidad.md) | `Trazabilidad.postman_collection.json` |
| Mermas | [`mermas.md`](mermas.md) | `Mermas.postman_collection.json` |

## Patrón común

Los módulos CRUD de esta carpeta (Plantas, Especies, Ubicaciones, Unidades de
medida, Insumos, Herramientas y Lotes) comparten estructura:

```text
GET  /dashboard/<modulo>          vista con permiso <modulo>:ver
GET  /<modulo>                    vista (solo sesión)
GET  /<modulo>?action=<list>      listado JSON
POST /<modulo>?action=add_ajax    crear      permiso <modulo>:crear
POST /<modulo>?action=edit_ajax   editar     permiso <modulo>:editar
POST /<modulo>?action=delete_ajax eliminar   permiso <modulo>:eliminar
```

Todos exigen `X-Requested-With: XMLHttpRequest`, devuelven `403` sin permiso y
`400` con `{"success":false,"message":"Acción AJAX inválida"}` cuando la acción
no está en el `match`. El borrado es lógico (`activo = 0`) y se registra en
auditoría como `DEACTIVATE`.
