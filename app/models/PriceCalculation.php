<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class PriceCalculation extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        c.id_calculo AS id, c.id_lote, c.costo_mano_obra, c.costo_total_insumo,
                        c.porcentaje_ganancia,
                        c.precio_final_sugerido, c.fecha_calculo, c.vigente,
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
        $stmt = $this->db->prepare("DELETE FROM calculo_precio WHERE id_calculo = :id");
        return $stmt->execute([':id' => $id]);
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

    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }

    public function add(
        int $idLote,
        float $costoManoObra,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo,
        int $vigente = 0
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO calculo_precio
                (id_lote, costo_mano_obra, costo_total_insumo,
                 porcentaje_ganancia, precio_final_sugerido,
                 fecha_calculo, vigente)
            VALUES
                (:id_lote, :costo_mano_obra, :costo_total_insumo,
                 :porcentaje_ganancia, :precio_final_sugerido,
                 :fecha_calculo, :vigente)
        ");
        return $stmt->execute([
            ':id_lote' => $idLote,
            ':costo_mano_obra' => $costoManoObra,
            ':costo_total_insumo' => $costoTotalInsumo,
            ':porcentaje_ganancia' => $porcentajeGanancia,
            ':precio_final_sugerido' => $precioFinalSugerido,
            ':fecha_calculo' => $fechaCalculo,
            ':vigente' => $vigente,
        ]);
    }

    public function update(
        int $id,
        int $idLote,
        float $costoManoObra,
        float $costoTotalInsumo,
        float $porcentajeGanancia,
        float $precioFinalSugerido,
        string $fechaCalculo,
        int $vigente = 0
    ): bool {
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
                fecha_calculo = :fecha_calculo,
                vigente = :vigente
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
            ':vigente' => $vigente,
        ]);
    }

    public function setVigente(int $idCalculo, int $idPlanta): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE calculo_precio SET vigente = 0 WHERE id_lote IN (SELECT id_lote FROM lote WHERE id_planta = :id_planta)");
            $stmt->execute([':id_planta' => $idPlanta]);

            $stmt2 = $this->db->prepare("UPDATE calculo_precio SET vigente = 1 WHERE id_calculo = :id");
            $stmt2->execute([':id' => $idCalculo]);

            $this->db->prepare("
                INSERT INTO planta_precio_vigente (id_planta, id_calculo)
                VALUES (:id_planta, :id_calculo)
                ON DUPLICATE KEY UPDATE id_calculo = :id_calculo2
            ")->execute([
                ':id_planta' => $idPlanta,
                ':id_calculo' => $idCalculo,
                ':id_calculo2' => $idCalculo,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getActivePrices(): array
    {
        try {
            $sql = "SELECT
                        pv.id_planta, pv.id_calculo,
                        c.precio_final_sugerido, c.fecha_calculo,
                        p.nombre_comun AS planta_nombre,
                        l.id_lote, l.cantidad_actual
                    FROM planta_precio_vigente pv
                    JOIN calculo_precio c ON pv.id_calculo = c.id_calculo
                    JOIN plantas p ON pv.id_planta = p.id_planta
                    LEFT JOIN lote l ON c.id_lote = l.id_lote";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en PriceCalculation::getActivePrices: ' . $e->getMessage());
            return [];
        }
    }
}
