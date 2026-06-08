-- Fase: Módulo de Mermas y Bajas Definitivas
-- Las mermas se registran desde cuarentena (trazabilidad), no desde lote
-- Flujo: Lote -> Cuarentena (trazabilidad) -> Merma (mermas_historico)

USE `sysinescolara`;

CREATE TABLE IF NOT EXISTS `mermas_historico` (
    `id_merma` INT AUTO_INCREMENT PRIMARY KEY,
    `id_trazabilidad` INT NOT NULL,
    `id_lote` INT NOT NULL,
    `cantidad` INT NOT NULL,
    `motivo` ENUM('plaga', 'daño_mecanico', 'factor_climatico', 'enfermedad', 'otro') NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `fecha_merma` DATE NOT NULL,
    `impacto_economico` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `id_usuario_registra` INT DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_trazabilidad`) REFERENCES `trazabilidad`(`id_trazabilidad`),
    FOREIGN KEY (`id_lote`) REFERENCES `lote`(`id_lote`),
    FOREIGN KEY (`id_usuario_registra`) REFERENCES `SysInescolara-Seguridad`.`usuarios`(`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

USE `SysInescolara-Seguridad`;

-- MERMAS_VIEW: Ver historial de mermas
INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`)
VALUES ('MERMAS_VIEW', 'Ver módulo de mermas y bajas');

-- MERMAS_CREATE: Registrar mermas (bajas de plantas)
INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`)
VALUES ('MERMAS_CREATE', 'Registrar mermas y bajas de plantas');

-- Assign new permissions to Admin role (id_rol = 1)
INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN ('MERMAS_VIEW', 'MERMAS_CREATE');
