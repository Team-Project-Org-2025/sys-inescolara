-- ============================================================
-- Correcciones Módulo Compras
-- ============================================================
-- Ejecutar DESPUÉS de add-compras-module.sql
-- ============================================================

USE `sysinescolara`;

-- 1. Agregar 'planta' al ENUM y columnas faltantes en compra_detalle
ALTER TABLE `compra_detalle`
  MODIFY COLUMN `tipo_item` ENUM('insumo','herramienta','planta') NOT NULL,
  ADD COLUMN `categoria_lote` VARCHAR(50) DEFAULT NULL AFTER `subtotal`,
  ADD COLUMN `id_ubicacion` INT DEFAULT NULL AFTER `categoria_lote`,
  ADD KEY `idx_detalle_ubicacion` (`id_ubicacion`);

-- 2. Agregar costo_unitario a lote
ALTER TABLE `lote`
  ADD COLUMN `costo_unitario` DECIMAL(10,2) DEFAULT NULL AFTER `categoria`;

-- 3. Cambiar ENUM de compra.estado: completada → recibida + fecha_recepcion
ALTER TABLE `compra`
  MODIFY COLUMN `estado` ENUM('pendiente','recibida','cancelada') NOT NULL DEFAULT 'pendiente',
  ADD COLUMN `fecha_recepcion` DATE DEFAULT NULL AFTER `estado`;
