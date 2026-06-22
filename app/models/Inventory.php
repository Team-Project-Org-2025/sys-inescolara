<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Inventory extends Database
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_insumo'    => ['type' => null,      'required' => true],
        'id_trabajador'=> ['type' => null,      'required' => true],
        'tipo_ajuste'  => ['type' => null,      'required' => true],
        'cantidad'     => ['type' => 'cantidad','required' => true],
        'motivo'       => ['type' => null,      'required' => true],
        'fecha'        => ['type' => null,      'required' => true],
    ];

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

    public function getAdjustments(): array
    {
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            $sql = "
                SELECT
                    a.id_ajuste,
                    i.nombre_insumo,
                    CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS trabajador,
                    a.tipo_ajuste,
                    a.cantidad,
                    a.motivo,
                    a.fecha_ajuste
                FROM ajuste_inventario a
                LEFT JOIN insumo i ON a.id_insumo = i.id_insumo
                LEFT JOIN trabajadores t ON a.id_trabajador = t.id_trabajador
                ORDER BY a.fecha_ajuste DESC
                LIMIT 200
            ";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Inventory::getAdjustments: ' . $e->getMessage());
            return [];
        }
    }

    public function addAdjustment(int $idInsumo, int $idTrabajador, string $tipoAjuste, float $cantidad, string $motivo, string $fecha): bool
    {
        $this->validateData([
            'id_insumo' => $idInsumo,
            'id_trabajador' => $idTrabajador,
            'tipo_ajuste' => $tipoAjuste,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'fecha' => $fecha,
        ]);
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO ajuste_inventario (id_insumo, id_trabajador, tipo_ajuste, cantidad, motivo, fecha_ajuste)
                VALUES (:id_insumo, :id_trabajador, :tipo_ajuste, :cantidad, :motivo, :fecha)
            ");
            return $stmt->execute([
                ':id_insumo' => $idInsumo,
                ':id_trabajador' => $idTrabajador,
                ':tipo_ajuste' => $tipoAjuste,
                ':cantidad' => $cantidad,
                ':motivo' => $motivo,
                ':fecha' => $fecha,
            ]);
        } catch (\Throwable $e) {
            error_log('Error en Inventory::addAdjustment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateSupplyStock(int $idInsumo, float $cantidad, string $tipoAjuste): bool
    {
        try {
            $stmt = $this->db()->prepare("
                UPDATE insumo
                SET stock_actual = GREATEST(0, stock_actual + :cantidad * CASE WHEN :tipo = 'entrada' THEN 1 ELSE -1 END)
                WHERE id_insumo = :id
            ");
            return $stmt->execute([
                ':tipo' => $tipoAjuste,
                ':cantidad' => $cantidad,
                ':id' => $idInsumo,
            ]);
        } catch (\Throwable $e) {
            error_log('Error en Inventory::updateSupplyStock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
