USE `sysinescolara`;

CREATE TABLE IF NOT EXISTS `recoleccion_semillas` (
    `id_recoleccion` INT NOT NULL AUTO_INCREMENT,
    `id_trabajador` INT NOT NULL,
    `id_ubicacion` INT NOT NULL,
    `fecha_asignacion` DATE NOT NULL,
    `fecha_recoleccion` DATE DEFAULT NULL,
    `estatus` VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    `observacion` TEXT DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_recoleccion`),
    KEY `idx_recoleccion_trabajador` (`id_trabajador`),
    KEY `idx_recoleccion_ubicacion` (`id_ubicacion`),
    KEY `idx_recoleccion_estatus` (`estatus`),
    CONSTRAINT `fk_recoleccion_trabajador` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`),
    CONSTRAINT `fk_recoleccion_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recoleccion_semillas_detalle` (
    `id_recoleccion_detalle` INT NOT NULL AUTO_INCREMENT,
    `id_recoleccion` INT NOT NULL,
    `planta_origen` VARCHAR(150) DEFAULT NULL,
    `nombre_semilla` VARCHAR(100) NOT NULL,
    `id_unidad_medida` INT NOT NULL,
    `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `id_insumo` INT DEFAULT NULL,
    PRIMARY KEY (`id_recoleccion_detalle`),
    KEY `idx_detalle_recoleccion` (`id_recoleccion`),
    KEY `idx_detalle_insumo` (`id_insumo`),
    CONSTRAINT `fk_detalle_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion_semillas` (`id_recoleccion`) ON DELETE CASCADE,
    CONSTRAINT `fk_detalle_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
