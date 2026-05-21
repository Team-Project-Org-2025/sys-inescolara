<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Supplier extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM proveedores")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('nombre_proveedor', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN nombre_proveedor VARCHAR(100) AFTER `id_proveedor`");
                    $this->db->exec("UPDATE proveedores SET nombre_proveedor = `nombre` WHERE nombre_proveedor IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN nombre_proveedor VARCHAR(100) NOT NULL DEFAULT ''");
                }
            }

            if (!in_array('rif_proveedor', $columns)) {
                if (in_array('tipo', $columns)) {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN rif_proveedor VARCHAR(20) DEFAULT NULL AFTER nombre_proveedor");
                    $this->db->exec("UPDATE proveedores SET rif_proveedor = `tipo` WHERE rif_proveedor IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN rif_proveedor VARCHAR(20) DEFAULT NULL AFTER nombre_proveedor");
                }
            }

            if (!in_array('contacto_vendedor', $columns)) {
                $this->db->exec("ALTER TABLE proveedores ADD COLUMN contacto_vendedor VARCHAR(100) DEFAULT NULL AFTER rif_proveedor");
            }

            if (!in_array('telefono_proveedor', $columns)) {
                if (in_array('telefono', $columns)) {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN telefono_proveedor VARCHAR(20) DEFAULT NULL AFTER contacto_vendedor");
                    $this->db->exec("UPDATE proveedores SET telefono_proveedor = `telefono` WHERE telefono_proveedor IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE proveedores ADD COLUMN telefono_proveedor VARCHAR(20) DEFAULT NULL AFTER contacto_vendedor");
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar proveedores: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_proveedor AS id, nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor FROM proveedores ORDER BY nombre_proveedor ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener proveedores: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id_proveedor = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proveedores WHERE id_proveedor = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM proveedores WHERE id_proveedor = :id");
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

    public function add(string $nombreProveedor, ?string $rifProveedor = null, ?string $contactoVendedor = null, ?string $telefonoProveedor = null)
    {
        $stmt = $this->db->prepare("INSERT INTO proveedores (nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor) VALUES (:nombre_proveedor, :rif_proveedor, :contacto_vendedor, :telefono_proveedor)");
        return $stmt->execute([
            ':nombre_proveedor' => $nombreProveedor,
            ':rif_proveedor' => $rifProveedor,
            ':contacto_vendedor' => $contactoVendedor,
            ':telefono_proveedor' => $telefonoProveedor,
        ]);
    }

    public function update(int $id, string $nombreProveedor, ?string $rifProveedor = null, ?string $contactoVendedor = null, ?string $telefonoProveedor = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el proveedor con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE proveedores SET nombre_proveedor = :nombre_proveedor, rif_proveedor = :rif_proveedor, contacto_vendedor = :contacto_vendedor, telefono_proveedor = :telefono_proveedor WHERE id_proveedor = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_proveedor' => $nombreProveedor,
            ':rif_proveedor' => $rifProveedor,
            ':contacto_vendedor' => $contactoVendedor,
            ':telefono_proveedor' => $telefonoProveedor,
        ]);
    }
}
