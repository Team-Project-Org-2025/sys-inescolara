-- ============================================================================
-- SYS INESCOLARA · Usuarios de prueba para la seguridad (idempotente)
-- ============================================================================
-- Crea el usuario "postman_viewer" (rol Trabajador, sin permisos individuales)
-- que usan los casos 403 de las colecciones. El usuario admin (id 1) lo crea
-- `php scripts/migrate.php`.
--
-- Uso (después de cargar el esquema y correr las migraciones):
--   mysql -h127.0.0.1 -uroot -proot < pruebas/data/seed-seguridad.sql
--
-- La contraseña de `postman_viewer` es `Postman123!`.
-- ============================================================================

USE `SysInescolara-Seguridad`;

SET NAMES utf8mb4;

INSERT IGNORE INTO `usuarios`
  (`id_usuario`, `nombre_usuario`, `password_hash`, `correo_electronico`,
   `id_rol`, `estatus`, `intentos_fallidos`, `created_at`)
VALUES
  (6, 'postman_viewer',
   '$2y$10$xC4P6gOW/9.8s4g0Bw4dIulJJyvDvpBTt6K2xOdVQtdT2eYf/VaLW',
   'postman_viewer@example.test', 2, 'Activo', 0, CURRENT_TIMESTAMP);
