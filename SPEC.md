# SYSINECOLARA — Contexto del Proyecto y especificaciones técnicas

## Descripción
Sistema de gestión integral para el Vivero Institucional INECOLARA (Lara, Venezuela).
Control de inventario, ventas, producción, lotes, insumos, trabajadores, tareas y más.

## Stack
- PHP 8.2 (MVC nativo, sin framework)
- MySQL (2 bases: datos negocio + seguridad)
- Apache (XAMPP / Docker)
- Composer (dompdf ^3.1, phpmailer ^7.1)
- Bootstrap 5 + DataTables 1.13 + SweetAlert2 + FontAwesome 6
- Google reCAPTCHA v2
- JavaScript vanilla (ES Modules, AJAX polling)
- Docker (PHP 8.2-apache + MySQL 8.0 + phpMyAdmin)
- Render (producción, Dockerfile + Aiven MySQL)

## Arquitectura

### FrontController (`app/controllers/FrontController.php`)
- Namespace: `SysInescolara\controllers`
- Analiza URL → carga controlador + acción
- Rutas predefinidas: `catalogo`, `servicios`, `nosotros`, `contacto`
- Soporte dual: funciones globales (mayoría) y clases (FrontController)
- `class_exists($className, false)` para detectar si hay clase; si no, busca función global

### Controladores (35 archivos)
- **Patrón:** Funciones globales (sin clases ni namespace, excepto FrontController)
- **Helpers compartidos:** `app/controllers/controller_helpers.php`
  - `jsonResponse()`, `handleError()`, `isAjaxRequest()`, `checkModuleAuth()`, `checkPermisoOrFail()`, `checkModuleAccess()`, `checkCsrf()`, `getRequestData()`
- **Ubicación:** `app/controllers/`

| # | Controlador | Acciones principales |
|---|-------------|---------------------|
| 1 | `AmpliacionController` | index, add/edit/delete_ajax, get_exchanges, get_detail, get_lotes, get_plantas, get_ubicaciones, get_especies, buscar_clientes |
| 2 | `AuditlogController` | index, get_auditlogs |
| 3 | `AuthController` | login, index |
| 4 | `BackupsController` | index, get_backups, create_backup, restore_backup, delete_backup, download_backup |
| 5 | `ClientesController` | index, add/edit/delete_ajax, get_clients |
| 6 | `ComprasController` | index, add/edit_ajax, eliminar_ajax, obtener_compras/detalles, recibir_ajax, cancelar_ajax, agregar_planta/insumo/herramienta_rapido |
| 7 | `Cuentas_cobrarController` | index, obtener_lista/estadisticas/detalle/pagos/clientes, registrar_pago |
| 8 | `CuentasPagarController` | index, obtener_cuentas/detalle/pagos, registrar_pago, anular_pago |
| 9 | `DashboardController` | index + 32 sub-vistas (asistente, inventario, ventas, cuentas_cobrar, usuarios, plantas, lotes, etc.) |
| 10 | `EmpleadosController` | index, add/edit/delete_ajax, get_employees, get_detail |
| 11 | `EspeciesController` | index, add/edit/delete_ajax, get_species |
| 12 | `FrontController` | Clase router: parseUrl, loadController, renderNotFound |
| 13 | `HerramientasController` | index, add/edit/delete_ajax, get_tools, record_usage_ajax, get_usages |
| 14 | `InicioController` | index (redirect) |
| 15 | `InsumosController` | index, add/edit/delete_ajax, get_supplies |
| 16 | `InventarioController` | index, get_consolidated |
| 17 | `LoginController` | index, checkAuth, show, login, dashboard, logout, logout_ajax, check_session |
| 18 | `LotesController` | index, add/edit/delete_ajax, get_batches |
| 19 | `MermasController` | index, add/delete_ajax, get_mermas, get_quarantine |
| 20 | `NotificationsController` | get_unread, mark_read, mark_all_read, delete_notification, create_notification |
| 21 | `OrnatosController` | listar, guardar, actualizar, eliminar, detalles, buscar_clientes |
| 22 | `PlantasController` | index, add/edit/delete_ajax, get_plants |
| 23 | `PreciosController` | index, add/edit/delete_ajax, get_prices, get_detalle, get_lotes, get_insumos |
| 24 | `ProveedoresController` | index, add/edit/delete_ajax, get_suppliers |
| 25 | `PublicController` | catalogo, home, servicios, nosotros, contacto |
| 26 | `RecoleccionController` | index, add/edit/delete_ajax, get_recolecciones, get_details, completar_ajax, registrar_insumo_ajax |
| 27 | `RecuperarpasswordController` | index, enviar, cambiar, restablecer |
| 28 | `ReportsController` | index, get_modules, get_filters, get_report_data, generate_pdf |
| 29 | `RolesController` | index, add/edit/delete_ajax, get_roles |
| 30 | `TareasController` | index, get_assignments/assignment, assign_ajax, edit_ajax, complete_ajax, cancel_ajax |
| 31 | `TrazabilidadController` | index, add/edit/delete_ajax, get_trazabilidad, get_batches, update_estado_ajax, restore_ajax |
| 32 | `UbicacionesController` | index, add/edit/delete_ajax, get_locations |
| 33 | `UnidadesMedidaController` | index, add/edit/delete_ajax, get_units |
| 34 | `UsuariosController` | index, add/edit/delete_ajax, get_users |
| 35 | `VentasController` | listar, guardar, cancelar, detalles, buscar_lotes, precio_lote, buscar_clientes, trabajadores, comprobante |

### Modelos (32 archivos)
- Namespace: `SysInescolara\models`
- Extienden `SysInescolara\core\Database` (composición con PDO, no herencia)
- Acceso a BD vía `$this->db()->prepare()` (getter protegido que retorna `PDO`)
- Conexiones: `'default'` → `sysinescolara`, `'security'` → `SysInescolara-Seguridad`
- Interfaces: `ReadableInterface` (`getAll`, `getById`), `DeletableInterface` (`delete`, `exists`)

| Modelo | Tabla | Conexión | Interfaces |
|--------|-------|----------|------------|
| `Ampliacion` | `movimiento_planta` | default | Readable |
| `AuditLog` | `auditoria_logs` | security | Readable |
| `Backup` | N/A (file-based) | N/A | — |
| `CalculoPrecio` | `calculo_precio` | default | Readable, Deletable |
| `Cliente` | `cliente` | default | Readable, Deletable |
| `ConsumoInsumo` | `consumo_insumos` | default | Readable |
| `CuentaCobrar` | `venta` + `pago_venta` | default | — |
| `CuentaPagar` | `cuentas_pagar` | default | Readable, Deletable |
| `DashboardData` | N/A (read-only stats) | default | — |
| `Empleado` | `trabajadores` | default | Readable, Deletable |
| `Especie` | `especie` | default | Readable, Deletable |
| `Herramienta` | `herramienta` | default | Readable, Deletable |
| `Insumo` | `insumo` | default | Readable, Deletable |
| `Inventory` | N/A (consolidado) | default | — |
| `Lote` | `lote` | default | Readable, Deletable |
| `Merma` | `mermas_historico` | default | Readable, Deletable |
| `Notification` | `notificaciones` | security | — |
| `Ornato` | `ornatos` | default | Readable, Deletable |
| `PasswordReset` | `password_resets` | security | — |
| `Planta` | `plantas` | default | Readable, Deletable |
| `Proveedor` | `proveedores` | default | Readable, Deletable |
| `Purchase` | `compra` | default | Readable, Deletable |
| `Reports` | N/A (read-only) | default | — |
| `Role` | `roles` | security | Readable, Deletable |
| `SeedCollection` | `recoleccion_semillas` | default | Readable, Deletable |
| `Tarea` | `tareas` | default | Readable, Deletable |
| `Trazabilidad` | `trazabilidad` | default | Readable, Deletable |
| `Ubicacion` | `ubicacion` | default | Readable, Deletable |
| `UnidadMedida` | `unidad_medida` | default | Readable, Deletable |
| `UsoHerramienta` | `uso_herramienta` | default | Readable |
| `Usuario` | `usuarios` | security | — |
| `Venta` | `venta` | default | Readable, Deletable |

### Traits
- `app/traits/ValidationTrait.php` — Validación centralizada con 22 patrones regex (nombre, email, precio, cantidad, cédula, teléfono, RIF, etc.)

### Vistas (49 archivos)

**dashboard/ (32 vistas):**
`ampliacion`, `asistente`, `auditlog`, `backups`, `clientes`, `compras`, `cuentas-cobrar`, `cuentas-pagar`, `empleados`, `especies`, `herramientas`, `index`, `insumos`, `inventario`, `lotes`, `mermas`, `ornatos`, `perfil`, `plantas`, `precios`, `proveedores`, `reports`, `reports_pdf`, `roles`, `seed-collection`, `tareas`, `trazabilidad`, `ubicaciones`, `unidades-medida`, `usuarios`, `ventas`, `ventas_comprobante_pdf`

**auth/ (3 vistas):** `login`, `recuperar`, `reset-password`

**layouts/ (3 vistas):** `auth`, `dashboard`, `main`

**partials/ (4 vistas):** `dashboard-header`, `footer`, `header`, `sidebar`

**common/ (2 vistas):** `links`, `modal`

**public/ (5 vistas):** `catalogo`, `contacto`, `home`, `nosotros`, `servicios`

### Assets JavaScript (41 archivos)

**Root (5):** `asistente.js`, `auth.js`, `data.js`, `main.js`, `sidebar.js`

**dashboard/ (29):** `ampliacion`, `auditlog`, `backups`, `clientes`, `compras`, `cuentas-cobrar`, `cuentas-pagar`, `dashboard`, `empleados`, `especies`, `herramientas`, `insumos`, `inventario`, `lotes`, `mermas`, `notifications`, `ornatos`, `plantas`, `precios`, `proveedores`, `reports`, `roles`, `seed-collection`, `tareas`, `trazabilidad`, `ubicaciones`, `unidades-medida`, `usuarios`, `ventas`

**utils/ (7):** `ajax-handler.js`, `bs5-jquery-bridge.js`, `components.js`, `helpers.js`, `maxlength-counter.js`, `skeleton.js`, `validation.js`

### Assets CSS (3 archivos)
- `styles.css`, `sidebar.css`, `asistente.css`

### Helpers (7 archivos en `app/helpers/`)
| Archivo | Propósito |
|---------|-----------|
| `Auth.php` | Clase estática: acceso a sesión, autenticación, permisos |
| `Csrf.php` | Generación/validación/render de tokens CSRF |
| `env_loader.php` | Carga manual de `.env` antes del autoloader |
| `ImageUploader.php` | Subida de imágenes con validación y redimensionado |
| `Mailer.php` | Envío de emails vía SMTP, Resend API o MailLogger |
| `MailLogger.php` | Log de emails a archivo (fallback desarrollo) |
| `PdfHelper.php` | Generación de PDF vía Dompdf |

## Bases de Datos

### `sysinescolara` (datos de negocio)
**Tablas principales:** especie, ubicacion, unidad_medida, plantas, lote, calculo_precio, trazabilidad, proveedores, insumo, herramienta, trabajadores, tareas, asignar_tarea, consumo_insumos, uso_herramienta, cliente, movimiento_planta, movimiento_planta_detalle, ajuste_inventario, compra, compra_detalle, cuentas_pagar, pago_compra, venta, detalle_venta, pago_venta, cuentas_cobrar, ornatos, mermas_historico, recoleccion_semillas, recoleccion_semillas_detalle, abonos, actas, actividades, almacenes, almacen_herramientas, almacen_insumos, asistencias, bitacora_intervenciones, consumo_tierra, dosis, egresos, eventos, gastos, invernaderos, lecturas, siembras

### `SysInescolara-Seguridad` (auth + logs)
**Tablas:** usuarios, roles, permisos, rol_modulo_permiso, usuario_modulo_permiso, modulos, auditoria_logs, notificaciones, password_resets

**Schema completo:** `database/sysinescolara_definitiva.sql`

## Autenticación
- Login por `nombre_usuario` o `correo_electronico`
- Sesión con `session_regenerate_id(true)` post-login
- Acceso a sesión únicamente vía `SysInescolara\helpers\Auth` (clase estática)
- Métodos Auth: `id()`, `name()`, `email()`, `avatar()`, `roleId()`, `permisos()`, `check()`, `isAdmin()`, `hasPermiso()`, `hasModuleAccess()`, `set()`, `setField()`, `attempt()`, `logout()`
- Recuperación de contraseña vía token + email (SMTP real, Resend API o MailLogger como fallback)
- reCAPTCHA v2 en login y recuperación

## Convenciones
- Nombres de archivo en inglés, UI en español
- Modelos: singular (User, Plant, Batch, etc.)
- Tablas BD: singular (cliente, lote, insumo, especie)
- Primary keys: `id_<tabla>` (id_usuario, id_planta, etc.)
- `getAll()` usa `PK AS id` para DataTables
- Controladores: funciones globales (una función pública por cada acción), usando prefijo de helper privado
- Permisos: constantes `MODULO_ACCION` (PLANTAS_VIEW, PLANTAS_CREATE, etc.)
- Soft deletes vía `activo TINYINT(1)` en la mayoría de modelos

## Estado Actual (Julio 2026)

### Completado — Módulos CRUD Base
- Plantas, Especies, Lotes, Insumos, Proveedores, Empleados (trabajadores), Clientes, Tareas, Usuarios, Roles, Ubicaciones, Unidades de Medida, Herramientas

### Completado — Módulos Funcionales
- **Dashboard** con KPIs y estadísticas
- **Inventario** (ajustes, stock consolidado)
- **Ventas/POS** — flujo completo: selección de lotes, cálculo de precio, clientes, comprobante PDF
- **Compras** — órdenes de compra con detalles, recepción, cancelación, agregado rápido
- **Cuentas por Cobrar** — gestión de créditos, registro de pagos, estadísticas
- **Cuentas por Pagar** — cuentas, pagos, anulación
- **Precios** — cálculo por lote (mano de obra, insumos, agua, margen)
- **Ornatos** — gestión de ornamentos con clientes
- **Ampliación** — movimientos de planta/intercambios con detalle
- **Mermas** — registro de pérdidas y cuarentena
- **Trazabilidad** — seguimiento de lotes con cambios de estado
- **Recolección de Semillas** — registro con insumos y completado
- **Herramientas** — CRUD + registro de uso
- **Tareas/Asignaciones** — asignación con consumos de insumos y herramientas
- **Asistente IA** — chat interactivo (vista + JS + CSS)
- **Reportes** — generación de reportes con filtros y PDF
- **Backups** — respaldo y restauración de ambas bases de datos
- **Auditoría** — bitácora de acciones
- **Notificaciones** — polling cada 5s
- **Perfil de usuario** — avatar, cambio de contraseña
- **Frontend público** — home, catálogo, servicios, nosotros, contacto

### Completado — Infraestructura y Seguridad
- Autenticación con reCAPTCHA v2
- Recuperación de contraseña (SMTP + Resend API + MailLogger)
- CSRF Protection (token en sesión, hash_equals)
- Permisos granulares por módulo/acción (rol + usuario)
- Soft deletes en todos los modelos principales
- Validación dual (JS + PHP) con regex centralizados
- Transacciones atómicas en operaciones multi-tabla
- Encapsulamiento: acceso a session solo vía `Auth::*()`
- Database con composición (no hereda PDO)

### Completado — Refactors y Mejoras
- **Fase 1.1-1.4:** Helpers centralizados, validación, transacciones
- **Fase 2:** Soft deletes (11 modelos + 14 controladores)
- **Refactor Encapsulamiento:** 32 modelos migrados de `$this->db->` a `$this->db()->`, 8 controladores migrados a Auth::, todas las vistas y helpers migrados
- **Migración JS `data-*` → `row().data()`:** 24+ archivos (PR #151, #153)
- **Validaciones JS centralizadas:** `validation.js` con REGEX/MESSAGES
- **Estandarización UI:** botones icono+texto, tablas thead negro, modales Bootstrap, componentes JS
- **Docker + Render:** contenerización y despliegue
- **Correcciones Linux case-sensitive:** nombres de archivo normalizados
- **Rama `testing`:** flujo feature/bugfix → testing → develop

### Pendiente (Mejoras Futuras)
- **Exchange Rate / BCV:** Servicio de tasa Bs/USD con caché
- **Catálogo público dinámico:** "Desde BD" en frontend
- **Capa de servicios:** `app/services/` actualmente vacía
- **Mejoras transversales:** estandarización de auditoría, migración de interfaces, legibilidad de código

## Decisiones de Arquitectura

### Patrón General
- **Controladores como funciones:** Cada módulo usa una función global por acción. Elimina overhead de DI y reduce indirection. El acceso a sesión se hace exclusivamente vía `Auth::*()`.
- **Database con composición:** `Database` no extiende `PDO`. Usa composición (`private PDO $pdo`) y expone getter protegido `db(): PDO`. Evita que los modelos hereden métodos públicos de PDO.
- **Modelos no entidad pura:** Extienden `Database` (composición con PDO). Tienen 6 métodos CRUD (`getAll`, `getById`, `save`, `update`, `delete`, `exists`).
- **Vista mínima:** HTML + DataTables + Bootstrap modales. Sin frameworks de componente.
- **JavaScript modular:** ES Modules (`import`, `export`) con helpers centralizados.

### Bases de Datos
- **Dual DB:** `sysinescolara` (datos operativos) + `SysInescolara-Seguridad` (auth, logs, permisos). Separación de concerns, backup/redistribution independiente.
- **Soft Deletes:** `activo TINYINT(1)` en lugar de DELETE físico. JOINs agregan `AND tabla.activo = 1`.
- **FKs reales vs polimórficas:** `compra_detalle` migró de `tipo_item`+`id_item` a columnas separadas con FKs reales.

### Patrones de Código
- **Transacción consistente:** Operaciones multi-tabla envueltas en transacciones atómicas (`beginTransaction()` + `commit()`).
- **Validación centralizada (JS + PHP):** Regex por tipo de campo en `validation.js` y `ValidationTrait.php`.
- **CSRF via token de sesión:** `Csrf.php` con `random_bytes(32)`, `hash_equals()`. No se exige en login (reCAPTCHA) ni AJAX (cookie+header).
- **Auto-migración de esquemas:** `bootstrapDefaults()` en cada modelo migra columnas al primer acceso.

## Flujo de Ramas
`feature/*` o `bugfix/*` → PR a `testing` → PR a `develop`. No se hace commit directo a `develop`.

## Datos Críticos
- Admin: `admin@inecolara.gob.ve` / `Admin123!`
- Timezone: `America/Caracas`
- reCAPTCHA v2 (site + secret key en `.env`)
- SMTP: configurar en `.env` para recovery de pass
- PHP bin: `C:\xampp\php\php.exe`
- URL base: configurable via `BASE_URL` en `.env`
