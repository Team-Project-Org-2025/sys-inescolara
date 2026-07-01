<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Empleado extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreTrabajador = '';
    private ?string $apellidoTrabajador = null;
    private ?string $cedulaTrabajador = null;
    private ?string $telefonoTrabajador = null;
    private ?string $cargo = null;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_trabajador'   => ['type' => 'nombre',  'required' => true],
        'apellido_trabajador' => ['type' => 'nombre',  'required' => false],
        'cedula_trabajador'   => ['type' => 'cedula',  'required' => false],
        'telefono_trabajador' => ['type' => 'telefono','required' => false],
        'cargo'               => ['type' => 'cargo',   'required' => false],
    ];

    protected array $fillable = ['nombre_trabajador', 'apellido_trabajador', 'cedula_trabajador', 'telefono_trabajador', 'cargo', 'activo'];
    protected array $guarded = ['id_trabajador'];

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
            'id_trabajador'      => 'id',
            'nombre_trabajador'  => 'nombreTrabajador',
            'apellido_trabajador'=> 'apellidoTrabajador',
            'cedula_trabajador'  => 'cedulaTrabajador',
            'telefono_trabajador'=> 'telefonoTrabajador',
            'cargo'              => 'cargo',
            'activo'             => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreTrabajador(): string { return $this->nombreTrabajador; }
    public function getApellidoTrabajador(): ?string { return $this->apellidoTrabajador; }
    public function getCedulaTrabajador(): ?string { return $this->cedulaTrabajador; }
    public function getTelefonoTrabajador(): ?string { return $this->telefonoTrabajador; }
    public function getCargo(): ?string { return $this->cargo; }
    public function isActivo(): bool { return $this->activo === 1; }

    // --- Setters ---
    public function setNombreTrabajador(string $nombreTrabajador): self
    {
        $this->nombreTrabajador = trim($nombreTrabajador);
        return $this;
    }

    public function setApellidoTrabajador(?string $apellidoTrabajador): self
    {
        $this->apellidoTrabajador = $apellidoTrabajador ? trim($apellidoTrabajador) : null;
        return $this;
    }

    public function setCedulaTrabajador(?string $cedulaTrabajador): self
    {
        $this->cedulaTrabajador = $cedulaTrabajador ? trim($cedulaTrabajador) : null;
        return $this;
    }

    public function setTelefonoTrabajador(?string $telefonoTrabajador): self
    {
        $this->telefonoTrabajador = $telefonoTrabajador ? trim($telefonoTrabajador) : null;
        return $this;
    }

    public function setCargo(?string $cargo): self
    {
        $this->cargo = $cargo ? trim($cargo) : null;
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
            'nombre_trabajador'   => $this->nombreTrabajador,
            'apellido_trabajador' => $this->apellidoTrabajador,
            'cedula_trabajador'   => $this->cedulaTrabajador,
            'telefono_trabajador' => $this->telefonoTrabajador,
            'cargo'               => $this->cargo,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO trabajadores (nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, activo)
                        VALUES (:nombre_trabajador, :apellido_trabajador, :cedula_trabajador, :telefono_trabajador, :cargo, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_trabajador'   => $this->nombreTrabajador,
                    ':apellido_trabajador' => $this->apellidoTrabajador,
                    ':cedula_trabajador'   => $this->cedulaTrabajador,
                    ':telefono_trabajador' => $this->telefonoTrabajador,
                    ':cargo'               => $this->cargo,
                    ':activo'              => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'trabajadores', $this->id, null, [
                        'nombre_trabajador'   => $this->nombreTrabajador,
                        'apellido_trabajador' => $this->apellidoTrabajador,
                        'cedula_trabajador'   => $this->cedulaTrabajador,
                        'telefono_trabajador' => $this->telefonoTrabajador,
                        'cargo'               => $this->cargo,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE trabajadores SET nombre_trabajador = :nombre_trabajador, apellido_trabajador = :apellido_trabajador,
                        cedula_trabajador = :cedula_trabajador, telefono_trabajador = :telefono_trabajador,
                        cargo = :cargo, activo = :activo WHERE id_trabajador = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                  => $this->id,
                    ':nombre_trabajador'   => $this->nombreTrabajador,
                    ':apellido_trabajador' => $this->apellidoTrabajador,
                    ':cedula_trabajador'   => $this->cedulaTrabajador,
                    ':telefono_trabajador' => $this->telefonoTrabajador,
                    ':cargo'               => $this->cargo,
                    ':activo'              => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'trabajadores', $this->id, $oldData, [
                        'nombre_trabajador'   => $this->nombreTrabajador,
                        'apellido_trabajador' => $this->apellidoTrabajador,
                        'cedula_trabajador'   => $this->cedulaTrabajador,
                        'telefono_trabajador' => $this->telefonoTrabajador,
                        'cargo'               => $this->cargo,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar trabajador: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM trabajadores WHERE id_trabajador = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $emp = new static($row);
        $emp->id = (int)$row['id_trabajador'];
        return $emp;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM trabajadores WHERE $column $operator :value AND activo = 1";
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
            $this->nombreTrabajador = $found->getNombreTrabajador();
            $this->apellidoTrabajador = $found->getApellidoTrabajador();
            $this->cedulaTrabajador = $found->getCedulaTrabajador();
            $this->telefonoTrabajador = $found->getTelefonoTrabajador();
            $this->cargo = $found->getCargo();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_trabajador AS id, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, activo FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener trabajadores: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM trabajadores WHERE id_trabajador = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM trabajadores WHERE id_trabajador = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE trabajadores SET activo = 0 WHERE id_trabajador = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'trabajadores', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE trabajadores SET activo = 1 WHERE id_trabajador = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getDistinctCargos(): array
    {
        try {
            $stmt = $this->db()->query("SELECT DISTINCT cargo FROM trabajadores WHERE cargo IS NOT NULL AND cargo != '' ORDER BY cargo");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener cargos: ' . $e->getMessage());
            return [];
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null, ?string $cargo = null, bool $activo = true)
    {
        $this->fill([
            'nombre_trabajador'   => $nombreTrabajador,
            'apellido_trabajador' => $apellidoTrabajador,
            'cedula_trabajador'   => $cedulaTrabajador,
            'telefono_trabajador' => $telefonoTrabajador,
            'cargo'               => $cargo,
            'activo'              => $activo,
        ]);
        return $this->save();
    }

    public function update(int $id, string $nombreTrabajador, ?string $apellidoTrabajador = null, ?string $cedulaTrabajador = null, ?string $telefonoTrabajador = null, ?string $cargo = null, bool $activo = true)
    {
        if (!$this->loadById($id)) {
            throw new \Exception("No existe el trabajador con ID: $id");
        }
        $this->fill([
            'nombre_trabajador'   => $nombreTrabajador,
            'apellido_trabajador' => $apellidoTrabajador,
            'cedula_trabajador'   => $cedulaTrabajador,
            'telefono_trabajador' => $telefonoTrabajador,
            'cargo'               => $cargo,
            'activo'              => $activo,
        ]);
        return $this->save();
    }
}
