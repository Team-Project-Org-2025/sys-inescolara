<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class UnidadMedida extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_unidad_medida AS id, nombre_unidad_medida, simbolo FROM unidad_medida ORDER BY nombre_unidad_medida ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener unidades de medida: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id_unidad_medida AS id, nombre_unidad_medida, simbolo FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(string $nombre, ?string $simbolo = null): bool
    {
        $stmt = $this->db->prepare("INSERT INTO unidad_medida (nombre_unidad_medida, simbolo) VALUES (:nombre, :simbolo)");
        return $stmt->execute([
            ':nombre' => trim($nombre),
            ':simbolo' => $simbolo ? trim($simbolo) : null,
        ]);
    }

    public function update(int $id, string $nombre, ?string $simbolo = null): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la unidad de medida con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE unidad_medida SET nombre_unidad_medida = :nombre, simbolo = :simbolo WHERE id_unidad_medida = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => trim($nombre),
            ':simbolo' => $simbolo ? trim($simbolo) : null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM unidad_medida WHERE id_unidad_medida = :id");
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
