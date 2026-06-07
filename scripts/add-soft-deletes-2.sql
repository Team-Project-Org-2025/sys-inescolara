-- Fase 2: Soft Deletes - Agregar columna activo a todas las tablas de referencia
USE `sysinescolara`;

ALTER TABLE plantas           ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER imagen;
ALTER TABLE especie           ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER descripcion;
ALTER TABLE insumo            ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER costo_unitario_actual;
ALTER TABLE herramienta       ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER observacion;
ALTER TABLE lote              ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER imagen;
ALTER TABLE proveedores       ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER telefono_proveedor;
-- trabajadores ya tiene columna activo
ALTER TABLE cliente           ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER contacto_cliente;
ALTER TABLE ubicacion         ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER zona;
ALTER TABLE unidad_medida     ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER simbolo;
ALTER TABLE tareas            ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER descripcion;
