<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\models\AuditLog;
use PDO;

class Tarea extends Database
{
    public function assignTask(array $assignmentData, array $tools = []): int
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

            foreach ($tools as $t) {
                $idHerramienta = (int)$t['id_herramienta'];
                $cantidad = (float)($t['cantidad'] ?? 1);

                $stmtCheck = $this->db()->prepare("
                    SELECT h.cantidad, COALESCE(SUM(CASE WHEN a.estatus_tarea = 'pendiente' THEN u.cantidad_usada ELSE 0 END), 0) AS en_uso
                    FROM herramienta h
                    LEFT JOIN uso_herramienta u ON u.id_herramienta = h.id_herramienta
                    LEFT JOIN asignar_tarea a ON u.id_asignacion = a.id_asignacion
                    WHERE h.id_herramienta = :id
                    GROUP BY h.id_herramienta
                ");
                $stmtCheck->execute([':id' => $idHerramienta]);
                $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if (!$check) {
                    throw new \Exception("Herramienta ID $idHerramienta no encontrada.");
                }
                $disponibles = (int)$check['cantidad'] - (int)$check['en_uso'];
                if ($cantidad > $disponibles) {
                    throw new \Exception("No hay suficientes unidades de la herramienta ID $idHerramienta. Disponibles: $disponibles, solicitadas: $cantidad.");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO uso_herramienta (id_asignacion, id_herramienta, cantidad_usada, fecha_uso, observacion, estado_herramienta_post_uso)
                    VALUES (:id_asignacion, :id_herramienta, :cantidad_usada, :fecha_uso, :observacion, 'ok')
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_herramienta' => $idHerramienta,
                    ':cantidad_usada' => $cantidad,
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

    public function updateAssignment(int $asignacionId, array $assignmentData, array $tools = []): void
    {
        $this->db()->beginTransaction();
        try {
            $oldAssignment = $this->getAssignmentById($asignacionId);
            if (!$oldAssignment) throw new \Exception("Asignación no encontrada: $asignacionId");

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

            foreach ($tools as $t) {
                $idHerramienta = (int)$t['id_herramienta'];
                $cantidad = (float)($t['cantidad'] ?? 1);

                $stmtCheck = $this->db()->prepare("
                    SELECT h.cantidad, COALESCE(SUM(CASE WHEN a.estatus_tarea = 'pendiente' THEN u.cantidad_usada ELSE 0 END), 0) AS en_uso
                    FROM herramienta h
                    LEFT JOIN uso_herramienta u ON u.id_herramienta = h.id_herramienta
                    LEFT JOIN asignar_tarea a ON u.id_asignacion = a.id_asignacion
                    WHERE h.id_herramienta = :id
                    GROUP BY h.id_herramienta
                ");
                $stmtCheck->execute([':id' => $idHerramienta]);
                $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if (!$check) {
                    throw new \Exception("Herramienta ID $idHerramienta no encontrada.");
                }
                $disponibles = (int)$check['cantidad'] - (int)$check['en_uso'];
                if ($cantidad > $disponibles) {
                    throw new \Exception("No hay suficientes unidades de la herramienta ID $idHerramienta. Disponibles: $disponibles, solicitadas: $cantidad.");
                }

                $stmt = $this->db()->prepare("
                    INSERT INTO uso_herramienta (id_asignacion, id_herramienta, cantidad_usada, fecha_uso, observacion, estado_herramienta_post_uso)
                    VALUES (:id_asignacion, :id_herramienta, :cantidad_usada, :fecha_uso, :observacion, 'ok')
                ");
                $stmt->execute([
                    ':id_asignacion'  => $asignacionId,
                    ':id_herramienta' => $idHerramienta,
                    ':cantidad_usada' => $cantidad,
                    ':fecha_uso'      => $t['fecha_uso'] ?? date('Y-m-d'),
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
                LEFT JOIN `SysInescolara-Seguridad`.usuarios u ON a.id_usuario = u.id_usuario
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
                LEFT JOIN `SysInescolara-Seguridad`.usuarios u ON a.id_usuario = u.id_usuario
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

    public function completeAssignment(int $id, string $fechaCumplimiento, ?float $horasDedicadas = null, array $consumptions = [], array $toolEstados = []): void
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

            foreach ($consumptions as $c) {
                $idInsumo = (int)($c['id_insumo'] ?? 0);
                $cantidad = (float)($c['cantidad'] ?? 0);
                if ($idInsumo <= 0 || $cantidad <= 0) continue;

                $stmt = $this->db()->prepare("SELECT stock_actual, costo_unitario_actual FROM insumo WHERE id_insumo = :id");
                $stmt->execute([':id' => $idInsumo]);
                $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$insumo) throw new \Exception("Insumo ID $idInsumo no existe.");

                $stockActual = (float)$insumo['stock_actual'];
                if ($cantidad > $stockActual) {
                    throw new \Exception("Stock insuficiente para insumo ID $idInsumo. Disponible: $stockActual, solicitado: $cantidad.");
                }

                $idLote = !empty($c['id_lote']) ? (int)$c['id_lote'] : null;
                $costoUnitario = (float)$insumo['costo_unitario_actual'];

                $stmt = $this->db()->prepare("
                    INSERT INTO registro_insumo (id_asignacion, id_lote, id_insumo, cantidad, costo_unitario, fecha_registro)
                    VALUES (:id_asignacion, :id_lote, :id_insumo, :cantidad, :costo_unitario, :fecha_registro)
                ");
                $stmt->execute([
                    ':id_asignacion'  => $id,
                    ':id_lote'        => $idLote,
                    ':id_insumo'      => $idInsumo,
                    ':cantidad'       => $cantidad,
                    ':costo_unitario' => $costoUnitario,
                    ':fecha_registro' => date('Y-m-d'),
                ]);

                $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = GREATEST(0, stock_actual - :cantidad) WHERE id_insumo = :id_insumo");
                $stmt->execute([':cantidad' => $cantidad, ':id_insumo' => $idInsumo]);
            }

            if (!empty($toolEstados)) {
                $this->updateToolEstados($id, $toolEstados);
            }

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
