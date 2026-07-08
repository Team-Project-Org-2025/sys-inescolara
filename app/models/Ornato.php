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

    private ?int $id = null;
    private ?int $idCliente = null;
    private string $tipoOrnato = 'Venta';
    private ?string $descripcion = null;
    private ?string $ubicacion = null;
    private float $montoTotal = 0.00;
    private ?string $fecha = null;
    private int $activo = 1;

    protected array $validationRules = [
        'id_cliente'  => ['type' => 'cantidad','required' => true],
        'tipo_ornato' => ['type' => null,      'required' => true],
        'descripcion' => ['type' => null,      'required' => false],
        'ubicacion'   => ['type' => null,      'required' => false],
        'monto_total' => ['type' => 'precio',  'required' => false],
        'fecha'       => ['type' => null,      'required' => true],
    ];

    protected array $fillable = ['id_cliente', 'tipo_ornato', 'descripcion', 'ubicacion', 'monto_total', 'fecha', 'activo'];
    protected array $guarded = ['id_ornato'];

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable, true)) {
                $property = $this->mapColumnToProperty($key);
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }
        }
        return $this;
    }

    private function mapColumnToProperty(string $column): string
    {
        $map = [
            'id_ornato'    => 'id',
            'id_cliente'   => 'idCliente',
            'tipo_ornato'  => 'tipoOrnato',
            'descripcion'  => 'descripcion',
            'ubicacion'    => 'ubicacion',
            'monto_total'  => 'montoTotal',
            'fecha'        => 'fecha',
            'activo'       => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getIdCliente(): ?int { return $this->idCliente; }
    public function getTipoOrnato(): string { return $this->tipoOrnato; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getUbicacion(): ?string { return $this->ubicacion; }
    public function getMontoTotal(): float { return $this->montoTotal; }
    public function getFecha(): ?string { return $this->fecha; }
    public function isActivo(): bool { return $this->activo === 1; }

    // --- Setters ---
    public function setIdCliente(?int $idCliente): self
    {
        $this->idCliente = $idCliente;
        return $this;
    }

    public function setTipoOrnato(string $tipoOrnato): self
    {
        $this->tipoOrnato = $tipoOrnato;
        return $this;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function setUbicacion(?string $ubicacion): self
    {
        $this->ubicacion = $ubicacion;
        return $this;
    }

    public function setMontoTotal(float $montoTotal): self
    {
        $this->montoTotal = max(0, $montoTotal);
        return $this;
    }

    public function setFecha(?string $fecha): self
    {
        $this->fecha = $fecha;
        return $this;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo ? 1 : 0;
        return $this;
    }

    private function validate(): void
    {
        $this->validateData([
            'id_cliente'  => $this->idCliente,
            'tipo_ornato' => $this->tipoOrnato,
            'descripcion' => $this->descripcion,
            'ubicacion'   => $this->ubicacion,
            'monto_total' => $this->montoTotal,
            'fecha'       => $this->fecha,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO ornatos (id_cliente, tipo_ornato, descripcion, ubicacion, monto_total, fecha, activo)
                        VALUES (:id_cliente, :tipo_ornato, :descripcion, :ubicacion, :monto_total, :fecha, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id_cliente'  => $this->idCliente,
                    ':tipo_ornato' => $this->tipoOrnato,
                    ':descripcion' => $this->descripcion,
                    ':ubicacion'   => $this->ubicacion,
                    ':monto_total' => $this->montoTotal,
                    ':fecha'       => $this->fecha,
                    ':activo'      => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'ornatos', $this->id, null, [
                        'id_cliente'  => $this->idCliente,
                        'tipo_ornato' => $this->tipoOrnato,
                        'descripcion' => $this->descripcion,
                        'ubicacion'   => $this->ubicacion,
                        'monto_total' => $this->montoTotal,
                        'fecha'       => $this->fecha,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE ornatos SET id_cliente = :id_cliente, tipo_ornato = :tipo_ornato,
                        descripcion = :descripcion, ubicacion = :ubicacion,
                        monto_total = :monto_total, fecha = :fecha, activo = :activo
                        WHERE id_ornato = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'          => $this->id,
                    ':id_cliente'  => $this->idCliente,
                    ':tipo_ornato' => $this->tipoOrnato,
                    ':descripcion' => $this->descripcion,
                    ':ubicacion'   => $this->ubicacion,
                    ':monto_total' => $this->montoTotal,
                    ':fecha'       => $this->fecha,
                    ':activo'      => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'ornatos', $this->id, $oldData, [
                        'id_cliente'  => $this->idCliente,
                        'tipo_ornato' => $this->tipoOrnato,
                        'descripcion' => $this->descripcion,
                        'ubicacion'   => $this->ubicacion,
                        'monto_total' => $this->montoTotal,
                        'fecha'       => $this->fecha,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar ornato: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM ornatos WHERE id_ornato = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $ornato = new static($row);
        $ornato->id = (int)$row['id_ornato'];
        return $ornato;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM ornatos WHERE activo = 1 ORDER BY fecha DESC, id_ornato DESC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM ornatos WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idCliente = $found->getIdCliente();
            $this->tipoOrnato = $found->getTipoOrnato();
            $this->descripcion = $found->getDescripcion();
            $this->ubicacion = $found->getUbicacion();
            $this->montoTotal = $found->getMontoTotal();
            $this->fecha = $found->getFecha();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
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
                        CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                        c.tipo_cedula_cliente,
                        c.cedula_cliente
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
                                            CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                                            c.tipo_cedula_cliente,
                                            c.cedula_cliente
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
        $this->fill($datos);
        return $this->save();
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
        if (!$this->loadById($id)) {
            throw new \Exception("No existe el ornato con ID: $id");
        }
        $this->fill($datos);
        return $this->save();
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

    public function buscarClientes(string $query): array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                                            id_cliente,
                                            CONCAT(nombre_cliente, ' ', apellido_cliente) AS nombre_cliente,
                                            tipo_cedula_cliente,
                                            cedula_cliente,
                                            apellido_cliente,
                                            contacto_cliente
                                        FROM cliente
                                        WHERE activo = 1
                                        AND (nombre_cliente LIKE ? OR apellido_cliente LIKE ? OR contacto_cliente LIKE ? OR cedula_cliente LIKE ?)
                                        ORDER BY nombre_cliente ASC, apellido_cliente ASC
                                        LIMIT 10");
            $searchTerm = "%{$query}%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al buscar clientes en Ornato: ' . $e->getMessage());
            return [];
        }
    }
}
