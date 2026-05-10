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

**SYS INESCOLARA** es una plataforma web desarrollada para optimizar la gestión operativa del Vivero INESCOLARA. Construida bajo una arquitectura **MVC (Modelo-Vista-Controlador)** personalizada en PHP 8.2, el sistema ofrece una experiencia de usuario premium con un enfoque en la eficiencia, seguridad y facilidad de despliegue mediante contenedores **Docker**.

Recientemente, el sistema ha sido migrado a **MySQL 8.0** para garantizar una mayor compatibilidad con entornos de producción y herramientas de análisis de datos.

## ✨ Características Principales

### 🔐 Gestión de Usuarios Avanzada
- **CRUD Completo**: Creación, lectura, actualización y eliminación de usuarios.
- **DataTables Integration**: Listado interactivo con paginación, búsqueda instantánea y ordenamiento dinámico.
- **Seguridad**: Manejo de contraseñas mediante `password_hash` y validaciones robustas en servidor y cliente.
- **Roles y Permisos**: Sistema preparado para la asignación de roles (Administrador, etc.).

### 📊 Panel de Control (Dashboard)
- **Interfaz Moderna**: Diseño limpio basado en CSS personalizado y Bootstrap 5.
- **Sidebar Dinámico**: Navegación fluida entre módulos (Ventas, Inventario, Usuarios).
- **Skeleton Loaders**: Experiencia de carga suave y profesional en tablas y formularios.

### 🏗️ Arquitectura y Backend
- **Vanilla MVC**: Implementación pura de Modelo-Vista-Controlador sin dependencias externas pesadas.
- **FrontController**: Sistema de enrutamiento centralizado para un control total de las peticiones.
- **Database Wrapper**: Conexión segura mediante PDO con soporte nativo para MySQL 8.0.

### 🚀 Infraestructura Dockerizada
- **Contenedores Optimizados**: Imágenes ligeras basadas en PHP 8.2 Apache.
- **Orquestación**: Configuración lista de la aplicación, base de datos y phpMyAdmin.
- **Persistencia**: Manejo de volúmenes para asegurar que los datos no se pierdan entre reinicios.

## 🚀 Inicio Rápido

### Requisitos previos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Git](https://git-scm.com/)

### Instalación en 3 pasos

1. **Clonar y Configurar**:
   ```bash
   git clone https://github.com/Team-Project-Org-2025/sys-inescolara.git
   cd sys-inescolara
   cp .env.example .env # Configura tus credenciales aquí
   ```

2. **Levantar Entorno**:
   ```bash
   docker-compose up -d --build
   ```

3. **Restaurar Base de Datos**:
   ```bash
   docker exec -i sys-inescolara-mysql mysql -u root -p sys_inescolara_db < "backups/sys-inescolara(spanish-version).sql"
   ```

## 🛠️ Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | PHP 8.2 (Vanilla MVC) |
| **Base de Datos** | MySQL 8.0 |
| **Frontend** | Bootstrap 5, jQuery, DataTables, SweetAlert2 |
| **Servidor** | Apache (Dockerizado) |
| **Utilidades** | Dompdf, Composer, Skeleton Loaders |

## ⚙️ Variables de Entorno (.env)

| Variable | Descripción | Valor por defecto |
| :--- | :--- | :--- |
| `APP_PORT` | Puerto de la aplicación web | `9080` |
| `DB_NAME` | Nombre de la BD MySQL | `sys_inescolara_db` |
| `DB_PORT` | Puerto interno de MySQL | `3306` |
| `PMA_PORT` | Puerto de phpMyAdmin | `9000` |

## 📂 Estructura de Directorios

- `app/`: El corazón del sistema (Controladores, Modelos y Vistas).
- `public/`: Assets estáticos (CSS, JS, Imágenes) y punto de entrada.
- `backups/`: Esquemas SQL y respaldos históricos.
- `docker/`: Configuraciones adicionales de infraestructura.

---

<p align="center">
  Desarrollado con ❤️ para <strong>INESCOLARA</strong><br>
  © 2026 - Todos los derechos reservados
</p>

