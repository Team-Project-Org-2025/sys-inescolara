<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Employee extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM trabajadores")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('nombre_trabajador', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN nombre_trabajador VARCHAR(100) AFTER `id_trabajadores`");
                    $this->db->exec("UPDATE trabajadores SET nombre_trabajador = `nombre` WHERE nombre_trabajador IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN nombre_trabajador VARCHAR(100) NOT NULL DEFAULT ''");
                }
            }

            if (!in_array('apellido_trabajador', $columns)) {
                if (in_array('apellido', $columns)) {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN apellido_trabajador VARCHAR(100) DEFAULT NULL AFTER nombre_trabajador");
                    $this->db->exec("UPDATE trabajadores SET apellido_trabajador = `apellido` WHERE apellido_trabajador IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN apellido_trabajador VARCHAR(100) DEFAULT NULL AFTER nombre_trabajador");
                }
            }

            if (!in_array('cedula_trabajador', $columns)) {
                if (in_array('cedula', $columns)) {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN cedula_trabajador VARCHAR(20) DEFAULT NULL AFTER apellido_trabajador");
                    $this->db->exec("UPDATE trabajadores SET cedula_trabajador = `cedula` WHERE cedula_trabajador IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN cedula_trabajador VARCHAR(20) DEFAULT NULL AFTER apellido_trabajador");
                }
            }

            if (!in_array('telefono_trabajador', $columns)) {
                if (in_array('telefono', $columns)) {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN telefono_trabajador VARCHAR(20) DEFAULT NULL AFTER cedula_trabajador");
                    $this->db->exec("UPDATE trabajadores SET telefono_trabajador = `telefono` WHERE telefono_trabajador IS NULL");
                } else {
                    $this->db->exec("ALTER TABLE trabajadores ADD COLUMN telefono_trabajador VARCHAR(20) DEFAULT NULL AFTER cedula_trabajador");
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar trabajadores: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_trabajadores AS id, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador FROM trabajadores ORDER BY nombre_trabajador ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener trabajadores: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM trabajadores WHERE id_trabajadores = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM trabajadores WHERE id_trabajadores = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM trabajadores WHERE id_trabajadores = :id");
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

    public function add(string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null)
    {
        $stmt = $this->db->prepare("INSERT INTO trabajadores (nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador) VALUES (:nombre_trabajador, :apellido_trabajador, :cedula_trabajador, :telefono_trabajador)");
        return $stmt->execute([
            ':nombre_trabajador' => $nombreTrabajador,
            ':apellido_trabajador' => $apellidoTrabajador,
            ':cedula_trabajador' => $cedulaTrabajador,
            ':telefono_trabajador' => $telefonoTrabajador,
        ]);
    }

    public function update(int $id, string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el trabajador con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE trabajadores SET nombre_trabajador = :nombre_trabajador, apellido_trabajador = :apellido_trabajador, cedula_trabajador = :cedula_trabajador, telefono_trabajador = :telefono_trabajador WHERE id_trabajadores = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_trabajador' => $nombreTrabajador,
            ':apellido_trabajador' => $apellidoTrabajador,
            ':cedula_trabajador' => $cedulaTrabajador,
            ':telefono_trabajador' => $telefonoTrabajador,
        ]);
    }
}
