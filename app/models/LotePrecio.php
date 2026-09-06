<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;

class LotePrecio extends Database implements ReadableInterface
{
    use ValidationTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        return self::getPreciosLotes();
    }

    public function getById(int $id): ?array
    {
        return self::calcularPrecioLote($id);
    }

    public static function calcularPrecioLote(int $idLote): ?array
    {
        $instance = new static();
        try {
            $stmt = $instance->db()->prepare("
                SELECT
                    l.id_lote,
                    l.costo_unitario,
                    l.porcentaje_ganancia,
                    l.cantidad_actual,
                    p.nombre_comun AS planta_nombre,
                    p.nombre_tecnico,
                    COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) AS total_insumos,
                    ROUND(
                        l.costo_unitario +
                        COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) +
                        (l.costo_unitario * l.porcentaje_ganancia / 100),
                    2) AS precio_final
                FROM lote l
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                LEFT JOIN registro_insumo ri ON l.id_lote = ri.id_lote
                WHERE l.id_lote = :id_lote AND l.activo = 1
                GROUP BY l.id_lote, l.costo_unitario, l.porcentaje_ganancia, l.cantidad_actual, p.nombre_comun, p.nombre_tecnico
            ");
            $stmt->execute([':id_lote' => $idLote]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Error en LotePrecio::calcularPrecioLote: ' . $e->getMessage());
            return null;
        }
    }

    public static function getDetalleInsumos(int $idLote): array
    {
        $instance = new static();
        try {
            $stmt = $instance->db()->prepare("
                SELECT
                    ri.id_registro_insumo,
                    ri.id_insumo,
                    ri.cantidad,
                    ri.costo_unitario,
                    ROUND(ri.costo_unitario * ri.cantidad, 2) AS subtotal,
                    ri.fecha_registro,
                    i.nombre_insumo,
                    u.simbolo,
                    CASE
                        WHEN ri.id_asignacion IS NOT NULL THEN 'Tarea'
                        ELSE 'Directo'
                    END AS origen
                FROM registro_insumo ri
                LEFT JOIN insumo i ON ri.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE ri.id_lote = :id_lote
                ORDER BY ri.fecha_registro DESC
            ");
            $stmt->execute([':id_lote' => $idLote]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en LotePrecio::getDetalleInsumos: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPreciosLotes(): array
    {
        $instance = new static();
        try {
            $sql = "SELECT
                        l.id_lote AS id,
                        l.id_lote,
                        l.id_planta,
                        l.costo_unitario,
                        l.porcentaje_ganancia,
                        l.cantidad_actual,
                        l.estado,
                        p.nombre_comun AS planta_nombre,
                        sp.nombre_especie AS especie_nombre,
                        COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) AS total_insumos,
                        ROUND(
                            l.costo_unitario +
                            COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) +
                            (l.costo_unitario * l.porcentaje_ganancia / 100),
                        2) AS precio_final,
                        ROUND(
                            (l.costo_unitario +
                            COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) +
                            (l.costo_unitario * l.porcentaje_ganancia / 100)) * l.cantidad_actual,
                        2) AS valor_total_inventario
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN especie sp ON p.id_especie = sp.id_especie
                    LEFT JOIN registro_insumo ri ON l.id_lote = ri.id_lote
                    WHERE l.activo = 1
                    GROUP BY l.id_lote, l.costo_unitario, l.porcentaje_ganancia, l.cantidad_actual,
                             l.estado, p.nombre_comun, sp.nombre_especie
                    ORDER BY p.nombre_comun ASC, l.fecha_siembra DESC";
            $stmt = $instance->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en LotePrecio::getPreciosLotes: ' . $e->getMessage());
            return [];
        }
    }

    public static function actualizarCostoUnitario(int $idLote, float $costoUnitario): bool
    {
        $instance = new static();
        try {
            $stmt = $instance->db()->prepare("
                UPDATE lote SET costo_unitario = :costo_unitario WHERE id_lote = :id_lote AND activo = 1
            ");
            $stmt->execute([':costo_unitario' => $costoUnitario, ':id_lote' => $idLote]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Error en LotePrecio::actualizarCostoUnitario: ' . $e->getMessage());
            return false;
        }
    }

    public static function actualizarPorcentajeGanancia(int $idLote, float $porcentaje): bool
    {
        $instance = new static();
        try {
            $stmt = $instance->db()->prepare("
                UPDATE lote SET porcentaje_ganancia = :porcentaje WHERE id_lote = :id_lote AND activo = 1
            ");
            $stmt->execute([':porcentaje' => $porcentaje, ':id_lote' => $idLote]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Error en LotePrecio::actualizarPorcentajeGanancia: ' . $e->getMessage());
            return false;
        }
    }
}
