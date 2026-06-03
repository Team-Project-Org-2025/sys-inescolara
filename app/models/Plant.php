<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Plant extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_especie AS especie_id, p.imagen, p.activo,
                        e.nombre_especie AS especie_nombre,
                        (SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta AND l2.activo = 1) AS stock_lotes,
                        c.precio_final_sugerido AS precio_vigente
                    FROM plantas p
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    LEFT JOIN planta_precio_vigente pv ON p.id_planta = pv.id_planta
                    LEFT JOIN calculo_precio c ON pv.id_calculo = c.id_calculo
                    WHERE p.activo = 1
                    ORDER BY p.nombre_comun ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener plantas: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT p.*, e.nombre_especie AS especie_nombre
                                   FROM plantas p
                                   LEFT JOIN especie e ON p.id_especie = e.id_especie
                                   WHERE p.id_planta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plantas SET activo = 0 WHERE id_planta = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plantas SET activo = 1 WHERE id_planta = :id");
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

    public function add(string $nombreComun, ?string $nombreTecnico = null, ?int $especieId = null, ?string $imagen = null)
    {
        $stmt = $this->db->prepare("INSERT INTO plantas (nombre_comun, nombre_tecnico, id_especie, imagen) VALUES (:nombre_comun, :nombre_tecnico, :id_especie, :imagen)");
        return $stmt->execute([
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
            ':id_especie' => $especieId,
            ':imagen' => $imagen,
        ]);
    }

    public function update(int $id, string $nombreComun, ?string $nombreTecnico = null, ?int $especieId = null, ?string $imagen = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe la planta con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE plantas SET nombre_comun = :nombre_comun, nombre_tecnico = :nombre_tecnico, id_especie = :id_especie, imagen = :imagen WHERE id_planta = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_comun' => $nombreComun,
            ':nombre_tecnico' => $nombreTecnico,
            ':id_especie' => $especieId,
            ':imagen' => $imagen,
        ]);
    }
}
