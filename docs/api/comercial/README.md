# Comercial · índice

Módulos de venta y relación con clientes. Dependen de Inventario (lotes e
insumos) y de la base de seguridad (usuarios que venden o cobran).

| Módulo | Ruta principal | Ficha | Colección |
|---|---|---|---|
| Clientes | `/clientes` | [`clientes.md`](clientes.md) | `Clientes.postman_collection.json` |
| Ventas | `/ventas` (usa `accion`) | [`ventas.md`](ventas.md) | `Ventas.postman_collection.json` |
| Cuentas por cobrar | `/cuentas_cobrar` | [`cuentas-cobrar.md`](cuentas-cobrar.md) | `CuentasCobrar.postman_collection.json` |
| Ornatos | `/ornatos` (usa `accion`) | [`ornatos.md`](ornatos.md) | `Ornatos.postman_collection.json` |
| Ampliación | `/ampliacion` | [`ampliacion.md`](ampliacion.md) | `Ampliacion.postman_collection.json` |

## Particularidades del dominio

- **Dos convenciones de parámetro.** Ventas y Ornatos usan `?accion=`; el resto
  usa `?action=`.
- **Cuentas por cobrar usa guion bajo en la URL.** La ruta correcta es
  `/cuentas_cobrar` porque el controlador se llama `Cuentas_cobrarController`.
  `/cuentas-cobrar` produce `El controlador 'CuentasCobrar' no existe.`
- **Ventas y Ampliación mueven stock.** Sus colecciones crean un lote propio y
  lo eliminan al final para no alterar el seed.
- **Los pagos no se borran.** No existe endpoint para anular un pago; al
  cancelar la venta solo se desactiva la venta. Las filas de `pago_venta`
  permanecen.
