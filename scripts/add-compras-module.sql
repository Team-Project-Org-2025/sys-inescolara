-- ============================================================
-- Módulo Compras
-- ============================================================

USE `sysinescolara`;

CREATE TABLE IF NOT EXISTS `compra` (
    `id_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor` INT NOT NULL,
    `fecha_compra` DATE NOT NULL,
    `tipo_comprobante` VARCHAR(30) DEFAULT 'Factura',
    `numero_comprobante` VARCHAR(50) DEFAULT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `iva` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `estado` ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    `observacion` TEXT DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_compra_proveedor` (`id_proveedor`),
    KEY `idx_compra_estado` (`estado`),
    CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compra_detalle` (
    `id_detalle` INT AUTO_INCREMENT PRIMARY KEY,
    `id_compra` INT NOT NULL,
    `tipo_item` ENUM('insumo','herramienta') NOT NULL,
    `id_item` INT NOT NULL,
    `cantidad` DECIMAL(10,2) NOT NULL,
    `costo_unitario` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_detalle_compra` (`id_compra`),
    CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

USE `SysInescolara-Seguridad`;

INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`) VALUES
('COMPRAS_VIEW', 'Ver compras'),
('COMPRAS_CREATE', 'Crear compras'),
('COMPRAS_EDIT', 'Editar compras'),
('COMPRAS_DELETE', 'Eliminar compras'),
('COMPRAS_COMPLETE', 'Completar/cancelar compras');

INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN (
    'COMPRAS_VIEW', 'COMPRAS_CREATE', 'COMPRAS_EDIT', 'COMPRAS_DELETE', 'COMPRAS_COMPLETE'
);
