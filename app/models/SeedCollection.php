<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class SeedCollection extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_trabajador'   => ['type' => null, 'required' => true],
        'id_ubicacion'    => ['type' => null, 'required' => true],
        'fecha_asignacion'=> ['type' => null, 'required' => true],
        'observacion'     => ['type' => null, 'required' => false],
    ];

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
                    WHERE r.activo = 1
                    ORDER BY r.fecha_asignacion DESC, r.id_recoleccion DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
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
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM recoleccion_semillas WHERE id_recoleccion = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE recoleccion_semillas SET activo = 0 WHERE id_recoleccion = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE recoleccion_semillas SET activo = 1 WHERE id_recoleccion = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            $id = $this->db()->lastInsertId();
            return $id !== false ? (int)$id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(int $idTrabajador, int $idUbicacion, string $fechaAsignacion, ?string $observacion = null): bool
    {
        $this->validateData([
            'id_trabajador' => $idTrabajador,
            'id_ubicacion' => $idUbicacion,
            'fecha_asignacion' => $fechaAsignacion,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db()->prepare("
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
        $this->validateData([
            'id_trabajador' => $idTrabajador,
            'id_ubicacion' => $idUbicacion,
            'fecha_asignacion' => $fechaAsignacion,
            'observacion' => $observacion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception('No existe la recolección solicitada para modificar.');
        }
        $stmt = $this->db()->prepare("
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
        $stmt = $this->db()->prepare("
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

    public function addDetail(int $idRecoleccion, ?string $plantaOrigen, string $nombreSemilla, int $idUnidadMedida, float $cantidad, ?int $idInsumo = null): bool
    {
        $stmt = $this->db()->prepare("
            INSERT INTO recoleccion_semillas_detalle
                (id_recoleccion, planta_origen, nombre_semilla, id_unidad_medida, cantidad, id_insumo)
            VALUES
                (:id_recoleccion, :planta_origen, :nombre_semilla, :id_unidad_medida, :cantidad, :id_insumo)
        ");
        return $stmt->execute([
            ':id_recoleccion'   => $idRecoleccion,
            ':planta_origen'    => $plantaOrigen,
            ':nombre_semilla'   => $nombreSemilla,
            ':id_unidad_medida' => $idUnidadMedida,
            ':cantidad'         => $cantidad,
            ':id_insumo'        => $idInsumo,
        ]);
    }

    public function getDetails(int $idRecoleccion): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT d.*,
                       u.nombre_unidad_medida, u.simbolo,
                       i.nombre_insumo AS insumo_nombre
                FROM recoleccion_semillas_detalle d
                LEFT JOIN unidad_medida u ON d.id_unidad_medida = u.id_unidad_medida
                LEFT JOIN insumo i ON d.id_insumo = i.id_insumo
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
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM recoleccion_semillas_detalle WHERE id_recoleccion = :id");
            $stmt->execute([':id' => $idRecoleccion]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function registerSeedsWithTransaction(int $idRecoleccion, array $items): int
    {
        $suppliesModel = new Insumo();
        $createdCount = 0;

        try {
            $this->db()->beginTransaction();

            foreach ($items as $item) {
                $nombreSemilla = trim((string)($item['nombre_semilla'] ?? ''));
                if ($nombreSemilla === '') continue;
                $idUnidadMedida = (int)($item['id_unidad_medida'] ?? 0);
                if ($idUnidadMedida <= 0) continue;
                $cantidad = floatval($item['cantidad'] ?? 0);
                if ($cantidad <= 0) continue;
                $plantaOrigen = trim((string)($item['planta_origen'] ?? ''));
                if ($plantaOrigen === '') $plantaOrigen = null;

                $existing = $suppliesModel->findByNameAndCategory($nombreSemilla, 'Semillas');

                if ($existing) {
                    $supplyId = (int)$existing['id_insumo'];
                    $ok = $suppliesModel->increaseStock($supplyId, $cantidad);
                } else {
                    $ok = $suppliesModel->add($nombreSemilla, $idUnidadMedida, 'Semillas', $cantidad, 0);
                    if (!$ok) continue;
                    $supplyId = $suppliesModel->getLastInsertId();
                }

                if (!$ok) continue;

                $ok = $this->addDetail($idRecoleccion, $plantaOrigen, $nombreSemilla, $idUnidadMedida, $cantidad, $supplyId);
                if (!$ok) continue;

                $createdCount++;
            }

            if ($createdCount === 0) {
                $this->db()->rollBack();
                return 0;
            }

            $this->db()->commit();
            return $createdCount;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            error_log('Error en SeedCollection::registerSeedsWithTransaction: ' . $e->getMessage());
            throw $e;
        }
    }
}
