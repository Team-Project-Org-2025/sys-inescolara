USE `SysInescolara-Seguridad`;

-- TAREAS_ASSIGN (47): Permission to assign tasks to workers
INSERT INTO `permisos` (`id_permiso`, `codigo_permiso`, `descripcion_permiso`)
SELECT 47, 'TAREAS_ASSIGN', 'Asignar tareas a trabajadores'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `codigo_permiso` = 'TAREAS_ASSIGN');

-- USO_HERRAMIENTA_CREATE (48): Permission to record tool usage
INSERT INTO `permisos` (`id_permiso`, `codigo_permiso`, `descripcion_permiso`)
SELECT 48, 'USO_HERRAMIENTA_CREATE', 'Registrar uso de herramientas'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `codigo_permiso` = 'USO_HERRAMIENTA_CREATE');

-- Assign new permissions to Admin role (id_rol = 1)
INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN ('TAREAS_ASSIGN', 'USO_HERRAMIENTA_CREATE');
