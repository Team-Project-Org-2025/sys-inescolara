# SYSINECOLARA — Contexto del Proyecto

## Descripción
Sistema de gestión integral para el Vivero Institucional INECOLARA (Lara, Venezuela).
Control de inventario, ventas, producción, lotes, insumos, trabajadores, tareas y más.

## Stack
- PHP 8.2 (MVC nativo, sin framework)
- MySQL (2 bases: datos negocio + seguridad)
- Apache (XAMPP)
- Composer (dompdf, phpmailer)
- Bootstrap 5 + DataTables + SweetAlert2 + FontAwesome
- Google reCAPTCHA v2
- JavaScript vanilla (AJAX, polling)

## Arquitectura

### FrontController (`app/controllers/FrontController.php`)
- Namespace: `SysInescolara\controllers`
- Analiza URL → carga controlador + acción
- Rutas predefinidas: `catalogo`, `servicios`, `nosotros`, `contacto`
- Soporte único: funciones globales (sin clases)
- `class_exists($className, false)` para detectar si hay clase (ya no usado, legacy)

### Controladores
- **Función global:** Todos los controladores usan funciones globales (sin clases ni namespace)
- **Lista:** SuppliesController, TasksController, BatchesController, ClientsController, EmployeesController, PlantsController, SpeciesController, LocationsController, SuppliersController, UserController, RolesController, AuditlogController, BackupsController, ReportsController, NotificationsController, LoginController, DashboardController, RecuperarpasswordController, PublicController, InicioController, AuthController, InventarioController, UnitmeasuresController, OrnatosController, Cuentas_cobrarController, MermasController, ComprasController, Cuentas_pagarController, VentasController, PricesController, AmpliacionController, ToolsController
- **Helpers compartidos:** `app/controllers/controller_helpers.php` (jsonResponse, checkModuleAuth, checkPermisoOrFail, isAjaxRequest, handleError, getRequestData, validateAndSanitize, checkCsrf)
- **Ubicación:** `app/controllers/`

### Modelos
- Namespace: `SysInescolara\models`
- Extienden `SysInescolara\core\Database` (composición con PDO, no herencia)
- Acceso a BD vía `$this->db()->prepare()` (getter protegido que retorna `PDO`)
- Conexiones: `'default'` → `sysinescolara`, `'security'` → `SysInescolara-Seguridad`
- Ubicación: `app/models/`

### Traits (solo disponibles como referencia, ya no usados en controladores)
- `app/traits/ResponseTrait.php` — `jsonResponse()`, `handleError()`, `isAjaxRequest()` → migrado a `controller_helpers.php`
- `app/traits/PermissionTrait.php` — `checkModuleAuth()`, `checkPermisoOrFail()` → migrado a `controller_helpers.php`

### Vistas
- Dashboard: `app/views/dashboard/*.php`
- Auth: `app/views/auth/` (login, recuperar, reset-password)
- Layouts: `app/views/layouts/` (auth.php)
- Partials: `app/views/partials/` (sidebar, dashboard-header)

### Assets
- CSS: `public/assets/css/styles.css` + `sidebar.css`
- JS: `public/assets/js/` (sidebar.js, dashboard/*.js, utils/validation.js, utils/ajax-handler.js, utils/helpers.js, utils/components.js)
- Imágenes: `public/assets/images/`

## Bases de Datos

### `sysinescolara` (datos de negocio)
**Tablas actuales (5 eliminadas, nuevas FKs en compra_detalle):**
especie, ubicacion, unidad_medida, plantas, lote, calculo_precio, trazabilidad, proveedores, insumo, herramienta, trabajadores, tareas, asignar_tarea, consumo_insumos, uso_herramienta, cliente, movimiento_planta, movimiento_planta_detalle, ajuste_inventario, **compra**, **compra_detalle**, **cuentas_pagar**, **pago_compra**

**Tablas eliminadas (redundantes/sin uso):**
- `asistencia` → reemplazada por lógica en tareas
- `movimiento_insumo` + `movimiento_insumo_detalle` → vacías, solapadas con compra + ajuste_inventario
- `planta_precio_vigente` → redundante, usar calculo_precio
- `recoleccion_semillas` + `recoleccion_semillas_detalle` → nuevas FKs: id_insumo, columnas activo, fecha_asignacion
- **NOTA:** Schema completo en `sysinescolara_definitiva.sql`

### `SysInescolara-Seguridad` (auth + logs)
Tablas: usuarios, roles, permisos, rol_permisos, usuario_permisos, auditoria_logs, notificaciones, password_resets

**Tablas eliminadas (sin uso):**
- `sesiones_activas` → vacía, sin uso

**Schema definitivo:** `sysinescolara_definitiva.sql`

## Autenticación
- Login por `nombre_usuario` o `correo_electronico`
- Sesión con `session_regenerate_id(true)` post-login
- Acceso a sesión únicamente vía `SysInescolara\helpers\Auth` (clase estática)
- Métodos Auth: `id()`, `name()`, `email()`, `avatar()`, `roleId()`, `permisos()`, `check()`, `isAdmin()`, `hasPermiso()`, `set()`, `setField()`, `attempt()`, `logout()`
- Recuperación de contraseña vía token + email (SMTP pendiente de configurar)

## Convenciones
- Nombres de archivo en inglés, UI en español
- Modelos: singular (User, Plant, Batch, etc.)
- Tablas BD: singular (cliente, lote, insumo, especie)
- Primary keys: `id_<tabla>` (id_usuario, id_planta, etc.)
- `getAll()` usa `PK AS id` para DataTables
- Controladores: funciones globales (una función pública por cada acción)
- Permisos: constantes `MODULO_ACCION` (PLANTAS_VIEW, PLANTAS_CREATE, etc.)

## Estado Actual
### Completado
- CRUD completo: Plantas, Especies, Lotes, Insumos, Proveedores, Trabajadores, Clientes, Tareas, Usuarios
- Dashboard con KPIs y reportes
- Auditoría (bitácora)
- Respaldos (mysql dump)
- Notificaciones (polling cada 5s)
- Perfil de usuario (avatar, cambio pass)
- Permisos granulares por usuario
- Frontend responsive público
- Login responsive (mobile)
- Recuperación de contraseña (backend listo, SMTP pendiente)
- Controladores refactorizados a funciones globales (sin clases ni traits)
- Módulo Inventario (modelo, controlador, vista, permisos INVENTARIO_ADJUST)
- Fase 1.1 — Validation Helper (app/helpers/Validation.php)
- Fase 1.2 — JS Validation Helper (public/assets/js/utils/validation.js) + migración supplies.js, inventario.js
- Fase 1.3 — controller_helpers.php (getRequestData, validateAndSanitize, jsonResponse mejorado, redundancia eliminada)
- Fase 1.4 — Transacciones en Tasks/Tools (modelos AsignarTarea, ConsumoInsumo, UsoHerramienta creados; Task.assignTaskWithConsumptions, Tool.recordUsageWithStateUpdate)
- **Fase 2 — Soft Deletes** (11 modelos refactorizados, 14 controladores actualizados, SQL migration)
- **Linux case‑sensitive fixes:** renames `AuditLogController→AuditlogController`, `UnitMeasuresController→UnitmeasuresController`, `Locations.php→locations.php`
- **plantas_precio_vigente removed:** referencias eliminadas en Plant::getAll(), DashboardData::getStats(), PriceCalculation::add(), PricesController
- **scripts/ folder eliminated:** todas las migraciones fragmentadas removidas
- **sysinescolara_definitiva.sql creado:** esquema completo con 5 tablas eliminadas, FKs reales en compra_detalle, columnas nuevas (fecha_recepcion, activo, id_insumo)

### Refactor de Encapsulamiento (rama `feature/refactor-encapsulamiento`)
- **Database.php:** `abstract class Database` ya no extiende PDO. Usa composición con `private PDO $pdo` + getter protegido `db(): PDO`. Métodos `beginTransaction()`, `commit()`, `rollback()` como públicos con verificación `inTransaction()`. Elimina fuga de métodos públicos de PDO a los modelos.
- **32 modelos:** `$this->db->` → `$this->db()->`. Los 3 modelos con transacciones duplicadas (`Inventory`, `Merma`, `Trazabilidad`) ahora heredan de `Database`.
- **app/helpers/Auth.php:** Clase estática que centraliza TODO el acceso a `$_SESSION['user_*']`. 0 referencias a `$_SESSION['user_*']` fuera de Auth.php después de migrar 50 archivos.
- **Controladores migrados (8):** `LoginController`, `UserController`, `DashboardController`, `Cuentas_cobrarController`, `InventarioController`, `MermasController`, `NotificationsController`, `ReportsController`
- **Helpers migrados (2):** `controller_helpers.php` (`checkModuleAuth`, `checkPermisoOrFail`), `sidebar.php` (`hasPermiso`)
- **Vistas migradas (7):** `ampliacion.php`, `seed-collection.php`, `trazabilidad.php`, `reports.php`, `index.php`, `usuarios.php`, `dashboard-header.php`
- **AuditLog.php:** `record()` migrado a `Auth::check()` + `Auth::id()`

### Rama `testing` (integración previa a `develop`)
- Creada como rama de integración para PRs antes de mergear a `develop`
- **PRs procesados:** `feature/pruebas` (CSRF), `feature/validaciones-js` (validaciones), `feature/notificacion` (stock crítico campana)
- **Bugfixes integrados:** `bugfix/cuentas-por-cobrar`, `bugfix/herramientas`, `bugfix/monitoreo`, `bugfix/Lote`, `bugfix/Ventas`, `bugfix/Cuentas-por-pagar`, `bugfix/empleados`, `bugfix/tareas`
- **Estandarización UI (develop):** `fix/standardize-buttons-tables` — botones icono+texto, tablas thead negro, modales Bootstrap componentizados, botones DataTables en `components.js`

### Migración JS `data-*` → `row().data()` (PR #151, #153)
- 24 archivos JS del dashboard migrados de `data-<campo>` HTML a `tabla.row($(this).closest('tr')).data()`
- **Cat A:** `ampliacion.js`, `auditlog.js`
- **Cat B1:** `plants.js`, `usuarios.js`, `roles.js`, `supplies.js`, `tools.js`, `unit-measures.js`, `employees.js`, `locations.js`
- **Cat B2:** `clients.js`, `species.js`, `suppliers.js`, `prices.js`, `mermas.js`
- **Cat B3:** `ornatos.js`, `cuentas-cobrar.js`, `compras.js`, `cuentas-pagar.js`, `seed-collection.js`
- **Cat C:** `ventas.js`
- **Cat D:** `task.js`, `batches.js`, `trazabilidad.js`
- Se mantiene `Helpers.escapeHtml()` en renders (XSS prevention)

### Bugfix Trazabilidad (PR #152)
- Validación de fecha futura en módulo trazabilidad
- Reglas añadidas a `validation.js` (`validateNoFutureDate`, `fechaFuturaCheck`)

### Validaciones JS Centralizadas (PR de `feature/validaciones-js`)
- `public/assets/js/utils/validation.js` con `REGEX`, `MESSAGES`, `validateField`, `validateSelect`, `validateNoFutureDate`, `setupRealTimeValidation`, `validateForm`, `clearValidation`, `hoy`
- Reglas actualizadas en 8 módulos: `supplies.js`, `suppliers.js`, `mermas.js`, `cuentas-pagar.js`, `ventas.js`, `ornatos.js`, `task.js`, `prices.js`
- Validaciones JS coinciden con backend PHP (referencias 6 dígitos, stock usa `'cantidad'`, RIF sin regex, fechas con `fechaFuturaCheck`)

### CSRF Protection (PR #170)
- `app/helpers/Csrf.php` creado con namespace `SysInescolara\helpers`
  - `generate(): string` — token 64 hex chars en `$_SESSION['_csrf_token']`
  - `validate(string $token): void` — `hash_equals()`, HTTP 419 en fallo
  - `render(): string` — `<input type="hidden" name="_csrf_token" value="...">`
- `checkCsrf()` eliminado de `LoginController` (protegido por reCAPTCHA)
- `checkCsrf()` eliminado de `checkModuleAuth()` (dashboard usa AJAX con cookie+header)
- `checkCsrf()` definido en `controller_helpers.php` como función standalone para uso futuro
- `Csrf::render()` añadido al formulario de login

### Pendiente (Fases 3‑6 del PLAN_MEJORAS)
- **Fase 3 — MÓDULO VENTAS / POS:** tablas (venta, detalle_venta, crédito, cuentas_cobrar, pago_venta), flujo POS, PDF, ventas crédito/contado
- **Fase 4 — MÓDULO COMPRAS:** migración compra_detalle a FKs reales, fecha_recepcion, validación duplicados, actualizaciones automáticas
- **Fase 5 — EXCHANGE RATE / BCV:** ExchangeRateService con caché de tasa Bs/USD
- **Fase 6 — MEJORAS TRANSVERSALES:** 6.1 auditoría estandarizada, 6.2 código legible, 6.3 migrar DeletableInterface, 6.3 migrar modelos a nueva estructura BD
- **Fase 6.4 — Catálogo público:** "Desde BD" en frontend
- **Fase 7 — Exchange Rate:** ExchangeRateService para tasa BCV

## Archivos Clave

### Específicos
- `app/controllers/controller_helpers.php` — centraliza validación y helper functions
- `app/helpers/Validation.php` — helper de validación centralizado (Fase 1.1)
- `app/helpers/Auth.php` — clase estática para acceso a sesión (Refactor Encapsulamiento)
- `public/assets/js/utils/validation.js` — validation helper del lado del cliente (REGEX, MESSAGES, validateForm, setupRealTimeValidation, validateNoFutureDate)
- `app/helpers/Csrf.php` — CSRF token generation/validation/render (PR #170)
- `app/controllers/FrontController.php` — router de patrón MVC, patrones de rutas predefinidas
- `app/models/Plant.php`, `DashboardData.php`, `PriceCalculation.php`, `PurchasesController.php` — fixes de la FASE 2 (Linux case‑sensitive, planta_precio_vigente removal, lastInsertId, duplicate batch validation)

### Consolidados
- `sysinescolara_definitiva.sql` — esquema completo definitivo con todas las mejoras y eliminaciones
- `app/views/dashboard/locations.php` (renamed from Locations.php)
- `app/controllers/AuditlogController.php` (renamed from AuditLogController.php)
- `app/controllers/UnitmeasuresController.php` (renamed from UnitMeasuresController.php)

## Decisiones de Arquitectura

### Patrón General
- **Controladores como funciones:** Cada módulo usa una función global por acción (`function index() { ... }`) en vez de clases. Elimina overhead de DI y reduce indirection. El acceso a sesión se hace exclusivamente vía `Auth::*()`.
- **Database con composición:** `Database` ya no extiende `PDO`. Usa composición (`private PDO $pdo`) y expone getter protegido `db(): PDO`. Esto evita que los modelos hereden métodos públicos de PDO (como `quote()`, `getAttribute()`) que no deberían estar expuestos.
- **Modelos no entidad pura:** Extienden `SysInescolara\core\Database` (composición con PDO, no herencia). Acceden a PDO vía `$this->db()->`. Tienen 6 métodos CRUD (`getAll`, `getById`, `save`, `update`, `delete`, `exists`). Eliminan necesidad de ORM externo.
- **Vista mínima:** HTML + DataTables + Bootstrap modales (sin frameworks de componente). Mantiene rendimiento ligero y máximo control sobre UI.
- **JavaScript modular:** ES Modules (`import`, `export`) con helper centralizado (`ajax-handler.js`, `validation.js`). Evita código duplicado entre módulos.

### Bases de Datos
- **Dual DB:** `sysinescolara` (datos operativos del vivero) + `SysInescolara-Seguridad` (autenticación, logs, permisos). Separa concernes y permite backup/redistribution independiente.
- **Soft Deletes:** Usamos `activo TINYINT(1)` en lugar de DELETE físico para todo. JOINs agregan `AND tabla.activo = 1`. Elimina rare delete-restore loops.
- **FKs reales vs polimórficas:** `compra_detalle` migró de `tipo_item`+`id_item` a `id_insumo`/`id_herramienta`/`id_planta` separados con FKs reales. Más eficiente, más fácil de indexar y consulta.

### Patterns de Código
- **Transacción consistente:** Operaciones multi‑tabla envuelven inserciones/actualizaciones en transacciones atómicas (`beginTransaction()` + `commit()` de Database). Implementado en `Task.assignTaskWithConsumptions` + `Tool.recordUsageWithStateUpdate`.
- **Validation centralizado (JS + PHP):** Regex por tipo de campo en `validation.js` (texto, correo, password, telefono, fechaFormato, decimal, entero, alfanumerico, codigo, rif, cedula, referencia) y en `Validacion.php` server-side. `setupRealTimeValidation()` + `validateForm()` en cliente; `validateAndSanitize()` en servidor.
- **CSRF via token de sesión:** `Csrf.php` genera token con `random_bytes(32)` almacenado en `$_SESSION['_csrf_token']`. Se renderiza como hidden input en formularios. Validación con `hash_equals()`. No se exige en login (reCAPTCHA) ni en AJAX (cookie+header).
- **Ajuste automático de esquemas:** `bootstrapDefaults()` en cada modelo migra automáticamente las columnas de la tabla al primer acceso. Sin necesidad de migraciones manuales.

### Principios de Operación
- **Timezone:** `America/Caracas` para toda la app.
- **Case‑sensitive:** Linux (Render) sistemas de archivos son case‑sensitive. Nombres de controlador/vista deben coincidir exactamente con `ucfirst(sanitize(ruta))`.
- **AJAX:** Chequea `X-Requested-With: XMLHttpRequest` header (en `isAjaxRequest()`). Protegido por cookie de sesión + header, no por CSRF token.
- **Permisos granulares:** Constantes `MODULO_ACCION` (PLANTAS_VIEW, PLANTAS_CREATE, etc.). `checkPermisoOrFail()` en cada controlador.
- **Flujo de ramas:** `feature/*` o `bugfix/*` → PR a `testing` → PR a `develop`. No se hace commit directo a `develop`.

### Referencias Externas
- **BarkiOS:** Referencia para estructura y templates. Nuestros módulos siguen patrón similar pero agregamos sistema de permisos granular.
- **Google reCAPTCHA v2:** En login + recovery. reCAPTCHA Admin → `site key` + `secret key` en `.env`.

### Resúmenes
- **Fase 1.1‑1.4:** Helper functions centralizados + validación + transacciones.
- **Fase 2:** Soft deletes (11 modelos + 14 controladores).
- **Migración JS `data-*` → `row().data()`:** 24 archivos (PR #151, #153).
- **Validaciones JS centralizadas:** `validation.js` con REGEX/MESSAGES, reglas en 8 módulos.
- **CSRF Protection:** `Csrf.php` creado, login sin checkCsrf (reCAPTCHA), dashboard sin checkCsrf (AJAX).
- **Estandarización UI:** botones icono+texto, tablas thead negro, modales/components Bootstrap.
- **Rama `testing`:** flujo de integración feature/bugfix → testing → develop.

## Datos Críticos
- Admin: `admin@inecolara.gob.ve` / `Admin123!`
- Timezone: `America/Caracas`
- reCAPTCHA v2 (site + secret key en .env)
- SMTP: configurar en .env para recovery de pass
- PHP bin: `C:\xampp\php\php.exe`
