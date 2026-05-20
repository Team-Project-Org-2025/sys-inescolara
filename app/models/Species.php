<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Species extends Database
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM especies")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('id', $columns) && in_array('id_especie', $columns)) {
                // Compatibilidad: si la PK se llama id_especie, se maneja con alias en las queries
            }

            if (!in_array('nombre_comun', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db->exec("ALTER TABLE especies ADD COLUMN nombre_comun VARCHAR(100) AFTER nombre");
                    $this->db->exec("UPDATE especies SET nombre_comun = nombre WHERE nombre_comun IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE especies ADD COLUMN nombre_comun VARCHAR(100) NOT NULL DEFAULT ''");
                }
            }

            if (!in_array('nombre_tecnico', $columns)) {
                $this->db->exec("ALTER TABLE especies ADD COLUMN nombre_tecnico VARCHAR(100) DEFAULT NULL AFTER nombre_comun");
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar especies: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id_especie AS id, nombre_comun, nombre_tecnico FROM especies ORDER BY nombre_comun ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT id_especie AS id, nombre_comun, nombre_tecnico FROM especies WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM especies WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(string $nombreComun, ?string $nombreTecnico = null)
    {
        $stmt = $this->db->prepare("INSERT INTO especies (nombre_comun, nombre_tecnico) VALUES (:nombre_comun, :nombre_tecnico)");
        return $stmt->execute([
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
        ]);
    }

    public function update(int $id, string $nombreComun, ?string $nombreTecnico = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la especie con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE especies SET nombre_comun = :nombre_comun, nombre_tecnico = :nombre_tecnico WHERE id_especie = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM especies WHERE id_especie = :id");
        return $stmt->execute([':id' => $id]);
    }
}
