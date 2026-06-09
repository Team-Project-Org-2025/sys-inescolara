ALTER TABLE consumo_insumos
ADD COLUMN stock_actual DECIMAL(10,2) DEFAULT NULL AFTER costo_unitario;
