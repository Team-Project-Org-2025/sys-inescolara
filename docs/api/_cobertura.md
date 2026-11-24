# Cobertura de documentación

Matriz de controladores ↔ fichas ↔ colecciones Postman.

Estados: ✅ documentado y verificado con respuesta real · 🟡 documentado desde
el código · ⬜ pendiente.

## Autenticación

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `LoginController` | `/login`, `/login/logout`, `/login/logout_ajax`, `/login/check_session`, `/login/dashboard` | [`01-autenticacion.md`](01-autenticacion.md) | — | ✅ |
| `RecuperarPasswordController` | `/recuperar-password`, `/enviar`, `/cambiar`, `/restablecer` | [`01-autenticacion.md`](01-autenticacion.md) | — | ✅ |
| `AuthController` | helpers de sesión | — | — | ⬜ |

## Inventario

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `PlantasController` | `/plantas` | [`inventario/plantas.md`](inventario/plantas.md) | `inventario/Plantas` | ✅ |
| `EspeciesController` | `/especies` | [`inventario/especies.md`](inventario/especies.md) | `inventario/Especies` | ✅ |
| `UbicacionesController` | `/ubicaciones` | [`inventario/ubicaciones.md`](inventario/ubicaciones.md) | `inventario/Ubicaciones` | ✅ |
| `UnidadesMedidaController` | `/unidades-medida` | [`inventario/unidades-medida.md`](inventario/unidades-medida.md) | `inventario/UnidadesMedida` | ✅ |
| `InsumosController` | `/insumos` | [`inventario/insumos.md`](inventario/insumos.md) | `inventario/Insumos` | ✅ |
| `HerramientasController` | `/herramientas` | [`inventario/herramientas.md`](inventario/herramientas.md) | `inventario/Herramientas` | ✅ |
| `LotesController` | `/lotes` | [`inventario/lotes.md`](inventario/lotes.md) | `inventario/Lotes` | ✅ |
| `PreciosController` | `/precios` | [`inventario/precios.md`](inventario/precios.md) | `inventario/Precios` | ✅ |
| `InventarioController` | `/inventario` | [`inventario/inventario.md`](inventario/inventario.md) | `inventario/Inventario` | ✅ |
| `MermasController` | `/mermas` | [`inventario/mermas.md`](inventario/mermas.md) | `inventario/Mermas` | ✅ |
| `TrazabilidadController` | `/trazabilidad` | [`inventario/trazabilidad.md`](inventario/trazabilidad.md) | `inventario/Trazabilidad` | ✅ |

## Comercial

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `ClientesController` | `/clientes` | [`comercial/clientes.md`](comercial/clientes.md) | `comercial/Clientes` | ✅ |
| `VentasController` | `/ventas` (usa `accion`) | [`comercial/ventas.md`](comercial/ventas.md) | `comercial/Ventas` | ✅ |
| `Cuentas_cobrarController` | `/cuentas_cobrar` | [`comercial/cuentas-cobrar.md`](comercial/cuentas-cobrar.md) | `comercial/CuentasCobrar` | ✅ |
| `OrnatosController` | `/ornatos` (usa `accion`) | [`comercial/ornatos.md`](comercial/ornatos.md) | `comercial/Ornatos` | ✅ |
| `AmpliacionController` | `/ampliacion` | [`comercial/ampliacion.md`](comercial/ampliacion.md) | `comercial/Ampliacion` | ✅ |

> `Cuentas_cobrarController` exige guion bajo en la URL. `/cuentas-cobrar`
> resuelve a `CuentasCobrarController` y devuelve
> `El controlador 'CuentasCobrar' no existe.`

## Compras

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `ComprasController` | `/compras` | — | — | ⬜ |
| `ProveedoresController` | `/proveedores` | — | — | ⬜ |
| `CuentasPagarController` | `/cuentas-pagar` | — | — | ⬜ |

## Operaciones

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `TareasController` | `/tareas` | — | — | ⬜ |
| `RecoleccionController` | `/recoleccion` | — | — | ⬜ |

## Sistema

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `DashboardController` | `/dashboard/*` | — | — | ⬜ |
| `InicioController` | `/` | — | — | ⬜ |
| `UsuariosController` | `/usuarios` | — | — | ⬜ |
| `RolesController` | `/roles` | — | — | ⬜ |
| `AuditlogController` | `/auditlog` | — | — | ⬜ |
| `BackupsController` | `/backups` | — | — | ⬜ |
| `ReportsController` | `/reports` | — | — | ⬜ |
| `NotificationsController` | `/notifications/*` | — | — | ⬜ |

## Público

| Controlador | Rutas | Ficha | Colección | Estado |
|---|---|---|---|---|
| `PublicController` | `/`, `/catalogo`, `/servicios`, `/nosotros`, `/contacto` | — | — | ⬜ |
| `FrontController` | router | — | — | ⬜ |

## Deuda transversal

- Envelopes heterogéneos y nombres de arreglo distintos por módulo.
- 500 en errores de validación; mensajes internos expuestos.
- `checkCsrf()` nunca se invoca.
- Permisos legacy en BD (`PLANTAS_VIEW`) frente a claves `modulo:accion`.
- Confirmar en `REQUISITOS.md` qué RF quedan fuera del backend (correo,
  reCAPTCHA, respaldos).
