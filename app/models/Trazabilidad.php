<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Trazabilidad extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idLote = null;
    private int $cantidad = 0;
    private ?int $idEstado = null;
    private ?string $fechaRegistro = null;
    private ?string $observacion = null;
    private int $activo = 1;

    private array $schemaCache = [];

    protected array $validationRules = [
        'id_lote'       => ['type' => null,       'required' => true],
        'cantidad'      => ['type' => 'cantidad', 'required' => true],
        'id_estado'     => ['type' => 'cantidad', 'required' => true],
        'fecha_registro'=> ['type' => null,       'required' => true],
        'observacion'   => ['type' => null,       'required' => false],
    ];

    protected array $fillable = ['id_lote', 'cantidad', 'id_estado', 'fecha_registro', 'observacion', 'activo'];
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
            'id_trazabilidad' => 'id',
            'id_lote'         => 'idLote',
            'cantidad'        => 'cantidad',
            'id_estado'       => 'idEstado',
            'fecha_registro'  => 'fechaRegistro',
            'observacion'     => 'observacion',
            'activo'          => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getIdLote(): ?int { return $this->idLote; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getIdEstado(): ?int { return $this->idEstado; }
    public function getFechaRegistro(): ?string { return $this->fechaRegistro; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setIdLote(?int $idLote): self
    {
        $this->idLote = $idLote;
        return $this;
    }

    public function setCantidad(int $cantidad): self
    {
        $this->cantidad = max(0, $cantidad);
        return $this;
    }

    public function setIdEstado(?int $idEstado): self
    {
        $this->idEstado = $idEstado;
        return $this;
    }

    public function setFechaRegistro(?string $fechaRegistro): self
    {
        $this->fechaRegistro = $fechaRegistro;
        return $this;
    }

    public function setObservacion(?string $observacion): self
    {
        $this->observacion = $observacion ? trim($observacion) : null;
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
            'id_lote'       => $this->idLote,
            'cantidad'      => $this->cantidad,
            'id_estado'     => $this->idEstado,
            'fecha_registro'=> $this->fechaRegistro,
            'observacion'   => $this->observacion,
        ]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = "$table.$column";
        if (array_key_exists($key, $this->schemaCache)) {
            return $this->schemaCache[$key];
        }
        try {
            $stmt = $this->db()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            return $this->schemaCache[$key] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return $this->schemaCache[$key] = false;
        }
    }

    private function fkEstado(): bool { return $this->hasColumn('trazabilidad', 'id_estado'); }

    private function estadoNameById(int $id): string
    {
        $lote = new Lote();
        foreach ($lote->getEstados() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return 'Sospechoso';
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->fkEstado()) {
                $estado = $this->idEstado;
                $estadoCol = 'id_estado';
            } else {
                $estado = $this->idEstado !== null ? $this->estadoNameById($this->idEstado) : 'Sospechoso';
                $estadoCol = 'estado_salud';
            }

            if ($this->id === null) {
                $sql = "INSERT INTO trazabilidad (id_lote, cantidad, $estadoCol, fecha_registro, observacion, activo) 
                        VALUES (:id_lote, :cantidad, :id_estado, :fecha_registro, :observacion, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id_lote'       => $this->idLote,
                    ':cantidad'      => $this->cantidad,
                    ':id_estado'     => $estado,
                    ':fecha_registro'=> $this->fechaRegistro,
                    ':observacion'   => $this->observacion,
                    ':activo'        => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'trazabilidad', $this->id, null, [
                        'id_lote'       => $this->idLote,
                        'cantidad'      => $this->cantidad,
                        'id_estado'     => $this->idEstado,
                        'fecha_registro'=> $this->fechaRegistro,
                        'observacion'   => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE trazabilidad SET id_lote = :id_lote, cantidad = :cantidad, 
                        $estadoCol = :id_estado, fecha_registro = :fecha_registro, 
                        observacion = :observacion, activo = :activo
                        WHERE id_trazabilidad = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'            => $this->id,
                    ':id_lote'       => $this->idLote,
                    ':cantidad'      => $this->cantidad,
                    ':id_estado'     => $estado,
                    ':fecha_registro'=> $this->fechaRegistro,
                    ':observacion'   => $this->observacion,
                    ':activo'        => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'trazabilidad', $this->id, $oldData, [
                        'id_lote'       => $this->idLote,
                        'cantidad'      => $this->cantidad,
                        'id_estado'     => $this->idEstado,
                        'fecha_registro'=> $this->fechaRegistro,
                        'observacion'   => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar trazabilidad: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM trazabilidad WHERE id_trazabilidad = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $trace = new static($row);
        $trace->id = (int)$row['id_trazabilidad'];
        return $trace;
    }

    public static function all(): array
    {
        $instance = new static();
        if ($instance->fkEstado()) {
            $sql = "SELECT
                        t.id_trazabilidad AS id,
                        t.id_lote,
                        t.cantidad,
                        t.id_estado,
                        e.nombre AS estado_salud,
                        t.observacion,
                        t.fecha_registro,
                        t.activo,
                        l.cantidad_actual AS lote_cantidad_actual,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN estado e ON t.id_estado = e.id_estado
                    WHERE t.activo = 1
                    ORDER BY t.fecha_registro DESC, t.id_trazabilidad DESC";
        } else {
            $sql = "SELECT
                        t.id_trazabilidad AS id,
                        t.id_lote,
                        t.cantidad,
                        t.estado_salud,
                        t.observacion,
                        t.fecha_registro,
                        t.activo,
                        l.cantidad_actual AS lote_cantidad_actual,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    WHERE t.activo = 1
                    ORDER BY t.fecha_registro DESC, t.id_trazabilidad DESC";
        }
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM trazabilidad WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            if ($this->fkEstado()) {
                $stmt = $this->db()->prepare("
                    SELECT t.*,
                           e.nombre AS estado_salud,
                           COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                           l.cantidad_actual AS lote_cantidad_actual
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN estado e ON t.id_estado = e.id_estado
                    WHERE t.id_trazabilidad = :id
                ");
            } else {
                $stmt = $this->db()->prepare("
                    SELECT t.*,
                           t.estado_salud,
                           COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                           l.cantidad_actual AS lote_cantidad_actual
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    WHERE t.id_trazabilidad = :id
                ");
            }
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error en Trazabilidad::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM trazabilidad WHERE id_trazabilidad = :id");
            $stmt->execute([':id' => $id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->getById($id);
            $stmt = $this->db()->prepare("UPDATE trazabilidad SET activo = 0 WHERE id_trazabilidad = :id");
            $result = $stmt->execute([':id' => $id]);
            if ($result) {
                AuditLog::record('DEACTIVATE', 'trazabilidad', $id, $oldData, null);
            }
            return $result;
        } catch (Throwable $e) {
            error_log('Error al desactivar trazabilidad: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE trazabilidad SET activo = 1 WHERE id_trazabilidad = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar trazabilidad: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->db()->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idLote = $found->getIdLote();
            $this->cantidad = $found->getCantidad();
            $this->idEstado = $found->getIdEstado();
            $this->fechaRegistro = $found->getFechaRegistro();
            $this->observacion = $found->getObservacion();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    // --- Métodos específicos de negocio ---

    public function getAvailableBatches(?int $includeId = null): array
    {
        try {
            $params = [];
            $extra = '';
            if ($includeId !== null) {
                $extra = ' OR l.id_lote = :include_id';
                $params[':include_id'] = $includeId;
            }
            $sql = "SELECT
                        l.id_lote AS id,
                        l.cantidad_actual,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        u.nombre_ubicacion
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    WHERE l.activo = 1 AND (l.cantidad_actual > 0$extra)
                    ORDER BY p.nombre_comun ASC, l.id_lote DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error en Trazabilidad::getAvailableBatches: ' . $e->getMessage());
            return [];
        }
    }

    public function deductBatchStock(int $idLote, int $cantidad): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :check");
            return $stmt->execute([':cantidad' => $cantidad, ':id' => $idLote, ':check' => $cantidad]);
        } catch (Throwable $e) {
            error_log('Error en Trazabilidad::deductBatchStock: ' . $e->getMessage());
            return false;
        }
    }

    public function restoreBatchStock(int $idLote, int $cantidad): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");
            return $stmt->execute([':cantidad' => $cantidad, ':id' => $idLote]);
        } catch (Throwable $e) {
            error_log('Error en Trazabilidad::restoreBatchStock: ' . $e->getMessage());
            return false;
        }
    }
}
