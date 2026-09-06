-- ============================================================================
-- SYSINECOLARA — Base de Datos Vacía (solo estructura, sin datos)
-- Generado: 2026-09-01
-- Versión: 3.4 — Eliminada tabla `tareas`, nombre_tarea directo en asignar_tarea
-- ============================================================================
-- CAMBIOS vs esquema v3.2:
--  1. ELIMINADOS de `lote`: costo_mano_obra, costo_total_insumo,
--     costo_agua_lote, precio_final_sugerido (calculados en código)
--  2. MANTENIDO en `lote`: costo_unitario (precio base por planta),
--     porcentaje_ganancia (configurable por lote)
--  3. ELIMINADA tabla `calculo_precio` → ya no existe
--  4. ELIMINADA tabla `calculo_precio_detalle` → ya no existe
--  5. ELIMINADA tabla `consumo_insumos` → unificada en `registro_insumo`
--  6. ELIMINADA tabla `lote_insumo` → unificada en `registro_insumo`
--  7. ELIMINADA tabla `ajuste_inventario` → usa bitácora de auditoría
--  8. NUEVA tabla `registro_insumo` → unifica insumos directos y vía tareas
--  9. Precio final calculado en código: costo_unitario + insumos + ganancia
-- 10. Agua registra como insumo normal en tabla `insumo`
-- 11. Renombrados id_trabajador → id_usuario en todas las tablas
-- 12. Renombrado id_trabajador_gestor → id_usuario_gestor en movimiento_planta
-- 13. NUEVAS tablas `estado`, `categoria`, `origen` como catálogos reutilizables
-- 14. `lote` ahora usa FKs id_estado, id_categoria, id_origen (no más VARCHAR)
-- 15. ELIMINADA tabla `tareas` → nombre_tarea y descripcion directo en asignar_tarea
-- 16. ELIMINADO `id_lote` de `asignar_tarea` → las tareas ya no dependen de un lote específico
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "-04:00";
SET NAMES utf8mb4;

-- ============================================================================
-- BASE DE DATOS DE NEGOCIO: sysinescolara
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `sysinescolara`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `sysinescolara`;

-- --------------------------------------------------------------------------
-- 1. Catálogos base
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `especie` (
  `id_especie`       INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre_especie`   VARCHAR(150) NOT NULL,
  `descripcion`      TEXT         DEFAULT NULL,
  `activo`           TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_especie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo botánico.';

CREATE TABLE IF NOT EXISTS `ubicacion` (
  `id_ubicacion`     INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre_ubicacion` VARCHAR(100) NOT NULL,
  `descripcion`      VARCHAR(255) DEFAULT NULL,
  `zona`             VARCHAR(50)  DEFAULT NULL,
  `activo`           TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Espacios físicos del vivero.';

CREATE TABLE IF NOT EXISTS `unidad_medida` (
  `id_unidad_medida`     INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre_unidad_medida` VARCHAR(50) NOT NULL,
  `simbolo`              VARCHAR(10) DEFAULT NULL,
  `activo`               TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad_medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unidades de medida para insumos.';

CREATE TABLE IF NOT EXISTS `estado` (
  `id_estado` INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre`    VARCHAR(30) NOT NULL,
  `activo`    TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de estados (reutilizable).';

INSERT INTO `estado` (`id_estado`, `nombre`, `activo`) VALUES
  (5, 'vivo', 1),
  (6, 'cuarentena', 1),
  (7, 'muerto', 1);

CREATE TABLE IF NOT EXISTS `categoria` (
  `id_categoria` INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(30) NOT NULL,
  `activo`       TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de categorías de lote.';

INSERT INTO `categoria` (`id_categoria`, `nombre`, `activo`) VALUES
  (1, 'germinado', 1),
  (2, 'plántula', 1),
  (3, 'adulto', 1);

CREATE TABLE IF NOT EXISTS `origen` (
  `id_origen` INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre`    VARCHAR(30) NOT NULL,
  `activo`    TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_origen`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de orígenes (reutilizable).';

INSERT INTO `origen` (`id_origen`, `nombre`, `activo`) VALUES
  (1, 'Siembra', 1),
  (2, 'Ampliación', 1),
  (3, 'Donación', 1),
  (4, 'Compra', 1);

-- --------------------------------------------------------------------------
-- 2. Plantas y Lotes
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `plantas` (
  `id_planta`       INT(11)      NOT NULL AUTO_INCREMENT,
  `id_especie`      INT(11)      DEFAULT NULL,
  `nombre_tecnico`  VARCHAR(150) DEFAULT '',
  `nombre_comun`    VARCHAR(150) DEFAULT NULL,
  `cantidad_total`  INT(11)      NOT NULL DEFAULT 0,
  `imagen`          VARCHAR(255) DEFAULT NULL,
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_planta`),
  KEY `id_especie` (`id_especie`),
  CONSTRAINT `fk_planta_especie` FOREIGN KEY (`id_especie`) REFERENCES `especie` (`id_especie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de plantas.';

CREATE TABLE IF NOT EXISTS `lote` (
  `id_lote`              INT(11)       NOT NULL AUTO_INCREMENT,
  `id_planta`            INT(11)       NOT NULL,
  `id_ubicacion`         INT(11)       DEFAULT NULL,
  `fecha_siembra`        DATE          NOT NULL,
  `cantidad_inicial`     INT(11)       NOT NULL,
  `cantidad_actual`      INT(11)       NOT NULL,
  `costo_unitario`       DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Precio base por planta',
  `id_estado`            INT(11)       DEFAULT NULL,
  `id_categoria`         INT(11)       DEFAULT NULL,
  `id_origen`            INT(11)       DEFAULT NULL,
  `observacion`          VARCHAR(255)  DEFAULT NULL,
  `imagen`               VARCHAR(255)  DEFAULT NULL,
  `activo`               TINYINT(1)    NOT NULL DEFAULT 1,
  `porcentaje_ganancia`  DECIMAL(5,2)  NOT NULL DEFAULT 30.00 COMMENT '% ganancia configurable por lote',
  PRIMARY KEY (`id_lote`),
  KEY `id_planta`    (`id_planta`),
  KEY `id_ubicacion` (`id_ubicacion`),
  KEY `id_estado`    (`id_estado`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_origen`    (`id_origen`),
  CONSTRAINT `fk_lote_planta`    FOREIGN KEY (`id_planta`)    REFERENCES `plantas`    (`id_planta`),
  CONSTRAINT `fk_lote_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`),
  CONSTRAINT `fk_lote_estado`    FOREIGN KEY (`id_estado`)    REFERENCES `estado`     (`id_estado`),
  CONSTRAINT `fk_lote_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  CONSTRAINT `fk_lote_origen`    FOREIGN KEY (`id_origen`)    REFERENCES `origen`     (`id_origen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unidad de producción. Precio calculado en código.';

-- --------------------------------------------------------------------------
-- 3. Insumos y Herramientas
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `insumo` (
  `id_insumo`            INT(11)       NOT NULL AUTO_INCREMENT,
  `id_unidad_medida`     INT(11)       NOT NULL,
  `nombre_insumo`        VARCHAR(150)  NOT NULL,
  `categoria`            VARCHAR(50)   DEFAULT NULL,
  `stock_actual`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `costo_unitario_actual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `activo`               TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_insumo`),
  KEY `id_unidad_medida` (`id_unidad_medida`),
  CONSTRAINT `fk_insumo_unidad` FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidad_medida` (`id_unidad_medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventario de insumos.';

CREATE TABLE IF NOT EXISTS `herramienta` (
  `id_herramienta`             INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre_herramienta`         VARCHAR(150) NOT NULL,
  `cantidad`                   INT(11)      NOT NULL DEFAULT 1,
  `tipo`                       VARCHAR(50)  DEFAULT NULL,
  `estado`                     VARCHAR(30)  NOT NULL DEFAULT 'disponible',
  `fecha_adquisicion`          DATE         DEFAULT NULL,
  `fecha_ultimo_mantenimiento` DATE         DEFAULT NULL,
  `observacion`                TEXT         DEFAULT NULL,
  `activo`                     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Herramientas con ciclo de vida propio.';

-- --------------------------------------------------------------------------
-- 4. Clientes y Proveedores
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente`          INT(11)      NOT NULL AUTO_INCREMENT,
  `tipo_cedula_cliente` VARCHAR(1)   DEFAULT NULL COMMENT 'V, E, J, G, P',
  `cedula_cliente`      VARCHAR(10)  DEFAULT NULL COMMENT 'Solo dígitos',
  `nombre_cliente`      VARCHAR(100) NOT NULL COMMENT 'Nombres',
  `apellido_cliente`    VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Apellidos',
  `contacto_cliente`    VARCHAR(250) DEFAULT NULL,
  `activo`              TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de clientes.';

CREATE TABLE IF NOT EXISTS `proveedores` (
  `id_proveedor`       INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre_proveedor`   VARCHAR(100) NOT NULL,
  `rif_proveedor`      VARCHAR(20) NOT NULL,
  `contacto_vendedor`  VARCHAR(100) DEFAULT NULL,
  `telefono_proveedor` VARCHAR(20)  DEFAULT NULL,
  `activo`             TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `uq_proveedor_rif` (`rif_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de proveedores.';

-- --------------------------------------------------------------------------
-- 5. Asignar Tareas
-- Nota: id_usuario referencia SysInescolara-Seguridad.usuarios.id_usuario
-- El nombre de la tarea se guarda directamente en asignar_tarea
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `asignar_tarea` (
  `id_asignacion`     INT(11)       NOT NULL AUTO_INCREMENT,
  `id_usuario`        INT(11)       NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `nombre_tarea`      VARCHAR(100)  NOT NULL COMMENT 'Nombre de la tarea',
  `descripcion`       TEXT          DEFAULT NULL COMMENT 'Descripcion opcional de la tarea',
  `fecha_asignacion`  DATE          NOT NULL,
  `fecha_cumplimiento` DATE         DEFAULT NULL,
  `estatus_tarea`     VARCHAR(20)   NOT NULL DEFAULT 'pendiente',
  `horas_dedicadas`   DECIMAL(5,2)  DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Asignacion de tareas a usuarios.';

CREATE TABLE IF NOT EXISTS `registro_insumo` (
  `id_registro_insumo` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_lote`            INT(11)       NOT NULL,
  `id_insumo`          INT(11)       NOT NULL,
  `id_asignacion`      INT(11)       DEFAULT NULL COMMENT 'NULL = directo, NO NULL = vía tarea',
  `cantidad`           DECIMAL(10,2) NOT NULL,
  `costo_unitario`     DECIMAL(10,2) NOT NULL,
  `fecha_registro`     DATE          NOT NULL,
  PRIMARY KEY (`id_registro_insumo`),
  KEY `idx_registro_lote`       (`id_lote`),
  KEY `idx_registro_insumo`     (`id_insumo`),
  KEY `idx_registro_asignacion` (`id_asignacion`),
  CONSTRAINT `fk_registro_lote`       FOREIGN KEY (`id_lote`)       REFERENCES `lote`          (`id_lote`),
  CONSTRAINT `fk_registro_insumo`     FOREIGN KEY (`id_insumo`)     REFERENCES `insumo`        (`id_insumo`),
  CONSTRAINT `fk_registro_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignar_tarea` (`id_asignacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro unificado de insumos: directo en lote o vía tarea.';

CREATE TABLE IF NOT EXISTS `uso_herramienta` (
  `id_uso`                      INT(11)     NOT NULL AUTO_INCREMENT,
  `id_asignacion`               INT(11)     NOT NULL,
  `id_herramienta`              INT(11)     NOT NULL,
  `fecha_uso`                   DATE        NOT NULL,
  `observacion`                 TEXT        DEFAULT NULL,
  `estado_herramienta_post_uso` VARCHAR(30) NOT NULL DEFAULT 'ok',
  PRIMARY KEY (`id_uso`),
  KEY `id_asignacion` (`id_asignacion`),
  KEY `id_herramienta` (`id_herramienta`),
  CONSTRAINT `fk_uso_asignacion`  FOREIGN KEY (`id_asignacion`)  REFERENCES `asignar_tarea`  (`id_asignacion`),
  CONSTRAINT `fk_uso_herramienta` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta`    (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Herramienta ligada a una tarea.';

-- --------------------------------------------------------------------------
-- 6. Compras
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `compra` (
  `id_compra`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_proveedor`       INT(11)       NOT NULL,
  `fecha_compra`       DATE          NOT NULL,
  `fecha_recepcion`    DATE          DEFAULT NULL COMMENT 'Fecha en que se recibió físicamente',
  `tipo_comprobante`   VARCHAR(30)   DEFAULT 'Factura',
  `numero_comprobante` VARCHAR(50)   DEFAULT NULL,
  `subtotal`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `iva`                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado`             ENUM('pendiente','recibida','pagada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observacion`        TEXT          DEFAULT NULL,
  `activo`             TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_compra`),
  KEY `idx_compra_proveedor` (`id_proveedor`),
  KEY `idx_compra_estado`    (`estado`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compra_detalle` (
  `id_detalle`       INT(11)       NOT NULL AUTO_INCREMENT,
  `id_compra`        INT(11)       NOT NULL,
  `id_insumo`        INT(11)       DEFAULT NULL COMMENT 'FK real a insumo',
  `id_herramienta`   INT(11)       DEFAULT NULL COMMENT 'FK real a herramienta',
  `id_planta`        INT(11)       DEFAULT NULL COMMENT 'FK real a plantas',
  `categoria_lote`   VARCHAR(30)   DEFAULT 'germinado',
  `id_ubicacion`     INT(11)       DEFAULT NULL,
  `cantidad`         DECIMAL(10,2) NOT NULL,
  `costo_unitario`   DECIMAL(10,2) NOT NULL,
  `subtotal`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_detalle_compra`  (`id_compra`),
  KEY `idx_detalle_insumo`  (`id_insumo`),
  KEY `idx_detalle_herramienta` (`id_herramienta`),
  KEY `idx_detalle_planta`  (`id_planta`),
  CONSTRAINT `fk_detalle_compra`      FOREIGN KEY (`id_compra`)      REFERENCES `compra`      (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_insumo`      FOREIGN KEY (`id_insumo`)      REFERENCES `insumo`      (`id_insumo`),
  CONSTRAINT `fk_detalle_herramienta` FOREIGN KEY (`id_herramienta`) REFERENCES `herramienta` (`id_herramienta`),
  CONSTRAINT `fk_detalle_planta`      FOREIGN KEY (`id_planta`)      REFERENCES `plantas`     (`id_planta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 7. Ornatos
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ornatos` (
  `id_ornato`     INT(11)       NOT NULL AUTO_INCREMENT,
  `id_cliente`    INT(11)       NOT NULL,
  `tipo_ornato`   ENUM('Venta','Donacion') NOT NULL DEFAULT 'Venta',
  `descripcion`   TEXT          DEFAULT NULL,
  `ubicacion`     VARCHAR(255)  DEFAULT NULL,
  `monto_total`   DECIMAL(10,2) DEFAULT 0.00,
  `fecha`         DATE          NOT NULL,
  `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_ornato`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `fk_ornato_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_ornatos` (
  `id_detalle_ornato` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_ornato`         INT(11)       NOT NULL,
  `id_lote`           INT(11)       NOT NULL,
  `cantidad`          INT(11)       NOT NULL,
  `precio_unitario`   DECIMAL(10,2) DEFAULT NULL,
  `sub_total`         DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_ornato`),
  KEY `id_ornato` (`id_ornato`),
  KEY `id_lote`   (`id_lote`),
  CONSTRAINT `fk_detornato_ornato` FOREIGN KEY (`id_ornato`) REFERENCES `ornatos` (`id_ornato`) ON DELETE CASCADE,
  CONSTRAINT `fk_detornato_lote`   FOREIGN KEY (`id_lote`)   REFERENCES `lote`    (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------------
-- 8. Recolección de Semillas
-- Nota: id_usuario referencia SysInescolara-Seguridad.usuarios.id_usuario
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `recoleccion_semillas` (
  `id_recoleccion`   INT(11)     NOT NULL AUTO_INCREMENT,
  `id_usuario`       INT(11)     NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `id_ubicacion`     INT(11)     NOT NULL,
  `fecha_asignacion` DATE        NOT NULL,
  `fecha_recoleccion` DATE       DEFAULT NULL,
  `estatus`          VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
  `observacion`      TEXT        DEFAULT NULL,
  `activo`           TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_recoleccion`),
  KEY `idx_recoleccion_usuario`  (`id_usuario`),
  KEY `idx_recoleccion_ubicacion` (`id_ubicacion`),
  KEY `idx_recoleccion_estatus`  (`estatus`),
  CONSTRAINT `fk_recoleccion_ubicacion` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recoleccion_semillas_detalle` (
  `id_recoleccion_detalle` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_recoleccion`         INT(11)       NOT NULL,
  `planta_origen`          VARCHAR(150)  DEFAULT NULL,
  `nombre_semilla`         VARCHAR(100)  NOT NULL,
  `id_unidad_medida`       INT(11)       NOT NULL,
  `id_insumo`              INT(11)       DEFAULT NULL COMMENT 'FK al insumo generado al procesar',
  `cantidad`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_recoleccion_detalle`),
  KEY `idx_detalle_recoleccion` (`id_recoleccion`),
  KEY `idx_detalle_insumo`      (`id_insumo`),
  CONSTRAINT `fk_detrecoleccion_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion_semillas` (`id_recoleccion`) ON DELETE CASCADE,
  CONSTRAINT `fk_detrecoleccion_insumo`      FOREIGN KEY (`id_insumo`)      REFERENCES `insumo`              (`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 9. Ventas
-- Nota: id_usuario referencia SysInescolara-Seguridad.usuarios.id_usuario
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `venta` (
  `id_venta`         INT(11)       NOT NULL AUTO_INCREMENT,
  `referencia`       VARCHAR(30)   NOT NULL,
  `id_cliente`       INT(11)       NOT NULL,
  `id_usuario`       INT(11)       NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `tipo_venta`       ENUM('contado','credito') NOT NULL DEFAULT 'contado',
  `estado`           ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
  `iva_porcentaje`   DECIMAL(5,2)  NOT NULL DEFAULT 16.00,
  `fecha_venta`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` DATE         DEFAULT NULL,
  `observaciones`    TEXT          DEFAULT NULL,
  `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uq_venta_referencia` (`referencia`),
  KEY `id_cliente`  (`id_cliente`),
  KEY `id_usuario`  (`id_usuario`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `id_detalle_venta` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_venta`         INT(11)       NOT NULL,
  `id_lote`           INT(11)       DEFAULT NULL COMMENT 'FK → lote.id_lote (opcional)',
  `cantidad`         INT(11)       NOT NULL,
  `precio_unitario`  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `id_venta` (`id_venta`),
  KEY `id_lote`  (`id_lote`),
  CONSTRAINT `fk_detventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  CONSTRAINT `fk_detventa_lote`  FOREIGN KEY (`id_lote`)  REFERENCES `lote`  (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pago_venta` (
  `id_pago`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_venta`       INT(11)       NOT NULL,
  `metodo`         ENUM('efectivo','transferencia','punto','pago_movil','otro') NOT NULL,
  `monto`          DECIMAL(10,2) NOT NULL,
  `referencia`     VARCHAR(50)   DEFAULT NULL,
  `fecha_pago`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `estado_pago`    ENUM('registrado','confirmado','rechazado') NOT NULL DEFAULT 'registrado',
  `banco`          VARCHAR(100)  DEFAULT NULL,
  `id_usuario`     INT(11)       DEFAULT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `observaciones`  TEXT          DEFAULT NULL,
  `created_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  KEY `id_venta`  (`id_venta`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_pagoventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------------
-- 10. Trazabilidad y Mermas
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `trazabilidad` (
  `id_trazabilidad` INT(11)     NOT NULL AUTO_INCREMENT,
  `id_lote`         INT(11)     NOT NULL,
  `cantidad`        INT(11)     NOT NULL DEFAULT 1,
  `estado_salud`    VARCHAR(30) NOT NULL,
  `observacion`     TEXT        DEFAULT NULL,
  `activo`          TINYINT(1)  NOT NULL DEFAULT 1,
  `fecha_registro`  DATE        NOT NULL,
  PRIMARY KEY (`id_trazabilidad`),
  KEY `id_lote` (`id_lote`),
  CONSTRAINT `fk_trazabilidad_lote` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial fitosanitario por lote.';

CREATE TABLE IF NOT EXISTS `mermas_historico` (
  `id_merma`           INT(11)       NOT NULL AUTO_INCREMENT,
  `id_trazabilidad`    INT(11)       NOT NULL,
  `id_lote`            INT(11)       NOT NULL,
  `cantidad`           INT(11)       NOT NULL,
  `motivo`             ENUM('plaga','dano_mecanico','factor_climatico','enfermedad','otro') NOT NULL,
  `descripcion`        TEXT          DEFAULT NULL,
  `fecha_merma`        DATE          NOT NULL,
  `impacto_economico`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `id_usuario_registra` INT(11)      DEFAULT NULL,
  `activo`             TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_merma`),
  KEY `id_trazabilidad` (`id_trazabilidad`),
  KEY `id_lote`         (`id_lote`),
  KEY `fk_merma_usuario` (`id_usuario_registra`),
  CONSTRAINT `fk_merma_trazabilidad` FOREIGN KEY (`id_trazabilidad`) REFERENCES `trazabilidad` (`id_trazabilidad`),
  CONSTRAINT `fk_merma_lote`         FOREIGN KEY (`id_lote`)         REFERENCES `lote`         (`id_lote`),
  CONSTRAINT `fk_merma_usuario`      FOREIGN KEY (`id_usuario_registra`) REFERENCES `SysInescolara-Seguridad`.`usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 11. Movimiento de Plantas
-- Nota: id_usuario_gestor referencia Seguridad.usuarios.id_usuario
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `movimiento_planta` (
  `id_movimiento_planta` INT(11)     NOT NULL AUTO_INCREMENT,
  `tipo_movimiento`      VARCHAR(30) NOT NULL COMMENT 'venta, ornato, donacion, intercambio',
  `id_cliente`           INT(11)     DEFAULT NULL,
  `id_usuario_gestor`    INT(11)     NOT NULL COMMENT 'FK → Seguridad.usuarios.id_usuario',
  `fecha_movimiento`     DATE        NOT NULL,
  `observacion`          TEXT        DEFAULT NULL,
  `activo`               TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_movimiento_planta`),
  KEY `id_cliente`          (`id_cliente`),
  KEY `id_usuario_gestor`   (`id_usuario_gestor`),
  KEY `idx_mp_activo`       (`activo`),
  CONSTRAINT `fk_movplanta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unifica venta, ornato, donación e intercambio de plantas.';

CREATE TABLE IF NOT EXISTS `movimiento_planta_detalle` (
  `id_detalle_mov_planta` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_movimiento_planta`  INT(11)       NOT NULL,
  `id_lote`               INT(11)       NOT NULL,
  `tipo`                  ENUM('entrada','salida') NOT NULL DEFAULT 'salida',
  `cantidad`              INT(11)       NOT NULL,
  `precio_unitario`       DECIMAL(10,2) DEFAULT NULL,
  `sub_total`             DECIMAL(10,2) DEFAULT NULL,
  `activo`                TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle_mov_planta`),
  KEY `id_movimiento_planta` (`id_movimiento_planta`),
  KEY `id_lote`              (`id_lote`),
  KEY `idx_mpd_activo`       (`activo`),
  CONSTRAINT `fk_detmovplanta_mov`  FOREIGN KEY (`id_movimiento_planta`) REFERENCES `movimiento_planta` (`id_movimiento_planta`),
  CONSTRAINT `fk_detmovplanta_lote` FOREIGN KEY (`id_lote`)              REFERENCES `lote`              (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle por lote del movimiento de plantas.';

-- --------------------------------------------------------------------------
-- 12. Cuentas por Pagar
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cuentas_pagar` (
  `id_cuenta_pagar`   INT(11)       NOT NULL AUTO_INCREMENT,
  `id_compra`         INT(11)       NOT NULL,
  `monto_total`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_vencimiento` DATE          DEFAULT NULL,
  `estado`            ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
  `observacion`       TEXT          DEFAULT NULL,
  `activo`            TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cuenta_pagar`),
  KEY `id_compra` (`id_compra`),
  CONSTRAINT `fk_cuentapagar_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pago_compra` (
  `id_pago_compra` INT(11)       NOT NULL AUTO_INCREMENT,
  `id_cuenta_pagar` INT(11)       NOT NULL,
  `monto`          DECIMAL(10,2) NOT NULL,
  `tipo_pago`      VARCHAR(30)   DEFAULT NULL,
  `referencia`     VARCHAR(100)  DEFAULT NULL,
  `fecha_pago`     DATE          DEFAULT NULL,
  `observacion`    TEXT          DEFAULT NULL,
  `estado`         ENUM('registrado','confirmado','anulado') NOT NULL DEFAULT 'registrado',
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago_compra`),
  KEY `id_cuenta_pagar` (`id_cuenta_pagar`),
  CONSTRAINT `fk_pagocompra_cuenta` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- BASE DE DATOS DE SEGURIDAD: SysInescolara-Seguridad
-- ============================================================================
-- La tabla `usuarios` ahora incluye los campos de trabajador:
--   nombre_trabajador, apellido_trabajador, cedula_trabajador,
--   telefono_trabajador, cargo
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `SysInescolara-Seguridad`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `SysInescolara-Seguridad`;

CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol`         INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre_rol`     VARCHAR(30) DEFAULT NULL,
  `descripcion_rol` TEXT        DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permisos` (
  `id_permiso`        INT(11)     NOT NULL AUTO_INCREMENT,
  `codigo_permiso`    VARCHAR(50) DEFAULT NULL,
  `descripcion_permiso` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permiso_codigo` (`codigo_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rol_permisos` (
  `id_rol`     INT(11) NOT NULL,
  `id_permiso` INT(11) NOT NULL,
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `id_permiso` (`id_permiso`),
  CONSTRAINT `fk_rolpermiso_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `roles`    (`id_rol`),
  CONSTRAINT `fk_rolpermiso_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario`          INT(11)     NOT NULL AUTO_INCREMENT,
  `nombre_usuario`      VARCHAR(50) NOT NULL,
  `password_hash`       VARCHAR(255) NOT NULL,
  `correo_electronico`  VARCHAR(100) DEFAULT NULL,
  `avatar`              VARCHAR(255) DEFAULT NULL,
  `id_rol`              INT(11)     DEFAULT NULL,
  `estatus`             ENUM('Activo','Inactivo','Bloqueado') DEFAULT 'Activo',
  `intentos_fallidos`   INT(11)     DEFAULT 0,
  `ultimo_acceso`       TIMESTAMP   NULL DEFAULT NULL,
  `created_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre_trabajador`   VARCHAR(100) DEFAULT NULL,
  `apellido_trabajador` VARCHAR(100) DEFAULT NULL,
  `cedula_trabajador`   VARCHAR(20)  DEFAULT NULL,
  `telefono_trabajador` VARCHAR(20)  DEFAULT NULL,
  `cargo`               VARCHAR(50)  DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuario_nombre`    (`nombre_usuario`),
  UNIQUE KEY `uq_usuario_correo`    (`correo_electronico`),
  UNIQUE KEY `uq_usuario_cedula`    (`cedula_trabajador`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios del sistema. Incluye datos de trabajador.';

CREATE TABLE IF NOT EXISTS `usuario_permisos` (
  `id_usuario` INT(11) NOT NULL,
  `id_permiso` INT(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_permiso`),
  KEY `id_permiso` (`id_permiso`),
  CONSTRAINT `fk_usuariopermiso_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuariopermiso_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auditoria_logs` (
  `id_log`               INT(11)      NOT NULL AUTO_INCREMENT,
  `id_usuario`           INT(11)      DEFAULT NULL,
  `accion`               VARCHAR(50)  DEFAULT NULL,
  `tabla_afectada`       VARCHAR(50)  DEFAULT NULL,
  `id_registro_afectado` INT(11)      DEFAULT NULL,
  `valor_anterior`       LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (JSON_VALID(`valor_anterior`)),
  `valor_nuevo`          LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (JSON_VALID(`valor_nuevo`)),
  `endpoint_solicitado`  VARCHAR(255) DEFAULT NULL,
  `fecha_accion`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id_notificacion` INT(11)      NOT NULL AUTO_INCREMENT,
  `id_usuario`      INT(11)      DEFAULT NULL,
  `titulo`          VARCHAR(255) DEFAULT NULL,
  `mensaje`         TEXT         DEFAULT NULL,
  `tipo`            VARCHAR(50)  DEFAULT NULL,
  `leida`           TINYINT(1)   DEFAULT 0,
  `link`            VARCHAR(500) DEFAULT NULL,
  `fecha_creacion`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notificacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11)      DEFAULT NULL,
  `token`      VARCHAR(64)  DEFAULT NULL,
  `correo`     VARCHAR(100) DEFAULT NULL,
  `expira_en`  DATETIME     DEFAULT NULL,
  `usado`      TINYINT(1)   DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_passwordreset_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
