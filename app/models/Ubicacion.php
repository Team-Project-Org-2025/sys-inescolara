<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Ubicacion extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreUbicacion = '';
    private ?string $descripcion = null;
    private ?string $tipo = null;
    private int $activo = 1;

    private array $schemaCache = [];

    protected array $validationRules = [
        'nombre_ubicacion' => ['type' => 'nombre',   'required' => true],
        'descripcion'      => ['type' => null,        'required' => false],
        'tipo'             => ['type' => null,        'required' => false],
    ];

    protected array $fillable = ['nombre_ubicacion', 'descripcion', 'tipo', 'activo'];
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
            'id_ubicacion'    => 'id',
            'nombre_ubicacion'=> 'nombreUbicacion',
            'descripcion'     => 'descripcion',
            'tipo'            => 'tipo',
            'activo'          => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreUbicacion(): string { return $this->nombreUbicacion; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getTipo(): ?string { return $this->tipo; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreUbicacion(string $nombreUbicacion): self
    {
        $this->nombreUbicacion = trim($nombreUbicacion);
        return $this;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion ? trim($descripcion) : null;
        return $this;
    }

    public function setTipo(?string $tipo): self
    {
        $this->tipo = $tipo ? trim($tipo) : null;
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
            'nombre_ubicacion' => $this->nombreUbicacion,
            'descripcion'      => $this->descripcion,
            'tipo'             => $this->tipo,
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

    private function tipoColumn(): string
    {
        return $this->hasColumn('ubicacion', 'tipo') ? 'tipo' : 'zona';
    }

    public function save(): bool
    {
        $this->validate();

        try {
            $tipoCol = $this->tipoColumn();
            if ($this->id === null) {
                $sql = "INSERT INTO ubicacion (nombre_ubicacion, descripcion, $tipoCol, activo) 
                        VALUES (:nombre_ubicacion, :descripcion, :tipo, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_ubicacion' => $this->nombreUbicacion,
                    ':descripcion'      => $this->descripcion,
                    ':tipo'             => $this->tipo,
                    ':activo'           => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'ubicacion', $this->id, null, [
                        'nombre_ubicacion' => $this->nombreUbicacion,
                        'descripcion'      => $this->descripcion,
                        'tipo'             => $this->tipo,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE ubicacion SET nombre_ubicacion = :nombre_ubicacion, 
                        descripcion = :descripcion, $tipoCol = :tipo, activo = :activo
                        WHERE id_ubicacion = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                => $this->id,
                    ':nombre_ubicacion'  => $this->nombreUbicacion,
                    ':descripcion'       => $this->descripcion,
                    ':tipo'              => $this->tipo,
                    ':activo'            => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'ubicacion', $this->id, $oldData, [
                        'nombre_ubicacion' => $this->nombreUbicacion,
                        'descripcion'      => $this->descripcion,
                        'tipo'             => $this->tipo,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar ubicación: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM ubicacion WHERE id_ubicacion = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $location = new static($row);
        $location->id = (int)$row['id_ubicacion'];
        return $location;
    }

    public static function all(): array
    {
        $instance = new static();
        $tipoCol = $instance->tipoColumn();
        $sql = "SELECT id_ubicacion AS id, nombre_ubicacion, descripcion, $tipoCol AS tipo, activo 
                FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM ubicacion WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $tipoCol = $this->tipoColumn();
            $stmt = $this->db()->prepare("SELECT id_ubicacion AS id, nombre_ubicacion, descripcion, $tipoCol AS tipo 
                                          FROM ubicacion WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener ubicación por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function getByTipo(string $tipo): array
    {
        try {
            $tipoCol = $this->tipoColumn();
            $stmt = $this->db()->prepare("
                SELECT id_ubicacion AS id, nombre_ubicacion, descripcion, $tipoCol AS tipo
                FROM ubicacion
                WHERE activo = 1 AND $tipoCol = :tipo
                ORDER BY nombre_ubicacion ASC
            ");
            $stmt->execute([':tipo' => $tipo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al obtener ubicaciones por tipo: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM ubicacion WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function hasAssociatedLots(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM lote WHERE id_ubicacion = :id AND activo = 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            error_log('Error al verificar relaciones de ubicación: ' . $e->getMessage());
            return true;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->getById($id);
            if ($this->hasAssociatedLots($id)) {
                throw new \Exception("No se puede desactivar la ubicación: Existen lotes vinculados en el inventario activo.");
            }
            $stmt = $this->db()->prepare("UPDATE ubicacion SET activo = 0 WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'ubicacion', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error al desactivar ubicación: ' . $e->getMessage());
            throw $e;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE ubicacion SET activo = 1 WHERE id_ubicacion = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar ubicación: ' . $e->getMessage());
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
            $this->nombreUbicacion = $found->getNombreUbicacion();
            $this->descripcion = $found->getDescripcion();
            $this->tipo = $found->getTipo();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }
}
