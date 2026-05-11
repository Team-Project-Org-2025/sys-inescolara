<p align="center">
  <img src="public/assets/images/logo_de_inecolara-sin-fondo.png" alt="Logo INESCOLARA" width="200">
</p>

<h1 align="center">SYS INESCOLARA</h1>

<p align="center">
  <strong>Sistema Integral de Gestión y Administración para Vivero</strong><br>
  <em>Una solución robusta, moderna y escalable para el control administrativo 🌿</em>
</p>

---

## 📖 Descripción

**SYS INESCOLARA** es una plataforma web desarrollada para optimizar la gestión operativa del Vivero INESCOLARA. Construida bajo una arquitectura **MVC (Modelo-Vista-Controlador)** personalizada en PHP 8.2, el sistema ofrece una experiencia de usuario premium con un enfoque en la eficiencia, seguridad y facilidad de despliegue.

El proyecto está optimizado para ejecutarse en entornos locales mediante **XAMPP**, manejando dinámicamente las rutas para funcionar correctamente en subdirectorios de `htdocs`.

## ✨ Características Principales

### 🔐 Gestión de Usuarios Avanzada
- **CRUD Completo**: Creación, lectura, actualización y eliminación de usuarios.
- **DataTables Integration**: Listado interactivo con paginación, búsqueda instantánea y ordenamiento dinámico.
- **Seguridad**: Manejo de contraseñas mediante `password_hash` y validaciones robustas en servidor y cliente.
- **Roles y Permisos**: Sistema preparado para la asignación de roles.

### 📊 Panel de Control (Dashboard)
- **Interfaz Moderna**: Diseño limpio basado en CSS personalizado y Bootstrap 5.
- **Sidebar Dinámico**: Navegación fluida entre módulos (Ventas, Inventario, Usuarios).
- **Skeleton Loaders**: Experiencia de carga suave y profesional en tablas y formularios.

### 🏗️ Arquitectura y Backend
- **Vanilla MVC**: Implementación pura de Modelo-Vista-Controlador sin dependencias externas pesadas.
- **Enrutamiento Dinámico**: FrontController inteligente que detecta automáticamente la ruta base (ideal para XAMPP).
- **Database Wrapper**: Conexión segura mediante PDO compatible con MySQL 8.0.

## 🚀 Instalación en XAMPP

Sigue estos pasos para configurar el proyecto en tu servidor local XAMPP:

### 1. Requisitos previos
- [XAMPP](https://www.apachefriends.org/index.html) con PHP 8.2+ y MySQL.
- [Composer](https://getcomposer.org/) instalado globalmente.
- Módulo `mod_rewrite` habilitado en Apache.

### 2. Configuración del Proyecto
1. Clona el repositorio dentro de la carpeta `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Team-Project-Org-2025/sys-inescolara.git
   ```
2. Entra al directorio e instala las dependencias:
   ```bash
   cd sys-inescolara
   composer install
   ```
3. Configura el archivo de entorno:
   - Renombra `.env.example` a `.env` (o crea uno nuevo).
   - Asegúrate de que las credenciales coincidan con tu XAMPP (por defecto: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASSWORD=`).
   - Ajusta `APP_URL=http://localhost/sys-inescolara/`.

### 3. Base de Datos
1. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Crea una base de datos llamada `sys_inescolara_db`.
3. Importa el archivo SQL ubicado en `backups/sys-inescolara(spanish-version).sql`.

### 4. Acceso
Navega a `http://localhost/sys-inescolara/` en tu navegador.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | PHP 8.2 (Vanilla MVC) |
| **Base de Datos** | MySQL 8.0 / MariaDB |
| **Frontend** | Bootstrap 5, jQuery, DataTables, SweetAlert2 |
| **Servidor** | Apache (XAMPP / WAMP) |
| **Utilidades** | Dompdf, Composer, Skeleton Loaders |

## ⚙️ Variables de Entorno (.env)

Para XAMPP, se recomienda la siguiente configuración:

| Variable | Valor Recomendado |
| :--- | :--- |
| `DB_HOST` | `localhost` |
| `DB_NAME` | `sys_inescolara_db` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | (Vacío) |
| `APP_URL` | `http://localhost/sys-inescolara/` |

---

<p align="center">
  Desarrollado con ❤️ para <strong>INESCOLARA</strong><br>
  © 2026 - Todos los derechos reservados
</p>
