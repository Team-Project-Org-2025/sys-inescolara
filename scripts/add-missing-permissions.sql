USE `SysInescolara-Seguridad`;

-- Missing module permissions for Tools, Prices, Units, Inventory, Backups, Audit, Recoleccion
INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`) VALUES
-- Tools (already has VIEW in DB, adding missing if needed)
('HERRAMIENTAS_VIEW', 'Ver herramientas'),
('HERRAMIENTAS_CREATE', 'Crear herramientas'),
('HERRAMIENTAS_EDIT', 'Editar herramientas'),
('HERRAMIENTAS_DELETE', 'Eliminar herramientas'),
-- Prices
('PRECIOS_VIEW', 'Ver precios'),
('PRECIOS_CREATE', 'Crear precios'),
('PRECIOS_EDIT', 'Editar precios'),
('PRECIOS_DELETE', 'Eliminar precios'),
-- Units
('UNIDADES_MEDIDA_VIEW', 'Ver unidades de medida'),
('UNIDADES_MEDIDA_CREATE', 'Crear unidades de medida'),
('UNIDADES_MEDIDA_EDIT', 'Editar unidades de medida'),
('UNIDADES_MEDIDA_DELETE', 'Eliminar unidades de medida'),
-- Inventory adjust (already in DB, safe to re-insert)
('INVENTARIO_ADJUST', 'Ajustar inventario'),
-- TAREAS_ASSIGN, USO_HERRAMIENTA_CREATE (already in DB from add-permissions-1.4.sql)
-- Backups
('BACKUPS_CREATE', 'Crear respaldos'),
('BACKUPS_DELETE', 'Eliminar y restaurar respaldos'),
-- Audit
('AUDIT_VIEW', 'Ver bitácora de auditoría'),
-- Recoleccion
('RECOLECCION_VIEW', 'Ver recolecciones'),
('RECOLECCION_CREATE', 'Crear recolecciones'),
('RECOLECCION_EDIT', 'Editar recolecciones'),
('RECOLECCION_DELETE', 'Eliminar recolecciones'),
('RECOLECCION_COMPLETE', 'Completar recolecciones y registrar insumos');

-- Assign all new permissions to Admin role (id_rol = 1)
INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN (
    'HERRAMIENTAS_VIEW', 'HERRAMIENTAS_CREATE', 'HERRAMIENTAS_EDIT', 'HERRAMIENTAS_DELETE',
    'PRECIOS_VIEW', 'PRECIOS_CREATE', 'PRECIOS_EDIT', 'PRECIOS_DELETE',
    'UNIDADES_MEDIDA_VIEW', 'UNIDADES_MEDIDA_CREATE', 'UNIDADES_MEDIDA_EDIT', 'UNIDADES_MEDIDA_DELETE',
    'INVENTARIO_ADJUST',
    'TAREAS_ASSIGN', 'USO_HERRAMIENTA_CREATE',
    'BACKUPS_CREATE', 'BACKUPS_DELETE',
    'AUDIT_VIEW',
    'RECOLECCION_VIEW', 'RECOLECCION_CREATE', 'RECOLECCION_EDIT', 'RECOLECCION_DELETE', 'RECOLECCION_COMPLETE'
);
