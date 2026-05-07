<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;
use Exception;

class Employees extends Database 
{
    public function getAll() {
        try {
            $stmt = $this->db->query("
                SELECT id, nombre, cedula, telefono 
                FROM trabajadores 
                ORDER BY nombre ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log("Error en Employees::getAll: " . $e->getMessage());
            return [];
        }
    }

    public function exists($cedula) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM trabajadores WHERE cedula = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT id, nombre, cedula, telefono 
            FROM trabajadores 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($nombre, $cedula, $telefono) {
        if ($this->exists($cedula)) {
            throw new Exception("Ya existe un trabajador con esta cédula");
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO trabajadores (nombre, cedula, telefono)
            VALUES (:nombre, :cedula, :telefono)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':cedula' => $cedula,
            ':telefono' => $telefono
        ]);
    }

    public function update($id, $nombre, $cedula, $telefono) {
        $stmt = $this->db->prepare("
            UPDATE trabajadores 
            SET nombre = :nombre, 
                cedula = :cedula,
                telefono = :telefono
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':cedula' => $cedula,
            ':telefono' => $telefono
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM trabajadores WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function searchByLocation($query) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, cedula, telefono
                FROM trabajadores 
                WHERE nombre LIKE :query OR cedula LIKE :query
                ORDER BY nombre ASC
                LIMIT 20
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([':query' => $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("Error en Employees::searchByLocation: " . $e->getMessage());
            return [];
        }
    }
}