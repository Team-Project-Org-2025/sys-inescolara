CREATE TABLE `especie` (
  `id_especie` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_especie` varchar(150) NOT NULL,
  `descripcion` text
);

CREATE TABLE `ubicacion` (
  `id_ubicacion` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255),
  `zona` varchar(50)
);

CREATE TABLE `unidad_medida` (
  `id_unidad_medida` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_unidad_medida` varchar(50) NOT NULL,
  `simbolo` varchar(10)
);

CREATE TABLE `plantas` (
  `id_planta` int PRIMARY KEY AUTO_INCREMENT,
  `id_especie` int NOT NULL,
  `nombre_tecnico` varchar(150) NOT NULL,
  `nombre_comun` varchar(150),
  `cantidad_total` int NOT NULL DEFAULT 0
);

CREATE TABLE `planta_precio_vigente` (
  `id_planta` int PRIMARY KEY,
  `id_calculo` int UNIQUE NOT NULL
);

CREATE TABLE `lote` (
  `id_lote` int PRIMARY KEY AUTO_INCREMENT,
  `id_planta` int NOT NULL,
  `id_ubicacion` int NOT NULL,
  `fecha_siembra` date NOT NULL,
  `cantidad_inicial` int NOT NULL,
  `cantidad_actual` int NOT NULL,
  `origen` varchar(30) NOT NULL,
  `observacion` varchar(255)
);

CREATE TABLE `calculo_precio` (
  `id_calculo` int PRIMARY KEY AUTO_INCREMENT,
  `id_lote` int NOT NULL,
  `costo_mano_obra` decimal(10,2) NOT NULL DEFAULT 0,
  `costo_total_insumo` decimal(10,2) NOT NULL DEFAULT 0,
  `costo_agua_lote` decimal(10,2) NOT NULL DEFAULT 0,
  `porcentaje_ganancia` decimal(5,2) NOT NULL DEFAULT 0,
  `cantidad_planta_base` int NOT NULL,
  `precio_final_sugerido` decimal(10,2) NOT NULL,
  `fecha_calculo` date NOT NULL,
  `vigente` boolean NOT NULL DEFAULT false
);

CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int PRIMARY KEY AUTO_INCREMENT,
  `id_lote` int NOT NULL,
  `estado_salud` varchar(30) NOT NULL,
  `observacion` text,
  `fecha_registro` date NOT NULL
);

CREATE TABLE `proveedores` (
  `id_proveedor` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_proveedor` varchar(100) NOT NULL,
  `rif_proveedor` varchar(20) UNIQUE NOT NULL,
  `contacto_vendedor` varchar(100),
  `telefono_proveedor` varchar(20)
);

CREATE TABLE `insumo` (
  `id_insumo` int PRIMARY KEY AUTO_INCREMENT,
  `id_unidad_medida` int NOT NULL,
  `nombre_insumo` varchar(150) NOT NULL,
  `categoria` varchar(50),
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0,
  `costo_unitario_actual` decimal(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE `compras_insumos` (
  `id_compra` int PRIMARY KEY AUTO_INCREMENT,
  `id_proveedor` int NOT NULL,
  `id_insumo` int NOT NULL,
  `cantidad_comprada` decimal(10,2) NOT NULL,
  `precio_compra_unitario` decimal(10,2) NOT NULL,
  `fecha_compra` date NOT NULL
);

CREATE TABLE `herramienta` (
  `id_herramienta` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_herramienta` varchar(150) NOT NULL,
  `tipo` varchar(50),
  `estado` varchar(30) NOT NULL DEFAULT 'disponible',
  `fecha_adquisicion` date,
  `fecha_ultimo_mantenimiento` date,
  `observacion` text
);

CREATE TABLE `trabajadores` (
  `id_trabajador` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_trabajador` varchar(100) NOT NULL,
  `apellido_trabajador` varchar(100) NOT NULL,
  `cedula_trabajador` varchar(20) UNIQUE NOT NULL,
  `telefono_trabajador` varchar(20),
  `cargo` varchar(50),
  `activo` boolean NOT NULL DEFAULT true
);

CREATE TABLE `asistencia` (
  `id_asistencia` int PRIMARY KEY AUTO_INCREMENT,
  `id_trabajador` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time,
  `hora_salida` time,
  `tipo_asistencia` varchar(20) NOT NULL DEFAULT 'presente'
);

CREATE TABLE `tareas` (
  `id_tarea` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_tarea` varchar(100) NOT NULL,
  `descripcion` text,
  `categoria` varchar(50)
);

CREATE TABLE `asignar_tarea` (
  `id_asignacion` int PRIMARY KEY AUTO_INCREMENT,
  `id_trabajador` int NOT NULL,
  `id_tarea` int NOT NULL,
  `id_lote` int NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_cumplimiento` date,
  `estatus_tarea` varchar(20) NOT NULL DEFAULT 'pendiente',
  `horas_dedicadas` decimal(5,2)
);

CREATE TABLE `consumo_insumos` (
  `id_consumo` int PRIMARY KEY AUTO_INCREMENT,
  `id_asignacion` int NOT NULL,
  `id_insumo` int NOT NULL,
  `cantidad_usada` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `fecha_consumo` date NOT NULL
);

CREATE TABLE `uso_herramienta` (
  `id_uso` int PRIMARY KEY AUTO_INCREMENT,
  `id_asignacion` int NOT NULL,
  `id_herramienta` int NOT NULL,
  `fecha_uso` date NOT NULL,
  `observacion` text,
  `estado_herramienta_post_uso` varchar(30) NOT NULL DEFAULT 'ok'
);

CREATE TABLE `cliente` (
  `id_cliente` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_cliente` varchar(100) NOT NULL,
  `contacto_cliente` varchar(250)
);

CREATE TABLE `movimiento_planta` (
  `id_movimiento_planta` int PRIMARY KEY AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL,
  `id_cliente` int,
  `id_trabajador_gestor` int NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text
);

CREATE TABLE `movimiento_planta_detalle` (
  `id_detalle_mov_planta` int PRIMARY KEY AUTO_INCREMENT,
  `id_movimiento_planta` int NOT NULL,
  `id_lote` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2),
  `sub_total` decimal(10,2)
);

CREATE TABLE `movimiento_insumo` (
  `id_movimiento_insumo` int PRIMARY KEY AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL,
  `id_proveedor` int,
  `id_trabajador_gestor` int NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text
);

CREATE TABLE `movimiento_insumo_detalle` (
  `id_detalle_mov_insumo` int PRIMARY KEY AUTO_INCREMENT,
  `id_movimiento_insumo` int NOT NULL,
  `id_insumo` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2),
  `sub_total` decimal(10,2)
);

CREATE TABLE `ajuste_inventario` (
  `id_ajuste` int PRIMARY KEY AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `id_trabajador` int NOT NULL,
  `tipo_ajuste` varchar(30) NOT NULL,
  `cantidad` int NOT NULL,
  `motivo` text NOT NULL,
  `fecha_ajuste` date NOT NULL
);

CREATE UNIQUE INDEX `asistencia_index_0` ON `asistencia` (`id_trabajador`, `fecha`);

ALTER TABLE `especie` COMMENT = 'Catálogo botánico.';

ALTER TABLE `ubicacion` COMMENT = 'Espacios físicos del vivero.';

ALTER TABLE `unidad_medida` COMMENT = 'Unidades de medida para insumos.';

ALTER TABLE `plantas` COMMENT = 'Catálogo de plantas.';

ALTER TABLE `planta_precio_vigente` COMMENT = '1 planta -> 1 cálculo vigente.';

ALTER TABLE `lote` COMMENT = 'Unidad de producción.';

ALTER TABLE `calculo_precio` COMMENT = 'Cálculo de precio por lote.';

ALTER TABLE `trazabilidad` COMMENT = 'Historial fitosanitario por lote.';

ALTER TABLE `proveedores` COMMENT = 'Catálogo de proveedores.';

ALTER TABLE `insumo` COMMENT = 'Inventario de insumos.';

ALTER TABLE `compras_insumos` COMMENT = 'Entrada de insumos por compra.';

ALTER TABLE `herramienta` COMMENT = 'Herramientas con ciclo de vida propio.';

ALTER TABLE `trabajadores` COMMENT = 'Personal del vivero.';

ALTER TABLE `asistencia` COMMENT = 'Un registro por trabajador y fecha.';

ALTER TABLE `tareas` COMMENT = 'Catálogo de tareas.';

ALTER TABLE `asignar_tarea` COMMENT = 'Nodo central entre talento humano y producción.';

ALTER TABLE `consumo_insumos` COMMENT = 'Todo consumo debe estar justificado por una tarea.';

ALTER TABLE `uso_herramienta` COMMENT = 'Herramienta ligada a una tarea.';

ALTER TABLE `cliente` COMMENT = 'Catálogo de clientes.';

ALTER TABLE `movimiento_planta` COMMENT = 'Unifica venta, ornato, donación e intercambio de plantas.';

ALTER TABLE `movimiento_planta_detalle` COMMENT = 'Detalle por lote del movimiento de plantas.';

ALTER TABLE `movimiento_insumo` COMMENT = 'Unifica compra, donación o ajuste de insumos.';

ALTER TABLE `movimiento_insumo_detalle` COMMENT = 'Detalle por insumo del movimiento.';

ALTER TABLE `ajuste_inventario` COMMENT = 'Correcciones manuales del stock.';

ALTER TABLE `plantas` ADD FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`);

ALTER TABLE `planta_precio_vigente` ADD FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`);

ALTER TABLE `planta_precio_vigente` ADD FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`);

ALTER TABLE `lote` ADD FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`);

ALTER TABLE `lote` ADD FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`);

ALTER TABLE `calculo_precio` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `trazabilidad` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `insumo` ADD FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`);

ALTER TABLE `compras_insumos` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

ALTER TABLE `compras_insumos` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `asistencia` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`);

ALTER TABLE `asignar_tarea` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `consumo_insumos` ADD FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`);

ALTER TABLE `consumo_insumos` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `uso_herramienta` ADD FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`);

ALTER TABLE `uso_herramienta` ADD FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`);

ALTER TABLE `movimiento_planta` ADD FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

ALTER TABLE `movimiento_planta` ADD FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`);

ALTER TABLE `movimiento_planta_detalle` ADD FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`);

ALTER TABLE `movimiento_planta_detalle` ADD FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

ALTER TABLE `movimiento_insumo` ADD FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

ALTER TABLE `movimiento_insumo` ADD FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`);

ALTER TABLE `movimiento_insumo_detalle` ADD FOREIGN KEY (`id_movimiento_insumo`) REFERENCES `movimiento_insumo` (`id_movimiento_insumo`);

ALTER TABLE `movimiento_insumo_detalle` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `ajuste_inventario` ADD FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

ALTER TABLE `ajuste_inventario` ADD FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);
