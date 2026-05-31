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
            $sql = "SELECT i.id_insumo, i.nombre_insumo, i.id_unidad_medida, i.categoria, i.stock_actual, i.costo_unitario_actual,
                           u.nombre_unidad_medida, u.simbolo
                    FROM insumo i
                    LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                    ORDER BY i.nombre_insumo ASC";
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
        $stmt = $this->db->prepare("SELECT i.*, u.nombre_unidad_medida, u.simbolo
                        FROM insumo i
                        LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                        WHERE i.id_insumo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function add($nombre, $id_unidad_medida, $categoria = null, $stock = 0, $costo = 0) {
        $stmt = $this->db->prepare("
            INSERT INTO insumo (nombre_insumo, id_unidad_medida, categoria, stock_actual, costo_unitario_actual)
            VALUES (:nombre, :id_unidad_medida, :categoria, :stock, :costo)
        ");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':id_unidad_medida' => $id_unidad_medida,
            ':categoria' => $categoria,
            ':stock'  => $stock,
            ':costo'  => $costo
        ]);
    }

    public function update($id, $nombre, $id_unidad_medida, $categoria = null, $stock = 0, $costo = 0) {
        if (!$this->exists($id)) {
            throw new Exception("No existe el insumo solicitado para modificar.");
        }

        $stmt = $this->db->prepare("
            UPDATE insumo
            SET nombre_insumo = :nombre,
                id_unidad_medida = :id_unidad_medida,
                categoria = :categoria,
                stock_actual = :stock,
                costo_unitario_actual = :costo
            WHERE id_insumo = :id
        ");
        return $stmt->execute([
            ':id'     => $id,
            ':nombre' => $nombre,
            ':id_unidad_medida' => $id_unidad_medida,
            ':categoria' => $categoria,
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
