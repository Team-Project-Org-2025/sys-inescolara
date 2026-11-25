# Compras · índice

Módulos de abastecimiento: proveedores, órdenes de compra y cuentas por pagar.
Dependen de Inventario (plantas, insumos, herramientas) y de la base de
seguridad (usuarios).

| Módulo | Ruta | Ficha | Colección |
|---|---|---|---|
| Proveedores | `/proveedores` | [`proveedores.md`](proveedores.md) | `Proveedores.postman_collection.json` |
| Compras | `/compras` | [`compras.md`](compras.md) | `Compras.postman_collection.json` |
| Cuentas por pagar | `/cuentas-pagar` | [`cuentas-pagar.md`](cuentas-pagar.md) | `CuentasPagar.postman_collection.json` |

## Particularidades

- **`?action=`** en los tres módulos.
- **Compras recibe stock al “recibir”**; crea lotes para plantas e incrementa
  `insumo.stock_actual` para insumos.
- **Compras vía `?action=` no valida permisos de escritura.** El despachador
  llama a los manejadores internos (`compras_manejarAgregarEditar`, etc.), que
  solo exigen sesión. Los permisos (`compras:crear`, `compras:editar`, …) solo
  se aplican si se entra por ruta de segmento (`/compras/agregar_ajax`).
- **`add_ajax` de Proveedores, Usuarios y Roles devuelve un id incorrecto** (el
  de la auditoría) por leer `lastInsertId` después de insertar el log.
