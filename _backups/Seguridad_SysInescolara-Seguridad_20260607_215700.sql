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
  `valor_anterior` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_anterior`)),
  `valor_nuevo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_nuevo`)),
  `endpoint_solicitado` varchar(255) DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_logs_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_logs`
--

LOCK TABLES `auditoria_logs` WRITE;
/*!40000 ALTER TABLE `auditoria_logs` DISABLE KEYS */;
INSERT INTO `auditoria_logs` VALUES (2,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-05-28 20:34:16'),(3,1,'CREATE','usuarios',2,NULL,'{\"nombreUsuario\":\"Enyell Duarte\",\"correoElectronico\":\"enyellduarte6@gmail.com\",\"rolId\":1}','/sys-inescolara/user?action=add_ajax','2026-05-28 20:34:39'),(4,1,'CREATE','usuarios',3,NULL,'{\"nombreUsuario\":\"Prueba\",\"correoElectronico\":\"prueba@gmail.com\",\"rolId\":2}','/sys-inescolara/user?action=add_ajax','2026-05-28 20:35:16'),(5,1,'LOGOUT','usuarios',1,NULL,NULL,'/sys-inescolara/login/logout','2026-05-28 20:35:20'),(6,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-28 20:36:08'),(7,2,'DELETE','usuarios',3,'{\"id\":3,\"nombre_usuario\":\"Prueba\",\"correo_electronico\":\"prueba@gmail.com\",\"avatar\":\"public\\/assets\\/uploads\\/avatars\\/avatar_1780000515_ecad8cf5.jpg\",\"rol_id\":2,\"nombre_rol\":null,\"estatus\":\"Activo\"}',NULL,'/sys-inescolara/user?action=delete_ajax','2026-05-28 20:36:30'),(8,2,'CREATE','usuarios',4,NULL,'{\"nombreUsuario\":\"Prueba\",\"correoElectronico\":\"prueba@correo.com\",\"rolId\":2}','/sys-inescolara/user?action=add_ajax','2026-05-28 20:37:32'),(9,2,'LOGOUT','usuarios',2,NULL,NULL,'/sys-inescolara/login/logout','2026-05-28 20:37:36'),(10,4,'LOGIN','usuarios',4,NULL,'{\"nombre_usuario\":\"Prueba\"}','/sys-inescolara/login','2026-05-28 20:37:57'),(11,4,'LOGOUT','usuarios',4,NULL,NULL,'/sys-inescolara/login/logout','2026-05-28 20:38:15'),(12,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-28 20:38:29'),(13,2,'CREATE','especie',1,NULL,'{\"nombreEspecie\":\"dsadasdasdasa\",\"descripcion\":null}','/sys-inescolara/species?action=add_ajax','2026-05-28 20:39:17'),(14,2,'CREATE','especie',2,NULL,'{\"nombreEspecie\":\"dadada\",\"descripcion\":null}','/sys-inescolara/species?action=add_ajax','2026-05-28 20:39:23'),(15,2,'CREATE','plantas',1,NULL,'{\"nombreComun\":\"Cactus\",\"nombreTecnico\":\"Cactuspro we\",\"especieId\":2,\"cantidadTotal\":1}','/sys-inescolara/plants?action=add_ajax','2026-05-28 20:39:55'),(16,2,'CREATE','especie',3,NULL,'{\"nombreEspecie\":\"dsadasdasdasad\",\"descripcion\":null}','/sys-inescolara/species?action=add_ajax','2026-05-28 20:43:24'),(17,2,'CREATE','ubicacion',1,NULL,'{\"nombre_ubicacion\":\"Invernadero\",\"descripcion\":null,\"zona\":null}','/sys-inescolara/locations?action=add_ajax','2026-05-28 20:49:43'),(18,2,'CREATE','lote',1,NULL,'{\"id_planta\":1,\"id_ubicacion\":1,\"fecha_siembra\":\"2026-05-27\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"origen\":\"Ampliación\",\"observacion\":\"No hay observaciones\"}','/sys-inescolara/batches?action=add_ajax','2026-05-28 22:20:45'),(19,2,'CREATE','trabajadores',1,NULL,'{\"nombre\":\"Enyell\",\"apellido\":\"Duarte\",\"cedula\":\"31511825\",\"telefono\":\"04120000000\",\"cargo\":\"Administrador\",\"activo\":true}','/sys-inescolara/employees?action=add_ajax','2026-05-28 22:21:25'),(20,2,'CREATE','cliente',1,NULL,'{\"nombre_cliente\":\"Mayra Perez\",\"contacto_cliente\":\"04123005644\"}','/sys-inescolara/clients?action=add_ajax','2026-05-28 22:21:48'),(21,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-05-30 03:01:51'),(22,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-05-31 22:35:28'),(23,2,'CREATE','roles',0,NULL,'{\"nombreRol\":\"Prueba\",\"descripcion\":null,\"permisoIds\":[1,2,3,5,9,13,17,21]}','/sys-inescolara/roles?action=add_ajax','2026-05-31 22:35:52'),(24,2,'CREATE','especie',4,NULL,'{\"nombreEspecie\":\"dsadasdasdasa\",\"descripcion\":\"dfdfdfd\"}','/sys-inescolara/species?action=add_ajax','2026-05-31 22:38:16'),(25,2,'CREATE','plantas',2,NULL,'{\"nombreComun\":\"Simon\",\"nombreTecnico\":\"Cactuspro\",\"especieId\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1780267336_412abda4.png\"}','/sys-inescolara/plants?action=add_ajax','2026-05-31 22:42:16'),(26,2,'UPDATE','plantas',2,'{\"id_planta\":2,\"id_especie\":2,\"nombre_tecnico\":\"Cactuspro\",\"nombre_comun\":\"Simon\",\"cantidad_total\":0,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1780267336_412abda4.png\",\"especie_nombre\":\"dadada\"}','{\"nombreComun\":\"Simon\",\"nombreTecnico\":\"Cactuspro\",\"especieId\":1,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1780267336_412abda4.png\"}','/sys-inescolara/plants?action=edit_ajax','2026-05-31 22:43:57'),(27,2,'DELETE','lote',1,'{\"id_lote\":1,\"id_planta\":1,\"id_ubicacion\":1,\"fecha_siembra\":\"2026-05-27\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"estado\":\"Activo\",\"origen\":\"Ampliación\",\"observacion\":\"No hay observaciones\",\"imagen\":null,\"planta_nombre\":\"Cactus\",\"especie_nombre\":\"dadada\"}',NULL,'/sys-inescolara/batches?action=delete_ajax','2026-05-31 22:44:27'),(28,2,'DELETE','especie',4,'{\"id_especie\":4,\"nombre_especie\":\"dsadasdasdasa\",\"descripcion\":\"dfdfdfd\"}',NULL,'/sys-inescolara/species?action=delete_ajax','2026-05-31 22:44:32'),(29,2,'DELETE','ubicacion',1,'{\"id\":1,\"nombre_ubicacion\":\"Invernadero\",\"descripcion\":null,\"zona\":null}',NULL,'/sys-inescolara/locations?action=delete_ajax','2026-05-31 22:44:36'),(30,2,'DELETE','plantas',1,'{\"id_planta\":1,\"id_especie\":2,\"nombre_tecnico\":\"Cactuspro we\",\"nombre_comun\":\"Cactus\",\"cantidad_total\":1,\"imagen\":\"planta_6a18a81b6443e.jpg\",\"especie_nombre\":\"dadada\"}',NULL,'/sys-inescolara/plants?action=delete_ajax','2026-05-31 22:46:27'),(31,2,'CREATE','plantas',3,NULL,'{\"nombreComun\":\"Simon\",\"nombreTecnico\":\"Cactuspro\",\"especieId\":2,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1780270066_bd3805d9.jpg\"}','/sys-inescolara/plants?action=add_ajax','2026-05-31 23:27:46'),(32,2,'DELETE','plantas',3,'{\"id_planta\":3,\"id_especie\":2,\"nombre_tecnico\":\"Cactuspro\",\"nombre_comun\":\"Simon\",\"cantidad_total\":0,\"imagen\":\"public\\/assets\\/uploads\\/plants\\/plant_1780270066_bd3805d9.jpg\",\"especie_nombre\":\"dadada\"}',NULL,'/sys-inescolara/plants?action=delete_ajax','2026-05-31 23:27:49'),(33,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-01 13:44:20'),(34,2,'CREATE','herramienta',1,NULL,'{\"nombre\":\"Pala\",\"tipo\":null,\"estado\":\"en_uso\",\"fechaAdquisicion\":\"2026-06-02\",\"fechaUltimoMantenimiento\":\"2026-06-02\",\"observacion\":null}','/sys-inescolara/tools?action=add_ajax','2026-06-01 13:45:07'),(35,2,'CREATE','herramienta',2,NULL,'{\"nombre\":\"Martillo\",\"tipo\":\"nose\",\"estado\":\"en_uso\",\"fechaAdquisicion\":\"2026-06-02\",\"fechaUltimoMantenimiento\":\"2026-06-02\",\"observacion\":\"dsadasdada\"}','/sys-inescolara/tools?action=add_ajax','2026-06-01 13:47:49'),(36,2,'CREATE','herramienta',3,NULL,'{\"nombre\":\"Pico\",\"tipo\":\"herramienta\",\"estado\":\"en_uso\",\"fechaAdquisicion\":\"2026-06-02\",\"fechaUltimoMantenimiento\":\"2026-06-02\",\"observacion\":null}','/sys-inescolara/tools?action=add_ajax','2026-06-01 13:59:48'),(37,2,'CREATE','herramienta',4,NULL,'{\"nombre\":\"Martillo\",\"tipo\":\"herramienta\",\"estado\":\"mantenimiento\",\"fechaAdquisicion\":\"2026-06-10\",\"fechaUltimoMantenimiento\":\"2026-06-09\",\"observacion\":null}','/sys-inescolara/tools?action=add_ajax','2026-06-01 14:19:12'),(38,2,'DELETE','herramienta',4,'{\"id\":4,\"nombre_herramienta\":\"Martillo\",\"tipo\":\"herramienta\",\"estado\":\"mantenimiento\",\"fecha_adquisicion\":\"2026-06-10\",\"fecha_ultimo_mantenimiento\":\"2026-06-09\",\"observacion\":null}',NULL,'/sys-inescolara/tools?action=delete_ajax','2026-06-01 14:19:14'),(39,2,'CREATE','lote',4,NULL,'{\"id_planta\":2,\"id_ubicacion\":3,\"fecha_siembra\":\"2026-06-01\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"estado\":\"Vivo\",\"origen\":\"Siembra\",\"observacion\":null,\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1780330649_5cde51b9.jpg\"}','/sys-inescolara/batches?action=add_ajax','2026-06-01 16:17:29'),(40,2,'CREATE','calculo_precio',1,NULL,'{\"idLote\":4,\"costoManoObra\":10,\"costoTotalInsumo\":20,\"costoAguaLote\":30,\"porcentajeGanancia\":30,\"cantidadPlantaBase\":1,\"precioFinalSugerido\":78}','/sys-inescolara/prices?action=add_ajax','2026-06-01 16:17:54'),(41,2,'UPDATE','calculo_precio',1,'{\"id_calculo\":1,\"id_lote\":4,\"costo_mano_obra\":\"10.00\",\"costo_total_insumo\":\"20.00\",\"costo_agua_lote\":\"30.00\",\"porcentaje_ganancia\":\"30.00\",\"cantidad_planta_base\":1,\"precio_final_sugerido\":\"78.00\",\"fecha_calculo\":\"2026-06-01\",\"vigente\":0,\"cantidad_actual\":1,\"planta_nombre\":\"Simon\",\"id_planta\":2}','{\"idLote\":4,\"costoManoObra\":10,\"costoTotalInsumo\":20,\"porcentajeGanancia\":30,\"precioFinalSugerido\":78}','/sys-inescolara/prices?action=edit_ajax','2026-06-01 17:02:59'),(42,2,'CREATE','insumo',1,NULL,'{\"nombre_insumo\":\"Fertilizante\",\"id_unidad_medida\":12,\"categoria\":\"dasd\",\"stock_actual\":0.04,\"costo_unitario_actual\":0.02}','/sys-inescolara/supplies?action=add_ajax','2026-06-01 21:43:55'),(43,2,'DELETE','insumo',1,'{\"id_insumo\":1,\"id_unidad_medida\":12,\"nombre_insumo\":\"Fertilizante\",\"categoria\":\"dasd\",\"stock_actual\":\"0.04\",\"costo_unitario_actual\":\"0.02\",\"nombre_unidad_medida\":\"Caja\",\"simbolo\":\"caja\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-01 21:44:03'),(44,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-06-02 00:50:02'),(45,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-02 00:53:41'),(46,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-02 00:54:47'),(47,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-06-02 01:11:59'),(48,1,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260601_211206.sql\",\"Seguridad_SysInescolara-Seguridad_20260601_211206.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-06-02 01:12:07'),(49,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-06-02 01:22:53'),(50,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-03 01:49:55'),(51,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-03 01:54:04'),(52,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-03 15:48:56'),(53,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-06 22:37:32'),(54,2,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260606_195724.sql\",\"Seguridad_SysInescolara-Seguridad_20260606_195725.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-06-06 23:57:25'),(55,2,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260606_195747.sql\",\"Seguridad_SysInescolara-Seguridad_20260606_195747.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-06-06 23:57:48'),(56,2,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260606_195724.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-06-06 23:57:56'),(57,2,'CREATE','insumo',2,NULL,'{\"nombre_insumo\":\"Fertilizante\",\"id_unidad_medida\":14,\"categoria\":\"dasd\",\"stock_actual\":0.01,\"costo_unitario_actual\":0.01}','/sys-inescolara/supplies?action=add_ajax','2026-06-07 06:02:28'),(58,2,'DEACTIVATE','insumo',2,'{\"id_insumo\":2,\"id_unidad_medida\":14,\"nombre_insumo\":\"Fertilizante\",\"categoria\":\"dasd\",\"stock_actual\":\"0.01\",\"costo_unitario_actual\":\"0.01\",\"activo\":1,\"nombre_unidad_medida\":\"Bidon\",\"simbolo\":\"bidon\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 06:02:31'),(59,2,'CREATE','recoleccion_semillas',1,NULL,'{\"idTrabajador\":1,\"idUbicacion\":2,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":\"Nada\"}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 06:15:58'),(60,2,'UPDATE','recoleccion_semillas',1,'{\"id_recoleccion\":1,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":\"Nada\",\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 06:16:25'),(61,2,'UPDATE','recoleccion_semillas',1,'{\"id_recoleccion\":1,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":\"Nada\",\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 06:16:40'),(62,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-07 13:44:15'),(63,2,'UPDATE','insumo',3,'{\"id_insumo\":3,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de Simon\",\"categoria\":\"Semillas\",\"stock_actual\":\"1.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}','{\"nombre_insumo\":\"Semillas de Simon\",\"id_unidad_medida\":5,\"categoria\":\"Semillas\",\"stock_actual\":1,\"costo_unitario_actual\":10}','/sys-inescolara/supplies?action=edit_ajax','2026-06-07 15:23:51'),(64,2,'CREATE','asignar_tarea',1,NULL,'{\"nombre_tarea\":\"FJadfhjdkf\",\"descripcion\":\"No hay detalles\",\"id_trabajador\":1,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"estatus_tarea\":\"pendiente\"}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:36:19'),(65,2,'CREATE','consumo_insumos',1,NULL,'{\"count\":1}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:36:19'),(66,2,'UPDATE','asignar_tarea',1,'{\"id_asignacion\":1,\"id_trabajador\":1,\"id_tarea\":1,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_cumplimiento\":null,\"estatus_tarea\":\"pendiente\",\"horas_dedicadas\":null,\"nombre_tarea\":\"FJadfhjdkf\",\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"codigo_lote\":4}','{\"estatus_tarea\":\"completada\",\"fecha_cumplimiento\":\"2026-06-07\",\"horas_dedicadas\":1}','/sys-inescolara/tasks?action=complete_ajax','2026-06-07 15:36:33'),(67,2,'CREATE','recoleccion_semillas',2,NULL,'{\"idTrabajador\":1,\"idUbicacion\":3,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 15:36:58'),(68,2,'UPDATE','recoleccion_semillas',2,'{\"id_recoleccion\":2,\"id_trabajador\":1,\"id_ubicacion\":3,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Almacén Norte\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 15:37:02'),(69,2,'UPDATE','recoleccion_semillas',2,'{\"id_recoleccion\":2,\"id_trabajador\":1,\"id_ubicacion\":3,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Almacén Norte\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 15:37:15'),(70,2,'CREATE','recoleccion_semillas',3,NULL,'{\"idTrabajador\":1,\"idUbicacion\":2,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 15:37:26'),(71,2,'UPDATE','recoleccion_semillas',3,'{\"id_recoleccion\":3,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 15:37:28'),(72,2,'UPDATE','recoleccion_semillas',3,'{\"id_recoleccion\":3,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 15:37:35'),(73,2,'DEACTIVATE','insumo',5,'{\"id_insumo\":5,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de Simon\",\"categoria\":\"Semillas\",\"stock_actual\":\"20.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 15:37:50'),(74,2,'DEACTIVATE','insumo',4,'{\"id_insumo\":4,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de Simon\",\"categoria\":\"Semillas\",\"stock_actual\":\"20.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 15:37:51'),(75,2,'DEACTIVATE','insumo',3,'{\"id_insumo\":3,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de Simon\",\"categoria\":\"Semillas\",\"stock_actual\":\"0.00\",\"costo_unitario_actual\":\"10.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 15:37:53'),(76,2,'CREATE','recoleccion_semillas',4,NULL,'{\"idTrabajador\":1,\"idUbicacion\":2,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 15:39:09'),(77,2,'UPDATE','recoleccion_semillas',4,'{\"id_recoleccion\":4,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 15:39:13'),(78,2,'UPDATE','recoleccion_semillas',4,'{\"id_recoleccion\":4,\"id_trabajador\":1,\"id_ubicacion\":2,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Invernadero Central\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 15:39:19'),(79,2,'UPDATE','insumo',6,'{\"id_insumo\":6,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de Simon\",\"categoria\":\"Semillas\",\"stock_actual\":\"10.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}','{\"nombre_insumo\":\"Semillas de Simon\",\"id_unidad_medida\":5,\"categoria\":\"Semillas\",\"stock_actual\":10,\"costo_unitario_actual\":20}','/sys-inescolara/supplies?action=edit_ajax','2026-06-07 15:39:55'),(80,2,'CREATE','asignar_tarea',2,NULL,'{\"nombre_tarea\":\"FJadfhjdkf\",\"descripcion\":null,\"id_trabajador\":1,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"estatus_tarea\":\"pendiente\"}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:40:17'),(81,2,'CREATE','consumo_insumos',2,NULL,'{\"count\":1}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:40:17'),(82,2,'UPDATE','asignar_tarea',2,'{\"id_asignacion\":2,\"id_trabajador\":1,\"id_tarea\":2,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_cumplimiento\":null,\"estatus_tarea\":\"pendiente\",\"horas_dedicadas\":null,\"nombre_tarea\":\"FJadfhjdkf\",\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"codigo_lote\":4}','{\"estatus_tarea\":\"completada\",\"fecha_cumplimiento\":\"2026-06-07\",\"horas_dedicadas\":10}','/sys-inescolara/tasks?action=complete_ajax','2026-06-07 15:47:02'),(83,2,'CREATE','herramienta',5,NULL,'{\"nombre\":\"Pico\",\"tipo\":\"nose\",\"estado\":\"mantenimiento\",\"fechaAdquisicion\":null,\"fechaUltimoMantenimiento\":null,\"observacion\":null}','/sys-inescolara/tools?action=add_ajax','2026-06-07 15:57:11'),(84,2,'UPDATE','herramienta',5,'{\"id\":5,\"nombre_herramienta\":\"Pico\",\"tipo\":\"nose\",\"estado\":\"mantenimiento\",\"fecha_adquisicion\":null,\"fecha_ultimo_mantenimiento\":null,\"observacion\":null}','{\"nombre\":\"Pico\",\"tipo\":\"nose\",\"estado\":\"disponible\",\"fechaAdquisicion\":null,\"fechaUltimoMantenimiento\":null,\"observacion\":null}','/sys-inescolara/tools?action=edit_ajax','2026-06-07 15:57:16'),(85,2,'CREATE','asignar_tarea',3,NULL,'{\"nombre_tarea\":\"FJadfhjdkf\",\"descripcion\":\"jhjhj\",\"id_trabajador\":1,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"estatus_tarea\":\"pendiente\"}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:57:48'),(86,2,'CREATE','consumo_insumos',3,NULL,'{\"count\":1}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:57:48'),(87,2,'CREATE','uso_herramienta',3,NULL,'{\"count\":1}','/sys-inescolara/tasks?action=assign_ajax','2026-06-07 15:57:48'),(88,2,'UPDATE','asignar_tarea',3,'{\"id_asignacion\":3,\"id_trabajador\":1,\"id_tarea\":3,\"id_lote\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_cumplimiento\":null,\"estatus_tarea\":\"pendiente\",\"horas_dedicadas\":null,\"nombre_tarea\":\"FJadfhjdkf\",\"nombre_trabajador\":\"Enyell\",\"apellido_trabajador\":\"Duarte\",\"codigo_lote\":4}','{\"estatus_tarea\":\"completada\",\"fecha_cumplimiento\":\"2026-06-07\"}','/sys-inescolara/tasks?action=complete_ajax','2026-06-07 16:03:29'),(89,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-07 16:48:19'),(90,2,'DELETE','calculo_precio',1,'{\"id_calculo\":1,\"id_lote\":4,\"costo_mano_obra\":\"10.00\",\"costo_total_insumo\":\"20.00\",\"costo_agua_lote\":\"30.00\",\"porcentaje_ganancia\":\"30.00\",\"cantidad_planta_base\":1,\"precio_final_sugerido\":\"78.00\",\"fecha_calculo\":\"2026-06-01\",\"vigente\":1,\"cantidad_actual\":1,\"planta_nombre\":\"Simon\",\"id_planta\":2}',NULL,'/sys-inescolara/prices?action=delete_ajax','2026-06-07 16:51:13'),(91,2,'UPDATE','lote',4,'{\"id_lote\":4,\"id_planta\":2,\"id_ubicacion\":3,\"fecha_siembra\":\"2026-06-01\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"estado\":\"Vivo\",\"categoria\":null,\"origen\":\"Siembra\",\"observacion\":null,\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1780330649_5cde51b9.jpg\",\"activo\":1,\"planta_nombre\":\"Simon\",\"especie_nombre\":\"dsadasdasdasa\"}','{\"id_planta\":2,\"id_ubicacion\":3,\"fecha_siembra\":\"2026-06-01\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"estado\":\"Vivo\",\"origen\":\"Siembra\",\"observacion\":null,\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1780330649_5cde51b9.jpg\"}','/sys-inescolara/batches?action=edit_ajax','2026-06-07 16:52:52'),(92,2,'CREATE','ubicacion',5,NULL,'{\"nombre_ubicacion\":\"Prueba\",\"descripcion\":null,\"zona\":\"dxsdsdsd\"}','/sys-inescolara/locations?action=add_ajax','2026-06-07 16:57:46'),(93,2,'UPDATE','lote',4,'{\"id_lote\":4,\"id_planta\":2,\"id_ubicacion\":3,\"fecha_siembra\":\"2026-06-01\",\"cantidad_inicial\":1,\"cantidad_actual\":1,\"estado\":\"Vivo\",\"categoria\":\"germinado\",\"origen\":\"Siembra\",\"observacion\":null,\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1780330649_5cde51b9.jpg\",\"activo\":1,\"planta_nombre\":\"Simon\",\"especie_nombre\":\"dsadasdasdasa\"}','{\"id_planta\":2,\"id_ubicacion\":3,\"fecha_siembra\":\"2026-06-01\",\"cantidad_inicial\":1,\"cantidad_actual\":30,\"estado\":\"Vivo\",\"origen\":\"Siembra\",\"observacion\":null,\"imagen\":\"public\\/assets\\/uploads\\/batches\\/batch_1780330649_5cde51b9.jpg\"}','/sys-inescolara/batches?action=edit_ajax','2026-06-07 17:14:00'),(94,2,'CREATE','ubicacion',6,NULL,'{\"nombre_ubicacion\":\"Nueva\",\"descripcion\":null,\"zona\":\"dasdasdasa\"}','/sys-inescolara/locations?action=add_ajax','2026-06-07 17:24:36'),(95,2,'CREATE','recoleccion_semillas',5,NULL,'{\"idTrabajador\":1,\"idUbicacion\":6,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 17:24:41'),(96,2,'LOGIN','usuarios',2,NULL,'{\"nombre_usuario\":\"Enyell Duarte\"}','/sys-inescolara/login','2026-06-07 17:51:03'),(97,2,'CREATE','backups',NULL,NULL,'{\"files\":[\"Datos_sysinescolara_20260607_135248.sql\",\"Seguridad_SysInescolara-Seguridad_20260607_135248.sql\"]}','/sys-inescolara/backups?action=create_backup','2026-06-07 17:52:49'),(98,1,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Seguridad_SysInescolara-Seguridad_20260607_135336.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-06-07 17:58:18'),(99,1,'UPDATE','backups',NULL,NULL,'{\"action\":\"restore\",\"file\":\"Datos_sysinescolara_20260607_135335.sql\",\"result\":\"success\"}','/sys-inescolara/backups?action=restore_backup','2026-06-07 17:58:27'),(100,1,'UPDATE','recoleccion_semillas',5,'{\"id_recoleccion\":5,\"id_trabajador\":1,\"id_ubicacion\":6,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Nueva\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 18:00:45'),(101,1,'LOGIN','usuarios',1,NULL,'{\"nombre_usuario\":\"admin\"}','/sys-inescolara/login','2026-06-07 18:06:13'),(102,1,'CREATE','plantas',4,NULL,'{\"nombreComun\":\"cardon\",\"nombreTecnico\":\"cactus\",\"especieId\":2,\"imagen\":null}','/sys-inescolara/plants?action=add_ajax','2026-06-07 18:06:55'),(103,1,'CREATE','plantas',5,NULL,'{\"nombreComun\":\"malojillo\",\"nombreTecnico\":\"nose\",\"especieId\":2,\"imagen\":null}','/sys-inescolara/plants?action=add_ajax','2026-06-07 18:07:17'),(104,1,'UPDATE','recoleccion_semillas',5,'{\"id_recoleccion\":5,\"id_trabajador\":1,\"id_ubicacion\":6,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Nueva\"}','{\"insumos_registrados\":2}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 18:07:51'),(105,1,'CREATE','recoleccion_semillas',6,NULL,'{\"idTrabajador\":1,\"idUbicacion\":4,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":\"nose\"}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 18:08:37'),(106,1,'UPDATE','recoleccion_semillas',6,'{\"id_recoleccion\":6,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":\"nose\",\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 18:08:54'),(107,1,'UPDATE','recoleccion_semillas',6,'{\"id_recoleccion\":6,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":\"nose\",\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 18:09:05'),(108,1,'CREATE','recoleccion_semillas',7,NULL,'{\"idTrabajador\":1,\"idUbicacion\":4,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 19:20:08'),(109,1,'UPDATE','recoleccion_semillas',7,'{\"id_recoleccion\":7,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"activo\":1,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 19:20:21'),(110,1,'UPDATE','recoleccion_semillas',7,'{\"id_recoleccion\":7,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"activo\":1,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 19:20:37'),(111,1,'DEACTIVATE','insumo',9,'{\"id_insumo\":9,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de cardon\",\"categoria\":\"Semillas\",\"stock_actual\":\"1.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 19:33:38'),(112,1,'DEACTIVATE','insumo',10,'{\"id_insumo\":10,\"id_unidad_medida\":5,\"nombre_insumo\":\"Semillas de cardon\",\"categoria\":\"Semillas\",\"stock_actual\":\"2.00\",\"costo_unitario_actual\":\"0.00\",\"activo\":1,\"nombre_unidad_medida\":\"Unidad\",\"simbolo\":\"und\"}',NULL,'/sys-inescolara/supplies?action=delete_ajax','2026-06-07 19:33:40'),(113,1,'CREATE','recoleccion_semillas',8,NULL,'{\"idTrabajador\":1,\"idUbicacion\":4,\"fechaAsignacion\":\"2026-06-06\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 19:34:12'),(114,1,'UPDATE','recoleccion_semillas',8,'{\"id_recoleccion\":8,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-06\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"activo\":1,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 19:34:49'),(115,1,'UPDATE','recoleccion_semillas',8,'{\"id_recoleccion\":8,\"id_trabajador\":1,\"id_ubicacion\":4,\"fecha_asignacion\":\"2026-06-06\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":null,\"activo\":1,\"trabajador_nombre\":\"Enyell Duarte\",\"nombre_ubicacion\":\"Vivero Exterior\"}','{\"insumos_registrados\":1}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 19:35:09'),(116,1,'CREATE','trabajadores',2,NULL,'{\"nombre\":\"Jormarly\",\"apellido\":\"Castillo\",\"cedula\":\"24157297\",\"telefono\":\"04245759005\",\"cargo\":\"Trabajador\",\"activo\":true}','/sys-inescolara/employees?action=add_ajax','2026-06-07 19:36:53'),(117,1,'CREATE','recoleccion_semillas',9,NULL,'{\"idTrabajador\":2,\"idUbicacion\":3,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":\"mio\"}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 19:37:17'),(118,1,'UPDATE','recoleccion_semillas',9,'{\"id_recoleccion\":9,\"id_trabajador\":2,\"id_ubicacion\":3,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":\"mio\",\"activo\":1,\"trabajador_nombre\":\"Jormarly Castillo\",\"nombre_ubicacion\":\"Almacén Norte\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 19:37:27'),(119,1,'UPDATE','recoleccion_semillas',9,'{\"id_recoleccion\":9,\"id_trabajador\":2,\"id_ubicacion\":3,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":\"2026-06-07\",\"estatus\":\"Realizada\",\"observacion\":\"mio\",\"activo\":1,\"trabajador_nombre\":\"Jormarly Castillo\",\"nombre_ubicacion\":\"Almacén Norte\"}','{\"insumos_registrados\":2}','/sys-inescolara/recoleccion?action=registrar_insumo_ajax','2026-06-07 19:37:52'),(120,1,'CREATE','ubicacion',7,NULL,'{\"nombre_ubicacion\":\"campamento nuevo\",\"descripcion\":null,\"zona\":\"oeste\"}','/sys-inescolara/locations?action=add_ajax','2026-06-07 20:36:03'),(121,1,'CREATE','recoleccion_semillas',10,NULL,'{\"idTrabajador\":2,\"idUbicacion\":7,\"fechaAsignacion\":\"2026-06-07\",\"observacion\":null}','/sys-inescolara/recoleccion?action=add_ajax','2026-06-07 20:36:16'),(122,1,'UPDATE','recoleccion_semillas',10,'{\"id_recoleccion\":10,\"id_trabajador\":2,\"id_ubicacion\":7,\"fecha_asignacion\":\"2026-06-07\",\"fecha_recoleccion\":null,\"estatus\":\"Pendiente\",\"observacion\":null,\"activo\":1,\"trabajador_nombre\":\"Jormarly Castillo\",\"nombre_ubicacion\":\"campamento nuevo\"}','{\"estatus\":\"Realizada\",\"fecha_recoleccion\":\"2026-06-07\"}','/sys-inescolara/recoleccion?action=completar_ajax','2026-06-07 20:36:22'),(123,1,'CREATE','movimiento_planta',6,NULL,'{\"tipo\":\"intercambio\",\"id_cliente\":1,\"id_trabajador\":2,\"salida_items\":[{\"id_lote\":4,\"cantidad\":1}],\"entrada_items\":[{\"id_planta\":5,\"id_ubicacion\":2,\"cantidad\":1}]}','/sys-inescolara/ampliacion?action=add_ajax','2026-06-07 23:53:43'),(124,1,'DEACTIVATE','movimiento_planta',6,'{\"id_movimiento_planta\":6,\"tipo_movimiento\":\"intercambio\",\"id_cliente\":1,\"id_trabajador_gestor\":2,\"fecha_movimiento\":\"2026-06-07\",\"observacion\":\"se recibe simon y se entrega malojillo\",\"activo\":1,\"gestor_nombre\":\"Jormarly Castillo\",\"nombre_cliente\":\"Mayra Perez\",\"detalles\":[{\"id_detalle_mov_planta\":7,\"id_movimiento_planta\":6,\"id_lote\":5,\"tipo\":\"entrada\",\"cantidad\":1,\"precio_unitario\":null,\"sub_total\":null,\"activo\":1,\"lote_stock_actual\":1,\"planta_nombre\":\"malojillo\",\"ubicacion_nombre\":\"Invernadero Central\"},{\"id_detalle_mov_planta\":6,\"id_movimiento_planta\":6,\"id_lote\":4,\"tipo\":\"salida\",\"cantidad\":1,\"precio_unitario\":null,\"sub_total\":null,\"activo\":1,\"lote_stock_actual\":29,\"planta_nombre\":\"Simon\",\"ubicacion_nombre\":\"Almacén Norte\"}]}',NULL,'/sys-inescolara/ampliacion?action=delete_ajax','2026-06-07 23:54:19'),(125,1,'CREATE','movimiento_planta',7,NULL,'{\"tipo\":\"intercambio\",\"id_cliente\":1,\"id_trabajador\":2,\"salida_items\":[{\"id_lote\":4,\"cantidad\":1}],\"entrada_items\":[{\"id_planta\":5,\"id_ubicacion\":2,\"cantidad\":1}]}','/sys-inescolara/ampliacion?action=add_ajax','2026-06-08 00:02:49'),(126,1,'CREATE','movimiento_planta',8,NULL,'{\"tipo\":\"intercambio\",\"id_cliente\":1,\"id_trabajador\":2,\"salida_items\":[{\"id_lote\":5,\"cantidad\":1}],\"entrada_items\":[{\"id_planta\":0,\"id_ubicacion\":7,\"cantidad\":1,\"nueva_planta_nombre\":\"semeruco\",\"nueva_planta_tecnico\":\"semeruco\",\"nueva_planta_id_especie\":1}]}','/sys-inescolara/ampliacion?action=add_ajax','2026-06-08 00:31:42'),(127,1,'CREATE','movimiento_planta',9,NULL,'{\"tipo\":\"intercambio\",\"id_cliente\":1,\"id_trabajador\":2,\"salida_items\":[{\"id_lote\":4,\"cantidad\":1}],\"entrada_items\":[{\"id_planta\":0,\"id_ubicacion\":3,\"cantidad\":1,\"nueva_planta_nombre\":\"lefaria\",\"nueva_planta_tecnico\":\"lefaria\",\"nueva_planta_id_especie\":2}]}','/sys-inescolara/ampliacion?action=add_ajax','2026-06-08 00:46:41'),(128,1,'CREATE','movimiento_planta',10,NULL,'{\"tipo\":\"intercambio\",\"id_cliente\":1,\"id_trabajador\":1,\"salida_items\":[{\"id_lote\":4,\"cantidad\":2}],\"entrada_items\":[{\"id_planta\":0,\"id_ubicacion\":2,\"cantidad\":1,\"nueva_planta_nombre\":\"girasol\",\"nueva_planta_tecnico\":\"girasol\",\"nueva_planta_id_especie\":2}]}','/sys-inescolara/ampliacion?action=add_ajax','2026-06-08 01:37:41');
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
  `id_usuario` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `expira_en` datetime DEFAULT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_permiso` varchar(50) DEFAULT NULL,
  `descripcion_permiso` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `codigo_permiso` (`codigo_permiso`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (1,'DASHBOARD_VIEW','Ver panel principal'),(2,'INVENTARIO_VIEW','Ver inventario'),(3,'VENTAS_ACCESS','Acceder a ventas/POS'),(4,'USUARIOS_MANAGE','Gestionar usuarios'),(5,'PLANTAS_VIEW','Ver plantas'),(6,'PLANTAS_CREATE','Crear plantas'),(7,'PLANTAS_EDIT','Editar plantas'),(8,'PLANTAS_DELETE','Eliminar plantas'),(9,'PROVEEDORES_VIEW','Ver proveedores'),(10,'PROVEEDORES_CREATE','Crear proveedores'),(11,'PROVEEDORES_EDIT','Editar proveedores'),(12,'PROVEEDORES_DELETE','Eliminar proveedores'),(13,'INSUMOS_VIEW','Ver insumos'),(14,'INSUMOS_CREATE','Crear insumos'),(15,'INSUMOS_EDIT','Editar insumos'),(16,'INSUMOS_DELETE','Eliminar insumos'),(17,'TRABAJADORES_VIEW','Ver trabajadores'),(18,'TRABAJADORES_CREATE','Crear trabajadores'),(19,'TRABAJADORES_EDIT','Editar trabajadores'),(20,'TRABAJADORES_DELETE','Eliminar trabajadores'),(21,'CLIENTES_VIEW','Ver clientes'),(22,'CLIENTES_CREATE','Crear clientes'),(23,'CLIENTES_EDIT','Editar clientes'),(24,'CLIENTES_DELETE','Eliminar clientes'),(25,'TAREAS_VIEW','Ver tareas'),(26,'TAREAS_CREATE','Crear tareas'),(27,'TAREAS_EDIT','Editar tareas'),(28,'TAREAS_DELETE','Eliminar tareas'),(29,'UBICACIONES_VIEW','Ver ubicaciones'),(30,'UBICACIONES_CREATE','Crear ubicaciones'),(31,'UBICACIONES_EDIT','Editar ubicaciones'),(32,'UBICACIONES_DELETE','Eliminar ubicaciones'),(33,'ASISTENTE_ACCESS','Acceder al asistente IA'),(34,'HERRAMIENTAS_VIEW','Ver herramientas'),(35,'HERRAMIENTAS_CREATE','Crear herramientas'),(36,'HERRAMIENTAS_EDIT','Editar herramientas'),(37,'HERRAMIENTAS_DELETE','Eliminar herramientas'),(38,'PRECIOS_VIEW','Ver precios'),(39,'PRECIOS_CREATE','Crear precios'),(40,'PRECIOS_EDIT','Editar precios'),(41,'PRECIOS_DELETE','Eliminar precios'),(42,'UNIDADES_MEDIDA_VIEW','Ver unidades de medida'),(43,'UNIDADES_MEDIDA_CREATE','Crear unidades de medida'),(44,'UNIDADES_MEDIDA_EDIT','Editar unidades de medida'),(45,'UNIDADES_MEDIDA_DELETE','Eliminar unidades de medida'),(46,'INVENTARIO_ADJUST','Realizar ajustes de inventario'),(47,'TAREAS_ASSIGN','Asignar tareas a trabajadores'),(48,'USO_HERRAMIENTA_CREATE','Registrar uso de herramientas'),(49,'BACKUPS_CREATE','Crear respaldos'),(50,'BACKUPS_DELETE','Eliminar y restaurar respaldos'),(51,'AUDIT_VIEW','Ver bit├ícora de auditor├¡a'),(52,'RECOLECCION_VIEW','Ver recolecciones'),(53,'RECOLECCION_CREATE','Crear recolecciones'),(54,'RECOLECCION_EDIT','Editar recolecciones'),(55,'RECOLECCION_DELETE','Eliminar recolecciones'),(56,'RECOLECCION_COMPLETE','Completar recolecciones y registrar insumos'),(57,'AMPLIACION_VIEW','Ver ampliaciones de especies'),(58,'AMPLIACION_CREATE','Registrar ampliaciones de especies'),(59,'AMPLIACION_DELETE','Desactivar ampliaciones de especies');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permisos`
--

LOCK TABLES `rol_permisos` WRITE;
/*!40000 ALTER TABLE `rol_permisos` DISABLE KEYS */;
INSERT INTO `rol_permisos` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,45),(1,46),(1,47),(1,48),(1,49),(1,50),(1,51),(1,52),(1,53),(1,54),(1,55),(1,56),(1,57),(1,58),(1,59),(2,1),(2,2),(2,3),(2,5),(2,6),(2,7),(2,21),(2,22),(2,23),(2,25),(2,33),(3,1),(3,2),(3,3),(3,5),(3,9),(3,13),(3,17),(3,21);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Acceso total al sistema'),(2,'Trabajador','Acceso a inventario, plantas, clientes y ventas'),(3,'Prueba',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permisos`
--

LOCK TABLES `usuario_permisos` WRITE;
/*!40000 ALTER TABLE `usuario_permisos` DISABLE KEYS */;
INSERT INTO `usuario_permisos` VALUES (4,5),(4,9);
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
  `id_trabajador_ref` int(11) DEFAULT NULL,
  `estatus` enum('Activo','Inactivo','Bloqueado') DEFAULT 'Activo',
  `intentos_fallidos` int(11) DEFAULT 0,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`),
  UNIQUE KEY `id_trabajador_ref` (`id_trabajador_ref`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$yrUxEDB84M3WLn2pr9sLp.jUEI8KJc8XFAP51u4mmBThlW/1B84TK','admin@inecolara.gob.ve',NULL,1,NULL,'Activo',0,NULL,'2026-05-28 19:52:20'),(2,'Enyell Duarte','$2y$10$gtoiRRHj/dmlWgLD1NKdOO9rvKrWVHXKmxfygYAxmakuA1OPWZRI2','enyellduarte6@gmail.com','public/assets/uploads/avatars/avatar_1780000479_8df167d0.jpg',1,NULL,'Activo',0,NULL,'2026-05-28 20:34:39'),(4,'Prueba','$2y$10$WqEVSomd5Hja8VQ41Hu.Ve6VJeoYLb8Zkwvs06zte.rvTRfu3u0F.','prueba@correo.com','public/assets/uploads/avatars/avatar_1780000652_e31719bf.jpg',2,NULL,'Activo',0,NULL,'2026-05-28 20:37:32');
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

-- Dump completed on 2026-06-07 21:57:00
