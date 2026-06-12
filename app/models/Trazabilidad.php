<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Trazabilidad extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_lote'       => ['type' => null,      'required' => true],
        'cantidad'      => ['type' => 'cantidad','required' => true],
        'estado_salud'  => ['type' => null,      'required' => true],
        'fecha_registro'=> ['type' => null,      'required' => true],
        'observacion'   => ['type' => null,      'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        t.id_trazabilidad AS id,
                        t.id_lote,
                        t.cantidad,
                        t.estado_salud,
                        t.observacion,
                        t.fecha_registro,
                        t.activo,
                        l.cantidad_actual AS lote_cantidad_actual,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    WHERE t.activo = 1
                    ORDER BY t.fecha_registro DESC, t.id_trazabilidad DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Trazabilidad::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                       l.cantidad_actual AS lote_cantidad_actual
                FROM trazabilidad t
                LEFT JOIN lote l ON t.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE t.id_trazabilidad = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Trazabilidad::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM trazabilidad WHERE id_trazabilidad = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE trazabilidad SET activo = 0 WHERE id_trazabilidad = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE trazabilidad SET activo = 1 WHERE id_trazabilidad = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(int $idLote, int $cantidad, string $estadoSalud, string $fechaRegistro, ?string $observacion = null): bool
    {
        $this->validateData([
            'id_lote' => $idLote,
            'cantidad' => $cantidad,
            'estado_salud' => $estadoSalud,
            'fecha_registro' => $fechaRegistro,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db->prepare("
            INSERT INTO trazabilidad (id_lote, cantidad, estado_salud, fecha_registro, observacion)
            VALUES (:id_lote, :cantidad, :estado_salud, :fecha_registro, :observacion)
        ");
        return $stmt->execute([
            ':id_lote'       => $idLote,
            ':cantidad'      => $cantidad,
            ':estado_salud'  => $estadoSalud,
            ':fecha_registro' => $fechaRegistro,
            ':observacion'   => $observacion,
        ]);
    }

    public function update(int $id, int $idLote, int $cantidad, string $estadoSalud, string $fechaRegistro, ?string $observacion = null): bool
    {
        $this->validateData([
            'id_lote' => $idLote,
            'cantidad' => $cantidad,
            'estado_salud' => $estadoSalud,
            'fecha_registro' => $fechaRegistro,
            'observacion' => $observacion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe el registro de trazabilidad con ID: $id");
        }
        $stmt = $this->db->prepare("
            UPDATE trazabilidad
            SET id_lote = :id_lote,
                cantidad = :cantidad,
                estado_salud = :estado_salud,
                fecha_registro = :fecha_registro,
                observacion = :observacion
            WHERE id_trazabilidad = :id
        ");
        return $stmt->execute([
            ':id'            => $id,
            ':id_lote'       => $idLote,
            ':cantidad'      => $cantidad,
            ':estado_salud'  => $estadoSalud,
            ':fecha_registro' => $fechaRegistro,
            ':observacion'   => $observacion,
        ]);
    }

    public function getAvailableBatches(): array
    {
        try {
            $sql = "SELECT
                        l.id_lote AS id,
                        l.cantidad_actual,
                        l.estado,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        u.nombre_ubicacion
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    WHERE l.activo = 1 AND l.cantidad_actual > 0
                    ORDER BY p.nombre_comun ASC, l.id_lote DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Trazabilidad::getAvailableBatches: ' . $e->getMessage());
            return [];
        }
    }

    public function deductBatchStock(int $idLote, int $cantidad): bool
    {
        $stmt = $this->db->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :check");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $idLote, ':check' => $cantidad]);
    }

    public function restoreBatchStock(int $idLote, int $cantidad): bool
    {
        $stmt = $this->db->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $idLote]);
    }

    public function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
