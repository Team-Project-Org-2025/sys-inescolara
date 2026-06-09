-- ============================================================
-- Migration: Módulo Ventas (POS)
-- Tablas: venta, detalle_venta, pago_venta
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
    observaciones TEXT DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    FOREIGN KEY (id_trabajador) REFERENCES trabajadores(id_trabajador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_lote INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_lote) REFERENCES lote(id_lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pago_venta (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    metodo ENUM('efectivo','transferencia','punto','otro') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    referencia VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
