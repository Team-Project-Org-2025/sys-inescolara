<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class PriceCalculation extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_lote'              => ['type' => null,   'required' => true],
        'costo_mano_obra'      => ['type' => 'precio','required' => true],
        'costo_total_insumo'   => ['type' => 'precio','required' => true],
        'porcentaje_ganancia'  => ['type' => 'precio','required' => true],
        'precio_final_sugerido'=> ['type' => 'precio','required' => true],
        'fecha_calculo'        => ['type' => null,   'required' => true],
    ];

    private ?int $_lastInsertId = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getLastInsertId(): ?int
    {
        return $this->_lastInsertId ?? parent::getLastInsertId();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        c.id_calculo AS id, c.id_lote, c.costo_mano_obra, c.costo_total_insumo,
                        c.porcentaje_ganancia,
                        c.precio_final_sugerido, c.fecha_calculo,
                        l.cantidad_actual,
                        p.nombre_comun AS planta_nombre,
                        p.id_planta
                    FROM calculo_precio c
                    LEFT JOIN lote l ON c.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    ORDER BY c.fecha_calculo DESC, c.id_calculo DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en PriceCalculation::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, l.cantidad_actual, p.nombre_comun AS planta_nombre, p.id_planta
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE c.id_calculo = :id
            ");
            // c.* incluye costo_agua_lote; se ignora (columna mantenida por compatibilidad BD)
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en PriceCalculation::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM calculo_precio WHERE id_calculo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM calculo_precio WHERE id_calculo = :id");
            $stmt->execute([':id' => $id]);
            return $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Error en PriceCalculation::delete: ' . $e->getMessage());
            return false;
        }
    }

    public function existsByBatch(int $idLote, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM calculo_precio WHERE id_lote = :id_lote";
        $params = [':id_lote' => $idLote];
        if ($excludeId !== null) {
            $sql .= " AND id_calculo != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    public function getBatchIdsWithPrices(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT id_lote FROM calculo_precio");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function add(
        int $idLote,
        float $costoManoObra,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo
    ): bool {
        $this->validateData([
            'id_lote' => $idLote,
            'costo_mano_obra' => $costoManoObra,
            'costo_total_insumo' => $costoTotalInsumo,
            'porcentaje_ganancia' => $porcentajeGanancia,
            'precio_final_sugerido' => $precioFinalSugerido,
            'fecha_calculo' => $fechaCalculo,
        ]);
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO calculo_precio
                    (id_lote, costo_mano_obra, costo_total_insumo,
                     porcentaje_ganancia, precio_final_sugerido,
                     fecha_calculo)
                VALUES
                    (:id_lote, :costo_mano_obra, :costo_total_insumo,
                     :porcentaje_ganancia, :precio_final_sugerido,
                     :fecha_calculo)
            ");
            $stmt->execute([
                ':id_lote' => $idLote,
                ':costo_mano_obra' => $costoManoObra,
                ':costo_total_insumo' => $costoTotalInsumo,
                ':porcentaje_ganancia' => $porcentajeGanancia,
                ':precio_final_sugerido' => $precioFinalSugerido,
                ':fecha_calculo' => $fechaCalculo,
            ]);

            $this->_lastInsertId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Error en PriceCalculation::add: ' . $e->getMessage());
            return false;
        }
    }

    public function update(
        int $id,
        int $idLote,
        float $costoManoObra,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo
    ): bool {
        $this->validateData([
            'id_lote' => $idLote,
            'costo_mano_obra' => $costoManoObra,
            'costo_total_insumo' => $costoTotalInsumo,
            'porcentaje_ganancia' => $porcentajeGanancia,
            'precio_final_sugerido' => $precioFinalSugerido,
            'fecha_calculo' => $fechaCalculo,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception('No existe el cálculo de precio solicitado para modificar.');
        }
        $stmt = $this->db->prepare("
            UPDATE calculo_precio
            SET id_lote = :id_lote,
                costo_mano_obra = :costo_mano_obra,
                costo_total_insumo = :costo_total_insumo,
                porcentaje_ganancia = :porcentaje_ganancia,
                precio_final_sugerido = :precio_final_sugerido,
                fecha_calculo = :fecha_calculo
            WHERE id_calculo = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':id_lote' => $idLote,
            ':costo_mano_obra' => $costoManoObra,
            ':costo_total_insumo' => $costoTotalInsumo,
            ':porcentaje_ganancia' => $porcentajeGanancia,
            ':precio_final_sugerido' => $precioFinalSugerido,
            ':fecha_calculo' => $fechaCalculo,
        ]);
    }

    public function getLotesByPlanta(int $idPlanta, ?string $categoria = null): array
    {
        try {
            $sql = "SELECT
                        l.id_lote,
                        l.cantidad_actual,
                        l.categoria,
                        COALESCE(cp.costo_mano_obra, 0) AS costo_mano_obra,
                        COALESCE(cp.costo_total_insumo, 0) AS costo_insumos,
                        COALESCE(cp.costo_agua_lote, 0) AS costo_agua,
                        COALESCE(cp.costo_mano_obra, 0) + COALESCE(cp.costo_total_insumo, 0) + COALESCE(cp.costo_agua_lote, 0) AS costo_total_lote,
                        CASE
                            WHEN cp.id_calculo IS NOT NULL THEN 1
                            ELSE 0
                        END AS tiene_calculo,
                        p.nombre_comun AS planta_nombre
                    FROM lote l
                    JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN calculo_precio cp ON cp.id_lote = l.id_lote
                    WHERE l.id_planta = :id_planta AND l.activo = 1";
            $params = [':id_planta' => $idPlanta];
            if ($categoria !== null) {
                $sql .= " AND l.categoria = :categoria";
                $params[':categoria'] = $categoria;
            }
            $sql .= " ORDER BY l.fecha_siembra DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $lotes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            // Para lotes sin calculo_precio, calcular costo desde consumos
            foreach ($lotes as &$lote) {
                $lote['costo_mano_obra'] = (float)$lote['costo_mano_obra'];
                $lote['costo_insumos'] = (float)$lote['costo_insumos'];
                $lote['costo_agua'] = (float)$lote['costo_agua'];
                if ((int)$lote['tiene_calculo'] === 0) {
                    $insumos = $this->getCostoInsumosByLote((int)$lote['id_lote']);
                    $lote['costo_insumos'] = $insumos;
                    $lote['costo_mano_obra'] = 0;
                    $lote['costo_agua'] = 0;
                }
                $lote['costo_total_lote'] = $lote['costo_mano_obra'] + $lote['costo_insumos'] + $lote['costo_agua'];
            }
            return $lotes;
        } catch (\Throwable $e) {
            error_log('Error en PriceCalculation::getLotesByPlanta: ' . $e->getMessage());
            return [];
        }
    }

    public function calcularCostoPorPlanta(int $idPlanta, ?string $categoria = null): array
    {
        $lotes = $this->getLotesByPlanta($idPlanta, $categoria);
        if (empty($lotes)) {
            return [
                'lotes' => [],
                'total_costos' => 0,
                'total_plantas' => 0,
                'costo_por_planta' => 0,
            ];
        }
        $totalCostos = array_sum(array_column($lotes, 'costo_total_lote'));
        $totalPlantas = array_sum(array_column($lotes, 'cantidad_actual'));
        $costoPorPlanta = $totalPlantas > 0 ? $totalCostos / $totalPlantas : 0;
        $plantaNombre = !empty($lotes) ? ($lotes[0]['planta_nombre'] ?? '') : '';
        return [
            'lotes' => $lotes,
            'total_costos' => round($totalCostos, 2),
            'total_plantas' => $totalPlantas,
            'costo_por_planta' => round($costoPorPlanta, 2),
            'planta_nombre' => $plantaNombre,
        ];
    }

    public function getCostoInsumosByLote(int $idLote): float
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(ci.cantidad_usada * ci.costo_unitario), 0)
                FROM consumo_insumos ci
                JOIN asignar_tarea a ON ci.id_asignacion = a.id_asignacion
                WHERE a.id_lote = :id_lote
            ");
            $stmt->execute([':id_lote' => $idLote]);
            return (float)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Error en PriceCalculation::getCostoInsumosByLote: ' . $e->getMessage());
            return 0;
        }
    }
}
