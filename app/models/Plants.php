<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;
use Exception;

class Plants extends Database
{

    public function getAll()
    {
        try {
            $sql = "SELECT e.nombre_comun, e.nombre_tecnico, l.* FROM lotes l
                    JOIN especies e ON l.especie_id = e.id 
                    ORDER BY l.id ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lotes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, e.nombre_comun, e.nombre_tecnico 
            FROM lotes l
            JOIN especies e ON l.especie_id = e.id
            WHERE l.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion)
    {
        $stmt = $this->db->prepare("
            INSERT INTO lotes (especie_id, fecha_siembra, cantidad_inicial, cantidad_actual, estado, ubicacion, creado_at)
            VALUES (:especie_id, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :estado, :ubicacion, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([
            ':especie_id' => $especie_id,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':ubicacion' => $ubicacion
        ]);
    }

    public function update($id, $especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion)
    {
        if (!$this->exists($id)) throw new Exception("No existe el registro con este ID");

        $stmt = $this->db->prepare("
            UPDATE lotes 
            SET especie_id = :especie_id, 
                fecha_siembra = :fecha_siembra, 
                cantidad_inicial = :cantidad_inicial,
                cantidad_actual = :cantidad_actual,
                estado = :estado, 
                ubicacion = :ubicacion 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':especie_id' => $especie_id,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':ubicacion' => $ubicacion
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lotes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function searchByLocation($query)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.id, e.nombre_comun, l.estado, l.ubicacion
                FROM lotes l
                JOIN especies e ON l.especie_id = e.id
                WHERE (l.ubicacion ILIKE :query OR l.estado ILIKE :query)
                ORDER BY l.id ASC
                LIMIT 20
            ");
            $searchTerm = $query . '%';
            $stmt->execute([':query' => $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("Error en searchByLocation: " . $e->getMessage());
            return [];
        }
    }
}
