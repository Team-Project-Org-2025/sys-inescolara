USE sysinescolara;

-- ============================================================
-- Tabla: venta
-- ============================================================
CREATE TABLE IF NOT EXISTS venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    referencia VARCHAR(30) NOT NULL UNIQUE,
    id_cliente INT NOT NULL,
    id_trabajador INT NOT NULL,
    tipo_venta ENUM('contado','credito') NOT NULL DEFAULT 'contado',
    estado ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
    iva_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 16.00,
    fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE DEFAULT NULL COMMENT 'Fecha de vencimiento para ventas a crédito',
    observaciones TEXT DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    FOREIGN KEY (id_trabajador) REFERENCES trabajadores(id_trabajador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: detalle_venta
-- ============================================================
CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_lote INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_lote) REFERENCES lote(id_lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: pago_venta
-- ============================================================
CREATE TABLE IF NOT EXISTS pago_venta (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    metodo ENUM('efectivo','transferencia','punto','pago_movil','otro') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    referencia VARCHAR(50) DEFAULT NULL COMMENT 'Numero de referencia (6 digitos, solo transferencia/pago_movil)',
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_pago ENUM('registrado','confirmado','rechazado') NOT NULL DEFAULT 'registrado',
    banco VARCHAR(100) DEFAULT NULL,
    id_trabajador INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_trabajador) REFERENCES trabajadores(id_trabajador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Permisos
-- ============================================================
USE `SysInescolara-Seguridad`;

INSERT INTO `permisos` (`id_permiso`, `codigo_permiso`, `descripcion_permiso`)
SELECT 57, 'CUENTAS_COBRAR_VIEW', 'Ver cuentas por cobrar'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `codigo_permiso` = 'CUENTAS_COBRAR_VIEW');

INSERT INTO `permisos` (`id_permiso`, `codigo_permiso`, `descripcion_permiso`)
SELECT 58, 'CUENTAS_COBRAR_PAY', 'Registrar pagos de cuentas por cobrar'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `codigo_permiso` = 'CUENTAS_COBRAR_PAY');

-- Asignar permisos al rol Administrador (id_rol = 1)
INSERT IGNORE INTO `rol_permisos` (`id_rol`, `id_permiso`)
SELECT 1, `id_permiso` FROM `permisos` WHERE `codigo_permiso` IN ('CUENTAS_COBRAR_VIEW', 'CUENTAS_COBRAR_PAY');
