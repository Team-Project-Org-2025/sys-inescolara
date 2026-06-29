<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class CalculoPrecio extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_lote'              => ['type' => null,   'required' => true],
        'precio_planta_base'   => ['type' => 'precio','required' => true],
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
                        c.id_calculo AS id, c.id_lote, c.precio_planta_base,
                        c.costo_total_insumo, c.porcentaje_ganancia,
                        c.precio_final_sugerido, c.fecha_calculo, c.vigente,
                        l.cantidad_actual,
                        p.nombre_comun AS planta_nombre,
                        p.id_planta
                    FROM calculo_precio c
                    LEFT JOIN lote l ON c.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    ORDER BY c.fecha_calculo DESC, c.id_calculo DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT c.*, l.cantidad_actual, p.nombre_comun AS planta_nombre, p.id_planta
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE c.id_calculo = :id
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['detalles'] = $this->getDetalles($id);
            }
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM calculo_precio WHERE id_calculo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->getById($id);
            $stmt = $this->db()->prepare("UPDATE calculo_precio SET vigente = 0 WHERE id_calculo = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'calculo_precio', $id, $oldData, null);
            return true;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::delete: ' . $e->getMessage());
            return false;
        }
    }

    public function add(
        int $idLote,
        float $precioPlantaBase,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo
    ): bool {
        $this->validateData([
            'id_lote'              => $idLote,
            'precio_planta_base'   => $precioPlantaBase,
            'porcentaje_ganancia'  => $porcentajeGanancia,
            'precio_final_sugerido'=> $precioFinalSugerido,
            'fecha_calculo'        => $fechaCalculo,
        ]);
        try {
            $this->db()->beginTransaction();

            $this->desmarcarVigentes($idLote);

            $stmt = $this->db()->prepare("
                INSERT INTO calculo_precio
                    (id_lote, precio_planta_base, costo_total_insumo,
                     porcentaje_ganancia, precio_final_sugerido,
                     fecha_calculo, vigente)
                VALUES
                    (:id_lote, :precio_planta_base, :costo_total_insumo,
                     :porcentaje_ganancia, :precio_final_sugerido,
                     :fecha_calculo, 1)
            ");
            $stmt->execute([
                ':id_lote'              => $idLote,
                ':precio_planta_base'   => $precioPlantaBase,
                ':costo_total_insumo'   => $costoTotalInsumo,
                ':porcentaje_ganancia'  => $porcentajeGanancia,
                ':precio_final_sugerido'=> $precioFinalSugerido,
                ':fecha_calculo'        => $fechaCalculo,
            ]);

            $this->_lastInsertId = (int)$this->db()->lastInsertId();
            $this->db()->commit();
            AuditLog::record('CREATE', 'calculo_precio', $this->_lastInsertId, null, [
                'id_lote'              => $idLote,
                'precio_planta_base'   => $precioPlantaBase,
                'costo_total_insumo'   => $costoTotalInsumo,
                'porcentaje_ganancia'  => $porcentajeGanancia,
                'precio_final_sugerido'=> $precioFinalSugerido,
                'fecha_calculo'        => $fechaCalculo,
                'vigente'              => 1,
            ]);
            return true;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en CalculoPrecio::add: ' . $e->getMessage());
            return false;
        }
    }

    public function update(
        int $id,
        int $idLote,
        float $precioPlantaBase,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo
    ): bool {
        $this->validateData([
            'id_lote'              => $idLote,
            'precio_planta_base'   => $precioPlantaBase,
            'porcentaje_ganancia'  => $porcentajeGanancia,
            'precio_final_sugerido'=> $precioFinalSugerido,
            'fecha_calculo'        => $fechaCalculo,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception('No existe el cálculo de precio solicitado para modificar.');
        }

        $oldData = $this->getById($id);

        $stmt = $this->db()->prepare("
            UPDATE calculo_precio
            SET id_lote = ?,
                precio_planta_base = ?,
                costo_total_insumo = ?,
                porcentaje_ganancia = ?,
                precio_final_sugerido = ?,
                fecha_calculo = ?
            WHERE id_calculo = ?
        ");
        $stmt->execute([$idLote, $precioPlantaBase, $costoTotalInsumo, $porcentajeGanancia, $precioFinalSugerido, $fechaCalculo, $id]);

        AuditLog::record('UPDATE', 'calculo_precio', $id, $oldData, [
            'id_lote'              => $idLote,
            'precio_planta_base'   => $precioPlantaBase,
            'costo_total_insumo'   => $costoTotalInsumo,
            'porcentaje_ganancia'  => $porcentajeGanancia,
            'precio_final_sugerido'=> $precioFinalSugerido,
            'fecha_calculo'        => $fechaCalculo,
        ]);

        return true;
    }

    // ---- Detalle de insumos ----

    public function addDetalle(int $idCalculo, int $idInsumo, float $monto): bool
    {
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO calculo_precio_detalle (id_calculo, id_insumo, monto)
                VALUES (:id_calculo, :id_insumo, :monto)
            ");
            $stmt->execute([
                ':id_calculo' => $idCalculo,
                ':id_insumo'  => $idInsumo,
                ':monto'      => $monto,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::addDetalle: ' . $e->getMessage());
            return false;
        }
    }

    public function getDetalles(int $idCalculo): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT d.id_detalle, d.id_insumo, d.monto, i.nombre_insumo, i.costo_unitario_actual, u.simbolo
                FROM calculo_precio_detalle d
                LEFT JOIN insumo i ON d.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE d.id_calculo = :id_calculo
                ORDER BY d.id_detalle ASC
            ");
            $stmt->execute([':id_calculo' => $idCalculo]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::getDetalles: ' . $e->getMessage());
            return [];
        }
    }

    public function removeDetalle(int $idDetalle): bool
    {
        try {
            $stmt = $this->db()->prepare("DELETE FROM calculo_precio_detalle WHERE id_detalle = :id");
            $stmt->execute([':id' => $idDetalle]);
            return true;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::removeDetalle: ' . $e->getMessage());
            return false;
        }
    }

    public function updateDetalleMonto(int $idDetalle, float $monto): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE calculo_precio_detalle SET monto = :monto WHERE id_detalle = :id");
            $stmt->execute([':monto' => $monto, ':id' => $idDetalle]);
            return true;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::updateDetalleMonto: ' . $e->getMessage());
            return false;
        }
    }

    public function recalcularTotalInsumo(int $idCalculo): float
    {
        $stmt = $this->db()->prepare("SELECT COALESCE(SUM(monto), 0) FROM calculo_precio_detalle WHERE id_calculo = :id");
        $stmt->execute([':id' => $idCalculo]);
        $total = (float)$stmt->fetchColumn();
        $stmt2 = $this->db()->prepare("UPDATE calculo_precio SET costo_total_insumo = ? WHERE id_calculo = ?");
        $stmt2->execute([$total, $idCalculo]);
        return $total;
    }

    // ---- Helpers ----

    private function desmarcarVigentes(int $idLote): void
    {
        $stmt = $this->db()->prepare("UPDATE calculo_precio SET vigente = 0 WHERE id_lote = ? AND vigente = 1");
        $stmt->execute([$idLote]);
    }

    public function getVigenteByLote(int $idLote): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT c.*, p.nombre_comun AS planta_nombre
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE c.id_lote = :id_lote AND c.vigente = 1
                LIMIT 1
            ");
            $stmt->execute([':id_lote' => $idLote]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['detalles'] = $this->getDetalles((int)$row['id_calculo']);
            }
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('Error en CalculoPrecio::getVigenteByLote: ' . $e->getMessage());
            return null;
        }
    }

    public function saveDetalles(int $idCalculo, array $detalles): void
    {
        $keepIds = [];
        foreach ($detalles as $d) {
            $idDetalle = (int)($d['id_detalle'] ?? 0);
            $idInsumo  = (int)($d['id_insumo'] ?? 0);
            $monto     = (float)($d['monto'] ?? 0);
            if ($idDetalle > 0) {
                $this->updateDetalleMonto($idDetalle, $monto);
                $keepIds[] = $idDetalle;
            } elseif ($idInsumo > 0 && $monto > 0) {
                $this->addDetalle($idCalculo, $idInsumo, $monto);
            }
        }
        if (!empty($keepIds)) {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $stmt = $this->db()->prepare("DELETE FROM calculo_precio_detalle WHERE id_calculo = ? AND id_detalle NOT IN ($placeholders)");
            $stmt->execute(array_merge([$idCalculo], $keepIds));
        }
    }
}
