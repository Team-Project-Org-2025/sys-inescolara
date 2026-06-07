-- ==========================================
-- Módulo: Recolección de Semillas
-- ==========================================

-- 1. Tabla de recolecciones
CREATE TABLE IF NOT EXISTS `recoleccion_semillas` (
  `id_recoleccion`     INT AUTO_INCREMENT PRIMARY KEY,
  `id_trabajador`      INT NOT NULL,
  `id_ubicacion`       INT NOT NULL,
  `fecha_asignacion`   DATE NOT NULL,
  `fecha_recoleccion`  DATE DEFAULT NULL,
  `estatus`            VARCHAR(20) DEFAULT 'Pendiente',
  `observacion`        TEXT DEFAULT NULL,
  `id_insumo`          INT DEFAULT NULL,
  `cantidad_recolectada` DECIMAL(10,2) DEFAULT NULL,
  FOREIGN KEY (`id_trabajador`) REFERENCES `trabajadores`(`id_trabajador`),
  FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion`(`id_ubicacion`),
  FOREIGN KEY (`id_insumo`) REFERENCES `insumo`(`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas de recolección de semillas en sitios externos.';

-- 2. Permisos (ejecutar en BD SysInescolara-Seguridad)
-- INSERT IGNORE INTO permisos (id_permiso, codigo_permiso, descripcion) VALUES
-- (NULL, 'RECOLECCION_VIEW', 'Ver recolecciones de semillas'),
-- (NULL, 'RECOLECCION_CREATE', 'Crear recolecciones de semillas'),
-- (NULL, 'RECOLECCION_EDIT', 'Editar recolecciones de semillas'),
-- (NULL, 'RECOLECCION_DELETE', 'Eliminar recolecciones de semillas'),
-- (NULL, 'RECOLECCION_COMPLETE', 'Completar recolecciones y registrar insumos');
