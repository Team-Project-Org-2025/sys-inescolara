CREATE TABLE `trabajadores` (
  `id_trabajadores` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_trabajador` varchar(50),
  `apellido_trabajador` varchar(50),
  `cedula_trabajador` varchar(20) UNIQUE,
  `telefono_trabajador` varchar(20)
);

CREATE TABLE `asistencia` (
  `id_asistencia` int PRIMARY KEY AUTO_INCREMENT,
  `id_trabajador` int,
  `fecha` date,
  `hora_entrada` time,
  `hora_salida` time
);

CREATE TABLE `tareas` (
  `id_tarea` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_tarea` varchar(50),
  `descripcion` text
);

CREATE TABLE `asignar_tarea` (
  `id_asignacion` int PRIMARY KEY AUTO_INCREMENT,
  `id_trabajador` int,
  `id_tarea` int,
  `id_lote` int,
  `fecha_asignacion` date,
  `fecha_cumplimiento` date,
  `estatus_tarea` varchar(30)
);

CREATE TABLE `proveedores` (
  `id_proveedor` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_proveedor` varchar(100),
  `rif_proveedor` varchar(20) UNIQUE,
  `contacto_vendedor` varchar(100),
  `telefono_proveedor` varchar(20)
);

CREATE TABLE `insumo` (
  `id_insumo` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_insumo` varchar(50),
  `unidad_medida` varchar(20),
  `stock_actual` decimal(10,2),
  `costo_unitario_actual` decimal(10,2)
);

CREATE TABLE `compras_insumos` (
  `id_compra` int PRIMARY KEY AUTO_INCREMENT,
  `id_proveedor` int,
  `id_insumo` int,
  `cantidad_comprada` decimal(10,2),
  `precio_compra_unitario` decimal(10,2),
  `fecha_compra` date
);

CREATE TABLE `especies` (
  `id_especie` int PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL
);

CREATE TABLE `plantas` (
  `id_planta` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_tecnico` varchar(100),
  `nombre_comun` varchar(100),
  `id_categoria` int
);

CREATE TABLE `ubicaciones` (
  `id_ubicacion` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_ubicacion` varchar(100)
);

CREATE TABLE `lote` (
  `id_lote` int PRIMARY KEY AUTO_INCREMENT,
  `id_planta` int,
  `id_ubicacion` int,
  `fecha_siembra` date,
  `cantidad_inicial` int,
  `cantidad_actual` int
);

CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int PRIMARY KEY AUTO_INCREMENT,
  `id_lote` int,
  `estado_salud` varchar(50),
  `observacion` text,
  `fecha_registro` date
);

CREATE TABLE `calculo_precio` (
  `id_calculo` int PRIMARY KEY AUTO_INCREMENT,
  `id_lote` int,
  `costo_mano_obra` decimal(10,2),
  `porcentaje_ganancia` decimal(5,2),
  `costo_total_insumos` decimal(10,2),
  `precio_final_sugerido` decimal(10,2),
  `fecha_calculo` date
);

CREATE TABLE `consumo_insumos` (
  `id_consumo` int PRIMARY KEY AUTO_INCREMENT,
  `id_calculo` int,
  `id_insumo` int,
  `cantidad_usada` decimal(10,2),
  `costo_unitario` decimal(10,2),
  `sub_total` decimal(10,2)
);

CREATE TABLE `donacion` (
  `id_donacion` int PRIMARY KEY AUTO_INCREMENT,
  `tipo_movimiento` varchar(20),
  `entidad_donante_receptor` varchar(150),
  `id_trabajador_gestor` int,
  `fecha_donacion` date,
  `observacion` text
);

CREATE TABLE `detalle_donacion` (
  `id_detalle_donacion` int PRIMARY KEY AUTO_INCREMENT,
  `id_donacion` int,
  `id_lote` int COMMENT 'Null si es insumo',
  `id_insumo` int COMMENT 'Null si es planta',
  `cantidad` int
);

CREATE TABLE `cliente` (
  `id_cliente` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_cliente` varchar(100),
  `contacto_cliente` varchar(250)
);

CREATE TABLE `venta` (
  `id_venta` int PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` int,
  `monto_total` decimal(10,2),
  `fecha_venta` timestamp DEFAULT (now())
);

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int PRIMARY KEY AUTO_INCREMENT,
  `id_venta` int,
  `id_lote` int,
  `id_calculo` int,
  `cantidad_vendida` int,
  `precio_unitario` decimal(10,2),
  `sub_total` decimal(10,2)
);

CREATE TABLE `ampliacion_plantas` (
  `id_ampliacion` int PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` int,
  `id_trabajador` int,
  `fecha_intercambio` date
);

CREATE TABLE `detalle_recibido` (
  `id_recibido` int PRIMARY KEY AUTO_INCREMENT,
  `id_ampliacion` int,
  `id_planta` int,
  `cantidad` int,
  `estado_recibido` varchar(50)
);

CREATE TABLE `detalle_entregado` (
  `id_entregado` int PRIMARY KEY AUTO_INCREMENT,
  `id_ampliacion` int,
  `id_lote` int,
  `cantidad` int
);

CREATE TABLE `ornatos` (
  `id_ornato` int PRIMARY KEY AUTO_INCREMENT,
  `id_cliente` int,
  `descripcion` text,
  `monto_total` decimal(10,2),
  `fecha` date
);

CREATE TABLE `detalle_ornatos` (
  `id_detalle_ornato` int PRIMARY KEY AUTO_INCREMENT,
  `id_ornato` int,
  `id_lote` int,
  `cantidad` int,
  `precio_unitario` decimal(10,2),
  `sub_total` decimal(10,2)
);

ALTER TABLE `asistencia` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajadores`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajadores`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `compras_insumos` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

ALTER TABLE `compras_insumos` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `plantas` ADD FOREIGN KEY (`id_categoria`) REFERENCES `especies` (`id_especie`);

ALTER TABLE `lote` ADD FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`);

ALTER TABLE `lote` ADD FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicaciones` (`id_ubicacion`);

ALTER TABLE `trazabilidad` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `calculo_precio` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `consumo_insumos` ADD FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`);

ALTER TABLE `consumo_insumos` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `donacion` ADD FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajadores`);

ALTER TABLE `detalle_donacion` ADD FOREIGN KEY (`id_donacion`) REFERENCES `donacion` (`id_donacion`);

ALTER TABLE `detalle_donacion` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `detalle_donacion` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `venta` ADD FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

ALTER TABLE `detalle_venta` ADD FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`);

ALTER TABLE `detalle_venta` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `detalle_venta` ADD FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`);

ALTER TABLE `ampliacion_plantas` ADD FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

ALTER TABLE `ampliacion_plantas` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajadores`);

ALTER TABLE `detalle_recibido` ADD FOREIGN KEY (`id_ampliacion`) REFERENCES `ampliacion_plantas` (`id_ampliacion`);

ALTER TABLE `detalle_recibido` ADD FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`);

ALTER TABLE `detalle_entregado` ADD FOREIGN KEY (`id_ampliacion`) REFERENCES `ampliacion_plantas` (`id_ampliacion`);

ALTER TABLE `detalle_entregado` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `ornatos` ADD FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

ALTER TABLE `detalle_ornatos` ADD FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`);

ALTER TABLE `detalle_ornatos` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);
