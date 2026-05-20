<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Batch extends Database
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM lote")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('estado', $columns)) {
                $this->db->exec("ALTER TABLE lote ADD COLUMN estado VARCHAR(50) DEFAULT NULL AFTER cantidad_actual");
            }
            if (!in_array('ubicacion', $columns)) {
                $this->db->exec("ALTER TABLE lote ADD COLUMN ubicacion VARCHAR(255) DEFAULT NULL AFTER estado");
            }
            if (!in_array('imagen', $columns)) {
                $this->db->exec("ALTER TABLE lote ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER ubicacion");
            }
            if (!in_array('creado_at', $columns)) {
                $this->db->exec("ALTER TABLE lote ADD COLUMN creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER imagen");
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar lote: ' . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $sql = "SELECT l.id_lote AS id, l.id_planta, l.fecha_siembra, l.cantidad_inicial, l.cantidad_actual,
                           l.estado, l.ubicacion, l.imagen,
                           p.nombre_comun AS planta_nombre, p.nombre_tecnico AS planta_tecnico,
                           e.nombre_comun AS especie_nombre
                    FROM lote l
                    JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN especies e ON p.id_categoria = e.id_especie
                    ORDER BY l.id_lote DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function exists($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lote WHERE id_lote = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT l.id_lote AS id, l.id_planta, l.fecha_siembra, l.cantidad_inicial, l.cantidad_actual,
                   l.estado, l.ubicacion, l.imagen,
                   p.nombre_comun AS planta_nombre, p.nombre_tecnico AS planta_tecnico,
                   e.nombre_comun AS especie_nombre
            FROM lote l
            JOIN plantas p ON l.id_planta = p.id_planta
            LEFT JOIN especies e ON p.id_categoria = e.id_especie
            WHERE l.id_lote = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add($id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO lote (id_planta, fecha_siembra, cantidad_inicial, cantidad_actual, estado, ubicacion, imagen, creado_at)
            VALUES (:id_planta, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :estado, :ubicacion, :imagen, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([
            ':id_planta' => $id_planta,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':ubicacion' => $ubicacion,
            ':imagen' => $imagen,
        ]);
    }

    public function update($id, $id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el registro con este ID");
        }

        $sql = "UPDATE lote SET id_planta = :id_planta, fecha_siembra = :fecha_siembra,
                cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual,
                estado = :estado, ubicacion = :ubicacion, imagen = :imagen WHERE id_lote = :id";
        return $this->db->prepare($sql)->execute([
            ':id' => $id,
            ':id_planta' => $id_planta,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':ubicacion' => $ubicacion,
            ':imagen' => $imagen,
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lote WHERE id_lote = :id");
        return $stmt->execute([':id' => $id]);
    }
}
