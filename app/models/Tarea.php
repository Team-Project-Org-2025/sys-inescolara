<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\models\AuditLog;
use PDO;

class Tarea extends Database
{
    public function assignTaskWithConsumptions(array $assignmentData, array $consumptions, array $tools = []): int
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO asignar_tarea (id_usuario, nombre_tarea, descripcion, fecha_asignacion, estatus_tarea)
                VALUES (:id_usuario, :nombre_tarea, :descripcion, :fecha_asignacion, :estatus_tarea)
            ");
            $stmt->execute([
                ':id_usuario'       => $assignmentData['id_usuario'],
                ':nombre_tarea'     => $assignmentData['nombre_tarea'],
                ':descripcion'      => $assignmentData['descripcion'] ?? null,
                ':fecha_asignacion' => $assignmentData['fecha_asignacion'],
                ':estatus_tarea'    => $assignmentData['estatus_tarea'] ?? 'pendiente',
            ]);
            $asignacionId = (int)$this->db()->lastInsertId();

            foreach ($consumptions as $consumo) {
                $idLote = !empty($consumo['id_lote']) ? (int)$consumo['id_lote'] : null;
                $stmt = $this->db()->prepare("
                    INSERT INTO registro_insumo (id_asignacion, id_lote, id_insumo, cantidad, costo_unitario, fecha_registro)
                    VALUES (:id_asignacion, :id_lote, :id_insumo, :cantidad, :costo_unitario, :fecha_registro)
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_lote'        => $idLote,
                    ':id_insumo'      => $consumo['id_insumo'],
                    ':cantidad'       => $consumo['cantidad_usada'],
                    ':costo_unitario' => $consumo['costo_unitario'],
                    ':fecha_registro' => $consumo['fecha_consumo'],
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

            foreach ($tools as $t) {
                $idHerramienta = (int)$t['id_herramienta'];
                $cantidad = (int)($t['cantidad'] ?? 1);

                $stmt = $this->db()->prepare("SELECT estado FROM herramienta WHERE id_herramienta = :id");
                $stmt->execute([':id' => $idHerramienta]);
                $toolEstado = $stmt->fetchColumn();
                if (!in_array($toolEstado, ['disponible', 'ok'])) {
                    throw new \Exception("La herramienta no está disponible.");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO uso_herramienta (id_asignacion, id_herramienta, fecha_uso, observacion, estado_herramienta_post_uso)
                    VALUES (:id_asignacion, :id_herramienta, :fecha_uso, :observacion, 'ok')
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_herramienta' => $idHerramienta,
                    ':fecha_uso'      => $t['fecha_uso'] ?? date('Y-m-d'),
                    ':observacion'    => $t['observacion'] ?? null,
                ]);
            }

            $this->db()->commit();
            AuditLog::record('CREATE', 'asignar_tarea', $asignacionId, null, $assignmentData);
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

            $oldConsumptions = $this->getConsumptions($asignacionId);
            foreach ($oldConsumptions as $oc) {
                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id_insumo");
                $stmt->execute([':cantidad' => $oc['cantidad'], ':id_insumo' => $oc['id_insumo']]);
            }
            $stmt = $this->db()->prepare("DELETE FROM registro_insumo WHERE id_asignacion = :id");
            $stmt->execute([':id' => $asignacionId]);

            $oldTools = $this->getToolUsages($asignacionId);
            foreach ($oldTools as $ot) {
                $stmt = $this->db()->prepare("UPDATE herramienta SET estado = 'disponible' WHERE id_herramienta = :id");
                $stmt->execute([':id' => $ot['id_herramienta']]);
            }
            $stmt = $this->db()->prepare("DELETE FROM uso_herramienta WHERE id_asignacion = :id");
            $stmt->execute([':id' => $asignacionId]);

            $stmt = $this->db()->prepare("UPDATE asignar_tarea SET id_usuario = :u, nombre_tarea = :n, descripcion = :d, fecha_asignacion = :f WHERE id_asignacion = :id");
            $stmt->execute([
                ':u'  => $assignmentData['id_usuario'],
                ':n'  => $assignmentData['nombre_tarea'],
                ':d'  => $assignmentData['descripcion'] ?? null,
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

                $idLote = !empty($c['id_lote']) ? (int)$c['id_lote'] : null;
                $stmt = $this->db()->prepare("
                    INSERT INTO registro_insumo (id_asignacion, id_lote, id_insumo, cantidad, costo_unitario, fecha_registro)
                    VALUES (:id_asignacion, :id_lote, :id_insumo, :cantidad, :costo_unitario, :fecha_registro)
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_lote'        => $idLote,
                    ':id_insumo'      => $c['id_insumo'],
                    ':cantidad'       => $c['cantidad_usada'],
                    ':costo_unitario' => $c['costo_unitario'],
                    ':fecha_registro' => $c['fecha_consumo'],
                ]);

                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = GREATEST(0, stock_actual - :c) WHERE id_insumo = :id");
                $stmt->execute([':c' => $c['cantidad_usada'], ':id' => $c['id_insumo']]);
            }

            foreach ($tools as $t) {
                $idHerramienta = (int)$t['id_herramienta'];
                $stmt = $this->db()->prepare("SELECT estado FROM herramienta WHERE id_herramienta = :id");
                $stmt->execute([':id' => $idHerramienta]);
                $toolEstado = $stmt->fetchColumn();
                if (!in_array($toolEstado, ['disponible', 'ok'])) {
                    throw new \Exception("La herramienta '{$t['nombre_herramienta']}' no está disponible.");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO uso_herramienta (id_asignacion, id_herramienta, fecha_uso, observacion, estado_herramienta_post_uso)
                    VALUES (:id_asignacion, :id_herramienta, :fecha_uso, :observacion, 'ok')
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_herramienta' => $idHerramienta,
                    ':fecha_uso'      => $t['fecha_uso'],
                    ':observacion'    => $t['observacion'] ?? null,
                ]);
            }

            $this->db()->commit();
            AuditLog::record('UPDATE', 'asignar_tarea', $asignacionId, null, $assignmentData);
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function getAssignments(): array
    {
        $sql = "SELECT a.id_asignacion, a.nombre_tarea, a.descripcion, a.fecha_asignacion,
                       a.fecha_cumplimiento, a.estatus_tarea, a.horas_dedicadas,
                       u.nombre_trabajador, u.apellido_trabajador, u.nombre_usuario
                FROM asignar_tarea a
                LEFT JOIN `sysinescolara-seguridad`.usuarios u ON a.id_usuario = u.id_usuario
                ORDER BY a.fecha_asignacion DESC";
        $stmt = $this->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getAssignmentById(int $id): ?array
    {
        $sql = "SELECT a.id_asignacion, a.nombre_tarea, a.descripcion, a.fecha_asignacion,
                       a.fecha_cumplimiento, a.estatus_tarea, a.horas_dedicadas,
                       u.nombre_trabajador, u.apellido_trabajador, u.nombre_usuario
                FROM asignar_tarea a
                LEFT JOIN `sysinescolara-seguridad`.usuarios u ON a.id_usuario = u.id_usuario
                WHERE a.id_asignacion = :id";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getConsumptions(int $asignacionId): array
    {
        $sql = "SELECT c.*, i.nombre_insumo, u.simbolo
                FROM registro_insumo c
                LEFT JOIN insumo i ON c.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE c.id_asignacion = :id_asignacion
                ORDER BY c.fecha_registro DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateToolEstados(int $asignacionId, array $toolEstados): void
    {
        $this->db()->beginTransaction();
        try {
            $stmtUso = $this->db()->prepare("UPDATE uso_herramienta SET estado_herramienta_post_uso = :estado WHERE id_uso = :id_uso AND id_asignacion = :id_asignacion");
            $stmtHerramienta = $this->db()->prepare("UPDATE herramienta SET estado = :estado WHERE id_herramienta = :id_herramienta");
            foreach ($toolEstados as $te) {
                $idUso = (int)($te['id_uso'] ?? 0);
                $estado = $te['estado'] ?? 'ok';
                if ($idUso <= 0) continue;
                $stmtUso->execute([':estado' => $estado, ':id_uso' => $idUso, ':id_asignacion' => $asignacionId]);
                $row = $this->db()->prepare("SELECT id_herramienta FROM uso_herramienta WHERE id_uso = :id_uso LIMIT 1");
                $row->execute([':id_uso' => $idUso]);
                $idHerr = $row->fetchColumn();
                if ($idHerr) {
                    $stmtHerramienta->execute([':estado' => $estado, ':id_herramienta' => (int)$idHerr]);
                }
            }
            $this->db()->commit();
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function getToolUsages(int $asignacionId): array
    {
        $sql = "SELECT u.*, h.nombre_herramienta
                FROM uso_herramienta u
                LEFT JOIN herramienta h ON u.id_herramienta = h.id_herramienta
                WHERE u.id_asignacion = :id_asignacion
                ORDER BY u.fecha_uso DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActiveToolUsages(int $idHerramienta, ?int $excludeAsignacionId = null): int
    {
        $sql = "SELECT SUM(COALESCE(uh.cantidad_usada, 1)) FROM uso_herramienta uh
                JOIN asignar_tarea a ON uh.id_asignacion = a.id_asignacion
                WHERE uh.id_herramienta = :id_herramienta
                AND a.estatus_tarea = 'pendiente'";
        $params = [':id_herramienta' => $idHerramienta];
        if ($excludeAsignacionId !== null) {
            $sql .= " AND a.id_asignacion != :exclude";
            $params[':exclude'] = $excludeAsignacionId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function completeAssignment(int $id, string $fechaCumplimiento, ?float $horasDedicadas = null): void
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                UPDATE asignar_tarea
                SET estatus_tarea = 'completada', fecha_cumplimiento = :fecha, horas_dedicadas = :horas
                WHERE id_asignacion = :id
            ");
            $stmt->execute([
                ':id'    => $id,
                ':fecha' => $fechaCumplimiento,
                ':horas' => $horasDedicadas,
            ]);
            $this->db()->commit();
            AuditLog::record('UPDATE', 'asignar_tarea', $id, null, [
                'estatus_tarea' => 'completada',
                'fecha_cumplimiento' => $fechaCumplimiento,
                'horas_dedicadas' => $horasDedicadas,
            ]);
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }

    public function cancelAssignment(int $id): void
    {
        $this->db()->beginTransaction();
        try {
            $oldConsumptions = $this->getConsumptions($id);
            foreach ($oldConsumptions as $oc) {
                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id_insumo");
                $stmt->execute([':cantidad' => $oc['cantidad'], ':id_insumo' => $oc['id_insumo']]);
            }

            $oldTools = $this->getToolUsages($id);
            foreach ($oldTools as $ot) {
                $stmt = $this->db()->prepare("UPDATE herramienta SET estado = 'disponible' WHERE id_herramienta = :id");
                $stmt->execute([':id' => $ot['id_herramienta']]);
            }

            $stmt = $this->db()->prepare("UPDATE asignar_tarea SET estatus_tarea = 'cancelada' WHERE id_asignacion = ?");
            $stmt->execute([$id]);

            $this->db()->commit();
            AuditLog::record('UPDATE', 'asignar_tarea', $id, null, ['estatus_tarea' => 'cancelada']);
        } catch (\Exception $e) {
            $this->db()->rollBack();
            throw $e;
        }
    }
}
