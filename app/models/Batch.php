<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Batch extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        l.id_lote AS id, l.id_planta, l.id_ubicacion, l.fecha_siembra,
                        l.cantidad_inicial, l.cantidad_actual, l.estado, l.origen, l.observacion, l.imagen,
                        p.nombre_comun AS planta_nombre,
                        e.nombre_especie AS especie_nombre
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN especie e ON p.id_especie = e.id_especie
                    ORDER BY l.fecha_siembra DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener lote: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lote WHERE id_lote = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT
                                        l.*,
                                        p.nombre_comun AS planta_nombre,
                                        e.nombre_especie AS especie_nombre
                                    FROM lote l
                                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                    LEFT JOIN especie e ON p.id_especie = e.id_especie
                                    WHERE l.id_lote = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM lote WHERE id_lote = :id");
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

    public function add($id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado = 'Activo', $origen = 'Siembra', $observacion = null, $imagen = null)
    {
        $stmt = $this->db->prepare("INSERT INTO lote (id_planta, fecha_siembra, cantidad_inicial, cantidad_actual, estado, origen, observacion, imagen) VALUES (:id_planta, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :estado, :origen, :observacion, :imagen)");
        return $stmt->execute([
            ':id_planta' => $id_planta,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':origen' => $origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);
    }

    public function update($id, $id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado = 'Activo', $origen = 'Siembra', $observacion = null, $imagen = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el lote con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE lote SET id_planta = :id_planta, fecha_siembra = :fecha_siembra, cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual, estado = :estado, origen = :origen, observacion = :observacion, imagen = :imagen WHERE id_lote = :id");
        return $stmt->execute([
            ':id' => $id,
            ':id_planta' => $id_planta,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':origen' => $origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);
    }
}
