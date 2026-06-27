<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Especie extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreEspecie = '';
    private ?string $descripcion = null;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_especie' => ['type' => 'nombre', 'required' => true],
        'descripcion'    => ['type' => null,     'required' => false],
    ];

    protected array $fillable = ['nombre_especie', 'descripcion', 'activo'];
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
            'id_especie'     => 'id',
            'nombre_especie' => 'nombreEspecie',
            'descripcion'    => 'descripcion',
            'activo'         => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreEspecie(): string { return $this->nombreEspecie; }
    public function setNombreEspecie(string $nombreEspecie): self
    {
        $this->nombreEspecie = trim($nombreEspecie);
        return $this;
    }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion ? trim($descripcion) : null;
        return $this;
    }
    public function isActivo(): bool { return $this->activo === 1; }
    public function setActivo(bool $activo): self
    {
        $this->activo = $activo ? 1 : 0;
        return $this;
    }

    private function validate(): void
    {
        $this->validateData([
            'nombre_especie' => $this->nombreEspecie,
            'descripcion'    => $this->descripcion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        if ($this->id === null) {
            // INSERT
            $sql = "INSERT INTO especie (nombre_especie, descripcion, activo) VALUES (:nombre, :descripcion, :activo)";
            $stmt = $this->db()->prepare($sql);
            $success = $stmt->execute([
                ':nombre'     => $this->nombreEspecie,
                ':descripcion'=> $this->descripcion,
                ':activo'     => $this->activo,
            ]);
            if ($success) {
                $this->id = (int) $this->db()->lastInsertId();
                AuditLog::record('CREATE', 'especie', $this->id, null, [
                    'nombre_especie' => $this->nombreEspecie,
                    'descripcion'    => $this->descripcion,
                ]);
            }
            return $success;
        } else {
            // UPDATE
            $oldData = $this->getById($this->id);
            $sql = "UPDATE especie SET nombre_especie = :nombre, descripcion = :descripcion, activo = :activo WHERE id_especie = :id";
            $stmt = $this->db()->prepare($sql);
            $success = $stmt->execute([
                ':id'          => $this->id,
                ':nombre'      => $this->nombreEspecie,
                ':descripcion' => $this->descripcion,
                ':activo'      => $this->activo,
            ]);
            if ($success) {
                AuditLog::record('UPDATE', 'especie', $this->id, $oldData, [
                    'nombre_especie' => $this->nombreEspecie,
                    'descripcion'    => $this->descripcion,
                ]);
            }
            return $success;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new static($row) : null;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map(fn($row) => new static($row), $rows);
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM especie WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    // --- Métodos de la interfaz (mantenidos por compatibilidad) ---
    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT id_especie AS id, nombre_especie, descripcion, activo FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_especie AS id, nombre_especie, descripcion, activo FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener especies: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE especie SET activo = 0 WHERE id_especie = :id");
        $success = $stmt->execute([':id' => $id]);
        if ($success) {
            AuditLog::record('DEACTIVATE', 'especie', $id, $oldData, null);
        }
        return $success;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE especie SET activo = 1 WHERE id_especie = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }
}