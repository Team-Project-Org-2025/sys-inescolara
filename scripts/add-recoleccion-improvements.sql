USE `sysinescolara`;

-- 1. Soft Delete: agregar columna activo a recoleccion_semillas
ALTER TABLE `recoleccion_semillas`
    ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `observacion`,
    ADD INDEX `idx_recoleccion_activo` (`activo`);

-- 2. FK a insumo: agregar id_insumo a recoleccion_semillas_detalle
ALTER TABLE `recoleccion_semillas_detalle`
    ADD COLUMN `id_insumo` INT DEFAULT NULL AFTER `cantidad`,
    ADD INDEX `idx_detalle_insumo` (`id_insumo`),
    ADD CONSTRAINT `fk_detalle_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`) ON DELETE SET NULL;
