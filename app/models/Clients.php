<?php
namespace Inescolara\models;
use Inescolara\core\Database;
use PDO;
use Exception;

class Clients extends Database {

    public function getAll() {
        try {
            $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($nombre, $informacion_contacto) {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (nombre, informacion_contacto)
            VALUES (:nombre, :informacion_contacto)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':informacion_contacto' => $informacion_contacto
        ]);
    }

    public function update($id, $nombre, $informacion_contacto) {
        if (!$this->exists($id)) throw new Exception("No existe el cliente");
        
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET nombre = :nombre, 
                informacion_contacto = :informacion_contacto 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':informacion_contacto' => $informacion_contacto
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}