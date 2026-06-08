USE `sysinescolara`;

-- 1. Columna tipo en detalle (entrada/salida)
ALTER TABLE `movimiento_planta_detalle`
    ADD COLUMN `tipo` ENUM('entrada','salida') NOT NULL DEFAULT 'salida' AFTER `id_lote`;

-- 2. Soft delete para movimiento_planta
ALTER TABLE `movimiento_planta`
    ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `observacion`,
    ADD INDEX `idx_mp_activo` (`activo`);

-- 3. Soft delete para movimiento_planta_detalle
ALTER TABLE `movimiento_planta_detalle`
    ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sub_total`,
    ADD INDEX `idx_mpd_activo` (`activo`);
