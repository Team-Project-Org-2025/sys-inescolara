-- ============================================================================
-- SYS INESCOLARA · Datos mínimos para las pruebas de API con Postman
-- ============================================================================
-- Uso:
--   mysql -u root < pruebas/data/seed.sql
--
-- Es idempotente (INSERT IGNORE con ids fijos), así que se puede ejecutar
-- antes de cada corrida sin duplicar registros.
--
-- Solo cubre catálogos base: sin ellos los endpoints con clave foránea
-- (plantas, lotes, ventas, compras) fallan con error 1452.
-- ============================================================================

USE `sysinescolara`;

SET FOREIGN_KEY_CHECKS = 0;

-- --- Catálogo: especie -------------------------------------------------------
INSERT IGNORE INTO `especie` (`id_especie`, `nombre_especie`, `descripcion`, `activo`) VALUES
  (1, 'Rosa',   'Rosa sp.',           1),
  (2, 'Ixora',  'Ixora coccinea',     1),
  (3, 'Jazmín', 'Jasminum officinale', 1);

-- --- Catálogo: ubicación -----------------------------------------------------
INSERT IGNORE INTO `ubicacion` (`id_ubicacion`, `nombre_ubicacion`, `descripcion`, `zona`, `activo`) VALUES
  (1, 'Vivero Central', 'Área principal de cultivo', 'A', 1),
  (2, 'Invernadero 1',  'Área protegida',            'B', 1);

-- --- Cliente de pruebas ------------------------------------------------------
INSERT IGNORE INTO `cliente` (`id_cliente`, `tipo_cedula_cliente`, `cedula_cliente`, `nombre_cliente`, `apellido_cliente`, `contacto_cliente`, `activo`) VALUES
  (1, 'V', '12345678', 'Cliente', 'Prueba', '0414-0000000', 1);

-- --- Proveedor de pruebas ----------------------------------------------------
INSERT IGNORE INTO `proveedores` (`id_proveedor`, `nombre_proveedor`, `rif_proveedor`, `contacto_vendedor`, `telefono_proveedor`, `activo`) VALUES
  (1, 'Proveedor Prueba', 'J-00000000-0', 'Contacto Prueba', '0212-0000000', 1);

-- --- Unidad de medida (por si el esquema viene vacío) ------------------------
INSERT IGNORE INTO `unidad_medida` (`id_unidad_medida`, `nombre_unidad_medida`, `simbolo`, `activo`) VALUES
  (1, 'Unidad', 'ud', 1),
  (2, 'Kilogramo', 'kg', 1),
  (3, 'Litro', 'L', 1);

SET FOREIGN_KEY_CHECKS = 1;
