-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sysinescolara
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sysinescolara`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sysinescolara` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sysinescolara`;

--
-- Table structure for table `ajuste_inventario`
--

DROP TABLE IF EXISTS `ajuste_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ajuste_inventario` (
  `id_ajuste` int(11) NOT NULL AUTO_INCREMENT,
  `id_insumo` int(11) NOT NULL,
  `id_trabajador` int(11) NOT NULL,
  `tipo_ajuste` varchar(30) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` text NOT NULL,
  `fecha_ajuste` date NOT NULL,
  PRIMARY KEY (`id_ajuste`),
  KEY `id_insumo` (`id_insumo`),
  KEY `id_trabajador` (`id_trabajador`),
  CONSTRAINT `ajuste_inventario_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  CONSTRAINT `ajuste_inventario_ibfk_2` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Correcciones manuales del stock.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ajuste_inventario`
--

LOCK TABLES `ajuste_inventario` WRITE;
/*!40000 ALTER TABLE `ajuste_inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `ajuste_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignar_tarea`
--

DROP TABLE IF EXISTS `asignar_tarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asignar_tarea` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_cumplimiento` date DEFAULT NULL,
  `estatus_tarea` varchar(20) NOT NULL DEFAULT 'pendiente',
  `horas_dedicadas` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `id_trabajador` (`id_trabajador`),
  KEY `id_tarea` (`id_tarea`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `asignar_tarea_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`),
  CONSTRAINT `asignar_tarea_ibfk_2` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id_tarea`),
  CONSTRAINT `asignar_tarea_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nodo central entre talento humano y producci├│n.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignar_tarea`
--

LOCK TABLES `asignar_tarea` WRITE;
/*!40000 ALTER TABLE `asignar_tarea` DISABLE KEYS */;
/*!40000 ALTER TABLE `asignar_tarea` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencia`
--

DROP TABLE IF EXISTS `asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `tipo_asistencia` varchar(20) NOT NULL DEFAULT 'presente',
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `asistencia_unique` (`id_trabajador`,`fecha`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Un registro por trabajador y fecha.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencia`
--

LOCK TABLES `asistencia` WRITE;
/*!40000 ALTER TABLE `asistencia` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calculo_precio`
--

DROP TABLE IF EXISTS `calculo_precio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calculo_precio` (
  `id_calculo` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `costo_mano_obra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_total_insumo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_agua_lote` decimal(10,2) NOT NULL DEFAULT 0.00,
  `porcentaje_ganancia` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cantidad_planta_base` int(11) NOT NULL,
  `precio_final_sugerido` decimal(10,2) NOT NULL,
  `fecha_calculo` date NOT NULL,
  `vigente` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_calculo`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `calculo_precio_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='C├ílculo de precio por lote.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calculo_precio`
--

LOCK TABLES `calculo_precio` WRITE;
/*!40000 ALTER TABLE `calculo_precio` DISABLE KEYS */;
INSERT INTO `calculo_precio` VALUES (1,4,10.00,20.00,30.00,30.00,1,78.00,'2026-06-01',1);
/*!40000 ALTER TABLE `calculo_precio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(100) NOT NULL,
  `contacto_cliente` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de clientes.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Mayra Perez','04123005644');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumo_insumos`
--

DROP TABLE IF EXISTS `consumo_insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumo_insumos` (
  `id_consumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_asignacion` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad_usada` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `fecha_consumo` date NOT NULL,
  PRIMARY KEY (`id_consumo`),
  KEY `id_asignacion` (`id_asignacion`),
  KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `consumo_insumos_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  CONSTRAINT `consumo_insumos_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Todo consumo debe estar justificado por una tarea.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumo_insumos`
--

LOCK TABLES `consumo_insumos` WRITE;
/*!40000 ALTER TABLE `consumo_insumos` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumo_insumos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especie`
--

DROP TABLE IF EXISTS `especie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `especie` (
  `id_especie` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_especie` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id_especie`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo bot├ínico.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especie`
--

LOCK TABLES `especie` WRITE;
/*!40000 ALTER TABLE `especie` DISABLE KEYS */;
INSERT INTO `especie` VALUES (1,'dsadasdasdasa',NULL),(2,'dadada',NULL),(3,'dsadasdasdasad',NULL);
/*!40000 ALTER TABLE `especie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `herramienta`
--

DROP TABLE IF EXISTS `herramienta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `herramienta` (
  `id_herramienta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_herramienta` varchar(150) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `fecha_ultimo_mantenimiento` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id_herramienta`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramientas con ciclo de vida propio.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `herramienta`
--

LOCK TABLES `herramienta` WRITE;
/*!40000 ALTER TABLE `herramienta` DISABLE KEYS */;
INSERT INTO `herramienta` VALUES (1,'Pala',NULL,'en_uso','2026-06-02','2026-06-02',NULL),(2,'Martillo','nose','en_uso','2026-06-02','2026-06-02','dsadasdada'),(3,'Pico','herramienta','en_uso','2026-06-02','2026-06-02',NULL);
/*!40000 ALTER TABLE `herramienta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insumo`
--

DROP TABLE IF EXISTS `insumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insumo` (
  `id_insumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad_medida` int(11) NOT NULL,
  `nombre_insumo` varchar(150) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_unitario_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_insumo`),
  KEY `id_unidad_medida` (`id_unidad_medida`),
  CONSTRAINT `insumo_ibfk_1` FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inventario de insumos.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumo`
--

LOCK TABLES `insumo` WRITE;
/*!40000 ALTER TABLE `insumo` DISABLE KEYS */;
/*!40000 ALTER TABLE `insumo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lote`
--

DROP TABLE IF EXISTS `lote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lote` (
  `id_lote` int(11) NOT NULL AUTO_INCREMENT,
  `id_planta` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `fecha_siembra` date NOT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `estado` varchar(50) DEFAULT 'Activo',
  `origen` varchar(30) NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_lote`),
  KEY `id_planta` (`id_planta`),
  KEY `id_ubicacion` (`id_ubicacion`),
  CONSTRAINT `lote_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `lote_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidad de producci├│n.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lote`
--

LOCK TABLES `lote` WRITE;
/*!40000 ALTER TABLE `lote` DISABLE KEYS */;
INSERT INTO `lote` VALUES (4,2,3,'2026-06-01',1,1,'Vivo','Siembra',NULL,'public/assets/uploads/batches/batch_1780330649_5cde51b9.jpg');
/*!40000 ALTER TABLE `lote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_insumo`
--

DROP TABLE IF EXISTS `movimiento_insumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_insumo` (
  `id_movimiento_insumo` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'compra, donacion, ajuste',
  `id_proveedor` int(11) DEFAULT NULL,
  `id_trabajador_gestor` int(11) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id_movimiento_insumo`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_trabajador_gestor` (`id_trabajador_gestor`),
  CONSTRAINT `movimiento_insumo_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `movimiento_insumo_ibfk_2` FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica compra, donaci├│n o ajuste de insumos.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_insumo`
--

LOCK TABLES `movimiento_insumo` WRITE;
/*!40000 ALTER TABLE `movimiento_insumo` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_insumo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_insumo_detalle`
--

DROP TABLE IF EXISTS `movimiento_insumo_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_insumo_detalle` (
  `id_detalle_mov_insumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_movimiento_insumo` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_mov_insumo`),
  KEY `id_movimiento_insumo` (`id_movimiento_insumo`),
  KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `movimiento_insumo_detalle_ibfk_1` FOREIGN KEY (`id_movimiento_insumo`) REFERENCES `movimiento_insumo` (`id_movimiento_insumo`),
  CONSTRAINT `movimiento_insumo_detalle_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por insumo del movimiento.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_insumo_detalle`
--

LOCK TABLES `movimiento_insumo_detalle` WRITE;
/*!40000 ALTER TABLE `movimiento_insumo_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_insumo_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_planta`
--

DROP TABLE IF EXISTS `movimiento_planta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_planta` (
  `id_movimiento_planta` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'venta, ornato, donacion, intercambio',
  `id_cliente` int(11) DEFAULT NULL,
  `id_trabajador_gestor` int(11) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id_movimiento_planta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_trabajador_gestor` (`id_trabajador_gestor`),
  CONSTRAINT `movimiento_planta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `movimiento_planta_ibfk_2` FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica venta, ornato, donaci├│n e intercambio de plantas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_planta`
--

LOCK TABLES `movimiento_planta` WRITE;
/*!40000 ALTER TABLE `movimiento_planta` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_planta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_planta_detalle`
--

DROP TABLE IF EXISTS `movimiento_planta_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_planta_detalle` (
  `id_detalle_mov_planta` int(11) NOT NULL AUTO_INCREMENT,
  `id_movimiento_planta` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_mov_planta`),
  KEY `id_movimiento_planta` (`id_movimiento_planta`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `movimiento_planta_detalle_ibfk_1` FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`),
  CONSTRAINT `movimiento_planta_detalle_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por lote del movimiento de plantas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_planta_detalle`
--

LOCK TABLES `movimiento_planta_detalle` WRITE;
/*!40000 ALTER TABLE `movimiento_planta_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_planta_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planta_precio_vigente`
--

DROP TABLE IF EXISTS `planta_precio_vigente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planta_precio_vigente` (
  `id_planta` int(11) NOT NULL,
  `id_calculo` int(11) NOT NULL,
  PRIMARY KEY (`id_planta`),
  UNIQUE KEY `id_calculo` (`id_calculo`),
  CONSTRAINT `planta_precio_vigente_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `planta_precio_vigente_ibfk_2` FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='1 planta -> 1 c├ílculo vigente.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planta_precio_vigente`
--

LOCK TABLES `planta_precio_vigente` WRITE;
/*!40000 ALTER TABLE `planta_precio_vigente` DISABLE KEYS */;
INSERT INTO `planta_precio_vigente` VALUES (2,1);
/*!40000 ALTER TABLE `planta_precio_vigente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plantas`
--

DROP TABLE IF EXISTS `plantas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plantas` (
  `id_planta` int(11) NOT NULL AUTO_INCREMENT,
  `id_especie` int(11) NOT NULL,
  `nombre_tecnico` varchar(150) NOT NULL,
  `nombre_comun` varchar(150) DEFAULT NULL,
  `cantidad_total` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_planta`),
  KEY `id_especie` (`id_especie`),
  CONSTRAINT `plantas_ibfk_1` FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de plantas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plantas`
--

LOCK TABLES `plantas` WRITE;
/*!40000 ALTER TABLE `plantas` DISABLE KEYS */;
INSERT INTO `plantas` VALUES (2,1,'Cactuspro','Simon',0,'public/assets/uploads/plants/plant_1780267336_412abda4.png');
/*!40000 ALTER TABLE `plantas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(100) NOT NULL,
  `rif_proveedor` varchar(20) NOT NULL,
  `contacto_vendedor` varchar(100) DEFAULT NULL,
  `telefono_proveedor` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `rif_proveedor` (`rif_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de proveedores.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tarea` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_tarea`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de tareas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trabajadores`
--

DROP TABLE IF EXISTS `trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trabajadores` (
  `id_trabajador` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_trabajador` varchar(100) NOT NULL,
  `apellido_trabajador` varchar(100) NOT NULL,
  `cedula_trabajador` varchar(20) NOT NULL,
  `telefono_trabajador` varchar(20) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_trabajador`),
  UNIQUE KEY `cedula_trabajador` (`cedula_trabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Personal del vivero.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trabajadores`
--

LOCK TABLES `trabajadores` WRITE;
/*!40000 ALTER TABLE `trabajadores` DISABLE KEYS */;
INSERT INTO `trabajadores` VALUES (1,'Enyell','Duarte','31511825','04120000000','Administrador',1);
/*!40000 ALTER TABLE `trabajadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trazabilidad`
--

DROP TABLE IF EXISTS `trazabilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `estado_salud` varchar(30) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  PRIMARY KEY (`id_trazabilidad`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `trazabilidad_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial fitosanitario por lote.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trazabilidad`
--

LOCK TABLES `trazabilidad` WRITE;
/*!40000 ALTER TABLE `trazabilidad` DISABLE KEYS */;
/*!40000 ALTER TABLE `trazabilidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicacion`
--

DROP TABLE IF EXISTS `ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `zona` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espacios f├¡sicos del vivero.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
/*!40000 ALTER TABLE `ubicacion` DISABLE KEYS */;
INSERT INTO `ubicacion` VALUES (2,'Invernadero Central','Invernadero principal','A'),(3,'Almacén Norte','Almacén de insumos','B'),(4,'Vivero Exterior','Área de exposición','C');
/*!40000 ALTER TABLE `ubicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidad_medida`
--

DROP TABLE IF EXISTS `unidad_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidad_medida` (
  `id_unidad_medida` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_unidad_medida` varchar(50) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_unidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades de medida para insumos.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_medida`
--

LOCK TABLES `unidad_medida` WRITE;
/*!40000 ALTER TABLE `unidad_medida` DISABLE KEYS */;
INSERT INTO `unidad_medida` VALUES (1,'Kilogramo','kg'),(2,'Gramo','g'),(3,'Litro','L'),(4,'Mililitro','mL'),(5,'Unidad','und'),(6,'Metro','m'),(7,'CentÝmetro','cm'),(8,'Saco','saco'),(9,'Bolsa','bolsa'),(10,'Paquete','pqte'),(11,'Gal¾n','gal'),(12,'Caja','caja'),(13,'Rollos','rllo'),(14,'Bidon','bidon');
/*!40000 ALTER TABLE `unidad_medida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uso_herramienta`
--

DROP TABLE IF EXISTS `uso_herramienta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  CONSTRAINT `uso_herramienta_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  CONSTRAINT `uso_herramienta_ibfk_2` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramienta ligada a una tarea.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uso_herramienta`
--

LOCK TABLES `uso_herramienta` WRITE;
/*!40000 ALTER TABLE `uso_herramienta` DISABLE KEYS */;
/*!40000 ALTER TABLE `uso_herramienta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sysinescolara'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-01 21:12:06
