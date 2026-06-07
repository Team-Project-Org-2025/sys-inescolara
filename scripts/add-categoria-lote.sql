ALTER TABLE lote
ADD COLUMN categoria VARCHAR(30) DEFAULT NULL COMMENT 'germinado, en_crecimiento, para_cosechar, maduro' AFTER estado;
