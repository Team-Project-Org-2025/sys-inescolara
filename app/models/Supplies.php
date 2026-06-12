<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Exception;

class Supplies extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre'            => ['type' => 'nombreProducto', 'required' => true],
        'id_unidad_medida'  => ['type' => null,            'required' => true],
        'categoria'         => ['type' => null,            'required' => false],
        'stock'             => ['type' => 'cantidad',      'required' => false],
        'costo'             => ['type' => 'precio',        'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array {
        try {
            $sql = "SELECT i.id_insumo AS id, i.id_insumo, i.nombre_insumo, i.id_unidad_medida, i.categoria, i.stock_actual, i.costo_unitario_actual, i.activo,
                           u.nombre_unidad_medida, u.simbolo
                    FROM insumo i
                    LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida AND u.activo = 1
                    WHERE i.activo = 1
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
        $this->validateData([
            'nombre' => $nombre,
            'id_unidad_medida' => $id_unidad_medida,
            'categoria' => $categoria,
            'stock' => $stock,
            'costo' => $costo,
        ]);
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
        $this->validateData([
            'nombre' => $nombre,
            'id_unidad_medida' => $id_unidad_medida,
            'categoria' => $categoria,
            'stock' => $stock,
            'costo' => $costo,
        ]);
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
        $stmt = $this->db->prepare("UPDATE insumo SET activo = 0 WHERE id_insumo = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool {
        $stmt = $this->db->prepare("UPDATE insumo SET activo = 1 WHERE id_insumo = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }

    public function findByNameAndCategory(string $name, string $category): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id_insumo, nombre_insumo, stock_actual
            FROM insumo
            WHERE nombre_insumo = :nombre AND categoria = :categoria AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([':nombre' => $name, ':categoria' => $category]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function increaseStock(int $id, float $quantity): bool
    {
        $stmt = $this->db->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id");
        return $stmt->execute([':id' => $id, ':cantidad' => $quantity]);
    }
}
