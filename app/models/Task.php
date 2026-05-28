<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Task extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_tarea AS id, nombre_tarea, descripcion 
                    FROM tareas 
                    ORDER BY id_tarea DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener tareas: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id_tarea AS id, nombre_tarea, descripcion FROM tareas WHERE id_tarea = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tareas WHERE id_tarea = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tareas WHERE id_tarea = :id");
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

    public function add(string $nombre, ?string $descripcion = null): bool
    {
        $stmt = $this->db->prepare("INSERT INTO tareas (nombre_tarea, descripcion) VALUES (:nombre, :descripcion)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);
    }

    public function update(int $id, string $nombre, ?string $descripcion = null): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la tarea con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE tareas SET nombre_tarea = :nombre, descripcion = :descripcion WHERE id_tarea = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);
    }
}