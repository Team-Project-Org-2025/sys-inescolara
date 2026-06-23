<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class UnidadMedida extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre'  => ['type' => 'nombre', 'required' => true],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_unidad_medida AS id, nombre_unidad_medida, activo FROM unidad_medida WHERE activo = 1 ORDER BY nombre_unidad_medida ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener unidades de medida: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT id_unidad_medida AS id, nombre_unidad_medida FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(string $nombre): bool
    {
        $this->validateData([
            'nombre' => $nombre,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO unidad_medida (nombre_unidad_medida) VALUES (:nombre)");
        return $stmt->execute([
            ':nombre' => trim($nombre),
        ]);
    }

    public function update(int $id, string $nombre): bool
    {
        $this->validateData([
            'nombre' => $nombre,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe la unidad de medida con ID: $id");
        }
        $stmt = $this->db()->prepare("UPDATE unidad_medida SET nombre_unidad_medida = :nombre WHERE id_unidad_medida = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => trim($nombre),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE unidad_medida SET activo = 0 WHERE id_unidad_medida = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE unidad_medida SET activo = 1 WHERE id_unidad_medida = :id");
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
}
