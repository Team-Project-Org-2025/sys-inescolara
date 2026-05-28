<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;
use Exception;

class Supplies extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    
    public function getAll(): array {
        try {
                $sql = "SELECT id_insumo, nombre_insumo, unidad_medida, stock_actual, costo_unitario_actual 
                    FROM insumo 
                    ORDER BY nombre_insumo ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            throw new Exception("Error en la consulta SQL de insumo: " . $e->getMessage());
        }
    }

    
    public function exists(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM insumo WHERE id_insumo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id_insumo, nombre_insumo, unidad_medida, stock_actual, costo_unitario_actual 
                        FROM insumo 
                        WHERE id_insumo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    
    public function add($nombre, $unidad, $stock, $costo) {
        $stmt = $this->db->prepare("
            INSERT INTO insumo (nombre_insumo, unidad_medida, stock_actual, costo_unitario_actual)
            VALUES (:nombre, :unidad, :stock, :costo)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':unidad' => $unidad,
            ':stock'  => $stock,
            ':costo'  => $costo
        ]);
    }

  
    public function update($id, $nombre, $unidad, $stock, $costo) {
        if (!$this->exists($id)) {
            throw new Exception("No existe el insumo solicitado para modificar.");
        }
        
        $stmt = $this->db->prepare("
            UPDATE insumo 
            SET nombre_insumo = :nombre, 
                unidad_medida = :unidad, 
                stock_actual = :stock,
                costo_unitario_actual = :costo
            WHERE id_insumo = :id
        ");
        return $stmt->execute([
            ':id'     => $id,
            ':nombre' => $nombre,
            ':unidad' => $unidad,
            ':stock'  => $stock,
            ':costo'  => $costo
        ]);
    }

    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM insumo WHERE id_insumo = :id");
        return $stmt->execute([':id' => $id]);
    }

    
    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }
}
