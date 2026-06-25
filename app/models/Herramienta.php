<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Herramienta extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre'                    => ['type' => 'nombreProducto', 'required' => true],
        'tipo'                      => ['type' => null,            'required' => false],
        'estado'                    => ['type' => null,            'required' => false],
        'fecha_adquisicion'         => ['type' => null,            'required' => false],
        'fecha_ultimo_mantenimiento'=> ['type' => null,            'required' => false],
        'observacion'               => ['type' => null,            'required' => false],
        'cantidad'                  => ['type' => 'cantidad',      'required' => true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db()->query("SHOW COLUMNS FROM herramienta")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('cantidad', $columns)) {
                $this->db()->exec("ALTER TABLE herramienta ADD COLUMN cantidad INT(11) NOT NULL DEFAULT 1 AFTER nombre_herramienta");
            }
        } catch (\Throwable $e) {
            error_log('Error en Herramienta::bootstrapDefaults: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT id_herramienta AS id, nombre_herramienta, cantidad, tipo, estado,
                       fecha_adquisicion, fecha_ultimo_mantenimiento, observacion, activo
                FROM herramienta
                WHERE activo = 1
                ORDER BY nombre_herramienta ASC
            ");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as &$row) {
                $row['cantidad'] = (int)$row['cantidad'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('Error en Herramienta::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT id_herramienta AS id, nombre_herramienta, cantidad, tipo, estado,
                       fecha_adquisicion, fecha_ultimo_mantenimiento, observacion
                FROM herramienta
                WHERE id_herramienta = :id
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['cantidad'] = (int)$row['cantidad'];
            }
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Herramienta::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM herramienta WHERE id_herramienta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(string $nombre, int $cantidad = 1, ?string $tipo = null, string $estado = 'disponible', ?string $fechaAdquisicion = null, ?string $fechaUltimoMantenimiento = null, ?string $observacion = null): int
    {
        $this->validateData([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'estado' => $estado,
            'fecha_adquisicion' => $fechaAdquisicion,
            'fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            'observacion' => $observacion,
            'cantidad' => $cantidad,
        ]);
        $stmt = $this->db()->prepare("
            INSERT INTO herramienta (nombre_herramienta, cantidad, tipo, estado, fecha_adquisicion, fecha_ultimo_mantenimiento, observacion)
            VALUES (:nombre, :cantidad, :tipo, :estado, :fecha_adquisicion, :fecha_ultimo_mantenimiento, :observacion)
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':cantidad' => $cantidad,
            ':tipo' => $tipo,
            ':estado' => $estado,
            ':fecha_adquisicion' => $fechaAdquisicion,
            ':fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            ':observacion' => $observacion,
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, string $nombre, int $cantidad = 1, ?string $tipo = null, string $estado = 'disponible', ?string $fechaAdquisicion = null, ?string $fechaUltimoMantenimiento = null, ?string $observacion = null): bool
    {
        $this->validateData([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'estado' => $estado,
            'fecha_adquisicion' => $fechaAdquisicion,
            'fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            'observacion' => $observacion,
            'cantidad' => $cantidad,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception('No existe la herramienta solicitada para modificar.');
        }

        $oldData = $this->getById($id);

        $stmt = $this->db()->prepare("
            UPDATE herramienta
            SET nombre_herramienta = :nombre,
                cantidad = :cantidad,
                tipo = :tipo,
                estado = :estado,
                fecha_adquisicion = :fecha_adquisicion,
                fecha_ultimo_mantenimiento = :fecha_ultimo_mantenimiento,
                observacion = :observacion
            WHERE id_herramienta = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':cantidad' => $cantidad,
            ':tipo' => $tipo,
            ':estado' => $estado,
            ':fecha_adquisicion' => $fechaAdquisicion,
            ':fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            ':observacion' => $observacion,
        ]);

        AuditLog::record('UPDATE', 'herramienta', $id, $oldData, [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'estado' => $estado,
            'fecha_adquisicion' => $fechaAdquisicion,
            'fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            'observacion' => $observacion,
            'cantidad' => $cantidad,
        ]);

        return true;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE herramienta SET activo = 0 WHERE id_herramienta = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'herramienta', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE herramienta SET activo = 1 WHERE id_herramienta = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        $id = $this->db()->lastInsertId();
        return $id !== false ? (int) $id : null;
    }

    // -- Transactional: record tool usage + update tool state --

    public function recordUsageWithStateUpdate(array $usageData): int
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO uso_herramienta (id_asignacion, id_herramienta, fecha_uso, observacion, estado_herramienta_post_uso)
                VALUES (:id_asignacion, :id_herramienta, :fecha_uso, :observacion, :estado_herramienta_post_uso)
            ");
            $stmt->execute([
                ':id_asignacion'            => $usageData['id_asignacion'],
                ':id_herramienta'           => $usageData['id_herramienta'],
                ':fecha_uso'                => $usageData['fecha_uso'],
                ':observacion'              => $usageData['observacion'] ?? null,
                ':estado_herramienta_post_uso' => $usageData['estado_herramienta_post_uso'] ?? 'ok',
            ]);
            $usoId = (int)$this->db()->lastInsertId();

            $stmt = $this->db()->prepare("UPDATE herramienta SET estado = :estado WHERE id_herramienta = :id");
            $stmt->execute([
                ':estado' => $usageData['estado_herramienta_post_uso'] ?? 'ok',
                ':id'     => $usageData['id_herramienta'],
            ]);

            $this->db()->commit();
            AuditLog::record('CREATE', 'uso_herramienta', $usoId, null, $usageData);
            return $usoId;
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function getUsages(int $herramientaId): array
    {
        $sql = "SELECT u.*, a.id_tarea, t.nombre_tarea
                FROM uso_herramienta u
                LEFT JOIN asignar_tarea a ON u.id_asignacion = a.id_asignacion
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                WHERE u.id_herramienta = :id_herramienta
                ORDER BY u.fecha_uso DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id_herramienta' => $herramientaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
