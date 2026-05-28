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
- Soporte dual: clase con namespace (prioridad) o función global legacy
- `class_exists($className, false)` para evitar autoload duplicado

### Controladores
- **Clase (namespace `SysInescolara\controllers`):** SuppliesController, TasksController, BatchesController, ClientsController, EmployeesController, PlantsController, SpeciesController, SuppliersController, UserController, AuditLogController, BackupsController, ReportsController, NotificationsController
- **Función global (legacy):** LoginController, DashboardController, RecuperarpasswordController, PublicController
- **Ubicación:** `app/controllers/`

### Modelos
- Namespace: `SysInescolara\models`
- Extienden `SysInescolara\core\Database` (que extiende PDO)
- Conexiones: `'default'` → `sysinescolara`, `'security'` → `SysInescolara-Seguridad`
- Ubicación: `app/models/`

### Traits
- `app/traits/ResponseTrait.php` — `jsonResponse()`, `handleError()`, `isAjaxRequest()`
- `app/traits/PermissionTrait.php` — `checkModuleAuth()`, `checkPermisoOrFail()`

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
- Controladores clase: método público por cada acción
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
- Controladores refactorizados a clases con traits

### Pendiente
- Catálogo público desde BD
- Módulo de Ventas/POS (movimiento_planta)
- Módulo de Cálculo de Precios
- Asignación de Tareas + Consumo de Insumos
- Trazabilidad fitosanitaria
- Herramientas + Uso de herramientas
- Asistencia de trabajadores
- Ajustes de inventario
- Migrar modelos a la nueva estructura BD
- Deploy en Render

## Datos Críticos
- Admin: `admin@inecolara.gob.ve` / `Admin123!`
- Timezone: `America/Caracas`
- reCAPTCHA v2 (site + secret key en .env)
- SMTP: configurar en .env para recovery de pass
- PHP bin: `C:\xampp\php\php.exe`
