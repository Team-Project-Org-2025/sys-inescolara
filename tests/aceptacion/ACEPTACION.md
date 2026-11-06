# Matriz de Pruebas de Aceptación — Sys Inescolara

Generado: `2026-11-06 00:48:13` · Codeception 5 + WebDriver (Brave/Chrome headless) · Orden: requisitos RF-01 a RF-31

## Resumen

| Casos | PASS | FAIL | SKIP | No ejecutados | Tiempo total |
| ---: | ---: | ---: | ---: | ---: | ---: |
| 45 | 45 | 0 | 0 | 0 | 54.54 s |

## Resumen por requisito

| # | Requisito | Módulo / pantalla | Casos | Estado |
| ---: | --- | --- | ---: | --- |
| 01 | RF-01 | Plantas | 2 | ✅ PASS |
| 02 | RF-02 | Especies | 2 | ✅ PASS |
| 03 | RF-03 | Ubicaciones | 2 | ✅ PASS |
| 04 | RF-04 | Inventario | 1 | ✅ PASS |
| 05 | RF-05 | Lotes | 1 | ✅ PASS |
| 06 | RF-06 | Trazabilidad | 1 | ✅ PASS |
| 07 | RF-07 | Insumos | 1 | ✅ PASS |
| 08 | RF-08 | Herramientas | 1 | ✅ PASS |
| 09 | RF-09 | Unidades de medida | 1 | ✅ PASS |
| 10 | RF-10 | Mermas | 1 | ✅ PASS |
| 11 | RF-11 | Registro de insumos (soporte) | 1 | ✅ PASS |
| 12 | RF-12 | Uso de herramientas (soporte) | 1 | ✅ PASS |
| 13 | RF-13 | Ventas | 1 | ✅ PASS |
| 14 | RF-14 | Precios | 1 | ✅ PASS |
| 15 | RF-15 | Clientes | 2 | ✅ PASS |
| 16 | RF-16 | Cuentas por cobrar | 1 | ✅ PASS |
| 17 | RF-17 | Cuentas por pagar | 1 | ✅ PASS |
| 18 | RF-18 | Compras | 1 | ✅ PASS |
| 19 | RF-19 | Ornatos | 1 | ✅ PASS |
| 20 | RF-20 | Ampliación | 1 | ✅ PASS |
| 21 | RF-21 | Proveedores | 1 | ✅ PASS |
| 22 | RF-22 | Tareas | 1 | ✅ PASS |
| 23 | RF-23 | Recolección de semillas | 1 | ✅ PASS |
| 24 | RF-24 | Reportes | 2 | ✅ PASS |
| 25 | RF-25 | Dashboard | 4 | ✅ PASS |
| 26 | RF-26 | Usuarios | 6 | ✅ PASS |
| 27 | RF-27 | Roles | 1 | ✅ PASS |
| 28 | RF-28 | Auditoría | 1 | ✅ PASS |
| 29 | RF-29 | Respaldos | 1 | ✅ PASS |
| 30 | RF-30 | Notificaciones (soporte) | 1 | ✅ PASS |
| 31 | RF-31 | Recuperación de contraseña (soporte) | 2 | ✅ PASS |

## Matriz detallada

| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-ACE-101 | Aceptación · UI | Abrir /dashboard/plantas y verificar que el módulo "Plantas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf01PlantasCargaYEsOperativa` (1.04 s) |
| RF-02 | El sistema debe permitir administrar el catálogo de especies botánicas asociadas a las plantas. | CP-ACE-102 | Aceptación · UI | Abrir /dashboard/especies y verificar que el módulo "Especies" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf02EspeciesCargaYEsOperativa` (0.99 s) |
| RF-03 | El sistema debe permitir administrar las ubicaciones físicas del vivero y su capacidad. | CP-ACE-103 | Aceptación · UI | Abrir /dashboard/ubicaciones y verificar que el módulo "Ubicaciones" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf03UbicacionesCargaYEsOperativa` (1.01 s) |
| RF-04 | El sistema debe consolidar el inventario de plantas, insumos, herramientas y lotes en una sola vista. | CP-ACE-104 | Aceptación · UI | Abrir /dashboard/inventario y verificar que el módulo "Inventario" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf04InventarioCargaYEsOperativo` (1.01 s) |
| RF-05 | El sistema debe permitir administrar lotes con su estado, categoría, origen y existencias. | CP-ACE-105 | Aceptación · UI | Abrir /dashboard/lotes y verificar que el módulo "Lotes" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf05LotesCargaYEsOperativo` (1.05 s) |
| RF-06 | El sistema debe permitir consultar la trazabilidad y disponibilidad de los lotes. | CP-ACE-106 | Aceptación · UI | Abrir /dashboard/trazabilidad y verificar que el módulo "Trazabilidad" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf06TrazabilidadCargaYEsOperativa` (1.01 s) |
| RF-07 | El sistema debe permitir administrar insumos, su unidad de medida y sus existencias. | CP-ACE-107 | Aceptación · UI | Abrir /dashboard/insumos y verificar que el módulo "Insumos" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf07InsumosCargaYEsOperativo` (1.00 s) |
| RF-08 | El sistema debe permitir administrar herramientas, su disponibilidad y su uso en tareas. | CP-ACE-108 | Aceptación · UI | Abrir /dashboard/herramientas y verificar que el módulo "Herramientas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf08HerramientasCargaYEsOperativo` (1.03 s) |
| RF-09 | El sistema debe permitir administrar el catálogo de unidades de medida. | CP-ACE-109 | Aceptación · UI | Abrir /dashboard/unidades-medida y verificar que el módulo "Unidades de medida" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf09UnidadesMedidaCargaYEsOperativo` (1.06 s) |
| RF-10 | El sistema debe permitir registrar mermas y consultar el inventario en cuarentena. | CP-ACE-110 | Aceptación · UI | Abrir /dashboard/mermas y verificar que el módulo "Mermas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf10MermasCargaYEsOperativo` (1.05 s) |
| RF-11 | El sistema debe registrar el consumo de insumos por lote o por asignación de tarea. | CP-ACE-111 | Aceptación · UI | Abrir /dashboard/precios y verificar que el módulo "Registro de insumos (soporte)" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf11RegistroInsumosSoporteCargaYEsOperativo` (1.07 s) |
| RF-12 | El sistema debe registrar el uso de herramientas asociado a una asignación de tarea. | CP-ACE-112 | Aceptación · UI | Abrir /dashboard/tareas y verificar que el módulo "Uso de herramientas (soporte)" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf12UsoHerramientasSoporteCargaYEsOperativo` (1.37 s) |
| RF-13 | El sistema debe permitir procesar ventas de contado o crédito con detalles y pagos. | CP-ACE-113 | Aceptación · UI | Abrir /dashboard/ventas y verificar que el módulo "Ventas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf13VentasCargaYEsOperativa` (1.30 s) |
| RF-14 | El sistema debe permitir calcular y administrar los precios de los lotes. | CP-ACE-114 | Aceptación · UI | Abrir /dashboard/precios y verificar que el módulo "Precios" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf14PreciosCargaYEsOperativo` (1.10 s) |
| RF-15 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los clientes. | CP-ACE-115 | Aceptación · UI | Abrir /dashboard/clientes y verificar que el módulo "Clientes" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf15ClientesCargaYEsOperativo` (1.02 s) |
| RF-16 | El sistema debe permitir consultar cuentas por cobrar, registrar pagos y actualizar su estado. | CP-ACE-116 | Aceptación · UI | Abrir /dashboard/cuentas_cobrar y verificar que el módulo "Cuentas por cobrar" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf16CuentasCobrarCargaYEsOperativa` (1.00 s) |
| RF-17 | El sistema debe permitir administrar cuentas por pagar, sus pagos y saldos. | CP-ACE-117 | Aceptación · UI | Abrir /dashboard/cuentas-pagar y verificar que el módulo "Cuentas por pagar" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf17CuentasPagarCargaYEsOperativa` (1.00 s) |
| RF-18 | El sistema debe permitir registrar compras, sus detalles, recepción y actualización de stock. | CP-ACE-118 | Aceptación · UI | Abrir /dashboard/compras y verificar que el módulo "Compras" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf18ComprasCargaYEsOperativa` (1.03 s) |
| RF-19 | El sistema debe permitir registrar y administrar los servicios de ornato. | CP-ACE-119 | Aceptación · UI | Abrir /dashboard/ornatos y verificar que el módulo "Ornatos" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf19OrnatosCargaYEsOperativo` (1.02 s) |
| RF-20 | El sistema debe permitir registrar ampliaciones e intercambios de plantas entre clientes. | CP-ACE-120 | Aceptación · UI | Abrir /dashboard/ampliacion y verificar que el módulo "Ampliación" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf20AmpliacionCargaYEsOperativa` (1.03 s) |
| RF-21 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los proveedores. | CP-ACE-121 | Aceptación · UI | Abrir /dashboard/proveedores y verificar que el módulo "Proveedores" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf21ProveedoresCargaYEsOperativo` (1.06 s) |
| RF-22 | El sistema debe permitir asignar tareas, actualizarlas, completarlas o cancelarlas. | CP-ACE-122 | Aceptación · UI | Abrir /dashboard/tareas y verificar que el módulo "Tareas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf22TareasCargaYEsOperativa` (1.02 s) |
| RF-23 | El sistema debe permitir registrar recolecciones de semillas, sus detalles y su cierre. | CP-ACE-123 | Aceptación · UI | Abrir /dashboard/seed-collection y verificar que el módulo "Recolección de semillas" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf23RecoleccionSemillasCargaYEsOperativa` (1.04 s) |
| RF-24 | El sistema debe generar reportes por módulo con filtros y datos consolidados. | CP-ACE-124 | Aceptación · UI | Abrir /dashboard/reports y verificar que el módulo "Reportes" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf24ReportesCargaYEsOperativo` (1.07 s) |
| RF-25 | El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard. | CP-ACE-125 | Aceptación · UI | Abrir /dashboard y verificar que el módulo "Dashboard" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf25DashboardCargaYEsOperativo` (1.05 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-126 | Aceptación · UI | Abrir /dashboard/usuarios y verificar que el módulo "Usuarios" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf26UsuariosCargaYEsOperativo` (1.01 s) |
| RF-27 | El sistema debe permitir administrar roles y validar su existencia y asignación. | CP-ACE-127 | Aceptación · UI | Abrir /dashboard/roles y verificar que el módulo "Roles" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf27RolesCargaYEsOperativo` (1.01 s) |
| RF-28 | El sistema debe registrar y consultar la bitácora de auditoría de las acciones. | CP-ACE-128 | Aceptación · UI | Abrir /dashboard/auditlog y verificar que el módulo "Auditoría" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf28AuditoriaCargaYEsOperativa` (1.26 s) |
| RF-29 | El sistema debe permitir crear, listar, descargar y restaurar respaldos de la base de datos. | CP-ACE-129 | Aceptación · UI | Abrir /dashboard/backups y verificar que el módulo "Respaldos" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf29RespaldosCargaYEsOperativo` (1.06 s) |
| RF-30 | El sistema debe mostrar y administrar las notificaciones de cada usuario. | CP-ACE-130 | Aceptación · UI | Abrir /dashboard y verificar que el módulo "Notificaciones (soporte)" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf30NotificacionesSoporteCargaYEsOperativo` (1.52 s) |
| RF-31 | El sistema debe permitir generar, validar y consumir tokens de recuperación de contraseña. | CP-ACE-131 | Aceptación · UI | Abrir /recuperar-password y verificar que el módulo "Recuperación de contraseña (soporte)" carga con su contenido y tabla operativa. | La pantalla responde sin 404/500, muestra el encabezado esperado y la tabla principal termina de cargar sin errores AJAX. | ✅ PASS | `Tests\Aceptacion\Acceptance\ModulosCest::rf31RecuperacionPasswordSoporteCargaYEsOperativa` (1.14 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-001 | Aceptación · UI | Verificar que el formulario de login muestra usuario, contraseña y reCAPTCHA. | El formulario es visible y contiene los controles #email, #password, #loginBtn y .g-recaptcha. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::elFormularioDeLoginMuestraUsuarioPasswordYCaptcha` (2.31 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-002 | Aceptación · UI | Intentar iniciar sesión con credenciales inexistentes. | El sistema permanece en /login y muestra un mensaje de credenciales incorrectas. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::credencialesInvalidasMuestranErrorSinEntrar` (1.10 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-003 | Aceptación · UI | Iniciar sesión con el usuario administrador de pruebas. | El sistema redirige a /dashboard y muestra el sidebar y el saludo de bienvenida. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::credencialesValidasRedirigenAlDashboard` (1.51 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-004 | Aceptación · UI | Cerrar la sesión desde el sistema. | El sistema regresa al formulario de login y la sesión deja de estar activa. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::cerrarSesionRegresaAlLogin` (1.11 s) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-ACE-005 | Aceptación · UI | Acceder a una ruta protegida sin sesión iniciada. | El sistema redirige al login y no expone el módulo. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::unaRutaProtegidaRedirigeAlLoginSinSesion` (0.23 s) |
| RF-31 | El sistema debe permitir generar, validar y consumir tokens de recuperación de contraseña. | CP-ACE-006 | Aceptación · UI | Abrir el formulario de recuperación de contraseña. | El formulario de recuperación es visible con los campos de correo y envío. | ✅ PASS | `Tests\Aceptacion\Acceptance\AutenticacionCest::laRecuperacionDePasswordMuestraElFormulario` (0.22 s) |
| RF-25 | El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard. | CP-ACE-201 | Aceptación · UI | Abrir la página de inicio sin autenticación. | La portada carga y muestra la propuesta "Propagamos vida para el Estado Lara". | ✅ PASS | `Tests\Aceptacion\Acceptance\PublicoCest::laPaginaDeInicioEsPublicaYMuestraLaPropuesta` (0.51 s) |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-ACE-202 | Aceptación · UI | Abrir el catálogo público de especies. | El catálogo carga y describe las especies disponibles. | ✅ PASS | `Tests\Aceptacion\Acceptance\PublicoCest::elCatalogoPublicoListaEspecies` (0.15 s) |
| RF-24 | El sistema debe generar reportes por módulo con filtros y datos consolidados. | CP-ACE-203 | Aceptación · UI | Abrir la página de servicios. | La página describe la oferta de producción y suministro. | ✅ PASS | `Tests\Aceptacion\Acceptance\PublicoCest::laPaginaDeServiciosDescribeLaOferta` (0.07 s) |
| RF-25 | El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard. | CP-ACE-204 | Aceptación · UI | Abrir la página "Nosotros". | La página presenta al Instituto de Ecosocialismo del Estado Lara. | ✅ PASS | `Tests\Aceptacion\Acceptance\PublicoCest::laPaginaNosotrosPresentaLaInstitucion` (0.07 s) |
| RF-25 | El sistema debe mostrar indicadores, alertas y actividad reciente en el dashboard. | CP-ACE-205 | Aceptación · UI | Abrir la página de contacto. | La página muestra los canales de contacto del vivero. | ✅ PASS | `Tests\Aceptacion\Acceptance\PublicoCest::laPaginaDeContactoMuestraLosCanales` (0.07 s) |
| RF-02 | El sistema debe permitir administrar el catálogo de especies botánicas asociadas a las plantas. | CP-ACE-301 | Aceptación · CRUD | Crear una especie desde la interfaz, buscarla y eliminarla. | La especie aparece en la tabla tras crearla y desaparece después de la baja lógica. | ✅ PASS | `Tests\Aceptacion\Acceptance\CatalogosCest::crearYeliminarUnaEspecie` (4.79 s) |
| RF-03 | El sistema debe permitir administrar las ubicaciones físicas del vivero y su capacidad. | CP-ACE-302 | Aceptación · CRUD | Crear una ubicación desde la interfaz, buscarla y eliminarla. | La ubicación aparece en la tabla tras crearla y desaparece después de la baja lógica. | ✅ PASS | `Tests\Aceptacion\Acceptance\CatalogosCest::crearYeliminarUnaUbicacion` (4.40 s) |
| RF-15 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los clientes. | CP-ACE-303 | Aceptación · CRUD | Crear un cliente desde la interfaz, buscarlo y eliminarlo. | El cliente aparece en la tabla tras crearlo y desaparece después de la baja lógica. | ✅ PASS | `Tests\Aceptacion\Acceptance\CatalogosCest::crearYeliminarUnCliente` (4.59 s) |

## Entorno de ejecución

- **Aplicación:** `http://127.0.0.1:8099/` (servidor PHP embebido de aceptación).
- **Navegador:** `/opt/brave-bin/brave` en modo headless.
- **PHP:** 8.5.12.
- **Usuario de pruebas:** `e2e_admin` (rol Administrador).
- **Reporte JUnit:** `tests/aceptacion/_output/report.xml`.

## Notas

- La suite usa las claves de prueba oficiales de Google reCAPTCHA v2 en `tests/aceptacion/server/index.php`; no modifica `.env` ni la aplicación en producción.
- Las pruebas de aceptación de módulos validan carga real, encabezado, contenido y DataTables por AJAX. Las pruebas CRUD crean datos con prefijo `ACE` y los eliminan al final.
- Un caso `PASS` exige que el escenario completo haya terminado sin excepciones ni aserciones fallidas.
- Para regenerar esta matriz: `./tests/aceptacion/run.sh` y luego `php tests/tools/generate_aceptacion.php`.

