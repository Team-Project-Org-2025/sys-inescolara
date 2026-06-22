<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Inventory extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getConsolidated(): array
    {
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            $sql = "
                SELECT id, nombre, tipo, stock, unidad, ubicacion, precio, item_id FROM (
                    -- Plantas
                    SELECT
                        CONCAT('PLANTA_', p.id_planta) AS id,
                        COALESCE(NULLIF(p.nombre_comun, ''), p.nombre_tecnico) AS nombre,
                        'Planta' AS tipo,
                        (SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta AND l2.activo = 1) AS stock,
                        'unidades' AS unidad,
                        NULL AS ubicacion,
                        (
                            SELECT c3.precio_final_sugerido
                            FROM calculo_precio c3
                            JOIN lote l3 ON c3.id_lote = l3.id_lote
                            WHERE l3.id_planta = p.id_planta AND l3.activo = 1
                            ORDER BY c3.fecha_calculo DESC, c3.id_calculo DESC
                            LIMIT 1
                        ) AS precio,
                        p.id_planta AS item_id
                    FROM plantas p
                    WHERE p.activo = 1

                    UNION ALL

                    -- Insumos
                    SELECT
                        CONCAT('INSUMO_', i.id_insumo),
                        i.nombre_insumo,
                        'Insumo',
                        i.stock_actual,
                        COALESCE(u.nombre_unidad_medida, 'unidades'),
                        NULL,
                        i.costo_unitario_actual,
                        i.id_insumo
                    FROM insumo i
                    LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                    WHERE i.activo = 1

                    UNION ALL

                    -- Herramientas
                    SELECT
                        CONCAT('HERRAMIENTA_', h.id_herramienta),
                        h.nombre_herramienta,
                        'Herramienta',
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        h.id_herramienta
                    FROM herramienta h
                    WHERE h.activo = 1

                    UNION ALL

                    -- Lotes
                    SELECT
                        CONCAT('LOTE_', l.id_lote),
                        CONCAT(COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))), CONCAT(' (Lote #', CAST(l.id_lote AS CHAR), ')')),
                        'Lote',
                        l.cantidad_actual,
                        'unidades',
                        u.nombre_ubicacion,
                        c2.precio_final_sugerido,
                        l.id_lote
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN calculo_precio c2 ON l.id_lote = c2.id_lote
                    WHERE l.activo = 1
                ) AS inv
                ORDER BY tipo, nombre
            ";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Inventory::getConsolidated: ' . $e->getMessage());
            return [];
        }
    }

}
