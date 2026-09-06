<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;

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
    protected array $guarded = ['id_usuario'];

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
            'id_usuario'          => 'id',
            'nombre_trabajador'   => 'nombreTrabajador',
            'apellido_trabajador' => 'apellidoTrabajador',
            'cedula_trabajador'   => 'cedulaTrabajador',
            'telefono_trabajador' => 'telefonoTrabajador',
            'cargo'               => 'cargo',
            'activo'              => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    public function getId(): ?int { return $this->id; }
    public function getNombreTrabajador(): string { return $this->nombreTrabajador; }
    public function getApellidoTrabajador(): ?string { return $this->apellidoTrabajador; }
    public function getCedulaTrabajador(): ?string { return $this->cedulaTrabajador; }
    public function getTelefonoTrabajador(): ?string { return $this->telefonoTrabajador; }
    public function getCargo(): ?string { return $this->cargo; }
    public function isActivo(): bool { return $this->activo === 1; }

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

    private function getSecurityDb(): string
    {
        $dbName = getenv('DB_SEC_NAME') ?: 'SysInescolara-Seguridad';
        return "`$dbName`";
    }

    public function save(): bool
    {
        $this->validate();
        try {
            $db = $this->getSecurityDb();
            if ($this->id === null) {
                $sql = "INSERT INTO $db.`usuarios` (nombre_usuario, password_hash, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, estatus)
                        VALUES (:nombre_usuario, :password_hash, :nombre_trabajador, :apellido_trabajador, :cedula_trabajador, :telefono_trabajador, :cargo, :estatus)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_usuario'      => 'trabajador_' . strtolower(preg_replace('/\s+/', '_', $this->nombreTrabajador)),
                    ':password_hash'       => password_hash('temp123', PASSWORD_DEFAULT),
                    ':nombre_trabajador'   => $this->nombreTrabajador,
                    ':apellido_trabajador' => $this->apellidoTrabajador,
                    ':cedula_trabajador'   => $this->cedulaTrabajador,
                    ':telefono_trabajador' => $this->telefonoTrabajador,
                    ':cargo'               => $this->cargo,
                    ':estatus'             => $this->activo ? 'Activo' : 'Inactivo',
                ]);
                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                }
                return $success;
            } else {
                $sql = "UPDATE $db.`usuarios`
                        SET nombre_trabajador = :nombre_trabajador, apellido_trabajador = :apellido_trabajador,
                            cedula_trabajador = :cedula_trabajador, telefono_trabajador = :telefono_trabajador,
                            cargo = :cargo, estatus = :estatus
                        WHERE id_usuario = :id";
                $stmt = $this->db()->prepare($sql);
                return $stmt->execute([
                    ':id'                  => $this->id,
                    ':nombre_trabajador'   => $this->nombreTrabajador,
                    ':apellido_trabajador' => $this->apellidoTrabajador,
                    ':cedula_trabajador'   => $this->cedulaTrabajador,
                    ':telefono_trabajador' => $this->telefonoTrabajador,
                    ':cargo'               => $this->cargo,
                    ':estatus'             => $this->activo ? 'Activo' : 'Inactivo',
                ]);
            }
        } catch (Throwable $e) {
            error_log('Error al guardar empleado: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $db = $instance->getSecurityDb();
        $stmt = $instance->db()->prepare("SELECT * FROM $db.`usuarios` WHERE id_usuario = :id AND (nombre_trabajador IS NOT NULL AND nombre_trabajador != '')");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $emp = new static($row);
        $emp->id = (int)$row['id_usuario'];
        return $emp;
    }

    public static function all(): array
    {
        $instance = new static();
        $db = $instance->getSecurityDb();
        $stmt = $instance->db()->query("SELECT id_usuario AS id,
                COALESCE(NULLIF(nombre_trabajador, ''), nombre_usuario) AS nombre_trabajador,
                apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, nombre_usuario,
                CASE WHEN estatus = 'Activo' THEN 1 ELSE 0 END AS activo
                FROM $db.`usuarios`
                WHERE estatus = 'Activo'
                ORDER BY nombre_trabajador ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function getById(int $id): ?array
    {
        $db = $this->getSecurityDb();
        $stmt = $this->db()->prepare("SELECT id_usuario AS id,
                COALESCE(NULLIF(nombre_trabajador, ''), nombre_usuario) AS nombre_trabajador,
                apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, nombre_usuario,
                CASE WHEN estatus = 'Activo' THEN 1 ELSE 0 END AS activo
                FROM $db.`usuarios` WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $db = $this->getSecurityDb();
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM $db.`usuarios` WHERE id_usuario = :id AND (nombre_trabajador IS NOT NULL AND nombre_trabajador != '')");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $db = $this->getSecurityDb();
        $stmt = $this->db()->prepare("UPDATE $db.`usuarios` SET estatus = 'Inactivo' WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $db = $this->getSecurityDb();
        $stmt = $this->db()->prepare("UPDATE $db.`usuarios` SET estatus = 'Activo' WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getDistinctCargos(): array
    {
        try {
            $db = $this->getSecurityDb();
            $stmt = $this->db()->query("SELECT DISTINCT cargo FROM $db.`usuarios` WHERE cargo IS NOT NULL AND cargo != '' AND (nombre_trabajador IS NOT NULL AND nombre_trabajador != '') ORDER BY cargo");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener cargos: ' . $e->getMessage());
            return [];
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }
}
