-- ============================================================================
-- SYS INESCOLARA · Datos mínimos para las pruebas de API con Postman
-- ============================================================================
-- Uso:
--   mysql -u root < pruebas/data/seed.sql
--
-- Es idempotente (INSERT IGNORE con ids fijos), así que se puede ejecutar
-- antes de cada corrida sin duplicar registros.
--
-- Cubre los catálogos base y una planta/lote/cuarentena de arranque para que
-- los CRUD de inventario tengan datos sin depender de filas creadas a mano.
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

-- --- Catálogos de lote (el esquema los trae, pero por si acaso) --------------
INSERT IGNORE INTO `estado` (`id_estado`, `nombre`, `activo`) VALUES
  (5, 'vivo', 1),
  (6, 'cuarentena', 1),
  (7, 'muerto', 1);

INSERT IGNORE INTO `categoria` (`id_categoria`, `nombre`, `activo`) VALUES
  (1, 'germinado', 1),
  (2, 'plántula', 1),
  (3, 'adulto', 1);

INSERT IGNORE INTO `origen` (`id_origen`, `nombre`, `activo`) VALUES
  (1, 'Siembra', 1),
  (2, 'Ampliación', 1),
  (3, 'Donación', 1),
  (4, 'Compra', 1);

-- --- Planta base -------------------------------------------------------------
INSERT IGNORE INTO `plantas` (`id_planta`, `id_especie`, `nombre_tecnico`, `nombre_comun`, `activo`) VALUES
  (1, 1, 'Rosa canina', 'Rosa Base', 1);

-- --- Lote base (10 iniciales: 7 disponibles + 3 en cuarentena) ---------------
INSERT IGNORE INTO `lote` (`id_lote`, `id_planta`, `id_ubicacion`, `fecha_siembra`, `cantidad_inicial`, `cantidad_actual`, `costo_unitario`, `id_estado`, `id_categoria`, `id_origen`, `observacion`, `imagen`, `activo`, `porcentaje_ganancia`) VALUES
  (1, 1, 1, '2026-01-15', 10, 7, 5.00, 5, 1, 1, NULL, NULL, 1, 30.00);

-- --- Cuarentena base ---------------------------------------------------------
INSERT IGNORE INTO `trazabilidad` (`id_trazabilidad`, `id_lote`, `cantidad`, `estado_salud`, `observacion`, `activo`, `fecha_registro`) VALUES
  (1, 1, 3, 'cuarentena', NULL, 1, '2026-01-20');

SET FOREIGN_KEY_CHECKS = 1;
