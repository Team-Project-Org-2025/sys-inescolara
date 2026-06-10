-- ============================================================
-- Migration: Agregar FKs reales a compra_detalle
-- Reemplaza el FK polimórfico (tipo_item + id_item)
-- por columnas separadas con CONSTRAINTs reales
-- ============================================================

-- 1. Agregar columnas FK
ALTER TABLE compra_detalle
  ADD COLUMN id_insumo INT NULL AFTER id_item,
  ADD COLUMN id_herramienta INT NULL AFTER id_insumo,
  ADD COLUMN id_planta INT NULL AFTER id_herramienta;

-- 2. Migrar datos existentes
UPDATE compra_detalle SET id_insumo = id_item WHERE tipo_item = 'insumo';
UPDATE compra_detalle SET id_herramienta = id_item WHERE tipo_item = 'herramienta';
UPDATE compra_detalle SET id_planta = id_item WHERE tipo_item = 'planta';

-- 3. Agregar constraints FK
ALTER TABLE compra_detalle
  ADD CONSTRAINT fk_detalle_insumo FOREIGN KEY (id_insumo) REFERENCES insumo(id_insumo),
  ADD CONSTRAINT fk_detalle_herramienta FOREIGN KEY (id_herramienta) REFERENCES herramienta(id_herramienta),
  ADD CONSTRAINT fk_detalle_planta FOREIGN KEY (id_planta) REFERENCES plantas(id_planta);

-- 4. Índices para rendimiento
CREATE INDEX idx_detalle_insumo ON compra_detalle(id_insumo);
CREATE INDEX idx_detalle_herramienta ON compra_detalle(id_herramienta);
CREATE INDEX idx_detalle_planta ON compra_detalle(id_planta);
