<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class SeedCollection extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        r.id_recoleccion AS id,
                        r.id_trabajador,
                        r.id_ubicacion,
                        r.fecha_asignacion,
                        r.fecha_recoleccion,
                        r.estatus,
                        r.observacion,
                        (SELECT COUNT(*) FROM recoleccion_semillas_detalle d WHERE d.id_recoleccion = r.id_recoleccion) AS total_detalles,
                        CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS trabajador_nombre,
                        u.nombre_ubicacion
                    FROM recoleccion_semillas r
                    LEFT JOIN trabajadores t ON r.id_trabajador = t.id_trabajador
                    LEFT JOIN ubicacion u ON r.id_ubicacion = u.id_ubicacion
                    ORDER BY r.fecha_asignacion DESC, r.id_recoleccion DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*,
                       CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS trabajador_nombre,
                       u.nombre_ubicacion
                FROM recoleccion_semillas r
                LEFT JOIN trabajadores t ON r.id_trabajador = t.id_trabajador
                LEFT JOIN ubicacion u ON r.id_ubicacion = u.id_ubicacion
                WHERE r.id_recoleccion = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recoleccion_semillas WHERE id_recoleccion = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM recoleccion_semillas WHERE id_recoleccion = :id");
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

    public function add(int $idTrabajador, int $idUbicacion, string $fechaAsignacion, ?string $observacion = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO recoleccion_semillas (id_trabajador, id_ubicacion, fecha_asignacion, estatus, observacion)
            VALUES (:id_trabajador, :id_ubicacion, :fecha_asignacion, 'Pendiente', :observacion)
        ");
        return $stmt->execute([
            ':id_trabajador'   => $idTrabajador,
            ':id_ubicacion'    => $idUbicacion,
            ':fecha_asignacion' => $fechaAsignacion,
            ':observacion'     => $observacion,
        ]);
    }

    public function update(int $id, int $idTrabajador, int $idUbicacion, string $fechaAsignacion, ?string $observacion = null): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception('No existe la recolección solicitada para modificar.');
        }
        $stmt = $this->db->prepare("
            UPDATE recoleccion_semillas
            SET id_trabajador = :id_trabajador,
                id_ubicacion = :id_ubicacion,
                fecha_asignacion = :fecha_asignacion,
                observacion = :observacion
            WHERE id_recoleccion = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':id_trabajador' => $idTrabajador,
            ':id_ubicacion' => $idUbicacion,
            ':fecha_asignacion' => $fechaAsignacion,
            ':observacion' => $observacion,
        ]);
    }

    public function complete(int $id, string $fechaRecoleccion): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception('No existe la recolección solicitada.');
        }
        $stmt = $this->db->prepare("
            UPDATE recoleccion_semillas
            SET estatus = 'Realizada',
                fecha_recoleccion = :fecha_recoleccion
            WHERE id_recoleccion = :id AND estatus = 'Pendiente'
        ");
        return $stmt->execute([
            ':id' => $id,
            ':fecha_recoleccion' => $fechaRecoleccion,
        ]);
    }

    public function addDetail(int $idRecoleccion, ?string $plantaOrigen, string $nombreSemilla, int $idUnidadMedida, float $cantidad): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO recoleccion_semillas_detalle
                (id_recoleccion, planta_origen, nombre_semilla, id_unidad_medida, cantidad)
            VALUES
                (:id_recoleccion, :planta_origen, :nombre_semilla, :id_unidad_medida, :cantidad)
        ");
        return $stmt->execute([
            ':id_recoleccion'   => $idRecoleccion,
            ':planta_origen'    => $plantaOrigen,
            ':nombre_semilla'   => $nombreSemilla,
            ':id_unidad_medida' => $idUnidadMedida,
            ':cantidad'         => $cantidad,
        ]);
    }

    public function getDetails(int $idRecoleccion): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, u.nombre_unidad_medida, u.simbolo
                FROM recoleccion_semillas_detalle d
                LEFT JOIN unidad_medida u ON d.id_unidad_medida = u.id_unidad_medida
                WHERE d.id_recoleccion = :id
                ORDER BY d.id_recoleccion_detalle ASC
            ");
            $stmt->execute([':id' => $idRecoleccion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getDetails: ' . $e->getMessage());
            return [];
        }
    }

    public function getDetailsCount(int $idRecoleccion): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM recoleccion_semillas_detalle WHERE id_recoleccion = :id");
            $stmt->execute([':id' => $idRecoleccion]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
