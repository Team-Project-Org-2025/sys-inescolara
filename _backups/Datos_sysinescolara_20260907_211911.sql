-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: sysinescolara
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT ;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS ;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION ;
SET NAMES utf8mb4 ;
SET @OLD_TIME_ZONE=@@TIME_ZONE ;
SET TIME_ZONE='+00:00' ;
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 ;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 ;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' ;
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 ;

--
-- Current Database: `sysinescolara`
--

CREATE DATABASE IF NOT EXISTS `sysinescolara` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

USE `sysinescolara`;

--
-- Table structure for table `asignar_tarea`
--

DROP TABLE IF EXISTS `asignar_tarea`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `asignar_tarea` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `nombre_tarea` varchar(100) NOT NULL COMMENT 'Nombre de la tarea',
  `descripcion` text DEFAULT NULL COMMENT 'Descripcion opcional de la tarea',
  `fecha_asignacion` date NOT NULL,
  `fecha_cumplimiento` date DEFAULT NULL,
  `estatus_tarea` varchar(20) NOT NULL DEFAULT 'pendiente',
  `horas_dedicadas` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignacion de tareas a usuarios.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `asignar_tarea`
--

LOCK TABLES `asignar_tarea` WRITE;
ALTER TABLE `asignar_tarea` DISABLE KEYS ;
ALTER TABLE `asignar_tarea` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de categorías de lote.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
ALTER TABLE `categoria` DISABLE KEYS ;
INSERT INTO `categoria` VALUES (1,'germinado',1),(2,'plántula',1),(3,'adulto',1);
ALTER TABLE `categoria` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_cedula_cliente` varchar(1) DEFAULT NULL COMMENT 'V, E, J, G, P',
  `cedula_cliente` varchar(10) DEFAULT NULL COMMENT 'Solo dígitos',
  `nombre_cliente` varchar(100) NOT NULL COMMENT 'Nombres',
  `apellido_cliente` varchar(100) NOT NULL DEFAULT '' COMMENT 'Apellidos',
  `contacto_cliente` varchar(250) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de clientes.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
ALTER TABLE `cliente` DISABLE KEYS ;
ALTER TABLE `cliente` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha_compra` date NOT NULL,
  `fecha_recepcion` date DEFAULT NULL COMMENT 'Fecha en que se recibió físicamente',
  `tipo_comprobante` varchar(30) DEFAULT 'Factura',
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','recibida','pagada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_compra`),
  KEY `idx_compra_proveedor` (`id_proveedor`),
  KEY `idx_compra_estado` (`estado`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
ALTER TABLE `compra` DISABLE KEYS ;
ALTER TABLE `compra` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `compra_detalle`
--

DROP TABLE IF EXISTS `compra_detalle`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `compra_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `tipo_item` enum('insumo','herramienta','planta') DEFAULT NULL COMMENT 'Tipo exacto del ítem',
  `id_insumo` int(11) DEFAULT NULL COMMENT 'FK real a insumo',
  `id_herramienta` int(11) DEFAULT NULL COMMENT 'FK real a herramienta',
  `id_planta` int(11) DEFAULT NULL COMMENT 'FK real a plantas',
  `categoria_lote` varchar(30) DEFAULT 'germinado',
  `id_ubicacion` int(11) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_detalle_compra` (`id_compra`),
  KEY `idx_detalle_insumo` (`id_insumo`),
  KEY `idx_detalle_herramienta` (`id_herramienta`),
  KEY `idx_detalle_planta` (`id_planta`),
  CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_herramienta` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`),
  CONSTRAINT `fk_detalle_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  CONSTRAINT `fk_detalle_planta` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `chk_detalle_tipo_item` CHECK ((`id_insumo` is not null) + (`id_herramienta` is not null) + (`id_planta` is not null) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `compra_detalle`
--

LOCK TABLES `compra_detalle` WRITE;
ALTER TABLE `compra_detalle` DISABLE KEYS ;
ALTER TABLE `compra_detalle` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_pagar`
--

DROP TABLE IF EXISTS `cuentas_pagar`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `cuentas_pagar` (
  `id_cuenta_pagar` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cuenta_pagar`),
  KEY `id_compra` (`id_compra`),
  CONSTRAINT `fk_cuentapagar_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `cuentas_pagar`
--

LOCK TABLES `cuentas_pagar` WRITE;
ALTER TABLE `cuentas_pagar` DISABLE KEYS ;
ALTER TABLE `cuentas_pagar` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `detalle_ornatos`
--

DROP TABLE IF EXISTS `detalle_ornatos`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `detalle_ornatos` (
  `id_detalle_ornato` int(11) NOT NULL AUTO_INCREMENT,
  `id_ornato` int(11) NOT NULL,
  `id_lote` int(11) DEFAULT NULL COMMENT 'FK → lote.id_lote (opcional, NULL si es vía tarea sin lote)',
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_ornato`),
  KEY `id_ornato` (`id_ornato`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `fk_detornato_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `fk_detornato_ornato` FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `detalle_ornatos`
--

LOCK TABLES `detalle_ornatos` WRITE;
ALTER TABLE `detalle_ornatos` DISABLE KEYS ;
ALTER TABLE `detalle_ornatos` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `tipo_item` enum('planta','insumo') NOT NULL DEFAULT 'planta',
  `id_lote` int(11) DEFAULT NULL COMMENT 'FK → lote.id_lote (opcional)',
  `id_insumo` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `id_venta` (`id_venta`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `fk_detventa_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `fk_detventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
ALTER TABLE `detalle_venta` DISABLE KEYS ;
ALTER TABLE `detalle_venta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `especie`
--

DROP TABLE IF EXISTS `especie`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `especie` (
  `id_especie` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_especie` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_especie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo botánico.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `especie`
--

LOCK TABLES `especie` WRITE;
ALTER TABLE `especie` DISABLE KEYS ;
ALTER TABLE `especie` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `estado`
--

DROP TABLE IF EXISTS `estado`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estados (reutilizable).';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `estado`
--

LOCK TABLES `estado` WRITE;
ALTER TABLE `estado` DISABLE KEYS ;
INSERT INTO `estado` VALUES (5,'vivo',1),(6,'cuarentena',1),(7,'muerto',1);
ALTER TABLE `estado` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `herramienta`
--

DROP TABLE IF EXISTS `herramienta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `herramienta` (
  `id_herramienta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_herramienta` varchar(150) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `tipo` varchar(50) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_ultimo_mantenimiento` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramientas con ciclo de vida propio.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `herramienta`
--

LOCK TABLES `herramienta` WRITE;
ALTER TABLE `herramienta` DISABLE KEYS ;
ALTER TABLE `herramienta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `insumo`
--

DROP TABLE IF EXISTS `insumo`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `insumo` (
  `id_insumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad_medida` int(11) NOT NULL,
  `nombre_insumo` varchar(150) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_unitario_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_insumo`),
  KEY `id_unidad_medida` (`id_unidad_medida`),
  CONSTRAINT `fk_insumo_unidad` FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inventario de insumos.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `insumo`
--

LOCK TABLES `insumo` WRITE;
ALTER TABLE `insumo` DISABLE KEYS ;
ALTER TABLE `insumo` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `lote`
--

DROP TABLE IF EXISTS `lote`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `lote` (
  `id_lote` int(11) NOT NULL AUTO_INCREMENT,
  `id_planta` int(11) NOT NULL,
  `id_ubicacion` int(11) DEFAULT NULL,
  `fecha_siembra` date NOT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00 COMMENT 'Precio base por planta',
  `id_estado` int(11) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_origen` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `porcentaje_ganancia` decimal(5,2) NOT NULL DEFAULT 30.00 COMMENT '% ganancia configurable por lote',
  PRIMARY KEY (`id_lote`),
  KEY `id_planta` (`id_planta`),
  KEY `id_ubicacion` (`id_ubicacion`),
  KEY `id_estado` (`id_estado`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_origen` (`id_origen`),
  CONSTRAINT `fk_lote_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  CONSTRAINT `fk_lote_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `fk_lote_origen` FOREIGN KEY (`id_origen`) REFERENCES `origen` (`id_origen`),
  CONSTRAINT `fk_lote_planta` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `fk_lote_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidad de producción. Precio calculado en código.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `lote`
--

LOCK TABLES `lote` WRITE;
ALTER TABLE `lote` DISABLE KEYS ;
ALTER TABLE `lote` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `mermas_historico`
--

DROP TABLE IF EXISTS `mermas_historico`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `mermas_historico` (
  `id_merma` int(11) NOT NULL AUTO_INCREMENT,
  `id_trazabilidad` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` enum('plaga','dano_mecanico','factor_climatico','enfermedad','otro') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_merma` date NOT NULL,
  `impacto_economico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_usuario_registra` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_merma`),
  KEY `id_trazabilidad` (`id_trazabilidad`),
  KEY `id_lote` (`id_lote`),
  KEY `fk_merma_usuario` (`id_usuario_registra`),
  CONSTRAINT `fk_merma_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `fk_merma_trazabilidad` FOREIGN KEY (`id_trazabilidad`) REFERENCES `trazabilidad` (`id_trazabilidad`),
  CONSTRAINT `fk_merma_usuario` FOREIGN KEY (`id_usuario_registra`) REFERENCES `SysInescolara-Seguridad`.`usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `mermas_historico`
--

LOCK TABLES `mermas_historico` WRITE;
ALTER TABLE `mermas_historico` DISABLE KEYS ;
ALTER TABLE `mermas_historico` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_planta`
--

DROP TABLE IF EXISTS `movimiento_planta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `movimiento_planta` (
  `id_movimiento_planta` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'venta, ornato, donacion, intercambio',
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario_gestor` int(11) NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_movimiento_planta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario_gestor` (`id_usuario_gestor`),
  KEY `idx_mp_activo` (`activo`),
  CONSTRAINT `fk_movplanta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica venta, ornato, donación e intercambio de plantas.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `movimiento_planta`
--

LOCK TABLES `movimiento_planta` WRITE;
ALTER TABLE `movimiento_planta` DISABLE KEYS ;
ALTER TABLE `movimiento_planta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_planta_detalle`
--

DROP TABLE IF EXISTS `movimiento_planta_detalle`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `movimiento_planta_detalle` (
  `id_detalle_mov_planta` int(11) NOT NULL AUTO_INCREMENT,
  `id_movimiento_planta` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL DEFAULT 'salida',
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle_mov_planta`),
  KEY `id_movimiento_planta` (`id_movimiento_planta`),
  KEY `id_lote` (`id_lote`),
  KEY `idx_mpd_activo` (`activo`),
  CONSTRAINT `fk_detmovplanta_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `fk_detmovplanta_mov` FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por lote del movimiento de plantas.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `movimiento_planta_detalle`
--

LOCK TABLES `movimiento_planta_detalle` WRITE;
ALTER TABLE `movimiento_planta_detalle` DISABLE KEYS ;
ALTER TABLE `movimiento_planta_detalle` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `origen`
--

DROP TABLE IF EXISTS `origen`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `origen` (
  `id_origen` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_origen`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de orígenes (reutilizable).';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `origen`
--

LOCK TABLES `origen` WRITE;
ALTER TABLE `origen` DISABLE KEYS ;
INSERT INTO `origen` VALUES (1,'Siembra',1),(2,'Ampliación',1),(3,'Donación',1),(4,'Compra',1);
ALTER TABLE `origen` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `ornatos`
--

DROP TABLE IF EXISTS `ornatos`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `ornatos` (
  `id_ornato` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `tipo_ornato` enum('Venta','Donacion') NOT NULL DEFAULT 'Venta',
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `monto_total` decimal(10,2) DEFAULT 0.00,
  `fecha` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_ornato`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `fk_ornato_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `ornatos`
--

LOCK TABLES `ornatos` WRITE;
ALTER TABLE `ornatos` DISABLE KEYS ;
ALTER TABLE `ornatos` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `pago_compra`
--

DROP TABLE IF EXISTS `pago_compra`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `pago_compra` (
  `id_pago_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta_pagar` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipo_pago` varchar(30) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('registrado','confirmado','anulado') NOT NULL DEFAULT 'registrado',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago_compra`),
  KEY `id_cuenta_pagar` (`id_cuenta_pagar`),
  CONSTRAINT `fk_pagocompra_cuenta` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `pago_compra`
--

LOCK TABLES `pago_compra` WRITE;
ALTER TABLE `pago_compra` DISABLE KEYS ;
ALTER TABLE `pago_compra` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `pago_venta`
--

DROP TABLE IF EXISTS `pago_venta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `pago_venta` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `metodo` enum('efectivo','transferencia','punto','pago_movil','otro') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `estado_pago` enum('registrado','confirmado','rechazado') NOT NULL DEFAULT 'registrado',
  `banco` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `id_venta` (`id_venta`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_pagoventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `pago_venta`
--

LOCK TABLES `pago_venta` WRITE;
ALTER TABLE `pago_venta` DISABLE KEYS ;
ALTER TABLE `pago_venta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `plantas`
--

DROP TABLE IF EXISTS `plantas`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `plantas` (
  `id_planta` int(11) NOT NULL AUTO_INCREMENT,
  `id_especie` int(11) DEFAULT NULL,
  `nombre_tecnico` varchar(150) DEFAULT '',
  `nombre_comun` varchar(150) DEFAULT NULL,
  `cantidad_total` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_planta`),
  KEY `id_especie` (`id_especie`),
  CONSTRAINT `fk_planta_especie` FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de plantas.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `plantas`
--

LOCK TABLES `plantas` WRITE;
ALTER TABLE `plantas` DISABLE KEYS ;
ALTER TABLE `plantas` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(100) NOT NULL,
  `rif_proveedor` varchar(20) NOT NULL,
  `contacto_vendedor` varchar(100) DEFAULT NULL,
  `telefono_proveedor` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `uq_proveedor_rif` (`rif_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de proveedores.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
ALTER TABLE `proveedores` DISABLE KEYS ;
ALTER TABLE `proveedores` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `recoleccion_semillas`
--

DROP TABLE IF EXISTS `recoleccion_semillas`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `recoleccion_semillas` (
  `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `id_ubicacion` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_recoleccion` date DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_recoleccion`),
  KEY `idx_recoleccion_usuario` (`id_usuario`),
  KEY `idx_recoleccion_ubicacion` (`id_ubicacion`),
  KEY `idx_recoleccion_estatus` (`estatus`),
  CONSTRAINT `fk_recoleccion_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `recoleccion_semillas`
--

LOCK TABLES `recoleccion_semillas` WRITE;
ALTER TABLE `recoleccion_semillas` DISABLE KEYS ;
ALTER TABLE `recoleccion_semillas` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `recoleccion_semillas_detalle`
--

DROP TABLE IF EXISTS `recoleccion_semillas_detalle`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `recoleccion_semillas_detalle` (
  `id_recoleccion_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_recoleccion` int(11) NOT NULL,
  `planta_origen` varchar(150) DEFAULT NULL,
  `nombre_semilla` varchar(100) NOT NULL,
  `id_unidad_medida` int(11) NOT NULL,
  `id_insumo` int(11) DEFAULT NULL COMMENT 'FK al insumo generado al procesar',
  `cantidad` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_recoleccion_detalle`),
  KEY `idx_detalle_recoleccion` (`id_recoleccion`),
  KEY `idx_detalle_insumo` (`id_insumo`),
  CONSTRAINT `fk_detrecoleccion_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  CONSTRAINT `fk_detrecoleccion_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion_semillas` (`id_recoleccion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `recoleccion_semillas_detalle`
--

LOCK TABLES `recoleccion_semillas_detalle` WRITE;
ALTER TABLE `recoleccion_semillas_detalle` DISABLE KEYS ;
ALTER TABLE `recoleccion_semillas_detalle` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `registro_insumo`
--

DROP TABLE IF EXISTS `registro_insumo`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `registro_insumo` (
  `id_registro_insumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) DEFAULT NULL COMMENT 'NULL si la tarea no tiene lote asociado',
  `id_insumo` int(11) NOT NULL,
  `id_asignacion` int(11) DEFAULT NULL COMMENT 'NULL = directo, NO NULL = vía tarea',
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `fecha_registro` date NOT NULL,
  PRIMARY KEY (`id_registro_insumo`),
  KEY `idx_registro_lote` (`id_lote`),
  KEY `idx_registro_insumo` (`id_insumo`),
  KEY `idx_registro_asignacion` (`id_asignacion`),
  CONSTRAINT `fk_registro_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  CONSTRAINT `fk_registro_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  CONSTRAINT `fk_registro_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro unificado de insumos: directo en lote o vía tarea.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `registro_insumo`
--

LOCK TABLES `registro_insumo` WRITE;
ALTER TABLE `registro_insumo` DISABLE KEYS ;
ALTER TABLE `registro_insumo` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `trazabilidad`
--

DROP TABLE IF EXISTS `trazabilidad`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `estado_salud` varchar(30) NOT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` date NOT NULL,
  PRIMARY KEY (`id_trazabilidad`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `fk_trazabilidad_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial fitosanitario por lote.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `trazabilidad`
--

LOCK TABLES `trazabilidad` WRITE;
ALTER TABLE `trazabilidad` DISABLE KEYS ;
ALTER TABLE `trazabilidad` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `ubicacion`
--

DROP TABLE IF EXISTS `ubicacion`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `zona` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espacios físicos del vivero.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
ALTER TABLE `ubicacion` DISABLE KEYS ;
ALTER TABLE `ubicacion` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `unidad_medida`
--

DROP TABLE IF EXISTS `unidad_medida`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `unidad_medida` (
  `id_unidad_medida` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_unidad_medida` varchar(50) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad_medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades de medida para insumos.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `unidad_medida`
--

LOCK TABLES `unidad_medida` WRITE;
ALTER TABLE `unidad_medida` DISABLE KEYS ;
ALTER TABLE `unidad_medida` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `uso_herramienta`
--

DROP TABLE IF EXISTS `uso_herramienta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `uso_herramienta` (
  `id_uso` int(11) NOT NULL AUTO_INCREMENT,
  `id_asignacion` int(11) NOT NULL,
  `id_herramienta` int(11) NOT NULL,
  `fecha_uso` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `estado_herramienta_post_uso` varchar(30) NOT NULL DEFAULT 'ok',
  PRIMARY KEY (`id_uso`),
  KEY `id_asignacion` (`id_asignacion`),
  KEY `id_herramienta` (`id_herramienta`),
  CONSTRAINT `fk_uso_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  CONSTRAINT `fk_uso_herramienta` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramienta ligada a una tarea.';
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `uso_herramienta`
--

LOCK TABLES `uso_herramienta` WRITE;
ALTER TABLE `uso_herramienta` DISABLE KEYS ;
ALTER TABLE `uso_herramienta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
SET @saved_cs_client     = @@character_set_client ;
SET character_set_client = utf8 ;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `referencia` varchar(30) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `tipo_venta` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `estado` enum('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 16.00,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uq_venta_referencia` (`referencia`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET character_set_client = @saved_cs_client ;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
ALTER TABLE `venta` DISABLE KEYS ;

ALTER TABLE `venta` ENABLE KEYS ;
UNLOCK TABLES;

--
-- Dumping routines for database 'sysinescolara'
--
