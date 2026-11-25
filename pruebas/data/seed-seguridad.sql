-- ============================================================================
-- SYS INESCOLARA · Usuarios de prueba para la seguridad (idempotente)
-- ============================================================================
-- Crea los usuarios de prueba que usan las colecciones:
--   * postman_admin  (id 5, rol Administrador) — usuario de referencia que
--     asume la colección de Usuarios en sus casos negativos (delete sin
--     current_password usa id=5; sin esta fila el backend responde 500 porque
--     el usuario no existe; ver hallazgo 11 de pruebas/README.md).
--   * postman_viewer (id 6, rol Trabajador, sin permisos individuales) —
--     usado por los casos 403 de las 24 colecciones.
-- El usuario admin (id 1) lo crea `php scripts/migrate.php`.
--
-- Uso (después de cargar el esquema y correr las migraciones):
--   mysql -h127.0.0.1 -uroot -proot < pruebas/data/seed-seguridad.sql
--
-- La contraseña de ambos usuarios es `Postman123!`.
-- ============================================================================

USE `SysInescolara-Seguridad`;

SET NAMES utf8mb4;

INSERT IGNORE INTO `usuarios`
  (`id_usuario`, `nombre_usuario`, `password_hash`, `correo_electronico`,
   `id_rol`, `estatus`, `intentos_fallidos`, `created_at`)
VALUES
  (5, 'postman_admin',
   '$2y$10$xC4P6gOW/9.8s4g0Bw4dIulJJyvDvpBTt6K2xOdVQtdT2eYf/VaLW',
   'postman_admin@example.test', 1, 'Activo', 0, CURRENT_TIMESTAMP),
  (6, 'postman_viewer',
   '$2y$10$xC4P6gOW/9.8s4g0Bw4dIulJJyvDvpBTt6K2xOdVQtdT2eYf/VaLW',
   'postman_viewer@example.test', 2, 'Activo', 0, CURRENT_TIMESTAMP);
