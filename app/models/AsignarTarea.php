<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use PDO;

class AsignarTarea extends Database implements ReadableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        $sql = "SELECT a.*, t.nombre_tarea, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.codigo_lote, l.id_planta
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                ORDER BY a.fecha_asignacion DESC";
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT a.*, t.nombre_tarea, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.codigo_lote
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                WHERE a.id_asignacion = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM asignar_tarea WHERE id_asignacion = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO asignar_tarea (id_trabajador, id_tarea, id_lote, fecha_asignacion, estatus_tarea)
            VALUES (:id_trabajador, :id_tarea, :id_lote, :fecha_asignacion, :estatus_tarea)
        ");
        return $stmt->execute([
            ':id_trabajador' => $data['id_trabajador'],
            ':id_tarea'      => $data['id_tarea'],
            ':id_lote'       => $data['id_lote'],
            ':fecha_asignacion' => $data['fecha_asignacion'],
            ':estatus_tarea' => $data['estatus_tarea'] ?? 'pendiente',
        ]);
    }

    public function complete(int $id, ?string $fechaCumplimiento = null, ?float $horasDedicadas = null): bool
    {
        $sql = "UPDATE asignar_tarea SET estatus_tarea = 'completada', fecha_cumplimiento = :fecha, horas_dedicadas = :horas WHERE id_asignacion = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'    => $id,
            ':fecha' => $fechaCumplimiento,
            ':horas' => $horasDedicadas,
        ]);
    }

    public function cancel(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE asignar_tarea SET estatus_tarea = 'cancelada' WHERE id_asignacion = :id");
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
}
