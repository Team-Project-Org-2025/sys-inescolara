<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Lote extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_planta'       => ['type' => null,      'required' => true],
        'id_ubicacion'    => ['type' => null,      'required' => true],
        'fecha_siembra'   => ['type' => null,      'required' => true],
        'cantidad_inicial'=> ['type' => 'cantidad','required' => true],
        'cantidad_actual' => ['type' => 'cantidad','required' => true],
        'id_estado'       => ['type' => null,      'required' => false],
        'id_categoria'    => ['type' => null,      'required' => false],
        'id_origen'       => ['type' => null,      'required' => false],
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
                        l.cantidad_inicial, l.cantidad_actual, l.observacion, l.imagen, l.activo,
                        l.id_estado, l.id_categoria, l.id_origen,
                        e.nombre AS estado_nombre,
                        c.nombre AS categoria_nombre,
                        o.nombre AS origen_nombre,
                        p.nombre_comun AS planta_nombre,
                        sp.nombre_especie AS especie_nombre,
                        u.nombre_ubicacion AS ubicacion_nombre,
                        cp.precio_final_sugerido AS precio_unitario
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN especie sp ON p.id_especie = sp.id_especie AND sp.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN estado e ON l.id_estado = e.id_estado
                    LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                    LEFT JOIN origen o ON l.id_origen = o.id_origen
                    LEFT JOIN calculo_precio cp ON l.id_lote = cp.id_lote
                    WHERE l.activo = 1
                    ORDER BY l.fecha_siembra DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener lote: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM lote WHERE id_lote = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT
                                        l.*,
                                        e.nombre AS estado_nombre,
                                        c.nombre AS categoria_nombre,
                                        o.nombre AS origen_nombre,
                                        p.nombre_comun AS planta_nombre,
                                        sp.nombre_especie AS especie_nombre
                                    FROM lote l
                                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                    LEFT JOIN especie sp ON p.id_especie = sp.id_especie
                                    LEFT JOIN estado e ON l.id_estado = e.id_estado
                                    LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                                    LEFT JOIN origen o ON l.id_origen = o.id_origen
                                    WHERE l.id_lote = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE lote SET activo = 0 WHERE id_lote = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'lote', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET activo = 1 WHERE id_lote = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getEstados(): array
    {
        $stmt = $this->db()->query("SELECT id_estado AS id, nombre FROM estado WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getCategorias(): array
    {
        $stmt = $this->db()->query("SELECT id_categoria AS id, nombre FROM categoria WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getOrigenes(): array
    {
        $stmt = $this->db()->query("SELECT id_origen AS id, nombre FROM origen WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getIdEstadoVivo(): int
    {
        $stmt = $this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1");
        return (int)$stmt->fetchColumn();
    }

    public function getIdOrigenPorNombre(string $nombre): int
    {
        $stmt = $this->db()->prepare("SELECT id_origen FROM origen WHERE nombre = ? LIMIT 1");
        $stmt->execute([$nombre]);
        return (int)$stmt->fetchColumn();
    }

    public function add($id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null)
    {
        if ($id_estado === null) $id_estado = $this->getIdEstadoVivo();
        if ($id_origen === null) $id_origen = $this->getIdOrigenPorNombre('Siembra');

        $this->validateData([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'id_estado' => $id_estado,
            'id_categoria' => $id_categoria,
            'id_origen' => $id_origen,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, id_estado, id_categoria, id_origen, observacion, imagen) VALUES (:id_planta, :id_ubicacion, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :id_estado, :id_categoria, :id_origen, :observacion, :imagen)");
        $stmt->execute([
            ':id_planta' => $id_planta,
            ':id_ubicacion' => $id_ubicacion,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':id_estado' => $id_estado,
            ':id_categoria' => $id_categoria,
            ':id_origen' => $id_origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);

        $newId = (int) $this->db()->lastInsertId();

        AuditLog::record('CREATE', 'lote', $newId, null, [
            'id_planta'        => $id_planta,
            'id_ubicacion'     => $id_ubicacion,
            'fecha_siembra'    => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual'  => $cantidad_actual,
            'id_estado'        => $id_estado,
            'id_origen'        => $id_origen,
            'observacion'      => $observacion,
        ]);

        return true;
    }

    public function update($id, $id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null)
    {
        if ($id_estado === null) $id_estado = $this->getIdEstadoVivo();
        if ($id_origen === null) $id_origen = $this->getIdOrigenPorNombre('Siembra');

        $this->validateData([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'id_estado' => $id_estado,
            'id_categoria' => $id_categoria,
            'id_origen' => $id_origen,
            'observacion' => $observacion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe el lote con ID: $id");
        }

        $oldData = $this->getById($id);

        $stmt = $this->db()->prepare("UPDATE lote SET id_planta = :id_planta, id_ubicacion = :id_ubicacion, fecha_siembra = :fecha_siembra, cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual, id_estado = :id_estado, id_categoria = :id_categoria, id_origen = :id_origen, observacion = :observacion, imagen = :imagen WHERE id_lote = :id");
        $stmt->execute([
            ':id' => $id,
            ':id_planta' => $id_planta,
            ':id_ubicacion' => $id_ubicacion,
            ':fecha_siembra' => $fecha_siembra,
            ':cantidad_inicial' => $cantidad_inicial,
            ':cantidad_actual' => $cantidad_actual,
            ':id_estado' => $id_estado,
            ':id_categoria' => $id_categoria,
            ':id_origen' => $id_origen,
            ':observacion' => $observacion,
            ':imagen' => $imagen,
        ]);

        AuditLog::record('UPDATE', 'lote', $id, $oldData, [
            'id_planta'        => $id_planta,
            'id_ubicacion'     => $id_ubicacion,
            'fecha_siembra'    => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual'  => $cantidad_actual,
            'id_estado'        => $id_estado,
            'id_origen'        => $id_origen,
            'observacion'      => $observacion,
        ]);

        return true;
    }

    protected function deductStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :cantidad2");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id, ':cantidad2' => $cantidad]);
    }

    protected function restoreStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id]);
    }
}
