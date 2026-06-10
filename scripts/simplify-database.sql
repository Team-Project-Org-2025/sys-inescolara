-- ============================================================
-- Migration: Simplificación de Base de Datos
-- Fecha: 2026-06-10
-- Descripción: Elimina tablas redundantes y sin uso
-- ============================================================

-- 1. Sincronizar datos de planta_precio_vigente a calculo_precio
UPDATE calculo_precio c
INNER JOIN planta_precio_vigente pv ON c.id_calculo = pv.id_calculo
SET c.vigente = 1;

-- 2. Eliminar planta_precio_vigente (redundante con calculo_precio.vigente)
DROP TABLE IF EXISTS planta_precio_vigente;

-- 3. Eliminar movimiento_insumo_detalle (hijo, sin datos, sin uso)
DROP TABLE IF EXISTS movimiento_insumo_detalle;

-- 4. Eliminar movimiento_insumo (padre, sin datos, sin uso)
DROP TABLE IF EXISTS movimiento_insumo;

-- 5. Eliminar asistencia (sin modelo, sin controlador, sin datos)
DROP TABLE IF EXISTS asistencia;

-- 6. Eliminar sesiones_activas en BD Seguridad (sin uso)
DROP TABLE IF EXISTS `SysInescolara-Seguridad`.`sesiones_activas`;
