<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use PDO;

class UsoHerramienta extends Database implements ReadableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        $sql = "SELECT u.*, h.nombre_herramienta, h.tipo,
                       t.nombre_tarea
                FROM uso_herramienta u
                LEFT JOIN herramienta h ON u.id_herramienta = h.id_herramienta
                LEFT JOIN asignar_tarea a ON u.id_asignacion = a.id_asignacion
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                ORDER BY u.fecha_uso DESC";
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM uso_herramienta WHERE id_uso = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM uso_herramienta WHERE id_uso = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getByAssignment(int $asignacionId): array
    {
        $sql = "SELECT u.*, h.nombre_herramienta, h.tipo
                FROM uso_herramienta u
                LEFT JOIN herramienta h ON u.id_herramienta = h.id_herramienta
                WHERE u.id_asignacion = :id_asignacion
                ORDER BY u.fecha_uso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO uso_herramienta (id_asignacion, id_herramienta, fecha_uso, observacion, estado_herramienta_post_uso)
            VALUES (:id_asignacion, :id_herramienta, :fecha_uso, :observacion, :estado_herramienta_post_uso)
        ");
        return $stmt->execute([
            ':id_asignacion'            => $data['id_asignacion'],
            ':id_herramienta'           => $data['id_herramienta'],
            ':fecha_uso'                => $data['fecha_uso'],
            ':observacion'              => $data['observacion'] ?? null,
            ':estado_herramienta_post_uso' => $data['estado_herramienta_post_uso'] ?? 'ok',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM uso_herramienta WHERE id_uso = :id");
        return $stmt->execute([':id' => $id]);
    }
}
