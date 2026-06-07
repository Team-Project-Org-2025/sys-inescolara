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
- **Lista:** SuppliesController, TasksController, BatchesController, ClientsController, EmployeesController, PlantsController, SpeciesController, LocationsController, SuppliersController, UserController, RolesController, AuditLogController, BackupsController, ReportsController, NotificationsController, LoginController, DashboardController, RecuperarpasswordController, PublicController, InicioController, AuthController, InventarioController
- **Helpers compartidos:** `app/controllers/controller_helpers.php` (jsonResponse, checkModuleAuth, checkPermisoOrFail, isAjaxRequest, handleError, getRequestData, validateAndSanitize)
- **Ubicación:** `app/controllers/`

### Modelos
- Namespace: `SysInescolara\models`
- Extienden `SysInescolara\core\Database` (que extiende PDO)
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
- JS: `public/assets/js/` (sidebar.js, dashboard/*.js)
- Imágenes: `public/assets/images/`

## Bases de Datos

### `sysinescolara` (datos de negocio)
Tablas: especie, ubicacion, unidad_medida, plantas, lote, calculo_precio, planta_precio_vigente, trazabilidad, proveedores, insumo, herramienta, trabajadores, asistencia, tareas, asignar_tarea, consumo_insumos, uso_herramienta, cliente, movimiento_planta, movimiento_planta_detalle, movimiento_insumo, movimiento_insumo_detalle, ajuste_inventario

### `SysInescolara-Seguridad` (auth + logs)
Tablas: usuarios, roles, permisos, rol_permisos, usuario_permisos, sesiones_activas, auditoria_logs, notificaciones, password_resets

## Autenticación
- Login por `nombre_usuario` o `correo_electronico`
- Sesión con `session_regenerate_id(true)` post-login
- Permisos en `$_SESSION['user_permisos']`
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
- Fase 2 — Soft Deletes (11 modelos refactorizados, 14 controladores actualizados, SQL migration)

### Pendiente
- Fase 2 — Soft Deletes
- Migrar modelos a la nueva estructura BD
- Catálogo público desde BD

## Datos Críticos
- Admin: `admin@inecolara.gob.ve` / `Admin123!`
- Timezone: `America/Caracas`
- reCAPTCHA v2 (site + secret key en .env)
- SMTP: configurar en .env para recovery de pass
- PHP bin: `C:\xampp\php\php.exe`
