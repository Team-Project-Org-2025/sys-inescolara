USE `sysinescolara`;

-- Add cantidad column to trazabilidad for quarantine quantity tracking
ALTER TABLE `trazabilidad`
    ADD COLUMN `cantidad` INT(11) NOT NULL DEFAULT 1 AFTER `id_lote`,
    ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `observacion`;

USE `SysInescolara-Seguridad`;

-- Permissions for trazabilidad module (57-60)
INSERT IGNORE INTO `permisos` (`id_permiso`, `codigo_permiso`, `descripcion_permiso`) VALUES
    (57, 'TRAZABILIDAD_VIEW',   'Ver monitoreo de ejemplares'),
    (58, 'TRAZABILIDAD_CREATE', 'Registrar cuarentena de ejemplares'),
    (59, 'TRAZABILIDAD_EDIT',   'Editar monitoreo de ejemplares'),
    (60, 'TRAZABILIDAD_DELETE', 'Eliminar registros de trazabilidad');

-- Assign new permissions to Admin role (id_rol = 1)
INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN ('TRAZABILIDAD_VIEW', 'TRAZABILIDAD_CREATE', 'TRAZABILIDAD_EDIT', 'TRAZABILIDAD_DELETE');
