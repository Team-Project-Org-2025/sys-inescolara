# SYS INESCOLARA - Sistema de Gestión de Vivero 🌿

Sistema integral para la gestión y administración del Vivero INESCOLARA, desarrollado en PHP con una arquitectura MVC robusta y despliegue mediante Docker.

## 🚀 Inicio Rápido con Docker

La forma más sencilla de iniciar el proyecto es utilizando Docker y Docker Compose.

### Requisitos previos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado.
- [Git](https://git-scm.com/) (opcional).

### Pasos para iniciar el entorno
1. **Configurar el archivo `.env`**:
   Asegúrate de tener un archivo `.env` en la raíz del proyecto configurado. Puedes guiarte por los valores actuales.

2. **Levantar los servicios**:
   ```bash
   docker-compose up -d
   ```

3. **Acceder a la aplicación**:
   Una vez levantados los contenedores, puedes acceder desde tu navegador:
   - **Aplicación**: [http://localhost:9080](http://localhost:9080)
   - **pgAdmin (Gestión DB)**: [http://localhost:9000](http://localhost:9000)

## ⚙️ Configuración (.env)

El archivo `.env` controla el comportamiento de la aplicación. Los parámetros principales son:

- `APP_PORT`: Puerto donde correrá la web (por defecto `9080`).
- `DB_NAME`: Nombre de la base de datos.
- `DB_USER`: Usuario de la base de datos.
- `DB_PASSWORD`: Contraseña para la base de datos PostgreSQL.
- `PGADMIN_PORT`: Puerto para acceder a pgAdmin (por defecto `9000`).

## 🗄️ Base de Datos

El sistema utiliza **PostgreSQL 16**. Para restaurar la base de datos con el esquema inicial:

1. El esquema inicial se encuentra en `backups/sys-inescolara.sql`.
2. Puedes importarlo ejecutando el siguiente comando (sustituyendo los valores de usuario y nombre de DB si los cambiaste):
   ```bash
   docker exec -i sys-inescolara-postgres psql -U sys_inescolara_admin -d sys_inescolara_db < backups/sys-inescolara.sql
   ```

## 📂 Estructura del Proyecto

```text
sys-inescolara/
├── app/            # Lógica central (Controladores, Modelos, Core, Views)
├── backups/        # Respaldos de la base de datos (SQL)
├── public/         # Punto de acceso para assets (CSS, JS, Imágenes)
├── index.php       # Punto de entrada de la aplicación
├── dockerfile      # Configuración de la imagen PHP + Apache
└── docker-compose.yml # Orquestación de contenedores (App, DB, pgAdmin)
```

## 🛠️ Tecnologías principales

- **Backend**: PHP 8.x (Arquitectura MVC personalizada)
- **Base de Datos**: PostgreSQL 16
- **Infraestructura**: Docker & Docker Compose
- **Reportes**: Dompdf
