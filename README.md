<p align="center">
  <img src="public/assets/images/logo_de_inecolara-sin-fondo.png" alt="Logo INESCOLARA" width="200">
</p>

<h1 align="center">SYS INESCOLARA</h1>

<p align="center">
  <strong>Sistema Integral de Gestión y Administración para Vivero</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-%23777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/JavaScript-ES6-%23F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript ES6">
  <img src="https://img.shields.io/badge/HTML5-%23E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS3-%231572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
  <img src="https://img.shields.io/badge/SQL-%234479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="SQL">
</p>
<p align="center">
  <img src="https://img.shields.io/badge/MySQL-8.0-%234479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.0">
  <img src="https://img.shields.io/badge/Apache-%23D42029?style=for-the-badge&logo=apache&logoColor=white" alt="Apache">
  <img src="https://img.shields.io/badge/Bootstrap-5-%237952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/jQuery-3-%230769AD?style=for-the-badge&logo=jquery&logoColor=white" alt="jQuery 3">
  <img src="https://img.shields.io/badge/DataTables-1.13-%23000?style=for-the-badge" alt="DataTables">
</p>
<p align="center">
  <img src="https://img.shields.io/badge/SweetAlert2-%23FF69B4?style=for-the-badge" alt="SweetAlert2">
  <img src="https://img.shields.io/badge/Font%20Awesome-6-%23528DD7?style=for-the-badge&logo=fontawesome&logoColor=white" alt="Font Awesome 6">
  <img src="https://img.shields.io/badge/Dompdf-%23000?style=for-the-badge" alt="Dompdf">
  <img src="https://img.shields.io/badge/Docker-ready-%232496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/Composer-%23885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer">
</p>
<p align="center">
  <img src="https://img.shields.io/badge/reCAPTCHA-v2-%23334eff?style=for-the-badge&logo=google&logoColor=white" alt="reCAPTCHA v2">
  <img src="https://img.shields.io/badge/Prettier-%23F7B93E?style=for-the-badge&logo=prettier&logoColor=black" alt="Prettier">
  <img src="https://img.shields.io/badge/PDO-%23FF6C37?style=for-the-badge&logo=php&logoColor=white" alt="PDO">
</p>

---

## Descripción

SYS INESCOLARA es una plataforma web para la gestión operativa del Vivero INESCOLARA. Construida sobre una arquitectura **MVC** con PHP 8.2 y Apache, implementa un patrón de módulos CRUD estandarizados con interfaz DataTables, modales AJAX y autenticación con Google reCAPTCHA v2.

El sistema gestiona dos bases de datos independientes: una para datos operativos del vivero y otra para seguridad y autenticación de usuarios.

---

## Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | PHP 8.2+, PDO, Apache (`mod_rewrite`), FrontController MVC propio |
| **Base de Datos** | MySQL 8.0 / MariaDB (arquitectura dual) |
| **Frontend** | Bootstrap 5, jQuery 3, DataTables 1.13, SweetAlert2, Font Awesome 6 |
| **Seguridad** | Google reCAPTCHA v2, `password_hash()`, sesiones PHP, permisos por rol |
| **Infraestructura** | Docker, Docker Compose, Apache |
| **Dev Tools** | Composer (PSR-4 autoload), Prettier |
| **Librerías** | Dompdf (reportes PDF), Google reCAPTCHA PHP Library |

---

## Arquitectura

### Flujo de petición

```
index.php → FrontController → {Modulo}Controller.php → función() → Vista
```

El **FrontController** (`app/controllers/FrontController.php`) analiza la URL, localiza el controlador correspondiente en `app/controllers/` y ejecuta la función asociada. Cada módulo expone cinco puntos de entrada: `index()`, `get_*()`, `add_ajax()`, `edit_ajax()`, `delete_ajax()`.

### Patrón de módulo

| Componente | Ruta | Rol |
| :--- | :--- | :--- |
| **Modelo** | `app/models/{Nombre}.php` | Extiende `Database`, 6 métodos CRUD + `bootstrapDefaults()` |
| **Controlador** | `app/controllers/{Nombre}Controller.php` | Funciones procedurales, manejo AJAX |
| **Vista** | `app/views/dashboard/{nombre}.php` | HTML + DataTable + modales Bootstrap (static backdrop) |
| **JavaScript** | `public/assets/js/dashboard/{nombre}.js` | ES Module: DataTable, AJAX, toasts, confirmaciones |

Los modelos incluyen `bootstrapDefaults()`, que migra automáticamente las columnas de la tabla al primer acceso, garantizando compatibilidad con esquemas preexistentes.

---

## Módulos del Sistema

| Módulo | Base de Datos | Tabla | PK | Modelo | Controlador |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Usuarios | `SysInescolara-Seguridad` | `usuarios` | `id` | `User` | `UsersController` |
| Especies | `sysinescolara` | `especies` | `id_especie` | `Species` | `SpeciesController` |
| Plantas | `sysinescolara` | `plantas` | `id_planta` | `Plant` | `PlantsController` |
| Lotes | `sysinescolara` | `lote` | `id_lote` | `Batch` | `BatchesController` |
| Clientes | `sysinescolara` | `cliente` | `id_cliente` | `Client` | `ClientsController` |
| Proveedores | `sysinescolara` | `proveedores` | `id_proveedor` | `Supplier` | `SuppliersController` |
| Empleados | `sysinescolara` | `trabajadores` | `id_trabajadores` | `Employee` | `EmployeesController` |

---

## Características

- **Arquitectura dual de bases de datos**: datos operativos (`sysinescolara`) y autenticación (`SysInescolara-Seguridad`) separados
- **Autenticación segura**: Google reCAPTCHA v2 en login, contraseñas con `password_hash()`, sesiones administradas
- **CRUD estandarizado**: todos los módulos siguen el mismo patrón DataTable + modales + AJAX con ES Modules
- **Migración automática de esquemas**: `bootstrapDefaults()` ajusta las tablas a las columnas esperadas sin intervención manual
- **Subida de imágenes**: con `ImageUploader`, redimensionamiento automático, thumbnails y lightbox (módulo Plantas)
- **Interfaz responsiva**: sidebar colapsable, tablas adaptables a móvil, skeleton loaders en carga de datos
- **Reportes PDF**: generación de documentos con Dompdf
- **JavaScript modular**: ES Modules con `import`/`export`, helpers centralizados (`helpers.js`, `ajax-handler.js`)
- **Permisos por módulo**: control de acceso granular (`PLANTAS_VIEW`, `CLIENTES_VIEW`, etc.)

---

## Instalación Local (XAMPP)

### Requisitos

- XAMPP con PHP 8.2+, MySQL y Apache (`mod_rewrite` habilitado)
- Composer

### Pasos

```bash
cd C:\xampp\htdocs
git clone https://github.com/Team-Project-Org-2025/sys-inescolara.git
cd sys-inescolara
composer install
```

### Base de Datos

1. Abre phpMyAdmin (`http://localhost/phpmyadmin`)
2. Crea la base de datos `sysinescolara` e importa `backups/SYSINESCOLARA.sql`
3. Crea la base de datos `SysInescolara-Seguridad` e importa `backups/SysInescolara-Seguridad.sql`

### Configuración

```env
DB_USER=root
DB_PASSWORD=
APP_URL=http://localhost/sys-inescolara/
```

### Acceso

```
http://localhost/sys-inescolara/
```

---

## Deploy en Render (Pruebas)

### Opción 1 — Render + Docker (recomendada)

1. **Sube el repositorio a GitHub**
2. **Crea una base de datos MySQL externa** (Render no ofrece MySQL administrado):
   - [Aiven](https://console.aiven.io/) (free tier, soporta múltiples bases de datos en una instancia)
   - [Railway](https://railway.app/) (crédito inicial gratuito)
3. **Conéctate a tu MySQL** y ejecuta los scripts de inicialización:
   ```bash
   mysql -h <host> -u <user> -p < scripts/setup-dbs.sql
   mysql -h <host> -u <user> -p sysinescolara < backups/SYSINESCOLARA.sql
   mysql -h <host> -u <user> -p SysInescolara-Seguridad < backups/SysInescolara-Seguridad.sql
   ```
4. **En Render**, crea un Web Service:
   - Conecta tu repositorio de GitHub
   - Runtime: selecciona **Docker**
   - Dockerfile Path: `./dockerfile`
5. **Configura las variables de entorno** en Render Dashboard:

   | Variable | Valor |
   | :--- | :--- |
   | `DB_HOST` | Host de tu MySQL |
   | `DB_PORT` | `3306` |
   | `DB_NAME` | `sysinescolara` |
   | `DB_USER` | Usuario MySQL |
   | `DB_PASSWORD` | Contraseña MySQL |
   | `DB_SEC_NAME` | `SysInescolara-Seguridad` |
   | `APP_URL` | `https://<tu-app>.onrender.com` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `RECAPTCHA_SITE_KEY` | Tu site key real |
   | `RECAPTCHA_SECRET_KEY` | Tu secret key real |

6. **Espera el build y deploy**. Render construirá la imagen Docker y desplegará automáticamente.

### Opción 2 — Render Blueprint

Si prefieres infraestructura como código, puedes usar el archivo `render.yaml` incluido:

1. En Render Dashboard: **New Blueprint**
2. Conecta tu repositorio
3. Render leerá `render.yaml` y creará el servicio automáticamente
4. Ajusta las variables de entorno en el Dashboard después del deploy

> **Nota**: Las llaves de reCAPTCHA v2 usadas en localhost **no funcionarán** en producción. Debes registrar un nuevo dominio en [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin) y usar las llaves correspondientes.

---

## Despliegue con Docker (Alternativa)

```bash
docker compose up -d
```

Esto levanta tres contenedores:
- **sys-inescolara-app**: Apache + PHP 8.2 en el puerto configurado
- **sys-inescolara-db**: MySQL 8.0
- **sys-inescolara-admin**: phpMyAdmin en el puerto configurado

Las variables de entorno se toman del archivo `.env`.

---

## Variables de Entorno

| Variable | Requerida | Descripción |
| :--- | :--- | :--- |
| `DB_HOST` | Sí | Host del servidor MySQL |
| `DB_NAME` | Sí | Base de datos principal |
| `DB_USER` | Sí | Usuario MySQL |
| `DB_PASSWORD` | Sí | Contraseña MySQL |
| `DB_SEC_NAME` | Sí | Base de datos de seguridad |
| `APP_URL` | Sí | URL pública de la aplicación |
| `RECAPTCHA_SITE_KEY` | Sí | Site key de Google reCAPTCHA v2 |
| `RECAPTCHA_SECRET_KEY` | Sí | Secret key de Google reCAPTCHA v2 |
| `APP_ENV` | No | `development` o `production` |
| `APP_DEBUG` | No | `true` o `false` |

---

<p align="center">
  Desarrollado para <strong>INESCOLARA</strong><br>
  © 2026
</p>
