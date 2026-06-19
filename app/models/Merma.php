<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Merma extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        m.id_merma AS id,
                        m.id_merma,
                        m.id_trazabilidad,
                        m.id_lote,
                        m.cantidad,
                        m.motivo,
                        m.descripcion,
                        m.fecha_merma,
                        m.impacto_economico,
                        m.id_usuario_registra,
                        m.activo,
                        m.created_at,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        t.estado_salud,
                        t.fecha_registro AS fecha_cuarentena,
                        u.nombre_usuario AS usuario_registra
                    FROM mermas_historico m
                    LEFT JOIN trazabilidad t ON m.id_trazabilidad = t.id_trazabilidad
                    LEFT JOIN lote l ON m.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN `SysInescolara-Seguridad`.usuarios u ON m.id_usuario_registra = u.id_usuario
                    WHERE m.activo = 1
                    ORDER BY m.fecha_merma DESC, m.id_merma DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Merma::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT m.*,
                       COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                       t.estado_salud,
                       u.nombre_usuario AS usuario_registra
                FROM mermas_historico m
                LEFT JOIN trazabilidad t ON m.id_trazabilidad = t.id_trazabilidad
                LEFT JOIN lote l ON m.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                LEFT JOIN `SysInescolara-Seguridad`.usuarios u ON m.id_usuario_registra = u.id_usuario
                WHERE m.id_merma = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Merma::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM mermas_historico WHERE id_merma = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE mermas_historico SET activo = 0 WHERE id_merma = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE mermas_historico SET activo = 1 WHERE id_merma = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getAvailableQuarantine(): array
    {
        try {
            $sql = "SELECT
                        t.id_trazabilidad AS id,
                        t.id_lote,
                        t.cantidad,
                        t.estado_salud,
                        t.fecha_registro,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        l.cantidad_actual AS lote_stock,
                        u.nombre_ubicacion,
                        c.precio_final_sugerido AS precio_unitario
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote AND l.activo = 1
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote
                    WHERE t.activo = 1 AND t.cantidad > 0
                    ORDER BY t.fecha_registro DESC, p.nombre_comun ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Merma::getAvailableQuarantine: ' . $e->getMessage());
            return [];
        }
    }

    public function registerLoss(int $idTrazabilidad, int $cantidad, string $motivo, ?string $descripcion, string $fecha, int $idUsuario): int
    {
        $quarantine = $this->getQuarantineInfo($idTrazabilidad);
        if (!$quarantine) {
            throw new \Exception('El registro de cuarentena seleccionado no existe.');
        }
        if ($cantidad > (int)$quarantine['cantidad']) {
            throw new \Exception("Esta cuarentena solo tiene {$quarantine['cantidad']} ejemplares disponibles.");
        }

        $precioUnitario = (float)($quarantine['precio_unitario'] ?? 0);
        $impactoEconomico = $cantidad * $precioUnitario;
        $idLote = (int)$quarantine['id_lote'];

        try {
            $this->beginTransaction();

            $stmt = $this->db()->prepare("
                INSERT INTO mermas_historico (id_trazabilidad, id_lote, cantidad, motivo, descripcion, fecha_merma, impacto_economico, id_usuario_registra)
                VALUES (:id_trazabilidad, :id_lote, :cantidad, :motivo, :descripcion, :fecha_merma, :impacto_economico, :id_usuario_registra)
            ");
            $stmt->execute([
                ':id_trazabilidad'     => $idTrazabilidad,
                ':id_lote'             => $idLote,
                ':cantidad'            => $cantidad,
                ':motivo'              => $motivo,
                ':descripcion'         => $descripcion,
                ':fecha_merma'         => $fecha,
                ':impacto_economico'   => $impactoEconomico,
                ':id_usuario_registra' => $idUsuario,
            ]);

            $newId = $this->getLastInsertId();

            $this->deductQuarantineStock($idTrazabilidad, $cantidad);

            $this->commit();

            return $newId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function getQuarantineInfo(int $idTrazabilidad): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT t.id_trazabilidad, t.id_lote, t.cantidad, t.estado_salud,
                   c.precio_final_sugerido AS precio_unitario
            FROM trazabilidad t
            LEFT JOIN lote l ON t.id_lote = l.id_lote
            LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote
            WHERE t.id_trazabilidad = :id AND t.activo = 1
        ");
        $stmt->execute([':id' => $idTrazabilidad]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function deductQuarantineStock(int $idTrazabilidad, int $cantidad): bool
    {
        $stmt = $this->db()->prepare("UPDATE trazabilidad SET cantidad = GREATEST(0, cantidad - :cantidad) WHERE id_trazabilidad = :id AND cantidad >= :check");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $idTrazabilidad, ':check' => $cantidad]);
    }
}
