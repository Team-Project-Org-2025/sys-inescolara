-- ============================================================
-- Migración: Crear tablas del módulo de Ornatos
-- Módulo: Gestión de Ornatos (C.U: 0.5)
-- ============================================================

CREATE TABLE IF NOT EXISTS `ornatos` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_ornatos` (
  `id_detalle_ornato` int(11) NOT NULL AUTO_INCREMENT,
  `id_ornato` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_ornato`),
  KEY `id_ornato` (`id_ornato`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `detalle_ornatos_ibfk_ornato` FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`) ON DELETE CASCADE,
  CONSTRAINT `detalle_ornatos_ibfk_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
