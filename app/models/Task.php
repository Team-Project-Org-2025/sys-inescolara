<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Task extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_tarea' => ['type' => 'nombre', 'required' => true],
        'descripcion'  => ['type' => null,     'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_tarea AS id, nombre_tarea, descripcion, activo
                    FROM tareas
                    WHERE activo = 1
                    ORDER BY id_tarea DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener tareas: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT id_tarea AS id, nombre_tarea, descripcion FROM tareas WHERE id_tarea = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM tareas WHERE id_tarea = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE tareas SET activo = 0 WHERE id_tarea = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE tareas SET activo = 1 WHERE id_tarea = :id");
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

    public function add(string $nombre, ?string $descripcion = null): bool
    {
        $this->validateData([
            'nombre_tarea' => $nombre,
            'descripcion' => $descripcion,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO tareas (nombre_tarea, descripcion) VALUES (:nombre, :descripcion)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);
    }

    public function update(int $id, string $nombre, ?string $descripcion = null): bool
    {
        $this->validateData([
            'nombre_tarea' => $nombre,
            'descripcion' => $descripcion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe la tarea con ID: $id");
        }
        $stmt = $this->db()->prepare("UPDATE tareas SET nombre_tarea = :nombre, descripcion = :descripcion WHERE id_tarea = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);
    }

    // -- Transactional: assign task + record consumptions + update stock --

    public function assignTaskWithConsumptions(array $assignmentData, array $consumptions): int
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO tareas (nombre_tarea, descripcion) VALUES (:nombre, :descripcion)
            ");
            $stmt->execute([
                ':nombre'      => $assignmentData['nombre_tarea'],
                ':descripcion' => $assignmentData['descripcion'] ?? null,
            ]);
            $newTaskId = (int)$this->db()->lastInsertId();

            $stmt = $this->db()->prepare("
                INSERT INTO asignar_tarea (id_trabajador, id_tarea, id_lote, fecha_asignacion, estatus_tarea)
                VALUES (:id_trabajador, :id_tarea, :id_lote, :fecha_asignacion, :estatus_tarea)
            ");
            $stmt->execute([
                ':id_trabajador'    => $assignmentData['id_trabajador'],
                ':id_tarea'         => $newTaskId,
                ':id_lote'          => $assignmentData['id_lote'],
                ':fecha_asignacion' => $assignmentData['fecha_asignacion'],
                ':estatus_tarea'    => $assignmentData['estatus_tarea'] ?? 'pendiente',
            ]);
            $asignacionId = (int)$this->db()->lastInsertId();

            foreach ($consumptions as $consumo) {
                $stmt = $this->db()->prepare("
                    INSERT INTO consumo_insumos (id_asignacion, id_insumo, cantidad_usada, costo_unitario, stock_actual, fecha_consumo)
                    VALUES (:id_asignacion, :id_insumo, :cantidad_usada, :costo_unitario, :stock_actual, :fecha_consumo)
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_insumo'      => $consumo['id_insumo'],
                    ':cantidad_usada' => $consumo['cantidad_usada'],
                    ':costo_unitario' => $consumo['costo_unitario'],
                    ':stock_actual'   => $consumo['stock_actual'] ?? null,
                    ':fecha_consumo'  => $consumo['fecha_consumo'],
                ]);

                $stmt = $this->db()->prepare("
                    UPDATE insumo
                    SET stock_actual = GREATEST(0, stock_actual - :cantidad)
                    WHERE id_insumo = :id_insumo
                ");
                $stmt->execute([
                    ':cantidad'   => $consumo['cantidad_usada'],
                    ':id_insumo'  => $consumo['id_insumo'],
                ]);
            }

            $this->db()->commit();
            return $asignacionId;
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function updateAssignmentWithConsumptions(int $asignacionId, array $assignmentData, array $consumptions, array $tools): void
    {
        $this->db()->beginTransaction();
        try {
            $oldAssignment = $this->getAssignmentById($asignacionId);
            if (!$oldAssignment) throw new \Exception("Asignación no encontrada: $asignacionId");
            $idTarea = (int)$oldAssignment['id_tarea'];

            $oldConsumptions = $this->getConsumptions($asignacionId);
            foreach ($oldConsumptions as $oc) {
                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id_insumo");
                $stmt->execute([':cantidad' => $oc['cantidad_usada'], ':id_insumo' => $oc['id_insumo']]);
            }
            $stmt = $this->db()->prepare("DELETE FROM consumo_insumos WHERE id_asignacion = :id");
            $stmt->execute([':id' => $asignacionId]);

            $oldTools = $this->getToolUsages($asignacionId);
            foreach ($oldTools as $ot) {
                $stmt = $this->db()->prepare("UPDATE herramienta SET estado = 'disponible' WHERE id_herramienta = :id");
                $stmt->execute([':id' => $ot['id_herramienta']]);
            }
            $stmt = $this->db()->prepare("DELETE FROM uso_herramienta WHERE id_asignacion = :id");
            $stmt->execute([':id' => $asignacionId]);

            $stmt = $this->db()->prepare("UPDATE tareas SET nombre_tarea = :nombre, descripcion = :descripcion WHERE id_tarea = :id");
            $stmt->execute([
                ':nombre'      => $assignmentData['nombre_tarea'],
                ':descripcion' => $assignmentData['descripcion'] ?? null,
                ':id'          => $idTarea,
            ]);

            $stmt = $this->db()->prepare("UPDATE asignar_tarea SET id_trabajador = :t, id_lote = :l, fecha_asignacion = :f WHERE id_asignacion = :id");
            $stmt->execute([
                ':t'  => $assignmentData['id_trabajador'],
                ':l'  => $assignmentData['id_lote'],
                ':f'  => $assignmentData['fecha_asignacion'],
                ':id' => $asignacionId,
            ]);

            foreach ($consumptions as $c) {
                $stmt = $this->db()->prepare("SELECT stock_actual FROM insumo WHERE id_insumo = :id");
                $stmt->execute([':id' => $c['id_insumo']]);
                $stockActual = (float)$stmt->fetchColumn();
                if ($c['cantidad_usada'] > $stockActual) {
                    throw new \Exception("Stock insuficiente para insumo ID {$c['id_insumo']}. Disponible: $stockActual");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO consumo_insumos (id_asignacion, id_insumo, cantidad_usada, costo_unitario, stock_actual, fecha_consumo)
                    VALUES (:id_asignacion, :id_insumo, :cantidad_usada, :costo_unitario, :stock_actual, :fecha_consumo)
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_insumo'      => $c['id_insumo'],
                    ':cantidad_usada' => $c['cantidad_usada'],
                    ':costo_unitario' => $c['costo_unitario'],
                    ':stock_actual'   => $stockActual,
                    ':fecha_consumo'  => $c['fecha_consumo'],
                ]);

                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = GREATEST(0, stock_actual - :c) WHERE id_insumo = :id");
                $stmt->execute([':c' => $c['cantidad_usada'], ':id' => $c['id_insumo']]);
            }

            foreach ($tools as $t) {
                $stmt = $this->db()->prepare("SELECT estado FROM herramienta WHERE id_herramienta = :id");
                $stmt->execute([':id' => $t['id_herramienta']]);
                $toolEstado = $stmt->fetchColumn();
                if ($toolEstado !== 'disponible') {
                    throw new \Exception("La herramienta '{$t['nombre_herramienta']}' no está disponible.");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO uso_herramienta (id_asignacion, id_herramienta, fecha_uso, observacion, estado_herramienta_post_uso)
                    VALUES (:id_asignacion, :id_herramienta, :fecha_uso, :observacion, 'ok')
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_herramienta' => $t['id_herramienta'],
                    ':fecha_uso'      => $t['fecha_uso'],
                    ':observacion'    => $t['observacion'] ?? null,
                ]);

                $stmt = $this->db()->prepare("UPDATE herramienta SET estado = 'ok' WHERE id_herramienta = :id");
                $stmt->execute([':id' => $t['id_herramienta']]);
            }

            $this->db()->commit();
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function completeAssignment(int $id, string $fechaCumplimiento): void
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                UPDATE asignar_tarea
                SET estatus_tarea = 'completada', fecha_cumplimiento = :fecha
                WHERE id_asignacion = :id
            ");
            $stmt->execute([
                ':id'    => $id,
                ':fecha' => $fechaCumplimiento,
            ]);
            $this->db()->commit();
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function cancelAssignment(int $id): void
    {
        $stmt = $this->db()->prepare("UPDATE asignar_tarea SET estatus_tarea = 'cancelada' WHERE id_asignacion = :id");
        $stmt->execute([':id' => $id]);
    }

    public function getAssignments(): array
    {
        $sql = "SELECT a.*, t.nombre_tarea, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.id_lote AS codigo_lote
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                ORDER BY a.fecha_asignacion DESC";
        $stmt = $this->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getAssignmentById(int $id): ?array
    {
        $sql = "SELECT a.*, t.nombre_tarea, t.descripcion, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.id_lote AS codigo_lote
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                WHERE a.id_asignacion = :id";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getConsumptions(int $asignacionId): array
    {
        $sql = "SELECT c.*, i.nombre_insumo, u.simbolo
                FROM consumo_insumos c
                LEFT JOIN insumo i ON c.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE c.id_asignacion = :id_asignacion
                ORDER BY c.fecha_consumo DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateToolEstados(int $asignacionId, array $toolEstados): void
    {
        $this->db()->beginTransaction();
        try {
            $stmtUso = $this->db()->prepare("UPDATE uso_herramienta SET estado_herramienta_post_uso = :estado WHERE id_uso = :id_uso AND id_asignacion = :id_asignacion");
            $stmtHerramienta = $this->db()->prepare("UPDATE herramienta SET estado = :estado WHERE id_herramienta = (SELECT id_herramienta FROM uso_herramienta WHERE id_uso = :id_uso)");
            foreach ($toolEstados as $te) {
                $idUso = (int)($te['id_uso'] ?? 0);
                $estado = $te['estado'] ?? 'ok';
                if ($idUso <= 0) continue;
                $stmtUso->execute([':estado' => $estado, ':id_uso' => $idUso, ':id_asignacion' => $asignacionId]);
                $stmtHerramienta->execute([':estado' => $estado, ':id_uso' => $idUso]);
            }
            $this->db()->commit();
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function getToolUsages(int $asignacionId): array
    {
        $sql = "SELECT u.*, h.nombre_herramienta, h.tipo
                FROM uso_herramienta u
                LEFT JOIN herramienta h ON u.id_herramienta = h.id_herramienta
                WHERE u.id_asignacion = :id_asignacion
                ORDER BY u.fecha_uso DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
