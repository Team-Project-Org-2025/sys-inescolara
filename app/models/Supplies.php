<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;
use Exception;

class Supplies extends Database implements ReadableInterface, DeletableInterface {

    public function getAll(): array {
        try {
            $sql = "SELECT * FROM insumos ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM insumos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM insumos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM insumos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function add($nombre, $tipo, $unidad) {
        $stmt = $this->db->prepare("INSERT INTO insumos (nombre, tipo, unidad) VALUES (:nombre, :tipo, :unidad)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':unidad' => $unidad,
        ]);
    }

    public function update($id, $nombre, $tipo, $unidad) {
        if (!$this->exists($id)) throw new Exception("No existe el insumo");
        $stmt = $this->db->prepare("UPDATE insumos SET nombre = :nombre, tipo = :tipo, unidad = :unidad WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':unidad' => $unidad,
        ]);
    }

    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }
}