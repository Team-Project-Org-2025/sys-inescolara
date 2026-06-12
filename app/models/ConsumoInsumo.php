<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class ConsumoInsumo extends Database implements ReadableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_asignacion' => ['type' => null,      'required' => true],
        'id_insumo'     => ['type' => null,      'required' => true],
        'cantidad_usada'=> ['type' => 'cantidad','required' => true],
        'costo_unitario'=> ['type' => 'precio',  'required' => true],
        'fecha_consumo' => ['type' => null,      'required' => true],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        $sql = "SELECT c.*, i.nombre_insumo, i.stock_actual, u.simbolo
                FROM consumo_insumos c
                LEFT JOIN insumo i ON c.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                ORDER BY c.fecha_consumo DESC";
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM consumo_insumos WHERE id_consumo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM consumo_insumos WHERE id_consumo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getByAssignment(int $asignacionId): array
    {
        $sql = "SELECT c.*, i.nombre_insumo, u.simbolo
                FROM consumo_insumos c
                LEFT JOIN insumo i ON c.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE c.id_asignacion = :id_asignacion
                ORDER BY c.fecha_consumo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_asignacion' => $asignacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(array $data): bool
    {
        $this->validateData([
            'id_asignacion' => $data['id_asignacion'] ?? null,
            'id_insumo'     => $data['id_insumo'] ?? null,
            'cantidad_usada'=> $data['cantidad_usada'] ?? null,
            'costo_unitario'=> $data['costo_unitario'] ?? null,
            'fecha_consumo' => $data['fecha_consumo'] ?? null,
        ]);
        $stmt = $this->db->prepare("
            INSERT INTO consumo_insumos (id_asignacion, id_insumo, cantidad_usada, costo_unitario, fecha_consumo)
            VALUES (:id_asignacion, :id_insumo, :cantidad_usada, :costo_unitario, :fecha_consumo)
        ");
        return $stmt->execute([
            ':id_asignacion'  => $data['id_asignacion'],
            ':id_insumo'      => $data['id_insumo'],
            ':cantidad_usada' => $data['cantidad_usada'],
            ':costo_unitario' => $data['costo_unitario'],
            ':fecha_consumo'  => $data['fecha_consumo'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM consumo_insumos WHERE id_consumo = :id");
        return $stmt->execute([':id' => $id]);
    }
}
