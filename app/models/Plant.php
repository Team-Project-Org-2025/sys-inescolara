<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Plant extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM plantas")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('imagen', $columns)) {
                $colPos = in_array('id_categoria', $columns) ? 'id_categoria' : 'nombre_tecnico';
                $this->db->exec("ALTER TABLE plantas ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER `$colPos`");
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar plantas: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_categoria AS especie_id, p.imagen, e.nombre_comun AS especie_nombre 
                    FROM plantas p
                    LEFT JOIN especies e ON p.id_categoria = e.id_especie
                    ORDER BY p.nombre_comun ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener plantas: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT p.*, e.nombre_comun AS especie_nombre 
                                   FROM plantas p
                                   LEFT JOIN especies e ON p.id_categoria = e.id_especie
                                   WHERE p.id_planta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM plantas WHERE id_planta = :id");
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

    public function add(string $nombreComun, ?string $nombreTecnico = null, ?int $especieId = null, ?string $imagen = null)
    {
        $stmt = $this->db->prepare("INSERT INTO plantas (nombre_comun, nombre_tecnico, id_categoria, imagen) VALUES (:nombre_comun, :nombre_tecnico, :id_categoria, :imagen)");
        return $stmt->execute([
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
            ':id_categoria' => $especieId,
            ':imagen' => $imagen,
        ]);
    }

    public function update(int $id, string $nombreComun, ?string $nombreTecnico = null, ?int $especieId = null, ?string $imagen = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la planta con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE plantas SET nombre_comun = :nombre_comun, nombre_tecnico = :nombre_tecnico, id_categoria = :id_categoria, imagen = :imagen WHERE id_planta = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
            ':id_categoria' => $especieId,
            ':imagen' => $imagen,
        ]);
    }
}
