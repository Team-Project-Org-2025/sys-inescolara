USE `SysInescolara-Seguridad`;

INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`) VALUES
('AMPLIACION_VIEW',   'Ver ampliaciones de especies'),
('AMPLIACION_CREATE', 'Registrar ampliaciones de especies'),
('AMPLIACION_DELETE', 'Desactivar ampliaciones de especies');

INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos`
WHERE `codigo_permiso` IN ('AMPLIACION_VIEW','AMPLIACION_CREATE','AMPLIACION_DELETE');
