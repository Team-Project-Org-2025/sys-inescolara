-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-06-2026 a las 21:52:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sysinescolara`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ajuste_inventario`
--

CREATE TABLE `ajuste_inventario` (
  `id_ajuste` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `tipo_ajuste` varchar(30) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` text NOT NULL,
  `fecha_ajuste` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Correcciones manuales del stock.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignar_tarea`
--

CREATE TABLE `asignar_tarea` (
  `id_asignacion` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_cumplimiento` date DEFAULT NULL,
  `estatus_tarea` varchar(20) NOT NULL DEFAULT 'pendiente',
  `horas_dedicadas` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nodo central entre talento humano y producci├│n.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `tipo_asistencia` varchar(20) NOT NULL DEFAULT 'presente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Un registro por trabajador y fecha.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calculo_precio`
--

CREATE TABLE `calculo_precio` (
  `id_calculo` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `costo_mano_obra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_total_insumo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_agua_lote` decimal(10,2) NOT NULL DEFAULT 0.00,
  `porcentaje_ganancia` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cantidad_planta_base` int(11) NOT NULL,
  `precio_final_sugerido` decimal(10,2) NOT NULL,
  `fecha_calculo` date NOT NULL,
  `vigente` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='C├ílculo de precio por lote.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `contacto_cliente` varchar(250) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de clientes.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha_compra` date NOT NULL,
  `tipo_comprobante` varchar(30) DEFAULT 'Factura',
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','recibida','pagada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra_detalle`
--

CREATE TABLE `compra_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `tipo_item` enum('insumo','herramienta','planta') NOT NULL,
  `id_item` int(11) NOT NULL,
  `categoria_lote` varchar(30) DEFAULT 'germinado',
  `id_ubicacion` int(11) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumo_insumos`
--

CREATE TABLE `consumo_insumos` (
  `id_consumo` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad_usada` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `stock_actual` decimal(10,2) DEFAULT NULL,
  `fecha_consumo` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Todo consumo debe estar justificado por una tarea.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_pagar`
--

CREATE TABLE `cuentas_pagar` (
  `id_cuenta_pagar` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ornatos`
--

CREATE TABLE `detalle_ornatos` (
  `id_detalle_ornato` int(11) NOT NULL,
  `id_ornato` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especie`
--

CREATE TABLE `especie` (
  `id_especie` int(11) NOT NULL,
  `nombre_especie` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo bot├ínico.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `herramienta`
--

CREATE TABLE `herramienta` (
  `id_herramienta` int(11) NOT NULL,
  `nombre_herramienta` varchar(150) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_ultimo_mantenimiento` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramientas con ciclo de vida propio.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumo`
--

CREATE TABLE `insumo` (
  `id_insumo` int(11) NOT NULL,
  `id_unidad_medida` int(11) NOT NULL,
  `nombre_insumo` varchar(150) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_unitario_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inventario de insumos.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lote`
--

CREATE TABLE `lote` (
  `id_lote` int(11) NOT NULL,
  `id_planta` int(11) NOT NULL,
  `id_ubicacion` int(11) DEFAULT NULL,
  `fecha_siembra` date NOT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00,
  `estado` varchar(50) DEFAULT 'Activo',
  `categoria` varchar(30) DEFAULT NULL,
  `origen` varchar(30) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidad de producci├│n.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mermas_historico`
--

CREATE TABLE `mermas_historico` (
  `id_merma` int(11) NOT NULL,
  `id_trazabilidad` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` enum('plaga','da±o_mecanico','factor_climatico','enfermedad','otro') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_merma` date NOT NULL,
  `impacto_economico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_usuario_registra` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento_insumo`
--

CREATE TABLE `movimiento_insumo` (
  `id_movimiento_insumo` int(11) NOT NULL,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'compra, donacion, ajuste',
  `id_proveedor` int(11) DEFAULT NULL,
  `id_trabajador_gestor` int(11) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica compra, donaci├│n o ajuste de insumos.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento_insumo_detalle`
--

CREATE TABLE `movimiento_insumo_detalle` (
  `id_detalle_mov_insumo` int(11) NOT NULL,
  `id_movimiento_insumo` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por insumo del movimiento.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento_planta`
--

CREATE TABLE `movimiento_planta` (
  `id_movimiento_planta` int(11) NOT NULL,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'venta, ornato, donacion, intercambio',
  `id_cliente` int(11) DEFAULT NULL,
  `id_trabajador_gestor` int(11) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica venta, ornato, donaci├│n e intercambio de plantas.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento_planta_detalle`
--

CREATE TABLE `movimiento_planta_detalle` (
  `id_detalle_mov_planta` int(11) NOT NULL,
  `id_movimiento_planta` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL DEFAULT 'salida',
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por lote del movimiento de plantas.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ornatos`
--

CREATE TABLE `ornatos` (
  `id_ornato` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `tipo_ornato` enum('Venta','Donacion') NOT NULL DEFAULT 'Venta',
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `monto_total` decimal(10,2) DEFAULT 0.00,
  `fecha` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_compra`
--

CREATE TABLE `pago_compra` (
  `id_pago_compra` int(11) NOT NULL,
  `id_cuenta_pagar` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipo_pago` varchar(30) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('registrado','confirmado','anulado') NOT NULL DEFAULT 'registrado',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago_venta`
--

CREATE TABLE `pago_venta` (
  `id_pago` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `metodo` enum('efectivo','transferencia','punto','pago_movil','otro') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `estado_pago` enum('registrado','confirmado','rechazado') NOT NULL DEFAULT 'registrado',
  `banco` varchar(100) DEFAULT NULL,
  `id_trabajador` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantas`
--

CREATE TABLE `plantas` (
  `id_planta` int(11) NOT NULL,
  `id_especie` int(11) DEFAULT NULL,
  `nombre_tecnico` varchar(150) DEFAULT '',
  `nombre_comun` varchar(150) DEFAULT NULL,
  `cantidad_total` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de plantas.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planta_precio_vigente`
--

CREATE TABLE `planta_precio_vigente` (
  `id_planta` int(11) NOT NULL,
  `id_calculo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='1 planta -> 1 c├ílculo vigente.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(100) NOT NULL,
  `rif_proveedor` varchar(20) NOT NULL,
  `contacto_vendedor` varchar(100) DEFAULT NULL,
  `telefono_proveedor` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de proveedores.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recoleccion_semillas`
--

CREATE TABLE `recoleccion_semillas` (
  `id_recoleccion` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_recoleccion` date DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `observacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recoleccion_semillas_detalle`
--

CREATE TABLE `recoleccion_semillas_detalle` (
  `id_recoleccion_detalle` int(11) NOT NULL,
  `id_recoleccion` int(11) NOT NULL,
  `planta_origen` varchar(150) DEFAULT NULL,
  `nombre_semilla` varchar(100) NOT NULL,
  `id_unidad_medida` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `nombre_tarea` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `categoria` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de tareas.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajadores`
--

CREATE TABLE `trabajadores` (
  `id_trabajador` int(11) NOT NULL,
  `nombre_trabajador` varchar(100) NOT NULL,
  `apellido_trabajador` varchar(100) NOT NULL,
  `cedula_trabajador` varchar(20) NOT NULL,
  `telefono_trabajador` varchar(20) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Personal del vivero.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trazabilidad`
--

CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `estado_salud` varchar(30) NOT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial fitosanitario por lote.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL,
  `nombre_ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `zona` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espacios f├¡sicos del vivero.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_medida`
--

CREATE TABLE `unidad_medida` (
  `id_unidad_medida` int(11) NOT NULL,
  `nombre_unidad_medida` varchar(50) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades de medida para insumos.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_herramienta`
--

CREATE TABLE `uso_herramienta` (
  `id_uso` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_herramienta` int(11) NOT NULL,
  `fecha_uso` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `estado_herramienta_post_uso` varchar(30) NOT NULL DEFAULT 'ok'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramienta ligada a una tarea.';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL,
  `referencia` varchar(30) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `tipo_venta` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `estado` enum('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 16.00,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajuste_inventario`
--
ALTER TABLE `ajuste_inventario`
  ADD PRIMARY KEY (`id_ajuste`),
  ADD KEY `id_insumo` (`id_insumo`),
  ADD KEY `id_trabajador` (`id_trabajador`);

--
-- Indices de la tabla `asignar_tarea`
--
ALTER TABLE `asignar_tarea`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `id_trabajador` (`id_trabajador`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD UNIQUE KEY `asistencia_unique` (`id_trabajador`,`fecha`);

--
-- Indices de la tabla `calculo_precio`
--
ALTER TABLE `calculo_precio`
  ADD PRIMARY KEY (`id_calculo`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `idx_compra_proveedor` (`id_proveedor`),
  ADD KEY `idx_compra_estado` (`estado`);

--
-- Indices de la tabla `compra_detalle`
--
ALTER TABLE `compra_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_detalle_compra` (`id_compra`);

--
-- Indices de la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  ADD PRIMARY KEY (`id_consumo`),
  ADD KEY `id_asignacion` (`id_asignacion`),
  ADD KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD PRIMARY KEY (`id_cuenta_pagar`),
  ADD KEY `id_compra` (`id_compra`);

--
-- Indices de la tabla `detalle_ornatos`
--
ALTER TABLE `detalle_ornatos`
  ADD PRIMARY KEY (`id_detalle_ornato`),
  ADD KEY `id_ornato` (`id_ornato`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `especie`
--
ALTER TABLE `especie`
  ADD PRIMARY KEY (`id_especie`);

--
-- Indices de la tabla `herramienta`
--
ALTER TABLE `herramienta`
  ADD PRIMARY KEY (`id_herramienta`);

--
-- Indices de la tabla `insumo`
--
ALTER TABLE `insumo`
  ADD PRIMARY KEY (`id_insumo`),
  ADD KEY `id_unidad_medida` (`id_unidad_medida`);

--
-- Indices de la tabla `lote`
--
ALTER TABLE `lote`
  ADD PRIMARY KEY (`id_lote`),
  ADD KEY `id_planta` (`id_planta`),
  ADD KEY `id_ubicacion` (`id_ubicacion`);

--
-- Indices de la tabla `mermas_historico`
--
ALTER TABLE `mermas_historico`
  ADD PRIMARY KEY (`id_merma`),
  ADD KEY `id_trazabilidad` (`id_trazabilidad`),
  ADD KEY `id_lote` (`id_lote`),
  ADD KEY `fk_merma_usuario` (`id_usuario_registra`);

--
-- Indices de la tabla `movimiento_insumo`
--
ALTER TABLE `movimiento_insumo`
  ADD PRIMARY KEY (`id_movimiento_insumo`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_trabajador_gestor` (`id_trabajador_gestor`);

--
-- Indices de la tabla `movimiento_insumo_detalle`
--
ALTER TABLE `movimiento_insumo_detalle`
  ADD PRIMARY KEY (`id_detalle_mov_insumo`),
  ADD KEY `id_movimiento_insumo` (`id_movimiento_insumo`),
  ADD KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `movimiento_planta`
--
ALTER TABLE `movimiento_planta`
  ADD PRIMARY KEY (`id_movimiento_planta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_trabajador_gestor` (`id_trabajador_gestor`),
  ADD KEY `idx_mp_activo` (`activo`);

--
-- Indices de la tabla `movimiento_planta_detalle`
--
ALTER TABLE `movimiento_planta_detalle`
  ADD PRIMARY KEY (`id_detalle_mov_planta`),
  ADD KEY `id_movimiento_planta` (`id_movimiento_planta`),
  ADD KEY `id_lote` (`id_lote`),
  ADD KEY `idx_mpd_activo` (`activo`);

--
-- Indices de la tabla `ornatos`
--
ALTER TABLE `ornatos`
  ADD PRIMARY KEY (`id_ornato`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `pago_compra`
--
ALTER TABLE `pago_compra`
  ADD PRIMARY KEY (`id_pago_compra`),
  ADD KEY `id_cuenta_pagar` (`id_cuenta_pagar`);

--
-- Indices de la tabla `pago_venta`
--
ALTER TABLE `pago_venta`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `plantas`
--
ALTER TABLE `plantas`
  ADD PRIMARY KEY (`id_planta`),
  ADD KEY `id_especie` (`id_especie`);

--
-- Indices de la tabla `planta_precio_vigente`
--
ALTER TABLE `planta_precio_vigente`
  ADD PRIMARY KEY (`id_planta`),
  ADD UNIQUE KEY `id_calculo` (`id_calculo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `rif_proveedor` (`rif_proveedor`);

--
-- Indices de la tabla `recoleccion_semillas`
--
ALTER TABLE `recoleccion_semillas`
  ADD PRIMARY KEY (`id_recoleccion`),
  ADD KEY `idx_recoleccion_trabajador` (`id_trabajador`),
  ADD KEY `idx_recoleccion_ubicacion` (`id_ubicacion`),
  ADD KEY `idx_recoleccion_estatus` (`estatus`);

--
-- Indices de la tabla `recoleccion_semillas_detalle`
--
ALTER TABLE `recoleccion_semillas_detalle`
  ADD PRIMARY KEY (`id_recoleccion_detalle`),
  ADD KEY `idx_detalle_recoleccion` (`id_recoleccion`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`);

--
-- Indices de la tabla `trabajadores`
--
ALTER TABLE `trabajadores`
  ADD PRIMARY KEY (`id_trabajador`),
  ADD UNIQUE KEY `cedula_trabajador` (`cedula_trabajador`);

--
-- Indices de la tabla `trazabilidad`
--
ALTER TABLE `trazabilidad`
  ADD PRIMARY KEY (`id_trazabilidad`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`id_ubicacion`);

--
-- Indices de la tabla `unidad_medida`
--
ALTER TABLE `unidad_medida`
  ADD PRIMARY KEY (`id_unidad_medida`);

--
-- Indices de la tabla `uso_herramienta`
--
ALTER TABLE `uso_herramienta`
  ADD PRIMARY KEY (`id_uso`),
  ADD KEY `id_asignacion` (`id_asignacion`),
  ADD KEY `id_herramienta` (`id_herramienta`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD UNIQUE KEY `referencia` (`referencia`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_trabajador` (`id_trabajador`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajuste_inventario`
--
ALTER TABLE `ajuste_inventario`
  MODIFY `id_ajuste` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignar_tarea`
--
ALTER TABLE `asignar_tarea`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calculo_precio`
--
ALTER TABLE `calculo_precio`
  MODIFY `id_calculo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compra_detalle`
--
ALTER TABLE `compra_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  MODIFY `id_consumo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  MODIFY `id_cuenta_pagar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ornatos`
--
ALTER TABLE `detalle_ornatos`
  MODIFY `id_detalle_ornato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `especie`
--
ALTER TABLE `especie`
  MODIFY `id_especie` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `herramienta`
--
ALTER TABLE `herramienta`
  MODIFY `id_herramienta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insumo`
--
ALTER TABLE `insumo`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lote`
--
ALTER TABLE `lote`
  MODIFY `id_lote` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mermas_historico`
--
ALTER TABLE `mermas_historico`
  MODIFY `id_merma` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimiento_insumo`
--
ALTER TABLE `movimiento_insumo`
  MODIFY `id_movimiento_insumo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimiento_insumo_detalle`
--
ALTER TABLE `movimiento_insumo_detalle`
  MODIFY `id_detalle_mov_insumo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimiento_planta`
--
ALTER TABLE `movimiento_planta`
  MODIFY `id_movimiento_planta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimiento_planta_detalle`
--
ALTER TABLE `movimiento_planta_detalle`
  MODIFY `id_detalle_mov_planta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ornatos`
--
ALTER TABLE `ornatos`
  MODIFY `id_ornato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago_compra`
--
ALTER TABLE `pago_compra`
  MODIFY `id_pago_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago_venta`
--
ALTER TABLE `pago_venta`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plantas`
--
ALTER TABLE `plantas`
  MODIFY `id_planta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recoleccion_semillas`
--
ALTER TABLE `recoleccion_semillas`
  MODIFY `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recoleccion_semillas_detalle`
--
ALTER TABLE `recoleccion_semillas_detalle`
  MODIFY `id_recoleccion_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trabajadores`
--
ALTER TABLE `trabajadores`
  MODIFY `id_trabajador` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trazabilidad`
--
ALTER TABLE `trazabilidad`
  MODIFY `id_trazabilidad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  MODIFY `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidad_medida`
--
ALTER TABLE `unidad_medida`
  MODIFY `id_unidad_medida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `uso_herramienta`
--
ALTER TABLE `uso_herramienta`
  MODIFY `id_uso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ajuste_inventario`
--
ALTER TABLE `ajuste_inventario`
  ADD CONSTRAINT `ajuste_inventario_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  ADD CONSTRAINT `ajuste_inventario_ibfk_2` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);

--
-- Filtros para la tabla `asignar_tarea`
--
ALTER TABLE `asignar_tarea`
  ADD CONSTRAINT `asignar_tarea_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`),
  ADD CONSTRAINT `asignar_tarea_ibfk_2` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`),
  ADD CONSTRAINT `asignar_tarea_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);

--
-- Filtros para la tabla `calculo_precio`
--
ALTER TABLE `calculo_precio`
  ADD CONSTRAINT `calculo_precio_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

--
-- Filtros para la tabla `compra_detalle`
--
ALTER TABLE `compra_detalle`
  ADD CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  ADD CONSTRAINT `consumo_insumos_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  ADD CONSTRAINT `consumo_insumos_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

--
-- Filtros para la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`);

--
-- Filtros para la tabla `detalle_ornatos`
--
ALTER TABLE `detalle_ornatos`
  ADD CONSTRAINT `detalle_ornatos_ibfk_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  ADD CONSTRAINT `detalle_ornatos_ibfk_ornato` FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `insumo`
--
ALTER TABLE `insumo`
  ADD CONSTRAINT `insumo_ibfk_1` FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`);

--
-- Filtros para la tabla `lote`
--
ALTER TABLE `lote`
  ADD CONSTRAINT `lote_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  ADD CONSTRAINT `lote_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`);

--
-- Filtros para la tabla `mermas_historico`
--
ALTER TABLE `mermas_historico`
  ADD CONSTRAINT `fk_merma_usuario` FOREIGN KEY (`id_usuario_registra`) REFERENCES `sysinescolara-seguridad`.`usuarios` (`id_usuario`),
  ADD CONSTRAINT `mermas_historico_ibfk_1` FOREIGN KEY (`id_trazabilidad`) REFERENCES `trazabilidad` (`id_trazabilidad`),
  ADD CONSTRAINT `mermas_historico_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `movimiento_insumo`
--
ALTER TABLE `movimiento_insumo`
  ADD CONSTRAINT `movimiento_insumo_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  ADD CONSTRAINT `movimiento_insumo_ibfk_2` FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`);

--
-- Filtros para la tabla `movimiento_insumo_detalle`
--
ALTER TABLE `movimiento_insumo_detalle`
  ADD CONSTRAINT `movimiento_insumo_detalle_ibfk_1` FOREIGN KEY (`id_movimiento_insumo`) REFERENCES `movimiento_insumo` (`id_movimiento_insumo`),
  ADD CONSTRAINT `movimiento_insumo_detalle_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`);

--
-- Filtros para la tabla `movimiento_planta`
--
ALTER TABLE `movimiento_planta`
  ADD CONSTRAINT `movimiento_planta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `movimiento_planta_ibfk_2` FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`);

--
-- Filtros para la tabla `movimiento_planta_detalle`
--
ALTER TABLE `movimiento_planta_detalle`
  ADD CONSTRAINT `movimiento_planta_detalle_ibfk_1` FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`),
  ADD CONSTRAINT `movimiento_planta_detalle_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `ornatos`
--
ALTER TABLE `ornatos`
  ADD CONSTRAINT `ornatos_ibfk_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `pago_compra`
--
ALTER TABLE `pago_compra`
  ADD CONSTRAINT `pago_compra_ibfk_1` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`);

--
-- Filtros para la tabla `pago_venta`
--
ALTER TABLE `pago_venta`
  ADD CONSTRAINT `pago_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`);

--
-- Filtros para la tabla `plantas`
--
ALTER TABLE `plantas`
  ADD CONSTRAINT `plantas_ibfk_1` FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`);

--
-- Filtros para la tabla `planta_precio_vigente`
--
ALTER TABLE `planta_precio_vigente`
  ADD CONSTRAINT `planta_precio_vigente_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  ADD CONSTRAINT `planta_precio_vigente_ibfk_2` FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recoleccion_semillas`
--
ALTER TABLE `recoleccion_semillas`
  ADD CONSTRAINT `fk_recoleccion_trabajador` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`),
  ADD CONSTRAINT `fk_recoleccion_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`);

--
-- Filtros para la tabla `recoleccion_semillas_detalle`
--
ALTER TABLE `recoleccion_semillas_detalle`
  ADD CONSTRAINT `fk_detalle_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion_semillas` (`id_recoleccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `trazabilidad`
--
ALTER TABLE `trazabilidad`
  ADD CONSTRAINT `trazabilidad_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`);

--
-- Filtros para la tabla `uso_herramienta`
--
ALTER TABLE `uso_herramienta`
  ADD CONSTRAINT `uso_herramienta_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  ADD CONSTRAINT `uso_herramienta_ibfk_2` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`);

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
