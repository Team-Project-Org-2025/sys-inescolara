<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Location extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

   
    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM ubicaciones")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('nombre_ubicacion', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db->exec("ALTER TABLE ubicaciones ADD COLUMN nombre_ubicacion VARCHAR(100) AFTER `id_ubicacion`");
                    $this->db->exec("UPDATE ubicaciones SET nombre_ubicacion = `nombre` WHERE nombre_ubicacion IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE ubicaciones ADD COLUMN nombre_ubicacion VARCHAR(100) NOT NULL DEFAULT '' AFTER `id_ubicacion`");
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar ubicaciones: ' . $e->getMessage());
        }
    }

   
    public function getAll(): array
    {
        try {
            $sql = "SELECT id_ubicacion AS id, nombre_ubicacion FROM ubicaciones ORDER BY nombre_ubicacion ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener ubicaciones: ' . $e->getMessage());
            return [];
        }
    }


    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_ubicacion AS id, nombre_ubicacion FROM ubicaciones WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener ubicación por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ubicaciones WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    
    public function hasAssociatedLots(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM lote WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            error_log('Error al verificar relaciones de ubicación: ' . $e->getMessage());
            return true; // Bloqueamos por seguridad en caso de error de consulta
        }
    }

    
    public function delete(int $id): bool
    {
        try {
            if ($this->hasAssociatedLots($id)) {
                throw new \Exception("No se puede eliminar la ubicación: Existen lotes vinculados en el inventario activo.");
            }

            $stmt = $this->db->prepare("DELETE FROM ubicaciones WHERE id_ubicacion = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('Error al eliminar ubicación: ' . $e->getMessage());
            throw $e; // Re-lanzamos la excepción para que el controlador la capture y la muestre en la vista
        }
    }

    
    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    
    public function add(string $nombreUbicacion): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO ubicaciones (nombre_ubicacion) VALUES (:nombre_ubicacion)");
            return $stmt->execute([
                ':nombre_ubicacion' => trim($nombreUbicacion)
            ]);
        } catch (\Throwable $e) {
            error_log('Error al agregar ubicación: ' . $e->getMessage());
            return false;
        }
    }

    
    public function update(int $id, string $nombreUbicacion): bool
    {
        try {
            if (!$this->exists($id)) {
                throw new \Exception("No existe la ubicación con ID: $id");
            }
            
            $stmt = $this->db->prepare("UPDATE ubicaciones SET nombre_ubicacion = :nombre_ubicacion WHERE id_ubicacion = :id");
            return $stmt->execute([
                ':id' => $id,
                ':nombre_ubicacion' => trim($nombreUbicacion)
            ]);
        } catch (\Throwable $e) {
            error_log('Error al actualizar ubicación: ' . $e->getMessage());
            throw $e;
        }
    }
}