/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sysinescolara
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB-0ubuntu0.22.04.1

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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sysinescolara` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `sysinescolara`;

--
-- Table structure for table `ajuste_inventario`
--

DROP TABLE IF EXISTS `ajuste_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Correcciones manuales del stock.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ajuste_inventario`
--

LOCK TABLES `ajuste_inventario` WRITE;
/*!40000 ALTER TABLE `ajuste_inventario` DISABLE KEYS */;
INSERT INTO `ajuste_inventario` VALUES (1,6,1,'salida',20,'DSDSD','2026-06-09'),(2,6,1,'entrada',5,'Ingreso de Prueba','2026-06-18');
/*!40000 ALTER TABLE `ajuste_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignar_tarea`
--

DROP TABLE IF EXISTS `asignar_tarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nodo central entre talento humano y producci├│n.';
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='C├ílculo de precio por lote.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calculo_precio`
--

LOCK TABLES `calculo_precio` WRITE;
/*!40000 ALTER TABLE `calculo_precio` DISABLE KEYS */;
/*!40000 ALTER TABLE `calculo_precio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de categorías (reutilizable)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (5,'semilla',1),(6,'pequeño',1),(7,'mediano',1),(8,'grande',1);
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_cedula_cliente` varchar(1) DEFAULT NULL,
  `cedula_cliente` varchar(10) DEFAULT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `apellido_cliente` varchar(100) NOT NULL DEFAULT '',
  `contacto_cliente` varchar(250) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de clientes.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'V','31205896','Mayra','Perez','04123005646',1),(2,'V','12451590','Detzy','Acosta','04144237719',0),(3,'V','31205898','Detzy','Acosta','04144237719',0),(4,NULL,NULL,'Detzy Acosta','','04144237719',0),(5,'V','12451590','Detzy','Acosta','04123005647',0);
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_compra`),
  KEY `idx_compra_proveedor` (`id_proveedor`),
  KEY `idx_compra_estado` (`estado`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
/*!40000 ALTER TABLE `compra` DISABLE KEYS */;
INSERT INTO `compra` VALUES (1,1,'2026-06-08','Factura',NULL,10.00,0.00,10.00,'',NULL,1,'2026-06-08 16:39:54','2026-06-08 16:56:26'),(2,1,'2026-06-09','Factura',NULL,200.00,0.00,200.00,'cancelada',NULL,1,'2026-06-09 00:25:25','2026-06-09 00:29:38'),(3,1,'2026-06-09','Factura',NULL,200.00,0.00,200.00,'pendiente',NULL,0,'2026-06-09 00:30:00','2026-06-09 00:31:13'),(4,1,'2026-06-08','Factura',NULL,50.00,0.00,50.00,'pendiente',NULL,0,'2026-06-09 03:46:05','2026-06-09 03:51:59'),(5,1,'2026-06-08','Factura',NULL,50.00,0.00,50.00,'pendiente',NULL,1,'2026-06-09 03:52:23','2026-06-09 03:52:23'),(6,1,'2026-06-08','Factura',NULL,45.00,0.00,45.00,'recibida',NULL,1,'2026-06-09 03:58:32','2026-06-25 11:57:27'),(7,2,'2026-06-09','Factura',NULL,20.08,0.00,20.08,'pendiente',NULL,0,'2026-06-09 04:05:37','2026-06-25 11:57:24'),(8,1,'2026-06-18','Factura',NULL,5.00,0.00,5.00,'recibida',NULL,1,'2026-06-19 03:13:59','2026-06-25 01:12:37'),(9,1,'2026-06-24','Factura',NULL,60.00,0.00,60.00,'recibida',NULL,1,'2026-06-24 22:11:09','2026-06-24 22:11:13'),(10,3,'2026-06-25','Recibo',NULL,40.00,0.00,40.00,'recibida',NULL,1,'2026-06-25 11:58:01','2026-06-25 11:58:20');
/*!40000 ALTER TABLE `compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra_detalle`
--

DROP TABLE IF EXISTS `compra_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `tipo_item` enum('insumo','herramienta','planta') NOT NULL,
  `id_item` int(11) NOT NULL,
  `categoria_lote` varchar(30) DEFAULT 'germinado',
  `id_ubicacion` int(11) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_detalle_compra` (`id_compra`),
  CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra_detalle`
--

LOCK TABLES `compra_detalle` WRITE;
/*!40000 ALTER TABLE `compra_detalle` DISABLE KEYS */;
INSERT INTO `compra_detalle` VALUES (1,1,'planta',5,'germinado',NULL,1.00,10.00,10.00,1),(2,2,'planta',5,'en_crecimiento',3,20.00,10.00,200.00,1),(3,3,'planta',5,'germinado',3,20.00,10.00,200.00,1),(4,4,'herramienta',2,NULL,NULL,1.00,50.00,50.00,1),(5,5,'herramienta',2,NULL,NULL,1.00,50.00,50.00,1),(6,6,'herramienta',1,NULL,NULL,1.00,45.00,45.00,1),(7,7,'herramienta',2,NULL,NULL,2.00,10.00,20.00,0),(8,8,'herramienta',2,NULL,NULL,1.00,5.00,5.00,1),(9,9,'insumo',6,NULL,NULL,1.00,60.00,60.00,1),(10,7,'herramienta',3,NULL,NULL,2.00,10.04,20.08,1),(11,10,'insumo',6,NULL,NULL,1.00,40.00,40.00,1);
/*!40000 ALTER TABLE `compra_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumo_insumos`
--

DROP TABLE IF EXISTS `consumo_insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consumo_insumos` (
  `id_consumo` int(11) NOT NULL AUTO_INCREMENT,
  `id_asignacion` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad_usada` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `stock_actual` decimal(10,2) DEFAULT NULL,
  `fecha_consumo` date NOT NULL,
  PRIMARY KEY (`id_consumo`),
  KEY `id_asignacion` (`id_asignacion`),
  KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `consumo_insumos_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`),
  CONSTRAINT `consumo_insumos_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Todo consumo debe estar justificado por una tarea.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumo_insumos`
--

LOCK TABLES `consumo_insumos` WRITE;
/*!40000 ALTER TABLE `consumo_insumos` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumo_insumos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_pagar`
--

DROP TABLE IF EXISTS `cuentas_pagar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_pagar`
--

LOCK TABLES `cuentas_pagar` WRITE;
/*!40000 ALTER TABLE `cuentas_pagar` DISABLE KEYS */;
INSERT INTO `cuentas_pagar` VALUES (1,5,50.00,50.00,NULL,'pendiente',NULL,1,'2026-06-09 03:52:23','2026-06-09 03:52:23'),(2,6,45.00,45.00,NULL,'pendiente',NULL,1,'2026-06-09 03:58:32','2026-06-09 03:58:32'),(3,7,20.08,20.08,NULL,'pendiente',NULL,0,'2026-06-09 04:05:37','2026-06-25 11:57:24'),(4,8,5.00,5.00,NULL,'pendiente',NULL,1,'2026-06-19 03:14:00','2026-06-19 03:14:00'),(5,9,60.00,60.00,NULL,'pendiente',NULL,1,'2026-06-24 22:11:09','2026-06-24 22:11:09'),(6,10,40.00,40.00,NULL,'pendiente',NULL,1,'2026-06-25 11:58:01','2026-06-25 11:58:01');
/*!40000 ALTER TABLE `cuentas_pagar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_ornatos`
--

DROP TABLE IF EXISTS `detalle_ornatos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_ornatos` (
  `id_detalle_ornato` int(11) NOT NULL AUTO_INCREMENT,
  `id_ornato` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_ornato`),
  KEY `id_ornato` (`id_ornato`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `detalle_ornatos_ibfk_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `detalle_ornatos_ibfk_ornato` FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_ornatos`
--

LOCK TABLES `detalle_ornatos` WRITE;
/*!40000 ALTER TABLE `detalle_ornatos` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_ornatos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `tipo_item` enum('planta','insumo') NOT NULL DEFAULT 'planta',
  `id_lote` int(11) DEFAULT NULL,
  `id_insumo` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `id_venta` (`id_venta`),
  KEY `id_lote` (`id_lote`),
  KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`),
  CONSTRAINT `fk_detventa_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especie`
--

DROP TABLE IF EXISTS `especie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `especie` (
  `id_especie` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_especie` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_especie`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo bot├ínico.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especie`
--

LOCK TABLES `especie` WRITE;
/*!40000 ALTER TABLE `especie` DISABLE KEYS */;
INSERT INTO `especie` VALUES (1,'medicinal',NULL,1),(2,'monte',NULL,1),(3,'reforestacion',NULL,1),(5,'Miercoles',NULL,0),(6,'sorry','m',0),(7,'jueves','jueves',0),(8,'ornamental',NULL,1);
/*!40000 ALTER TABLE `especie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado`
--

DROP TABLE IF EXISTS `estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de estados (reutilizable)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado`
--

LOCK TABLES `estado` WRITE;
/*!40000 ALTER TABLE `estado` DISABLE KEYS */;
INSERT INTO `estado` VALUES (5,'vivo',1),(6,'cuarentena',1),(7,'muerto',1);
/*!40000 ALTER TABLE `estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `herramienta`
--

DROP TABLE IF EXISTS `herramienta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramientas con ciclo de vida propio.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `herramienta`
--

LOCK TABLES `herramienta` WRITE;
/*!40000 ALTER TABLE `herramienta` DISABLE KEYS */;
INSERT INTO `herramienta` VALUES (1,'Pala',1,NULL,'requiere_mantenimiento','2026-06-02','2026-06-02',NULL,1),(2,'Martillo',1,'nose','danada','2026-06-02','2026-06-02','dsadasdada',0),(3,'Pico',1,'herramienta','en_uso','2026-06-02','2026-06-02',NULL,1),(5,'Pico',1,'nose','ok',NULL,NULL,NULL,1),(6,'mmm',3,'nose nada','baja','2026-06-24','2026-06-24',NULL,1),(7,'mmm',2,'nose nada','baja','2026-06-24','2026-06-24',NULL,1),(8,'mmm',2,'nose nada','baja','2026-06-24','2026-06-24',NULL,1),(9,'mmm',2,'nose nada','baja','2026-06-24','2026-06-24',NULL,1),(10,'mmm',2,'nose nada','baja','2026-06-24','2026-06-24',NULL,1),(11,'mmmmm',4,'mmmmm','disponible','2026-06-23','2026-06-24',NULL,1),(12,'mmmmm',4,'mmmmm','disponible','2026-06-23','2026-06-24',NULL,1),(13,'mmmmm',4,'mmmmm','disponible','2026-06-23','2026-06-24',NULL,1),(14,'mmmmm',4,'mmmmm','disponible','2026-06-23','2026-06-24',NULL,1),(15,'mmmmm',4,'mmmmm','disponible','2026-06-23','2026-06-24',NULL,1),(16,'rrrr',1,'nn','disponible','2026-06-23','2026-06-24',NULL,1),(17,'jueves',4,'jueves','disponible','2026-06-24','2026-06-24','jueves',0);
/*!40000 ALTER TABLE `herramienta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insumo`
--

DROP TABLE IF EXISTS `insumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `insumo_ibfk_1` FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inventario de insumos.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumo`
--

LOCK TABLES `insumo` WRITE;
/*!40000 ALTER TABLE `insumo` DISABLE KEYS */;
INSERT INTO `insumo` VALUES (2,14,'Fertilizante','dasd',0.01,0.01,1),(3,5,'Semillas de Simon','Semillas',0.00,10.00,0),(4,5,'Semillas de Simon','Semillas',20.00,0.00,1),(5,5,'Semillas de Simon','Semillas',20.00,0.00,1),(6,5,'Semillas de Simon','Semillas',0.00,40.00,1),(7,6,'compra','carpinteria',5.00,5.00,0),(8,10,'sorry','sorry',1.00,1.00,0),(9,10,'jueves','jueves',0.05,0.04,0);
/*!40000 ALTER TABLE `insumo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lote`
--

DROP TABLE IF EXISTS `lote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lote` (
  `id_lote` int(11) NOT NULL AUTO_INCREMENT,
  `id_planta` int(11) NOT NULL,
  `id_ubicacion` int(11) DEFAULT NULL,
  `fecha_siembra` date NOT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00,
  `id_estado` int(11) NOT NULL DEFAULT @`default_estado_id`,
  `id_categoria` int(11) DEFAULT NULL,
  `id_origen` int(11) NOT NULL DEFAULT @`default_origen_id`,
  `observacion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_lote`),
  KEY `id_planta` (`id_planta`),
  KEY `id_ubicacion` (`id_ubicacion`),
  KEY `idx_lote_id_estado` (`id_estado`),
  KEY `idx_lote_id_categoria` (`id_categoria`),
  KEY `idx_lote_id_origen` (`id_origen`),
  CONSTRAINT `fk_lote_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  CONSTRAINT `fk_lote_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `fk_lote_origen` FOREIGN KEY (`id_origen`) REFERENCES `origen` (`id_origen`),
  CONSTRAINT `lote_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `lote_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidad de producci├│n.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lote`
--

LOCK TABLES `lote` WRITE;
/*!40000 ALTER TABLE `lote` DISABLE KEYS */;
INSERT INTO `lote` VALUES (22,10,3,'2026-06-28',1,15,0.00,5,6,4,NULL,'public/assets/uploads/lotes/lote_1782666864_aae82d50.jpg',1),(23,10,2,'2026-06-28',3,77,0.00,5,7,1,NULL,'public/assets/uploads/lotes/lote_1782668012_4fbb9775.png',1),(24,12,2,'2026-06-28',2,2,0.00,5,7,3,NULL,NULL,1);
/*!40000 ALTER TABLE `lote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mermas_historico`
--

DROP TABLE IF EXISTS `mermas_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mermas_historico` (
  `id_merma` int(11) NOT NULL AUTO_INCREMENT,
  `id_trazabilidad` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` enum('plaga','da±o_mecanico','factor_climatico','enfermedad','otro') NOT NULL,
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
  CONSTRAINT `fk_merma_usuario` FOREIGN KEY (`id_usuario_registra`) REFERENCES `sysinescolara-seguridad`.`usuarios` (`id_usuario`),
  CONSTRAINT `mermas_historico_ibfk_1` FOREIGN KEY (`id_trazabilidad`) REFERENCES `trazabilidad` (`id_trazabilidad`),
  CONSTRAINT `mermas_historico_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mermas_historico`
--

LOCK TABLES `mermas_historico` WRITE;
/*!40000 ALTER TABLE `mermas_historico` DISABLE KEYS */;
/*!40000 ALTER TABLE `mermas_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimiento_insumo`
--

DROP TABLE IF EXISTS `movimiento_insumo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimiento_planta` (
  `id_movimiento_planta` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` varchar(30) NOT NULL COMMENT 'venta, ornato, donacion, intercambio',
  `id_cliente` int(11) DEFAULT NULL,
  `id_trabajador_gestor` int(11) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_movimiento_planta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_trabajador_gestor` (`id_trabajador_gestor`),
  KEY `idx_mp_activo` (`activo`),
  CONSTRAINT `movimiento_planta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `movimiento_planta_ibfk_2` FOREIGN KEY (`id_trabajador_gestor`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unifica venta, ornato, donaci├│n e intercambio de plantas.';
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `movimiento_planta_detalle_ibfk_1` FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`),
  CONSTRAINT `movimiento_planta_detalle_ibfk_2` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por lote del movimiento de plantas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimiento_planta_detalle`
--

LOCK TABLES `movimiento_planta_detalle` WRITE;
/*!40000 ALTER TABLE `movimiento_planta_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimiento_planta_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `origen`
--

DROP TABLE IF EXISTS `origen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `origen` (
  `id_origen` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_origen`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de orígenes (reutilizable)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `origen`
--

LOCK TABLES `origen` WRITE;
/*!40000 ALTER TABLE `origen` DISABLE KEYS */;
INSERT INTO `origen` VALUES (1,'Siembra',1),(2,'Ampliación',1),(3,'Donación',1),(4,'Compra',1);
/*!40000 ALTER TABLE `origen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ornatos`
--

DROP TABLE IF EXISTS `ornatos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `ornatos_ibfk_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ornatos`
--

LOCK TABLES `ornatos` WRITE;
/*!40000 ALTER TABLE `ornatos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ornatos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_compra`
--

DROP TABLE IF EXISTS `pago_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `pago_compra_ibfk_1` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_compra`
--

LOCK TABLES `pago_compra` WRITE;
/*!40000 ALTER TABLE `pago_compra` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_venta`
--

DROP TABLE IF EXISTS `pago_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago_venta` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `metodo` enum('efectivo','transferencia','punto','pago_movil','otro') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `estado_pago` enum('registrado','confirmado','rechazado') NOT NULL DEFAULT 'registrado',
  `banco` varchar(100) DEFAULT NULL,
  `id_trabajador` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `id_venta` (`id_venta`),
  CONSTRAINT `pago_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_venta`
--

LOCK TABLES `pago_venta` WRITE;
/*!40000 ALTER TABLE `pago_venta` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planta_precio_vigente`
--

DROP TABLE IF EXISTS `planta_precio_vigente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planta_precio_vigente` (
  `id_planta` int(11) NOT NULL,
  `id_calculo` int(11) NOT NULL,
  PRIMARY KEY (`id_planta`),
  UNIQUE KEY `id_calculo` (`id_calculo`),
  CONSTRAINT `planta_precio_vigente_ibfk_1` FOREIGN KEY (`id_planta`) REFERENCES `plantas` (`id_planta`),
  CONSTRAINT `planta_precio_vigente_ibfk_2` FOREIGN KEY (`id_calculo`) REFERENCES `calculo_precio` (`id_calculo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='1 planta -> 1 c├ílculo vigente.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planta_precio_vigente`
--

LOCK TABLES `planta_precio_vigente` WRITE;
/*!40000 ALTER TABLE `planta_precio_vigente` DISABLE KEYS */;
/*!40000 ALTER TABLE `planta_precio_vigente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plantas`
--

DROP TABLE IF EXISTS `plantas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `plantas_ibfk_1` FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de plantas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plantas`
--

LOCK TABLES `plantas` WRITE;
/*!40000 ALTER TABLE `plantas` DISABLE KEYS */;
INSERT INTO `plantas` VALUES (10,2,'Girasol','Girasol',160,'public/assets/uploads/plants/plant_1782667229_d99b96b1.jpg',1),(12,8,'rosas','Rosas',0,'public/assets/uploads/plants/plant_1782669961_feed5aef.png',1);
/*!40000 ALTER TABLE `plantas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(100) NOT NULL,
  `rif_proveedor` varchar(20) NOT NULL,
  `contacto_vendedor` varchar(100) DEFAULT NULL,
  `telefono_proveedor` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `rif_proveedor` (`rif_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de proveedores.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Plantas nuevas','J-123456789','Prueba','04123232323',0),(2,'Plantas nuevas','J-123456788','Maria Dantes','04144237719',0),(3,'Plantas nuevas','V-312057688','jueves','04144237719',1);
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recoleccion_semillas`
--

DROP TABLE IF EXISTS `recoleccion_semillas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recoleccion_semillas` (
  `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_trabajador` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_recoleccion` date DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_recoleccion`),
  KEY `idx_recoleccion_trabajador` (`id_trabajador`),
  KEY `idx_recoleccion_ubicacion` (`id_ubicacion`),
  KEY `idx_recoleccion_estatus` (`estatus`),
  CONSTRAINT `fk_recoleccion_trabajador` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`),
  CONSTRAINT `fk_recoleccion_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recoleccion_semillas`
--

LOCK TABLES `recoleccion_semillas` WRITE;
/*!40000 ALTER TABLE `recoleccion_semillas` DISABLE KEYS */;
INSERT INTO `recoleccion_semillas` VALUES (8,1,6,'2026-06-18',NULL,'Pendiente','Adiós',0),(9,1,5,'2026-06-18','2026-06-24','Realizada',NULL,1),(10,1,5,'2026-06-24','2026-06-25','Realizada',NULL,1);
/*!40000 ALTER TABLE `recoleccion_semillas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recoleccion_semillas_detalle`
--

DROP TABLE IF EXISTS `recoleccion_semillas_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recoleccion_semillas_detalle` (
  `id_recoleccion_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_recoleccion` int(11) NOT NULL,
  `planta_origen` varchar(150) DEFAULT NULL,
  `nombre_semilla` varchar(100) NOT NULL,
  `id_unidad_medida` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_recoleccion_detalle`),
  KEY `idx_detalle_recoleccion` (`id_recoleccion`),
  CONSTRAINT `fk_detalle_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion_semillas` (`id_recoleccion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recoleccion_semillas_detalle`
--

LOCK TABLES `recoleccion_semillas_detalle` WRITE;
/*!40000 ALTER TABLE `recoleccion_semillas_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `recoleccion_semillas_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tarea` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `categoria` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_tarea`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat├ílogo de tareas.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
INSERT INTO `tareas` VALUES (1,'FJadfhjdkf','No hay detalles',1,NULL),(2,'FJadfhjdkf',NULL,1,NULL),(3,'FJadfhjdkf','jhjhj',1,NULL),(4,'JDiksadjasldas','dasdasdadasdas',1,NULL),(5,'JDiksadjasldas',NULL,1,NULL),(6,'Moler el ques','adasd',1,NULL),(7,'Regar','nn',1,NULL),(8,'jueves','vvv',1,NULL);
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trabajadores`
--

DROP TABLE IF EXISTS `trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trazabilidad` (
  `id_trazabilidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `id_estado` int(11) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` date NOT NULL,
  PRIMARY KEY (`id_trazabilidad`),
  KEY `id_lote` (`id_lote`),
  KEY `idx_trazabilidad_id_estado` (`id_estado`),
  CONSTRAINT `fk_trazabilidad_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `trazabilidad_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial fitosanitario por lote.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trazabilidad`
--

LOCK TABLES `trazabilidad` WRITE;
/*!40000 ALTER TABLE `trazabilidad` DISABLE KEYS */;
INSERT INTO `trazabilidad` VALUES (4,23,17,5,NULL,0,'2026-06-28'),(5,23,1,5,'ss',0,'2026-06-28'),(6,23,2,5,NULL,0,'2026-06-28'),(7,23,2,6,'f',0,'2026-06-28'),(8,22,5,6,NULL,1,'2026-06-28');
/*!40000 ALTER TABLE `trazabilidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicacion`
--

DROP TABLE IF EXISTS `ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_ubicacion` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `Tipo` enum('interno','externo') NOT NULL DEFAULT 'interno',
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espacios f├¡sicos del vivero.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicacion`
--

LOCK TABLES `ubicacion` WRITE;
/*!40000 ALTER TABLE `ubicacion` DISABLE KEYS */;
INSERT INTO `ubicacion` VALUES (2,'Invernadero Central','Invernadero principal',1,'interno'),(3,'Almacén Norte','Almacén de insumos',1,'interno'),(5,'parque baradida','av los abogados',1,'externo'),(6,'cuatena','pruebasss',1,'interno');
/*!40000 ALTER TABLE `ubicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidad_medida`
--

DROP TABLE IF EXISTS `unidad_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidad_medida` (
  `id_unidad_medida` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_unidad_medida` varchar(50) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad_medida`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades de medida para insumos.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_medida`
--

LOCK TABLES `unidad_medida` WRITE;
/*!40000 ALTER TABLE `unidad_medida` DISABLE KEYS */;
INSERT INTO `unidad_medida` VALUES (1,'Kilogramo','kg',1),(2,'Gramo','g',1),(3,'Litro','L',1),(4,'Mililitre','mL',0),(5,'Unidad','und',1),(6,'Metro','m',1),(7,'CentÝmetro','cm',1),(8,'Saco','saco',1),(9,'Bolsa','bolsa',1),(10,'Paquete','pqte',1),(11,'Gal¾n','gal',1),(12,'Caja','caja',1),(13,'Rollos','rllo',1),(14,'Bidon z','bidon',0),(15,'Centigrados',NULL,1),(16,'Centigrados',NULL,1),(17,'KiIKI',NULL,0),(18,'jueves',NULL,0);
/*!40000 ALTER TABLE `unidad_medida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uso_herramienta`
--

DROP TABLE IF EXISTS `uso_herramienta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Herramienta ligada a una tarea.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uso_herramienta`
--

LOCK TABLES `uso_herramienta` WRITE;
/*!40000 ALTER TABLE `uso_herramienta` DISABLE KEYS */;
/*!40000 ALTER TABLE `uso_herramienta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `referencia` (`referencia`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_trabajador` (`id_trabajador`),
  CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores` (`id_trabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
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

-- Dump completed on 2026-06-28 15:09:29
