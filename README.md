# SYS INESCOLARA - Sistema de Gestión de Vivero 🌿

Sistema integral para la gestión y administración del Vivero INESCOLARA, desarrollado en PHP con una arquitectura MVC robusta y despliegue mediante Docker. El sistema ahora utiliza MySQL para una mejor compatibilidad y rendimiento.

## 🚀 Inicio Rápido con Docker

La forma más sencilla de iniciar el proyecto es utilizando Docker y Docker Compose.

### Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado.
- [Git](https://git-scm.com/) instalado.

### Pasos para iniciar el entorno

1. **Configurar el archivo `.env`**:
   Asegúrate de tener un archivo `.env` en la raíz del proyecto configurado. Puedes guiarte por los valores actuales del archivo.

2. **Levantar los servicios**:

   ```bash
   docker-compose up -d --build
   ```

3. **Acceder a la aplicación**:
   Una vez levantados los contenedores, puedes acceder desde tu navegador:
   - **Aplicación**: [http://localhost:9080](http://localhost:9080)
   - **phpMyAdmin (Gestión DB)**: [http://localhost:9000](http://localhost:9000)

## ⚙️ Configuración (.env)

El archivo `.env` controla el comportamiento de la aplicación. Los parámetros principales son:

- `APP_PORT`: Puerto donde correrá la web (por defecto `9080`).
- `DB_NAME`: Nombre de la base de datos.
- `DB_USER`: Usuario de la base de datos.
- `DB_PASSWORD`: Contraseña para la base de datos MySQL.
- `DB_PORT`: Puerto de la base de datos (por defecto `3306`).
- `PMA_PORT`: Puerto para acceder a phpMyAdmin (por defecto `9000`).

## 🗄️ Base de Datos

El sistema utiliza **MySQL 8.0**. Para restaurar la base de datos con el esquema inicial:

1. El esquema inicial se encuentra en `backups/sys-inescolara(spanish-version).sql`.
2. Puedes importarlo ejecutando el siguiente comando:
   ```bash
   docker exec -i sys-inescolara-mysql mysql -u root -p sys_inescolara_db < "backups/sys-inescolara(spanish-version).sql"
   ```
   _(Nota: Se te pedirá la contraseña del root definida en el `.env`)_

## 📂 Estructura del Proyecto

```text
sys-inescolara/
├── app/            # Lógica central (Controladores, Modelos, Core, Views)
├── backups/        # Respaldos de la base de datos (SQL)
├── public/         # Punto de acceso para assets (CSS, JS, Imágenes)
├── index.php       # Punto de entrada de la aplicación (Enrutamiento)
├── dockerfile      # Configuración de la imagen PHP 8.2 + Apache + Extensiones MySQL
└── docker-compose.yml # Orquestación de contenedores (App, MySQL, phpMyAdmin)
```

## 🛠️ Tecnologías principales

- **Backend**: PHP 8.2 (Arquitectura MVC personalizada)
- **Base de Datos**: MySQL 8.0
- **Frontend**: Bootstrap 5, DataTables, jQuery, SweetAlert2
- **Infraestructura**: Docker & Docker Compose
- **Reportes**: Dompdf

---

© 2026 - INESCOLARA
