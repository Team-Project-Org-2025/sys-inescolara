<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class UnidadMedida extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_unidad_medida AS id, nombre_unidad_medida, simbolo FROM unidad_medida ORDER BY nombre_unidad_medida ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener unidades de medida: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
