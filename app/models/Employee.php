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
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_trabajador AS id, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, activo FROM trabajadores ORDER BY nombre_trabajador ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener trabajadores: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM trabajadores WHERE id_trabajador = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM trabajadores WHERE id_trabajador = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM trabajadores WHERE id_trabajador = :id");
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

    public function add(string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null, ?string $cargo = null, bool $activo = true)
    {
        $stmt = $this->db->prepare("INSERT INTO trabajadores (nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, activo) VALUES (:nombre_trabajador, :apellido_trabajador, :cedula_trabajador, :telefono_trabajador, :cargo, :activo)");
        return $stmt->execute([
            ':nombre_trabajador' => $nombreTrabajador,
            ':apellido_trabajador' => $apellidoTrabajador,
            ':cedula_trabajador' => $cedulaTrabajador,
            ':telefono_trabajador' => $telefonoTrabajador,
            ':cargo' => $cargo,
            ':activo' => $activo ? 1 : 0,
        ]);
    }

    public function update(int $id, string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null, ?string $cargo = null, bool $activo = true)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el trabajador con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE trabajadores SET nombre_trabajador = :nombre_trabajador, apellido_trabajador = :apellido_trabajador, cedula_trabajador = :cedula_trabajador, telefono_trabajador = :telefono_trabajador, cargo = :cargo, activo = :activo WHERE id_trabajador = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_trabajador' => $nombreTrabajador,
            ':apellido_trabajador' => $apellidoTrabajador,
            ':cedula_trabajador' => $cedulaTrabajador,
            ':telefono_trabajador' => $telefonoTrabajador,
            ':cargo' => $cargo,
            ':activo' => $activo ? 1 : 0,
        ]);
    }
}
