-- ============================================================================
-- SEED: Datos de prueba para probar los filtros del módulo de Reportes
-- ============================================================================
-- Este script inserta datos sintéticos en la BD `sysinescolara` para que cada
-- reporte tenga registros variados que permitan probar los filtros:
--   * trabajadores / tareas / recolección  -> tareas pendientes y completadas
--   * precios (calculo_precio)             -> cálculos vigentes y no vigentes
--   * cuentas x cobrar                     -> ventas al crédito pagadas, vigentes y vencidas
--   * cuentas x pagar / compras            -> compras pendientes, recibidas y pagadas
--   * insumos / herramientas               -> stock bajo y mantenimientos antiguos
--   * proveedores / clientes               -> nuevos registros sin operaciones
--
-- Es seguro re-ejecutarlo: las filas se insertan con IDs explícitos y se
-- ignoran las que ya existen (INSERT IGNORE).
--
-- Cómo ejecutarlo:
--   mysql -u root -p sysinescolara < database/seed_reports_test.sql
-- ============================================================================

USE `sysinescolara`;
SET NAMES utf8mb4;
SET time_zone = "-04:00";

-- --------------------------------------------------------------------------
-- Trabajadores adicionales (para tareas/recolección/ventas)
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `trabajadores`
  (`id_trabajador`, `nombre_trabajador`, `apellido_trabajador`, `cedula_trabajador`, `telefono_trabajador`, `cargo`, `activo`) VALUES
  (2, 'Juan Carlos', 'Pérez',   'V12345678', '04141234567', 'Viverista', 1),
  (3, 'María Isabel', 'López',  'V23456789', '04161234568', 'Secretaria', 1),
  (4, 'Carlos Alberto', 'Rodríguez', 'V34567890', '04241234569', 'Técnico', 1);

-- --------------------------------------------------------------------------
-- Tareas asignadas (mixto: pendientes y completadas)
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `asignar_tarea`
  (`id_asignacion`, `id_trabajador`, `id_tarea`, `id_lote`, `fecha_asignacion`, `fecha_cumplimiento`, `estatus_tarea`, `horas_dedicadas`) VALUES
  (9,  2, 1, 4,  '2026-06-15', NULL,         'pendiente',   NULL),
  (10, 2, 2, 7,  '2026-06-20', '2026-06-24', 'completada',  5.50),
  (11, 3, 3, 10, '2026-06-28', NULL,         'pendiente',   NULL),
  (12, 3, 4, 12, '2026-07-01', '2026-07-05', 'completada',  3.00),
  (13, 4, 5, 14, '2026-07-08', NULL,         'pendiente',   NULL),
  (14, 4, 6, 16, '2026-07-15', NULL,         'pendiente',   NULL),
  (15, 2, 7, 18, '2026-07-20', '2026-07-22', 'completada',  4.00),
  (16, 3, 8, 19, '2026-07-25', NULL,         'pendiente',   NULL);

-- --------------------------------------------------------------------------
-- Recolección de semillas
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `recoleccion_semillas`
  (`id_recoleccion`, `id_trabajador`, `id_ubicacion`, `fecha_asignacion`, `fecha_recoleccion`, `estatus`, `observacion`, `activo`) VALUES
  (11, 2, 1, '2026-06-20', NULL,         'Pendiente', 'Semillas de girasol pendientes', 1),
  (12, 3, 2, '2026-06-25', '2026-07-02', 'Realizada', 'Recolección completa', 1),
  (13, 4, 3, '2026-07-10', NULL,         'Pendiente', 'Pendiente por lluvias', 1),
  (14, 2, 4, '2026-07-18', NULL,         'Pendiente', 'Pendiente', 1);

-- --------------------------------------------------------------------------
-- Cálculos de precio por lote (vigentes y no vigentes)
--   precio_final = (mano_obra + insumo + agua) * (1 + ganancia/100)
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `calculo_precio`
  (`id_calculo`, `id_lote`, `costo_mano_obra`, `costo_total_insumo`, `costo_agua_lote`, `porcentaje_ganancia`, `cantidad_planta_base`, `precio_final_sugerido`, `fecha_calculo`, `vigente`) VALUES
  (13, 1,  5.00, 15.00, 2.00, 40.00, 1, 30.80, '2026-07-01', 1),
  (14, 2,  8.00, 20.00, 3.00, 35.00, 1, 41.85, '2026-07-05', 1),
  (15, 3,  3.00, 10.00, 1.00, 50.00, 1, 21.00, '2026-07-08', 0),
  (16, 10, 6.00, 12.00, 2.00, 30.00, 1, 26.00, '2026-07-10', 1),
  (17, 12, 4.00,  8.00, 1.00, 25.00, 1, 16.25, '2026-07-12', 0),
  (18, 13, 10.00, 25.00, 4.00, 45.00, 1, 56.55, '2026-07-15', 1),
  (19, 14, 7.00, 18.00, 2.00, 38.00, 1, 37.26, '2026-07-18', 1),
  (20, 15, 5.00,  9.00, 1.00, 30.00, 1, 19.50, '2026-07-20', 0),
  (21, 16, 12.00, 30.00, 5.00, 50.00, 1, 70.50, '2026-07-22', 1),
  (22, 17, 6.00, 14.00, 2.00, 35.00, 1, 29.70, '2026-07-25', 0),
  (23, 18, 9.00, 22.00, 3.00, 40.00, 1, 47.60, '2026-07-28', 1),
  (24, 19, 3.00, 11.00, 1.00, 28.00, 1, 19.20, '2026-07-30', 0);

-- --------------------------------------------------------------------------
-- Ventas al crédito para cuentas x cobrar (vencidas y vigentes)
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `venta`
  (`id_venta`, `referencia`, `id_cliente`, `id_trabajador`, `tipo_venta`, `estado`, `iva_porcentaje`, `fecha_venta`, `fecha_vencimiento`, `observaciones`, `activo`) VALUES
  (18, 'VEN-20260630-001', 1, 3, 'credito', 'completada', 16.00, '2026-06-30 12:00:00', '2026-07-05', 'Crédito vencido sin pago', 1),
  (19, 'VEN-20260710-001', 2, 2, 'credito', 'completada', 16.00, '2026-07-10 10:00:00', '2026-07-20', 'Crédito vencido sin pago', 1),
  (20, 'VEN-20260715-001', 3, 3, 'credito', 'completada', 16.00, '2026-07-15 11:30:00', '2026-08-20', 'Crédito vigente sin pago', 1),
  (21, 'VEN-20260720-001', 4, 4, 'credito', 'completada', 16.00, '2026-07-20 09:15:00', '2026-08-25', 'Crédito vigente con pago parcial', 1),
  (22, 'VEN-20260725-001', 5, 2, 'credito', 'completada', 16.00, '2026-07-25 15:00:00', '2026-08-30', 'Crédito vigente sin pago', 1);

INSERT IGNORE INTO `detalle_venta`
  (`id_venta`, `id_lote`, `cantidad`, `precio_unitario`) VALUES
  (18, 14, 2, 15.00),   -- total 30.00
  (19, 4,  5, 10.00),   -- total 50.00
  (20, 5,  2, 17.50),   -- total 35.00
  (21, 7,  3, 20.00),   -- total 60.00
  (22, 10, 1, 25.00);   -- total 25.00

-- Pago parcial de la venta 21 (queda saldo de 40.00)
INSERT IGNORE INTO `pago_venta`
  (`id_venta`, `metodo`, `monto`, `referencia`, `fecha_pago`, `estado_pago`, `id_trabajador`) VALUES
  (21, 'transferencia', 20.00, 'PAG-PARCIAL-001', '2026-07-21 10:00:00', 'confirmado', 4);

-- --------------------------------------------------------------------------
-- Compras y cuentas x pagar
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `compra`
  (`id_compra`, `id_proveedor`, `fecha_compra`, `tipo_comprobante`, `numero_comprobante`, `subtotal`, `iva`, `total`, `estado`, `observacion`, `activo`) VALUES
  (11, 2, '2026-07-05', 'Factura', 'FC-2026-100', 80.00, 0.00, 80.00, 'recibida',  'Seed de prueba', 1),
  (12, 3, '2026-07-12', 'Factura', 'FC-2026-101', 120.00, 0.00, 120.00, 'pendiente', 'Seed de prueba', 1),
  (13, 4, '2026-07-20', 'Factura', 'FC-2026-102', 60.00, 0.00, 60.00, 'pagada',    'Seed de prueba', 1),
  (14, 1, '2026-07-15', 'Factura', 'FC-2026-103', 90.00, 0.00, 90.00, 'recibida',  'Seed de prueba', 1);

INSERT IGNORE INTO `cuentas_pagar`
  (`id_cuenta_pagar`, `id_compra`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `observacion`, `activo`) VALUES
  (7, 11, 80.00, 0.00,   '2026-07-25', 'pagada',    'Pagada en su totalidad', 1),
  (8, 12, 120.00, 120.00, '2026-08-10', 'pendiente', 'Pendiente', 1),
  (9, 13, 60.00, 60.00,  '2026-07-15', 'pendiente', 'Vencida sin pago', 1),
  (10, 14, 90.00, 40.00, '2026-08-05', 'parcial',   'Pago parcial', 1);

-- --------------------------------------------------------------------------
-- Insumos con stock bajo (para filtros de stock)
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `insumo`
  (`id_insumo`, `id_unidad_medida`, `nombre_insumo`, `categoria`, `stock_actual`, `costo_unitario_actual`, `activo`) VALUES
  (10, 1, 'Sustrato orgánico', 'Sustratos', 2.50,  3.00, 1),
  (11, 8, 'Malla sombra',      'Insumos',  1.00,  12.00, 1),
  (12, 5, 'Tierra abonada',    'Sustratos', 0.00,  2.50, 1);

-- --------------------------------------------------------------------------
-- Herramientas con mantenimientos antiguos
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `herramienta`
  (`id_herramienta`, `nombre_herramienta`, `cantidad`, `tipo`, `estado`, `fecha_adquisicion`, `fecha_ultimo_mantenimiento`, `observacion`, `activo`) VALUES
  (18, 'Podadora manual',  2, 'corte',        'disponible',       '2026-05-10', '2026-06-01', 'Seed de prueba', 1),
  (19, 'Rastrillo',        3, 'limpieza',     'en_mantenimiento', '2026-04-15', '2026-06-15', 'Seed de prueba', 1),
  (20, 'Carretilla',       1, 'transporte',   'disponible',       '2026-03-01', NULL,         'Seed de prueba', 1);

-- --------------------------------------------------------------------------
-- Proveedor y cliente nuevos (sin operaciones, para filtros de "sin datos")
-- --------------------------------------------------------------------------
INSERT IGNORE INTO `proveedores`
  (`id_proveedor`, `nombre_proveedor`, `rif_proveedor`, `contacto_vendedor`, `telefono_proveedor`, `activo`) VALUES
  (5, 'Semillas Andinas C.A.', 'J-123456700', 'Ana Torres', '04141234500', 1);

INSERT IGNORE INTO `cliente`
  (`id_cliente`, `tipo_cedula_cliente`, `cedula_cliente`, `nombre_cliente`, `apellido_cliente`, `contacto_cliente`, `activo`) VALUES
  (6, 'V', '9876543', 'Pedro', 'Rojas', '04161234501', 1);
