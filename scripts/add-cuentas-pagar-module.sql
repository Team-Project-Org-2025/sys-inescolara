-- ============================================================
-- Módulo Cuentas por Pagar
-- ============================================================

USE `sysinescolara`;

CREATE TABLE IF NOT EXISTS `cuentas_pagar` (
    `id_cuenta_pagar` INT AUTO_INCREMENT PRIMARY KEY,
    `id_compra` INT NOT NULL,
    `monto_total` DECIMAL(10,2) NOT NULL,
    `saldo_pendiente` DECIMAL(10,2) NOT NULL,
    `fecha_vencimiento` DATE DEFAULT NULL,
    `estado` ENUM('pendiente','parcial','pagada','vencida') NOT NULL DEFAULT 'pendiente',
    `observacion` TEXT DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_cp_compra` (`id_compra`),
    CONSTRAINT `fk_cp_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pago_compra` (
    `id_pago_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_cuenta_pagar` INT NOT NULL,
    `monto` DECIMAL(10,2) NOT NULL,
    `tipo_pago` VARCHAR(30) DEFAULT NULL,
    `referencia` VARCHAR(50) DEFAULT NULL,
    `fecha_pago` DATE NOT NULL,
    `observacion` TEXT DEFAULT NULL,
    `estado` ENUM('pendiente','confirmado','anulado') NOT NULL DEFAULT 'confirmado',
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_pg_cuenta` (`id_cuenta_pagar`),
    CONSTRAINT `fk_pg_cuenta` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar 'pagada' al ENUM de compra
ALTER TABLE `compra`
  MODIFY COLUMN `estado` ENUM('pendiente','recibida','pagada','cancelada') NOT NULL DEFAULT 'pendiente';

USE `SysInescolara-Seguridad`;

INSERT IGNORE INTO `permisos` (`codigo_permiso`, `descripcion_permiso`) VALUES
('CUENTAS_VIEW', 'Ver cuentas por pagar'),
('CUENTAS_PAGAR', 'Registrar pagos'),
('CUENTAS_DELETE', 'Anular pagos');

INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN (
    'CUENTAS_VIEW', 'CUENTAS_PAGAR', 'CUENTAS_DELETE'
);
