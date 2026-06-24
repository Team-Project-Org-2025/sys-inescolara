<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use SysInescolara\models\AuditLog;

class Ornato extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_cliente'  => ['type' => null,   'required' => false],
        'tipo_ornato' => ['type' => null,   'required' => false],
        'descripcion' => ['type' => null,   'required' => false],
        'ubicacion'   => ['type' => null,   'required' => false],
        'monto_total' => ['type' => 'precio','required' => false],
        'fecha'       => ['type' => null,   'required' => true],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        o.id_ornato AS id,
                        o.id_ornato,
                        o.id_cliente,
                        o.tipo_ornato,
                        o.descripcion,
                        o.ubicacion,
                        o.monto_total,
                        o.fecha,
                        o.activo,
                        c.nombre_cliente
                    FROM ornatos o
                    LEFT JOIN cliente c ON o.id_cliente = c.id_cliente AND c.activo = 1
                    WHERE o.activo = 1
                    ORDER BY o.fecha DESC, o.id_ornato DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener ornatos: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                                            o.*,
                                            c.nombre_cliente
                                        FROM ornatos o
                                        LEFT JOIN cliente c ON o.id_cliente = c.id_cliente
                                        WHERE o.id_ornato = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener ornato por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM ornatos WHERE id_ornato = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            error_log('Error en exists: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->getById($id);
            $stmt = $this->db()->prepare("UPDATE ornatos SET activo = 0 WHERE id_ornato = :id");
            $result = $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'ornatos', $id, $oldData, null);
            return $result;
        } catch (\Throwable $e) {
            error_log('Error al eliminar ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE ornatos SET activo = 1 WHERE id_ornato = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('Error al restaurar ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerUltimoId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function agregar(array $datos): bool
    {
        $this->validateData([
            'id_cliente'  => $datos['id_cliente'] ?? null,
            'tipo_ornato' => $datos['tipo_ornato'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'ubicacion'   => $datos['ubicacion'] ?? null,
            'monto_total' => $datos['monto_total'] ?? null,
            'fecha'       => $datos['fecha'] ?? null,
        ]);
        try {
            $stmt = $this->db()->prepare("INSERT INTO ornatos
                (id_cliente, tipo_ornato, descripcion, ubicacion, monto_total, fecha)
                VALUES (:id_cliente, :tipo_ornato, :descripcion, :ubicacion, :monto_total, :fecha)");
            $result = $stmt->execute([
                ':id_cliente'  => $datos['id_cliente'],
                ':tipo_ornato' => $datos['tipo_ornato'] ?? 'Venta',
                ':descripcion' => $datos['descripcion'] ?? null,
                ':ubicacion'   => $datos['ubicacion'] ?? null,
                ':monto_total' => $datos['monto_total'] ?? 0.00,
                ':fecha'       => $datos['fecha'],
            ]);
            if ($result) {
                AuditLog::record('CREATE', 'ornatos', $this->db()->lastInsertId(), null, $datos);
            }
            return $result;
        } catch (\Throwable $e) {
            error_log('Error al agregar ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function agregarDetalles(int $idOrnato, array $items): bool
    {
        try {
            $this->db()->beginTransaction();
            $stmt = $this->db()->prepare("INSERT INTO detalle_ornatos
                (id_ornato, id_lote, cantidad, precio_unitario, sub_total)
                VALUES (:id_ornato, :id_lote, :cantidad, :precio_unitario, :sub_total)");

            foreach ($items as $item) {
                $stmt->execute([
                    ':id_ornato'       => $idOrnato,
                    ':id_lote'         => (int)($item['id_lote'] ?? 0),
                    ':cantidad'        => (int)($item['cantidad'] ?? 0),
                    ':precio_unitario' => $item['precio_unitario'] ?? null,
                    ':sub_total'       => $item['sub_total'] ?? null,
                ]);
            }

            $this->db()->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log('Error al agregar detalles de ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function actualizar(int $id, array $datos): bool
    {
        $this->validateData([
            'id_cliente'  => $datos['id_cliente'] ?? null,
            'tipo_ornato' => $datos['tipo_ornato'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'ubicacion'   => $datos['ubicacion'] ?? null,
            'monto_total' => $datos['monto_total'] ?? null,
            'fecha'       => $datos['fecha'] ?? null,
        ]);
        try {
            if (!$this->exists($id)) {
                throw new \Exception("No existe el ornato con ID: $id");
            }
            $stmt = $this->db()->prepare("UPDATE ornatos SET
                id_cliente  = :id_cliente,
                tipo_ornato = :tipo_ornato,
                descripcion = :descripcion,
                ubicacion   = :ubicacion,
                monto_total = :monto_total,
                fecha       = :fecha
                WHERE id_ornato = :id");
            $oldData = $this->getById($id);
            $result = $stmt->execute([
                ':id'          => $id,
                ':id_cliente'  => $datos['id_cliente'],
                ':tipo_ornato' => $datos['tipo_ornato'] ?? 'Venta',
                ':descripcion' => $datos['descripcion'] ?? null,
                ':ubicacion'   => $datos['ubicacion'] ?? null,
                ':monto_total' => $datos['monto_total'] ?? 0.00,
                ':fecha'       => $datos['fecha'],
            ]);
            AuditLog::record('UPDATE', 'ornatos', $id, $oldData, $datos);
            return $result;
        } catch (\Throwable $e) {
            error_log('Error al actualizar ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function actualizarDetalles(int $idOrnato, array $items): bool
    {
        try {
            $this->db()->beginTransaction();

            $stmtDelete = $this->db()->prepare("DELETE FROM detalle_ornatos WHERE id_ornato = :id_ornato");
            $stmtDelete->execute([':id_ornato' => $idOrnato]);

            $stmtInsert = $this->db()->prepare("INSERT INTO detalle_ornatos
                (id_ornato, id_lote, cantidad, precio_unitario, sub_total)
                VALUES (:id_ornato, :id_lote, :cantidad, :precio_unitario, :sub_total)");

            foreach ($items as $item) {
                $stmtInsert->execute([
                    ':id_ornato'       => $idOrnato,
                    ':id_lote'         => (int)($item['id_lote'] ?? 0),
                    ':cantidad'        => (int)($item['cantidad'] ?? 0),
                    ':precio_unitario' => $item['precio_unitario'] ?? null,
                    ':sub_total'       => $item['sub_total'] ?? null,
                ]);
            }

            $this->db()->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log('Error al actualizar detalles de ornato: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerDetalles(int $idOrnato): array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                                            d.id_detalle_ornato,
                                            d.id_ornato,
                                            d.id_lote,
                                            d.cantidad,
                                            d.precio_unitario,
                                            d.sub_total,
                                            l.cantidad_actual,
                                            p.nombre_comun AS planta_nombre,
                                            e.nombre_especie AS especie_nombre
                                        FROM detalle_ornatos d
                                        JOIN lote l ON d.id_lote = l.id_lote
                                        LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                        LEFT JOIN especie e ON p.id_especie = e.id_especie
                                        WHERE d.id_ornato = :id_ornato
                                        ORDER BY d.id_detalle_ornato ASC");
            $stmt->execute([':id_ornato' => $idOrnato]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener detalles de ornato: ' . $e->getMessage());
            return [];
        }
    }
}
