<p align="center">
  <img src="public/assets/images/logo_de_inecolara-sin-fondo.png" alt="Logo INESCOLARA" width="200">
</p>

<h1 align="center">SYS INESCOLARA</h1>

<p align="center">
  <strong>Sistema Integral de Gestión y Administración para Vivero</strong><br>
  <em>MVC moderno con PHP 8.2, DataTables, AJAX y reCAPTCHA</em>
</p>

---

## Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | PHP 8.2+, PDO, FrontController MVC propio |
| **Base de Datos** | MySQL (dual: `sysinescolara` + `SysInescolara-Seguridad`) |
| **Frontend** | Bootstrap 5, jQuery 3, DataTables, SweetAlert2, Font Awesome 6 |
| **Seguridad** | Google reCAPTCHA v2, `password_hash()`, sesiones, permisos por rol |
| **Dev Tools** | Composer (PSR-4 autoload), Prettier, Docker |
| **Librerías** | Dompdf (reportes PDF), reCAPTCHA PHP Library |

## Arquitectura

**MVC personalizado** con un único punto de entrada (`index.php` + `.htaccess`):

```
index.php → FrontController.php → {Controller}.php → función action() → Vista
```

- **FrontController** (`app/controllers/FrontController.php`): parsea la URL, carga el controlador y llama a la función correspondiente.
- **Controladores procedurales**: Cada módulo es un archivo PHP con funciones sueltas (`index()`, `get_*()`, `add_ajax()`, `edit_ajax()`, `delete_ajax()`) — sin clases, sin herencia.
- **Modelos** (`app/models/`): Extienden `Database` y usan `bootstrapDefaults()` para migrar columnas automáticamente al primer acceso.
- **Vistas** (`app/views/dashboard/`): HTML con DataTable server-side + modales Bootstrap para CRUD.
- **JS** (`public/assets/js/dashboard/`): ES Modules (`import`/`export`) que orquestan DataTable, AJAX, toasts y confirmaciones.

## Módulos del Sistema

Todos los módulos siguen el mismo patrón CRUD con DataTable + modales + AJAX:

| Módulo | Tabla (BD) | PK | Modelo | Controlador |
| :--- | :--- | :--- | :--- | :--- |
| **Usuarios** | `SysInescolara-Seguridad`.`usuarios` | `id` | `User` | `UsersController` |
| **Especies** | `sysinescolara`.`especies` | `id_especie` | `Species` | `SpeciesController` |
| **Plantas** | `sysinescolara`.`plantas` | `id_planta` | `Plant` | `PlantsController` |
| **Lotes** | `sysinescolara`.`lote` | `id_lote` | `Batch` | `BatchesController` |
| **Clientes** | `sysinescolara`.`cliente` | `id_cliente` | `Client` | `ClientsController` |
| **Proveedores** | `sysinescolara`.`proveedores` | `id_proveedor` | `Supplier` | `SuppliersController` |
| **Empleados** | `sysinescolara`.`trabajadores` | `id_trabajadores` | `Employee` | `EmployeesController` |

### Patrón de Módulo
Cada módulo nuevo requiere 4 archivos:

1. **Modelo** — `app/models/{Nombre}.php` (extends Database, 6 métodos: `getAll`, `getById`, `exists`, `getLastInsertId`, `add`, `update`, `delete`)
2. **Controlador** — `app/controllers/{Nombre}Controller.php` (procedural, 5 entry points: `index`, `get_*`, `add_ajax`, `edit_ajax`, `delete_ajax`)
3. **Vista** — `app/views/dashboard/{nombre}.php` (HTML con DataTable + modales estáticos)
4. **JS** — `public/assets/js/dashboard/{nombre}.js` (ES Module con DataTable AJAX CRUD)

## Características

- **Doble base de datos**: Datos de negocio (`sysinescolara`) separados de autenticación (`SysInescolara-Seguridad`)
- **Google reCAPTCHA v2** en login (llaves configurables en `.env`)
- **Subida de imágenes** con `ImageUploader` (redimensionamiento, thumbnails, lightbox)
- **Skeleton loaders** mientras carga la DataTable
- **Permisos por módulo** (`PLANTAS_VIEW`, `CLIENTES_VIEW`, etc.)
- **bootstrapDefaults()**: Migración automática de columnas al primer acceso al modelo
- **Responsive**: Sidebar colapsable, tablas responsivas, breakpoints móviles
- **PDF**: Generación de reportes con Dompdf
- **Toast notifications** + **confirm dialogs** vía helpers JS

## Instalación

### Requisitos

- XAMPP con PHP 8.2+, MySQL, Apache (`mod_rewrite` activo)
- Composer

### Pasos

```bash
cd C:\xampp\htdocs
git clone https://github.com/Team-Project-Org-2025/sys-inescolara.git
cd sys-inescolara
composer install
```

### Base de Datos

1. Crear BD `sysinescolara` e importar `backups/SYSINESCOLARA.sql`
2. Crear BD `SysInescolara-Seguridad` e importar `backups/SysInescolara-Seguridad.sql`

### Configuración

Copiar `.env` con la configuración deseada:

```env
DB_NAME=sysinescolara
DB_SEC_NAME=SysInescolara-Seguridad
APP_URL=http://localhost/sys-inescolara/
RECAPTCHA_SITE_KEY=tu_site_key
RECAPTCHA_SECRET_KEY=tu_secret_key
```

### Acceso

`http://localhost/sys-inescolara/`

---

<a href="https://next.ossinsight.io/widgets/official/compose-recent-top-contributors?repo_id=1195424621" target="_blank" style="display: block" align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/compose-recent-top-contributors/thumbnail.png?repo_id=1195424621&image_size=auto&color_scheme=dark" width="373" height="auto">
    <img alt="Top Contributors of Team-Project-Org-2025/sys-inescolara - Last 28 days" src="https://next.ossinsight.io/widgets/official/compose-recent-top-contributors/thumbnail.png?repo_id=1195424621&image_size=auto&color_scheme=light" width="373" height="auto">
  </picture>
</a>

---
<p align="center">
  Desarrollado para <strong>INESCOLARA</strong><br>
  © 2026
</p>
