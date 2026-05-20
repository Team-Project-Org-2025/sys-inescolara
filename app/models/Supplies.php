<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;
use Exception;

class Supplies extends Database {

    public function getAll() {
        try {
            $sql = "SELECT * FROM insumos ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM insumos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM insumos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($nombre, $tipo, $unidad) {
        $stmt = $this->db->prepare("
            INSERT INTO insumos (nombre, tipo, unidad)
            VALUES (:nombre, :tipo, :unidad)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':unidad' => $unidad
        ]);
    }

    public function update($id, $nombre, $tipo, $unidad) {
        if (!$this->exists($id)) throw new Exception("No existe el insumo");
        
        $stmt = $this->db->prepare("
            UPDATE insumos 
            SET nombre = :nombre, 
                tipo = :tipo, 
                unidad = :unidad 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':unidad' => $unidad
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM insumos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}