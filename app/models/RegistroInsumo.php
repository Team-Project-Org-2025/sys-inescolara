<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class RegistroInsumo extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idLote = null;
    private ?int $idInsumo = null;
    private ?int $idAsignacion = null;
    private float $cantidad = 0.0;
    private float $costoUnitario = 0.0;
    private ?string $fechaRegistro = null;

    protected array $validationRules = [
        'id_lote'         => ['type' => null,      'required' => true],
        'id_insumo'       => ['type' => null,      'required' => true],
        'cantidad'        => ['type' => 'cantidad','required' => true],
        'costo_unitario'  => ['type' => 'precio',  'required' => true],
        'fecha_registro'  => ['type' => null,      'required' => true],
    ];

    protected array $fillable = ['id_lote', 'id_insumo', 'id_asignacion', 'cantidad', 'costo_unitario', 'fecha_registro'];
    protected array $guarded = ['id'];

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
            if (in_array($key, $this->fillable, true)) {
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
            'id_registro_insumo' => 'id',
            'id_lote'            => 'idLote',
            'id_insumo'          => 'idInsumo',
            'id_asignacion'      => 'idAsignacion',
            'cantidad'           => 'cantidad',
            'costo_unitario'     => 'costoUnitario',
            'fecha_registro'     => 'fechaRegistro',
        ];
        return $map[$column] ?? $column;
    }

    public function getId(): ?int { return $this->id; }
    public function getIdLote(): ?int { return $this->idLote; }
    public function getIdInsumo(): ?int { return $this->idInsumo; }
    public function getIdAsignacion(): ?int { return $this->idAsignacion; }
    public function getCantidad(): float { return $this->cantidad; }
    public function getCostoUnitario(): float { return $this->costoUnitario; }
    public function getFechaRegistro(): ?string { return $this->fechaRegistro; }

    private function validate(): void
    {
        $this->validateData([
            'id_lote'         => $this->idLote,
            'id_insumo'       => $this->idInsumo,
            'cantidad'        => $this->cantidad,
            'costo_unitario'  => $this->costoUnitario,
            'fecha_registro'  => $this->fechaRegistro,
        ]);
    }

    public function save(): bool
    {
        $this->validate();
        try {
            if ($this->id === null) {
                $stmt = $this->db()->prepare("
                    INSERT INTO registro_insumo (id_lote, id_insumo, id_asignacion, cantidad, costo_unitario, fecha_registro)
                    VALUES (:id_lote, :id_insumo, :id_asignacion, :cantidad, :costo_unitario, :fecha_registro)
                ");
                $success = $stmt->execute([
                    ':id_lote'         => $this->idLote,
                    ':id_insumo'       => $this->idInsumo,
                    ':id_asignacion'   => $this->idAsignacion,
                    ':cantidad'        => $this->cantidad,
                    ':costo_unitario'  => $this->costoUnitario,
                    ':fecha_registro'  => $this->fechaRegistro,
                ]);
                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'registro_insumo', $this->id, null, [
                        'id_lote'        => $this->idLote,
                        'id_insumo'      => $this->idInsumo,
                        'cantidad'       => $this->cantidad,
                        'costo_unitario' => $this->costoUnitario,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $stmt = $this->db()->prepare("
                    UPDATE registro_insumo
                    SET id_lote = :id_lote, id_insumo = :id_insumo, id_asignacion = :id_asignacion,
                        cantidad = :cantidad, costo_unitario = :costo_unitario, fecha_registro = :fecha_registro
                    WHERE id_registro_insumo = :id
                ");
                $success = $stmt->execute([
                    ':id'              => $this->id,
                    ':id_lote'         => $this->idLote,
                    ':id_insumo'       => $this->idInsumo,
                    ':id_asignacion'   => $this->idAsignacion,
                    ':cantidad'        => $this->cantidad,
                    ':costo_unitario'  => $this->costoUnitario,
                    ':fecha_registro'  => $this->fechaRegistro,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'registro_insumo', $this->id, $oldData, [
                        'id_lote'        => $this->idLote,
                        'id_insumo'      => $this->idInsumo,
                        'cantidad'       => $this->cantidad,
                        'costo_unitario' => $this->costoUnitario,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar registro_insumo: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM registro_insumo WHERE id_registro_insumo = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $reg = new static($row);
        $reg->id = (int)$row['id_registro_insumo'];
        return $reg;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT r.*, i.nombre_insumo, u.simbolo
                FROM registro_insumo r
                LEFT JOIN insumo i ON r.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                ORDER BY r.fecha_registro DESC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT r.*, i.nombre_insumo, u.simbolo
                FROM registro_insumo r
                LEFT JOIN insumo i ON r.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE r.id_registro_insumo = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error en RegistroInsumo::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM registro_insumo WHERE id_registro_insumo = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("DELETE FROM registro_insumo WHERE id_registro_insumo = :id");
        $success = $stmt->execute([':id' => $id]);
        if ($success) {
            AuditLog::record('DELETE', 'registro_insumo', $id, $oldData, null);
        }
        return $success;
    }

    public function getByLote(int $idLote): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT r.*, i.nombre_insumo, u.simbolo
                FROM registro_insumo r
                LEFT JOIN insumo i ON r.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE r.id_lote = :id_lote
                ORDER BY r.fecha_registro DESC
            ");
            $stmt->execute([':id_lote' => $idLote]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en RegistroInsumo::getByLote: ' . $e->getMessage());
            return [];
        }
    }

    public function getByAsignacion(int $idAsignacion): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT r.*, i.nombre_insumo, u.simbolo
                FROM registro_insumo r
                LEFT JOIN insumo i ON r.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE r.id_asignacion = :id_asignacion
                ORDER BY r.fecha_registro DESC
            ");
            $stmt->execute([':id_asignacion' => $idAsignacion]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en RegistroInsumo::getByAsignacion: ' . $e->getMessage());
            return [];
        }
    }

    public function eliminarPorLote(int $idLote): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM registro_insumo WHERE id_lote = :id_lote");
        return $stmt->execute([':id_lote' => $idLote]);
    }

    public function eliminarPorAsignacion(int $idAsignacion): bool
    {
        $stmt = $this->db()->prepare("DELETE FROM registro_insumo WHERE id_asignacion = :id_asignacion");
        return $stmt->execute([':id_asignacion' => $idAsignacion]);
    }
}
