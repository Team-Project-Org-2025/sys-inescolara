-- Migración: Limpieza y configuración de permisos por rol
-- Ejecutar una vez en la base de datos SysInescolara-Seguridad
-- Descripción: Elimina filas legacy y duplicados, registra módulo 'permisos',
-- y asegura el set canónico de 4 acciones (ver/crear/editar/eliminar) para Admin.

USE `SysInescolara-Seguridad`;

-- 1. Limpiar tablas legacy no usadas por el código actual
DELETE FROM `rol_permisos`;
DELETE FROM `usuario_permisos`;

-- 2. Eliminar permisos no canónicos (mantiene solo ids 105,106,107,108)
-- El CASCADE limpia automáticamente referencias en rol_modulo_permiso y usuario_modulo_permiso
DELETE FROM `permisos` WHERE `id_permiso` NOT IN (105,106,107,108);

-- 3. Unique key para evitar duplicados futuros
ALTER TABLE `permisos` ADD UNIQUE KEY `uq_nombre_permiso` (`nombre_permiso`);

-- 4. Re-asignar set completo canónico al rol Administrador (id_rol = 1)
-- 28 módulos × 4 acciones = 112 filas
INSERT IGNORE INTO `rol_modulo_permiso` (`id_rol`, `id_modulo`, `id_permiso`)
SELECT 1, m.`id_modulo`, p.`id_permiso`
FROM `modulos` m
CROSS JOIN `permisos` p;

-- 5. Registrar módulo 'permisos' y asignarlo a Admin
INSERT INTO `modulos` (`nombre_modulo`, `descripcion_modulo`)
VALUES ('permisos', 'Gestión de permisos por rol')
ON DUPLICATE KEY UPDATE `descripcion_modulo` = VALUES(`descripcion_modulo`);

INSERT IGNORE INTO `rol_modulo_permiso` (`id_rol`, `id_modulo`, `id_permiso`)
SELECT 1, m.`id_modulo`, p.`id_permiso`
FROM `modulos` m
CROSS JOIN `permisos` p
WHERE m.`nombre_modulo` = 'permisos';

-- Verificación post-migración
SELECT 'permisos' AS tabla, COUNT(*) AS filas FROM `permisos`
UNION ALL
SELECT 'modulos', COUNT(*) FROM `modulos`
UNION ALL
SELECT 'rol_modulo_permiso (Admin)', COUNT(*) FROM `rol_modulo_permiso` WHERE `id_rol` = 1
UNION ALL
SELECT 'rol_modulo_permiso (Trabajador)', COUNT(*) FROM `rol_modulo_permiso` WHERE `id_rol` = 2;