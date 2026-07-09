<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class UnidadMedida extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreUnidadMedida = '';
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_unidad_medida' => ['type' => 'nombre', 'required' => true],
    ];

    protected array $fillable = ['nombre_unidad_medida', 'activo'];
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
            'id_unidad_medida'     => 'id',
            'nombre_unidad_medida' => 'nombreUnidadMedida',
            'activo'               => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreUnidadMedida(): string { return $this->nombreUnidadMedida; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreUnidadMedida(string $nombreUnidadMedida): self
    {
        $this->nombreUnidadMedida = trim($nombreUnidadMedida);
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
            'nombre_unidad_medida' => $this->nombreUnidadMedida,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO unidad_medida (nombre_unidad_medida, activo) 
                        VALUES (:nombre_unidad_medida, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_unidad_medida' => $this->nombreUnidadMedida,
                    ':activo'               => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'unidad_medida', $this->id, null, [
                        'nombre_unidad_medida' => $this->nombreUnidadMedida,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE unidad_medida SET nombre_unidad_medida = :nombre_unidad_medida, 
                        activo = :activo WHERE id_unidad_medida = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                   => $this->id,
                    ':nombre_unidad_medida' => $this->nombreUnidadMedida,
                    ':activo'               => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'unidad_medida', $this->id, $oldData, [
                        'nombre_unidad_medida' => $this->nombreUnidadMedida,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar unidad de medida: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM unidad_medida WHERE id_unidad_medida = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $unit = new static($row);
        $unit->id = (int)$row['id_unidad_medida'];
        return $unit;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT id_unidad_medida AS id, nombre_unidad_medida, activo 
                FROM unidad_medida WHERE activo = 1 ORDER BY nombre_unidad_medida ASC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM unidad_medida WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT id_unidad_medida AS id, nombre_unidad_medida FROM unidad_medida WHERE id_unidad_medida = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener unidad de medida por ID: ' . $e->getMessage());
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
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM unidad_medida WHERE id_unidad_medida = :id");
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
            $stmt = $this->db()->prepare("UPDATE unidad_medida SET activo = 0 WHERE id_unidad_medida = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'unidad_medida', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error al desactivar unidad de medida: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE unidad_medida SET activo = 1 WHERE id_unidad_medida = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar unidad de medida: ' . $e->getMessage());
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
            $this->nombreUnidadMedida = $found->getNombreUnidadMedida();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }
}
