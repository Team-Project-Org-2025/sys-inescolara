<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Batch extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_planta'       => ['type' => null,      'required' => true],
        'id_ubicacion'    => ['type' => null,      'required' => true],
        'fecha_siembra'   => ['type' => null,      'required' => true],
        'cantidad_inicial'=> ['type' => 'cantidad','required' => true],
        'cantidad_actual' => ['type' => 'cantidad','required' => true],
        'estado'          => ['type' => null,      'required' => false],
        'categoria'       => ['type' => null,      'required' => false],
        'origen'          => ['type' => null,      'required' => false],
        'observacion'     => ['type' => null,      'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        l.id_lote AS id, l.id_planta, l.id_ubicacion, l.fecha_siembra,
                        l.cantidad_inicial, l.cantidad_actual, l.estado, l.categoria, l.origen, l.observacion, l.imagen, l.activo,
                        p.nombre_comun AS planta_nombre,
                        e.nombre_especie AS especie_nombre,
                        u.nombre_ubicacion AS ubicacion_nombre,
                        c.precio_final_sugerido AS precio_unitario
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote
                    WHERE l.activo = 1
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
        $stmt = $this->db->prepare("UPDATE lote SET activo = 0 WHERE id_lote = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE lote SET activo = 1 WHERE id_lote = :id");
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

    public function add($id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado = 'Activo', $categoria = null, $origen = 'Siembra', $observacion = null, $imagen = null)
    {
        $this->validateData([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'estado' => $estado,
            'categoria' => $categoria,
            'origen' => $origen,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db->prepare("INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, estado, categoria, origen, observacion, imagen) VALUES (:id_planta, :id_ubicacion, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :estado, :categoria, :origen, :observacion, :imagen)");
        return $stmt->execute([
            ':id_planta' => $id_planta,
            ':id_ubicacion' => $id_ubicacion,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':categoria' => $categoria,
            ':origen' => $origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);
    }

    public function update($id, $id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado = 'Activo', $categoria = null, $origen = 'Siembra', $observacion = null, $imagen = null)
    {
        $this->validateData([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'estado' => $estado,
            'categoria' => $categoria,
            'origen' => $origen,
            'observacion' => $observacion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe el lote con ID: $id");
        }
        $stmt = $this->db->prepare("UPDATE lote SET id_planta = :id_planta, id_ubicacion = :id_ubicacion, fecha_siembra = :fecha_siembra, cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual, estado = :estado, categoria = :categoria, origen = :origen, observacion = :observacion, imagen = :imagen WHERE id_lote = :id");
        return $stmt->execute([
            ':id' => $id,
            ':id_planta' => $id_planta,
            ':id_ubicacion' => $id_ubicacion,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':estado' => $estado,
            ':categoria' => $categoria,
            ':origen' => $origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);
    }

    public function deductStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :cantidad2");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id, ':cantidad2' => $cantidad]);
    }

    public function restoreStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id]);
    }

    public function getDb()
    {
        return $this->db;
    }
}
