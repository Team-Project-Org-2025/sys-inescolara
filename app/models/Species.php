<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Species extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_especie AS id, nombre_especie, descripcion, activo FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error obtener especies: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE especie SET activo = 0 WHERE id_especie = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE especie SET activo = 1 WHERE id_especie = :id");
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

    public function add(string $nombreEspecie, ?string $descripcion = null)
    {
        $stmt = $this->db->prepare("INSERT INTO especie (nombre_especie, descripcion) VALUES (:nombre_especie, :descripcion)");
        return $stmt->execute([
            ':nombre_especie' => $nombreEspecie,
            ':descripcion' => $descripcion,
        ]);
    }

    public function update(int $id, string $nombreEspecie, ?string $descripcion = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la especie con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE especie SET nombre_especie = :nombre_especie, descripcion = :descripcion WHERE id_especie = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_especie' => $nombreEspecie,
            ':descripcion' => $descripcion,
        ]);
    }
}
