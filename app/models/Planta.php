<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Planta extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreComun = '';
    private ?string $nombreTecnico = null;
    private ?int $idEspecie = null;
    private ?string $imagen = null;
    private int $cantidadTotal = 0;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_comun'   => ['type' => 'nombre',   'required' => true],
        'nombre_tecnico' => ['type' => 'nombre',   'required' => false],
        'id_especie'     => ['type' => 'cantidad', 'required' => true],
        'cantidad_total' => ['type' => 'cantidad', 'required' => false],
    ];

    protected array $fillable = ['nombre_comun', 'nombre_tecnico', 'id_especie', 'imagen', 'cantidad_total', 'activo'];
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
            'id_planta'      => 'id',
            'nombre_comun'   => 'nombreComun',
            'nombre_tecnico' => 'nombreTecnico',
            'id_especie'     => 'idEspecie',
            'imagen'         => 'imagen',
            'cantidad_total' => 'cantidadTotal',
            'activo'         => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreComun(): string { return $this->nombreComun; }
    public function getNombreTecnico(): ?string { return $this->nombreTecnico; }
    public function getIdEspecie(): ?int { return $this->idEspecie; }
    public function getImagen(): ?string { return $this->imagen; }
    public function getCantidadTotal(): int { return $this->cantidadTotal; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreComun(string $nombreComun): self
    {
        $this->nombreComun = trim($nombreComun);
        return $this;
    }

    public function setNombreTecnico(?string $nombreTecnico): self
    {
        $this->nombreTecnico = $nombreTecnico ? trim($nombreTecnico) : null;
        return $this;
    }

    public function setIdEspecie(?int $idEspecie): self
    {
        $this->idEspecie = $idEspecie;
        return $this;
    }

    public function setImagen(?string $imagen): self
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function setCantidadTotal(int $cantidadTotal): self
    {
        $this->cantidadTotal = max(0, $cantidadTotal);
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
            'nombre_comun'   => $this->nombreComun,
            'nombre_tecnico' => $this->nombreTecnico,
            'id_especie'     => $this->idEspecie,
            'cantidad_total' => $this->cantidadTotal,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO plantas (nombre_comun, nombre_tecnico, id_especie, imagen, cantidad_total, activo) 
                        VALUES (:nombre_comun, :nombre_tecnico, :id_especie, :imagen, :cantidad_total, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_comun'   => $this->nombreComun,
                    ':nombre_tecnico' => $this->nombreTecnico,
                    ':id_especie'     => $this->idEspecie,
                    ':imagen'         => $this->imagen,
                    ':cantidad_total' => $this->cantidadTotal,
                    ':activo'         => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'plantas', $this->id, null, [
                        'nombre_comun'   => $this->nombreComun,
                        'nombre_tecnico' => $this->nombreTecnico,
                        'id_especie'     => $this->idEspecie,
                        'imagen'         => $this->imagen,
                        'cantidad_total' => $this->cantidadTotal,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE plantas SET nombre_comun = :nombre_comun, nombre_tecnico = :nombre_tecnico, 
                        id_especie = :id_especie, imagen = :imagen, cantidad_total = :cantidad_total, activo = :activo
                        WHERE id_planta = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'             => $this->id,
                    ':nombre_comun'   => $this->nombreComun,
                    ':nombre_tecnico' => $this->nombreTecnico,
                    ':id_especie'     => $this->idEspecie,
                    ':imagen'         => $this->imagen,
                    ':cantidad_total' => $this->cantidadTotal,
                    ':activo'         => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'plantas', $this->id, $oldData, [
                        'nombre_comun'   => $this->nombreComun,
                        'nombre_tecnico' => $this->nombreTecnico,
                        'id_especie'     => $this->idEspecie,
                        'imagen'         => $this->imagen,
                        'cantidad_total' => $this->cantidadTotal,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar planta: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new static($row) : null;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT
                    p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_especie AS especie_id, 
                    p.imagen, p.activo, p.cantidad_total,
                    e.nombre_especie AS especie_nombre,
                    (SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta AND l2.activo = 1) AS stock_lotes,
                    (SELECT cp.precio_final_sugerido
                     FROM calculo_precio cp
                     JOIN lote l ON cp.id_lote = l.id_lote
                     WHERE l.id_planta = p.id_planta
                     ORDER BY cp.fecha_calculo DESC
                     LIMIT 1) AS precio_vigente
                FROM plantas p
                LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                WHERE p.activo = 1
                ORDER BY p.nombre_comun ASC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM plantas WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_especie AS especie_id, 
                    p.imagen, p.activo, p.cantidad_total,
                    e.nombre_especie AS especie_nombre
                FROM plantas p
                LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                WHERE p.id_planta = :id";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE plantas SET activo = 0 WHERE id_planta = :id");
        $success = $stmt->execute([':id' => $id]);
        if ($success) {
            AuditLog::record('DEACTIVATE', 'plantas', $id, $oldData, null);
        }
        return $success;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE plantas SET activo = 1 WHERE id_planta = :id");
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

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->nombreComun = $found->getNombreComun();
            $this->nombreTecnico = $found->getNombreTecnico();
            $this->idEspecie = $found->getIdEspecie();
            $this->imagen = $found->getImagen();
            $this->cantidadTotal = $found->getCantidadTotal();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }
}