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

Render no ofrece MySQL administrado, así que necesitas un MySQL externo. Usaremos **Aiven** (tier gratis, sin tarjeta de crédito).

### Paso 1 — Crear MySQL en Aiven

1. Ve a [console.aiven.io](https://console.aiven.io/) y regístrate (puedes usar Google o GitHub)
2. Haz clic en **Create service**
3. Selecciona **MySQL**
4. En **Service plan** elige **Free** (1 CPU, 1 GB RAM, 1 GB disco)
5. En **Cloud provider** elige el que aparezca por defecto (no hay opción en Free)
6. En **Region** elige la más cercana a ti
7. En **Service name** ponle `sys-inescolara-mysql`
8. Haz clic en **Create service**

Espera 2-3 minutos hasta que el estado cambie de *Rebuilding* a **Running**.

### Paso 2 — Obtener datos de conexión

1. En el dashboard del servicio, ve a **Overview**
2. En la sección **Connection information**, anota:
   - **Host** (ej: `mysql-sys-inescolara-mysql.f.aivencloud.com`)
   - **Port** (ej: `12691` — **no es 3306**, es un puerto distinto)
   - **User** (`avnadmin`)
   - **Password** (haz clic en el ojo para revelarla)
3. Ve a la pestaña **Databases** y crea dos bases de datos:
   - `sysinescolara`
   - `SysInescolara-Seguridad`

### Paso 3 — Importar los datos

Puedes usar MySQL Workbench, DBeaver, HeidiSQL o terminal. Conéctate con SSL obligatorio:

```bash
mysql -h <HOST> -P <PORT> -u avnadmin -p --ssl-mode=REQUIRED
```

Una vez conectado:

```sql
CREATE DATABASE IF NOT EXISTS sysinescolara;
CREATE DATABASE IF NOT EXISTS SysInescolara-Seguridad;
```

Luego importa los backups (desde otra terminal, fuera del cliente MySQL):

```bash
mysql -h <HOST> -P <PORT> -u avnadmin -p --ssl-mode=REQUIRED sysinescolara < backups/SYSINESCOLARA.sql

mysql -h <HOST> -P <PORT> -u avnadmin -p --ssl-mode=REQUIRED SysInescolara-Seguridad < backups/SysInescolara-Seguridad.sql
```

### Paso 4 — Subir el proyecto a GitHub

```bash
git add .
git commit -m "Ready for Render deploy"
git push
```

### Paso 5 — Crear Web Service en Render

1. Entra a [dashboard.render.com](https://dashboard.render.com)
2. Haz clic en **New +** → **Web Service**
3. Conecta tu repositorio de GitHub
4. Configura:
   - **Name**: `sys-inescolara`
   - **Runtime**: selecciona **Docker**
   - **Dockerfile Path**: `./dockerfile`
   - **Instance Type**: Free
5. Haz clic en **Create Web Service**

### Paso 6 — Configurar variables de entorno

En el dashboard del servicio, ve a **Environment** y agrega:

| Variable | Valor |
| :--- | :--- |
| `DB_HOST` | Host de Aiven (ej: `mysql-sys-inescolara-mysql.f.aivencloud.com`) |
| `DB_PORT` | Puerto de Aiven (ej: `12691`) |
| `DB_NAME` | `sysinescolara` |
| `DB_USER` | `avnadmin` |
| `DB_PASSWORD` | Password de Aiven |
| `DB_SSL` | `true` |
| `DB_SEC_NAME` | `SysInescolara-Seguridad` |
| `APP_URL` | `https://<tu-app>.onrender.com` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `RECAPTCHA_SITE_KEY` | (la que registres para el dominio de Render) |
| `RECAPTCHA_SECRET_KEY` | (la que registres para el dominio de Render) |

### Paso 7 — reCAPTCHA para producción

Las llaves de prueba `6LfCAPQsAAAAAKlgdHnkBf2utrrPZ5MjXK1chf4k` **solo funcionan en localhost**. Para Render:

1. Ve a [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
2. Registra una nueva clave, selecciona **reCAPTCHA v2** → **"No soy un robot"**
3. En **Dominios** agrega `https://<tu-app>.onrender.com`
4. Copia las llaves nuevas y pégalas en las variables de entorno de Render

### Resultado

Render construye la imagen Docker automáticamente y en unos minutos la app está online en `https://<tu-app>.onrender.com`.

> **Nota**: Aiven usa SSL obligatorio. La app lo soporta mediante `DB_SSL=true`. Si quieres verificar el certificado, descarga el CA cert desde Aiven (pestaña **Overview** → **CA Certificate**) y configúralo en `DB_CA_CERT=/ruta/ca.pem`.

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
