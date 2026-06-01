<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Tool extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT id_herramienta AS id, nombre_herramienta, tipo, estado,
                       fecha_adquisicion, fecha_ultimo_mantenimiento, observacion
                FROM herramienta
                ORDER BY nombre_herramienta ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Tool::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id_herramienta AS id, nombre_herramienta, tipo, estado,
                       fecha_adquisicion, fecha_ultimo_mantenimiento, observacion
                FROM herramienta
                WHERE id_herramienta = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Tool::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM herramienta WHERE id_herramienta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(string $nombre, ?string $tipo = null, string $estado = 'disponible', ?string $fechaAdquisicion = null, ?string $fechaUltimoMantenimiento = null, ?string $observacion = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO herramienta (nombre_herramienta, tipo, estado, fecha_adquisicion, fecha_ultimo_mantenimiento, observacion)
            VALUES (:nombre, :tipo, :estado, :fecha_adquisicion, :fecha_ultimo_mantenimiento, :observacion)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':estado' => $estado,
            ':fecha_adquisicion' => $fechaAdquisicion,
            ':fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            ':observacion' => $observacion,
        ]);
    }

    public function update(int $id, string $nombre, ?string $tipo = null, string $estado = 'disponible', ?string $fechaAdquisicion = null, ?string $fechaUltimoMantenimiento = null, ?string $observacion = null): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception('No existe la herramienta solicitada para modificar.');
        }
        $stmt = $this->db->prepare("
            UPDATE herramienta
            SET nombre_herramienta = :nombre,
                tipo = :tipo,
                estado = :estado,
                fecha_adquisicion = :fecha_adquisicion,
                fecha_ultimo_mantenimiento = :fecha_ultimo_mantenimiento,
                observacion = :observacion
            WHERE id_herramienta = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':estado' => $estado,
            ':fecha_adquisicion' => $fechaAdquisicion,
            ':fecha_ultimo_mantenimiento' => $fechaUltimoMantenimiento,
            ':observacion' => $observacion,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM herramienta WHERE id_herramienta = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }
}
