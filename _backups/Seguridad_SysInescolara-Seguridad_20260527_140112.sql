-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: SysInescolara-Seguridad
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
-- Current Database: `SysInescolara-Seguridad`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sysinescolara-seguridad` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `SysInescolara-Seguridad`;

--
-- Table structure for table `auditoria_logs`
--

DROP TABLE IF EXISTS `auditoria_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_logs` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro_afectado` int(11) DEFAULT NULL,
  `valor_anterior` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'MySQL soporta tipo JSON para datos estructurados' CHECK (json_valid(`valor_anterior`)),
  `valor_nuevo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_nuevo`)),
  `endpoint_solicitado` varchar(255) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_logs_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_logs`
--

LOCK TABLES `auditoria_logs` WRITE;
/*!40000 ALTER TABLE `auditoria_logs` DISABLE KEYS */;
INSERT INTO `auditoria_logs` VALUES (1,1,'CREATE','plantas',4,NULL,'{\"nombre_comun\":\"Rosa\",\"nombre_tecnico\":\"Flor\",\"especie_id\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779317884_98bf0e60.jpg\"}','/sys-inescolara/plants?action=add_ajax','2026-05-20 22:58:04'),(2,1,'LOGOUT','usuarios',1,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 22:59:13'),(3,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-20 22:59:34'),(4,1,'LOGOUT','usuarios',1,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 23:01:18'),(5,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-20 23:02:15'),(6,3,'DELETE','usuarios',7,'{\"id\":7,\"nombre_usuario\":\"Pedro Sanchez\",\"correo_electronico\":\"pedro@gmail.com\",\"avatar\":null,\"rol_id\":2,\"nombre_rol\":null,\"estatus\":\"Activo\"}',NULL,'/sys-inescolara/user?action=delete_ajax','2026-05-20 23:03:37'),(7,3,'CREATE','usuarios',8,NULL,'{\"nombre_usuario\":\"Prueba\",\"correo_electronico\":\"prueba1@gmail.com\",\"rol_id\":1}','/sys-inescolara/user?action=add_ajax','2026-05-20 23:04:38'),(8,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 23:04:51'),(9,8,'LOGIN','usuarios',8,NULL,'{\"nombre_usuario\":\"Prueba\"}','/sys-inescolara/login','2026-05-20 23:05:06'),(10,8,'LOGOUT','usuarios',8,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 23:05:13'),(11,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-20 23:05:27'),(12,3,'UPDATE','usuarios',8,'{\"id\":8,\"nombre_usuario\":\"Prueba\",\"correo_electronico\":\"prueba1@gmail.com\",\"avatar\":\"public\\/assets\\/uploads\\/avatars\\/avatar_1779318278_eabb6afd.jpg\",\"rol_id\":1,\"nombre_rol\":null,\"estatus\":\"Activo\"}','{\"nombre_usuario\":\"Prueba\",\"correo_electronico\":\"prueba1@gmail.com\",\"rol_id\":2}','/sys-inescolara/user?action=edit_ajax','2026-05-20 23:05:33'),(13,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 23:05:38'),(14,8,'LOGIN','usuarios',8,NULL,'{\"nombre_usuario\":\"Prueba\"}','/sys-inescolara/login','2026-05-20 23:06:51'),(15,8,'LOGOUT','usuarios',8,NULL,NULL,'/sys-inescolara/login/logout','2026-05-20 23:07:14'),(16,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-20 23:08:51'),(17,3,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260520_192837.sql\",\"Seguridad_SysInescolara-Seguridad_20260520_192838.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-05-20 23:28:38'),(18,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Datos_sysinescolara_20260520_192837.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:29:03'),(19,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Seguridad_SysInescolara-Seguridad_20260520_192838.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:29:05'),(20,3,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260520_192908.sql\",\"Seguridad_SysInescolara-Seguridad_20260520_192908.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-05-20 23:29:09'),(21,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Datos_sysinescolara_20260520_192908.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:29:15'),(22,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Seguridad_SysInescolara-Seguridad_20260520_192908.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:29:16'),(23,3,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260520_193009.sql\",\"Seguridad_SysInescolara-Seguridad_20260520_193009.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-05-20 23:30:10'),(24,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Datos_sysinescolara_20260520_193009.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:41:39'),(25,3,'DELETE','backups',NULL,NULL,'{\"file\":\"Seguridad_SysInescolara-Seguridad_20260520_193009.sql\"}','/sys-inescolara/backups?action=delete_backup','2026-05-20 23:41:41'),(26,3,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Seguridad_SysInescolara-Seguridad_20260520_194143.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-05-20 23:44:36'),(27,3,'CREATE','especies',4,NULL,'{\"nombre_comun\":\"Margarita\",\"nombre_tecnico\":\"Cactuspro\"}','/sys-inescolara/species?action=add_ajax','2026-05-20 23:44:56'),(28,3,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260520_194142.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-05-20 23:45:04'),(29,3,'CREATE','especies',4,NULL,'{\"nombre_comun\":\"sasasas\",\"nombre_tecnico\":\"sasasasasa\"}','/sys-inescolara/species?action=add_ajax','2026-05-20 23:45:28'),(30,3,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260520_194533.sql\",\"Seguridad_SysInescolara-Seguridad_20260520_194533.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-05-20 23:45:33'),(31,3,'DELETE','especies',4,'{\"id\":4,\"nombre_comun\":\"sasasas\",\"nombre_tecnico\":\"sasasasasa\"}',NULL,'/sys-inescolara/species?action=delete_ajax','2026-05-20 23:45:38'),(32,3,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260520_194533.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-05-20 23:45:45'),(33,3,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260520_194142.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-05-20 23:45:52'),(34,3,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260520_194533.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-05-20 23:46:00'),(35,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-21 12:27:02'),(36,1,'CREATE','cliente',2,NULL,'{\"nombre_cliente\":\"Mayra Perez\",\"contacto_cliente\":\"04123005644aaa\"}','/sys-inescolara/clients?action=add_ajax','2026-05-21 12:27:21'),(37,1,'DELETE','cliente',2,'{\"id\":2,\"nombre_cliente\":\"Mayra Perez\",\"contacto_cliente\":\"04123005644aaa\"}',NULL,'/sys-inescolara/clients?action=delete_ajax','2026-05-21 12:27:24'),(38,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-21 13:58:23'),(39,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-21 18:13:42'),(40,3,'CREATE','trabajadores',3,NULL,'{\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"cedula_trabajador\":\"3123131333\",\"telefono_trabajador\":\"04120000000\"}','/sys-inescolara/employees?action=add_ajax','2026-05-21 18:16:39'),(41,3,'DELETE','trabajadores',3,'{\"id_trabajadores\":3,\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"cedula_trabajador\":\"3123131333\",\"telefono_trabajador\":\"04120000000\"}',NULL,'/sys-inescolara/employees?action=delete_ajax','2026-05-21 18:21:30'),(42,3,'CREATE','trabajadores',4,NULL,'{\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":null,\"cedula_trabajador\":\"300000032\",\"telefono_trabajador\":\"04120000000\"}','/sys-inescolara/employees?action=add_ajax','2026-05-21 18:21:39'),(43,3,'UPDATE','trabajadores',4,'{\"id_trabajadores\":4,\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":null,\"cedula_trabajador\":\"300000032\",\"telefono_trabajador\":\"04120000000\"}','{\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":null,\"cedula_trabajador\":\"30000003\",\"telefono_trabajador\":\"04120000000\"}','/sys-inescolara/employees?action=edit_ajax','2026-05-21 18:21:42'),(44,3,'DELETE','trabajadores',4,'{\"id_trabajadores\":4,\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":null,\"cedula_trabajador\":\"30000003\",\"telefono_trabajador\":\"04120000000\"}',NULL,'/sys-inescolara/employees?action=delete_ajax','2026-05-21 18:21:46'),(45,3,'CREATE','proveedores',3,NULL,'{\"nombre_proveedor\":\"Casita Verde\",\"rif_proveedor\":\"J-3231331232131\",\"contacto_vendedor\":\"Perez Perez\",\"telefono_proveedor\":\"04120000000\"}','/sys-inescolara/suppliers?action=add_ajax','2026-05-21 18:21:56'),(46,3,'UPDATE','proveedores',3,'{\"id_proveedor\":3,\"nombre_proveedor\":\"Casita Verde\",\"rif_proveedor\":\"J-3231331232131\",\"contacto_vendedor\":\"Perez Perez\",\"telefono_proveedor\":\"04120000000\"}','{\"nombre_proveedor\":\"Casita Verde\",\"rif_proveedor\":\"J-3231331232131\",\"contacto_vendedor\":\"Perez Pere2\",\"telefono_proveedor\":\"04120000000\"}','/sys-inescolara/suppliers?action=edit_ajax','2026-05-21 18:22:04'),(47,3,'DELETE','proveedores',3,'{\"id_proveedor\":3,\"nombre_proveedor\":\"Casita Verde\",\"rif_proveedor\":\"J-3231331232131\",\"contacto_vendedor\":\"Perez Pere2\",\"telefono_proveedor\":\"04120000000\"}',NULL,'/sys-inescolara/suppliers?action=delete_ajax','2026-05-21 18:22:08'),(48,3,'CREATE','especies',5,NULL,'{\"nombre_comun\":\"Simon\",\"nombre_tecnico\":\"Cactuspro we\"}','/sys-inescolara/species?action=add_ajax','2026-05-21 18:22:19'),(49,3,'DELETE','especies',5,'{\"id_especie\":5,\"nombre\":\"\",\"nombre_comun\":\"Simon\",\"nombre_tecnico\":\"Cactuspro we\"}',NULL,'/sys-inescolara/species?action=delete_ajax','2026-05-21 18:22:39'),(50,3,'CREATE','especies',6,NULL,'{\"nombre_comun\":\"Simon\",\"nombre_tecnico\":\"Cactuspro we\"}','/sys-inescolara/species?action=add_ajax','2026-05-21 18:22:44'),(51,3,'DELETE','especies',4,'{\"id_especie\":4,\"nombre\":\"\",\"nombre_comun\":\"sasasas\",\"nombre_tecnico\":\"sasasasasa\"}',NULL,'/sys-inescolara/species?action=delete_ajax','2026-05-21 18:22:52'),(52,3,'CREATE','plantas',5,NULL,'{\"nombre_comun\":\"Cactus\",\"nombre_tecnico\":\"Cactuspro\",\"especie_id\":6,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779388139_f0700717.jpg\"}','/sys-inescolara/plants?action=add_ajax','2026-05-21 18:28:59'),(53,3,'DELETE','plantas',5,'{\"id_planta\":5,\"nombre_tecnico\":\"Cactuspro\",\"nombre_comun\":\"Cactus\",\"id_categoria\":6,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779388139_f0700717.jpg\",\"especie_nombre\":\"Simon\"}',NULL,'/sys-inescolara/plants?action=delete_ajax','2026-05-21 18:29:02'),(54,3,'CREATE','plantas',6,NULL,'{\"nombre_comun\":\"Simon\",\"nombre_tecnico\":\"Cactuspro we\",\"especie_id\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779388152_8b04678f.jpg\"}','/sys-inescolara/plants?action=add_ajax','2026-05-21 18:29:12'),(55,3,'UPDATE','plantas',6,'{\"id_planta\":6,\"nombre_tecnico\":\"Cactuspro we\",\"nombre_comun\":\"Simon\",\"id_categoria\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779388152_8b04678f.jpg\",\"especie_nombre\":\"Holi\"}','{\"nombre_comun\":\"Simon\",\"nombre_tecnico\":\"Cactuspro we\",\"especie_id\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1779388158_bc4946a2.jpg\"}','/sys-inescolara/plants?action=edit_ajax','2026-05-21 18:29:18'),(56,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-21 20:40:13'),(57,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-21 20:40:31'),(58,3,'UPDATE','proveedores',2,'{\"id_proveedor\":2,\"nombre_proveedor\":\"Casita Roja\",\"rif_proveedor\":\"G-123456789\",\"contacto_vendedor\":\"Perez Perez\",\"telefono_proveedor\":\"04120000000\"}','{\"nombre_proveedor\":\"Casita Roja\",\"rif_proveedor\":\"G-123456789\",\"contacto_vendedor\":\"Perez Peree\",\"telefono_proveedor\":\"04120000000\"}','/sys-inescolara/suppliers?action=edit_ajax','2026-05-21 21:07:12'),(59,3,'CREATE','lote',4,NULL,'{\"id_planta\":4,\"fecha_siembra\":\"2026-05-25\",\"cantidad_inicial\":20,\"cantidad_actual\":20,\"estado\":\"Vivo\",\"ubicacion\":\"Nada\",\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1779397811_c26c0317.jpg\"}','/sys-inescolara/batches?action=add_ajax','2026-05-21 21:10:11'),(60,3,'CREATE','cliente',3,NULL,'{\"nombre_cliente\":\"Mayra Perez\",\"contacto_cliente\":\"04123005644\"}','/sys-inescolara/clients?action=add_ajax','2026-05-21 21:12:01'),(61,3,'CREATE','trabajadores',5,NULL,'{\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"cedula_trabajador\":\"300000032\",\"telefono_trabajador\":\"04120000000\"}','/sys-inescolara/employees?action=add_ajax','2026-05-22 01:05:33'),(62,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-22 01:34:09'),(63,3,'CREATE','proveedores',4,NULL,'{\"nombre_proveedor\":\"Casita Verde\",\"rif_proveedor\":\"J-323133123\",\"contacto_vendedor\":\"Maria Rojas\",\"telefono_proveedor\":\"04120000000\"}','/sys-inescolara/suppliers?action=add_ajax','2026-05-22 02:14:48'),(64,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-22 20:33:26'),(65,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-22 20:55:38'),(66,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 21:44:12'),(67,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-22 21:44:31'),(68,3,'CREATE','usuarios',9,NULL,'{\"nombre_usuario\":\"Pedro Sanchez\",\"correo_electronico\":\"prueba@correo.com\",\"rol_id\":2}','/sys-inescolara/user?action=add_ajax','2026-05-22 21:54:54'),(69,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 21:54:57'),(70,9,'LOGIN','usuarios',9,NULL,'{\"nombre_usuario\":\"Pedro Sanchez\"}','/sys-inescolara/login','2026-05-22 21:55:21'),(71,9,'LOGOUT','usuarios',9,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 22:48:49'),(72,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-22 22:49:16'),(73,3,'CREATE','usuarios',10,NULL,'{\"nombre_usuario\":\"Prueba nueva\",\"correo_electronico\":\"prueba3@correo.com\",\"rol_id\":2}','/sys-inescolara/user?action=add_ajax','2026-05-22 22:53:28'),(74,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 22:53:34'),(75,10,'LOGIN','usuarios',10,NULL,'{\"nombre_usuario\":\"Prueba nueva\"}','/sys-inescolara/login','2026-05-22 22:54:32'),(76,10,'LOGOUT','usuarios',10,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 22:55:11'),(77,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-22 22:56:07'),(78,3,'UPDATE','usuarios',10,'{\"id\":10,\"nombre_usuario\":\"Prueba nueva\",\"correo_electronico\":\"prueba3@correo.com\",\"avatar\":\"public\\/assets\\/uploads\\/avatars\\/avatar_1779490408_11ed9d13.jpg\",\"rol_id\":2,\"nombre_rol\":null,\"estatus\":\"Activo\"}','{\"nombre_usuario\":\"Prueba nueva\",\"correo_electronico\":\"prueba3@correo.com\",\"rol_id\":2}','/sys-inescolara/user?action=edit_ajax','2026-05-22 22:56:39'),(79,3,'LOGOUT','usuarios',3,NULL,NULL,'/sys-inescolara/login/logout','2026-05-22 22:56:44'),(80,10,'LOGIN','usuarios',10,NULL,'{\"nombre_usuario\":\"Prueba nueva\"}','/sys-inescolara/login','2026-05-22 22:57:06'),(81,10,'LOGOUT','usuarios',10,NULL,NULL,'/sys-inescolara/login/logout','2026-05-23 00:12:01'),(82,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-23 00:12:57'),(83,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"Super Usuario\"}','/sys-inescolara/login','2026-05-26 02:51:18'),(84,1,'LOGOUT','usuarios',1,NULL,NULL,'/sys-inescolara/login/logout','2026-05-26 02:51:40'),(85,3,'LOGIN','usuarios',3,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-27 17:36:56'),(86,3,'CREATE','plantas',7,NULL,'{\"nombreComun\":\"Cactus\",\"nombreTecnico\":\"Cactuspro\",\"especieId\":6,\"imagen\":null}','/sys-inescolara/plants?action=add_ajax','2026-05-27 17:59:50'),(87,3,'DELETE','plantas',7,'{\"id_planta\":7,\"nombre_tecnico\":\"Cactuspro\",\"nombre_comun\":\"Cactus\",\"id_categoria\":6,\"imagen\":null,\"especie_nombre\":\"Simon\"}',NULL,'/sys-inescolara/plants?action=delete_ajax','2026-05-27 17:59:52'),(88,3,'CREATE','lote',5,NULL,'{\"id_planta\":4,\"fecha_siembra\":\"2026-05-28\",\"cantidad_inicial\":2,\"cantidad_actual\":2,\"estado\":\"Vivo\",\"ubicacion\":\"Nada\",\"imagen\":null}','/sys-inescolara/batches?action=add_ajax','2026-05-27 18:00:20'),(89,3,'UPDATE','lote',5,'{\"id_lote\":5,\"id_planta\":4,\"id_ubicacion\":null,\"fecha_siembra\":\"2026-05-28\",\"cantidad_inicial\":2,\"cantidad_actual\":2,\"estado\":\"Vivo\",\"ubicacion\":\"Nada\",\"imagen\":null,\"creado_at\":\"2026-05-27 14:00:20\",\"planta_nombre\":\"Rosa\",\"especie_nombre\":\"Holi\"}','{\"id_planta\":4,\"fecha_siembra\":\"2026-05-28\",\"cantidad_inicial\":2,\"cantidad_actual\":2,\"estado\":\"Vivo\",\"ubicacion\":\"Nadw\",\"imagen\":null}','/sys-inescolara/batches?action=edit_ajax','2026-05-27 18:00:26'),(90,3,'DELETE','lote',5,'{\"id_lote\":5,\"id_planta\":4,\"id_ubicacion\":null,\"fecha_siembra\":\"2026-05-28\",\"cantidad_inicial\":2,\"cantidad_actual\":2,\"estado\":\"Vivo\",\"ubicacion\":\"Nadw\",\"imagen\":null,\"creado_at\":\"2026-05-27 14:00:20\",\"planta_nombre\":\"Rosa\",\"especie_nombre\":\"Holi\"}',NULL,'/sys-inescolara/batches?action=delete_ajax','2026-05-27 18:00:33');
/*!40000 ALTER TABLE `auditoria_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'info',
  `leida` tinyint(1) DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,1,'Bienvenido a SYSINECOLARA','El sistema de gesti¾n de vivero estß listo para usar.','success',1,'dashboard','2026-05-21 21:50:55'),(2,1,'Revisa los reportes','Ya estßn disponibles los nuevos reportes de inventario.','info',1,'dashboard/reports','2026-05-21 21:50:55'),(3,1,'Stock bajo detectado','Hay lotes con cantidad crÝtica, revisa las alertas.','warning',1,'dashboard','2026-05-21 21:50:55'),(4,1,'Notif test','Mensaje de prueba','success',1,'dashboard','2026-05-21 21:52:21'),(5,1,'M¾dulo de notificaciones activado','Ya puedes recibir notificaciones en tiempo real. Pr¾ximamente: tareas con WebSocket.','info',1,'notifications','2026-05-21 21:52:56'),(6,1,'Notif test','Mensaje de prueba','success',1,'dashboard','2026-05-22 01:47:11'),(9,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 20:51:33'),(10,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 20:55:01'),(11,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 20:55:49'),(12,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 21:00:23'),(13,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 21:03:36'),(14,3,'Notificación de prueba','Esto es una prueba del sistema','success',1,NULL,'2026-05-22 21:03:45');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_permiso` varchar(50) DEFAULT NULL COMMENT 'Ej: VENTAS_REGISTRAR, INV_ELIMINAR',
  `descripcion_permiso` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `codigo_permiso` (`codigo_permiso`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (1,'DASHBOARD_VIEW','Ver panel principal'),(2,'INVENTARIO_VIEW','Ver inventario'),(3,'VENTAS_ACCESS','Acceder a ventas/POS'),(4,'USUARIOS_MANAGE','Gestionar usuarios'),(5,'PLANTAS_VIEW','Ver plantas'),(6,'PLANTAS_MANAGE','Gestionar plantas'),(7,'PROVEEDORES_VIEW','Ver proveedores'),(8,'PROVEEDORES_MANAGE','Gestionar proveedores'),(9,'INSUMOS_VIEW','Ver insumos'),(10,'INSUMOS_MANAGE','Gestionar insumos'),(11,'TRABAJADORES_VIEW','Ver trabajadores'),(12,'TRABAJADORES_MANAGE','Gestionar trabajadores'),(13,'CLIENTES_VIEW','Ver clientes'),(14,'CLIENTES_MANAGE','Gestionar clientes'),(15,'ASISTENTE_ACCESS','Acceder al asistente IA'),(16,'INSUMO_VIEW','Ver insumo'),(17,'INSUMO_MANAGE','Gestionar insumo'),(18,'PLANTAS_CREATE','Crear plantas'),(19,'PLANTAS_EDIT','Editar plantas'),(20,'PLANTAS_DELETE','Eliminar plantas'),(21,'PROVEEDORES_CREATE','Crear proveedores'),(22,'PROVEEDORES_EDIT','Editar proveedores'),(23,'PROVEEDORES_DELETE','Eliminar proveedores'),(24,'INSUMOS_CREATE','Crear insumos'),(25,'INSUMOS_EDIT','Editar insumos'),(26,'INSUMOS_DELETE','Eliminar insumos'),(27,'TRABAJADORES_CREATE','Crear trabajadores'),(28,'TRABAJADORES_EDIT','Editar trabajadores'),(29,'TRABAJADORES_DELETE','Eliminar trabajadores'),(30,'CLIENTES_CREATE','Crear clientes'),(31,'CLIENTES_EDIT','Editar clientes'),(32,'CLIENTES_DELETE','Eliminar clientes'),(33,'TAREAS_VIEW','Ver tareas');
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_permisos`
--

DROP TABLE IF EXISTS `rol_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `id_permiso` (`id_permiso`),
  CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permisos`
--

LOCK TABLES `rol_permisos` WRITE;
/*!40000 ALTER TABLE `rol_permisos` DISABLE KEYS */;
INSERT INTO `rol_permisos` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(2,1),(2,2),(2,3),(2,5),(2,6),(2,13),(2,14),(2,15),(2,18),(2,19),(2,30),(2,31),(2,33);
/*!40000 ALTER TABLE `rol_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(30) DEFAULT NULL,
  `descripcion_rol` text DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Acceso total al sistema'),(2,'Trabajador','Acceso a inventario, plantas, clientes y ventas');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones_activas`
--

DROP TABLE IF EXISTS `sesiones_activas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesiones_activas` (
  `id_sesion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `token_sesion` varchar(255) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `fecha_expiracion` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `token_sesion` (`token_sesion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `sesiones_activas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones_activas`
--

LOCK TABLES `sesiones_activas` WRITE;
/*!40000 ALTER TABLE `sesiones_activas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sesiones_activas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_permisos`
--

DROP TABLE IF EXISTS `usuario_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_permisos` (
  `id_usuario` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_permiso`),
  KEY `id_permiso` (`id_permiso`),
  CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permisos`
--

LOCK TABLES `usuario_permisos` WRITE;
/*!40000 ALTER TABLE `usuario_permisos` DISABLE KEYS */;
INSERT INTO `usuario_permisos` VALUES (10,1),(10,6),(10,13),(10,16);
/*!40000 ALTER TABLE `usuario_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `id_trabajador_ref` int(11) DEFAULT NULL COMMENT 'Vínculo 1:1 con trabajadores en DB Core',
  `estatus` enum('Activo','Inactivo','Bloqueado') DEFAULT 'Activo',
  `intentos_fallidos` int(11) DEFAULT 0,
  `ultimo_acceso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`),
  UNIQUE KEY `id_trabajador_ref` (`id_trabajador_ref`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Super Usuario','$2y$10$yrUxEDB84M3WLn2pr9sLp.jUEI8KJc8XFAP51u4mmBThlW/1B84TK','admin@inecolara.gob.ve','public/assets/uploads/avatars/avatar_1779229743_1e10cf82.jpg',1,NULL,'Activo',0,'2026-05-19 22:53:30','2026-05-19 16:32:47'),(3,'Enyell Duarte','$2y$10$BI4YwBgo6.wQk5Bi7dQ7wOLDmBHznRKGfr9NMB32LJj1DEy3P8oSe','enyellduarte6@gmail.com','public/assets/uploads/avatars/avatar_1779229316_0006e14e.jpg',1,NULL,'Activo',0,'2026-05-22 21:43:41','2026-05-19 16:50:34'),(8,'Prueba','$2y$10$5QlpZMfs9OSnOZvRBZDtdeQzf4ieNxUGntQmdB4UH2I6N0Kvlm.tG','prueba1@gmail.com','public/assets/uploads/avatars/avatar_1779318278_eabb6afd.jpg',2,NULL,'Activo',0,'2026-05-20 23:05:33','2026-05-20 23:04:38'),(9,'Pedro Sanchez','$2y$10$FFtBU7x0Nty9lWSztt1UmeFGpH3J0yaphl8rgDRkSs/6fjYVuKegK','prueba@correo.com','public/assets/uploads/avatars/avatar_1779486894_d40dcd8e.jpg',2,NULL,'Activo',0,'2026-05-22 21:54:54','2026-05-22 21:54:54'),(10,'Prueba nueva','$2y$10$YXBzb7A2JWNbxJXYSYaSo./EBvvfynaztq4z274rAFwrdZxkU4lDi','prueba3@correo.com','public/assets/uploads/avatars/avatar_1779490408_11ed9d13.jpg',2,NULL,'Activo',0,'2026-05-22 22:53:28','2026-05-22 22:53:28');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'SysInescolara-Seguridad'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-27 14:01:13
