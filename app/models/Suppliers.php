<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;
use Exception;

class Suppliers extends Database {

    public function getAll() {
        try {
            $sql = "SELECT * FROM proveedores ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($nombre, $tipo, $informacion_contacto) {
        $stmt = $this->db->prepare("
            INSERT INTO proveedores (nombre, tipo, informacion_contacto)
            VALUES (:nombre, :tipo, :informacion_contacto)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':informacion_contacto' => $informacion_contacto
        ]);
    }

    public function update($id, $nombre, $tipo, $informacion_contacto) {
        if (!$this->exists($id)) throw new Exception("No existe el proveedor");
        
        $stmt = $this->db->prepare("
            UPDATE proveedores 
            SET nombre = :nombre, 
                tipo = :tipo, 
                informacion_contacto = :informacion_contacto 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':informacion_contacto' => $informacion_contacto
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM proveedores WHERE id = :id");
        return $stmt->execute([':id' => $id]);         
        
    }
}
?>