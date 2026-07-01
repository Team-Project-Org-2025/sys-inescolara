<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Proveedor extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreProveedor = '';
    private ?string $rifProveedor = null;
    private ?string $contactoVendedor = null;
    private ?string $telefonoProveedor = null;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_proveedor'   => ['type' => 'nombre',   'required' => true],
        'rif_proveedor'      => ['type' => 'rif',       'required' => false],
        'contacto_vendedor'  => ['type' => 'nombre',    'required' => false],
        'telefono_proveedor' => ['type' => 'telefono',  'required' => false],
    ];

    protected array $fillable = ['nombre_proveedor', 'rif_proveedor', 'contacto_vendedor', 'telefono_proveedor', 'activo'];
    protected array $guarded = ['id_proveedor'];

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db()->query("SHOW COLUMNS FROM proveedores")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('nombre_proveedor', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN nombre_proveedor VARCHAR(100) AFTER `id_proveedor`");
                    $this->db()->exec("UPDATE proveedores SET nombre_proveedor = `nombre` WHERE nombre_proveedor IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN nombre_proveedor VARCHAR(100) NOT NULL DEFAULT ''");
                }
            }

            if (!in_array('rif_proveedor', $columns)) {
                if (in_array('tipo', $columns)) {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN rif_proveedor VARCHAR(20) DEFAULT NULL AFTER nombre_proveedor");
                    $this->db()->exec("UPDATE proveedores SET rif_proveedor = `tipo` WHERE rif_proveedor IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN rif_proveedor VARCHAR(20) DEFAULT NULL AFTER nombre_proveedor");
                }
            }

            if (!in_array('contacto_vendedor', $columns)) {
                $this->db()->exec("ALTER TABLE proveedores ADD COLUMN contacto_vendedor VARCHAR(100) DEFAULT NULL AFTER rif_proveedor");
            }

            if (!in_array('telefono_proveedor', $columns)) {
                if (in_array('telefono', $columns)) {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN telefono_proveedor VARCHAR(20) DEFAULT NULL AFTER contacto_vendedor");
                    $this->db()->exec("UPDATE proveedores SET telefono_proveedor = `telefono` WHERE telefono_proveedor IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE proveedores ADD COLUMN telefono_proveedor VARCHAR(20) DEFAULT NULL AFTER contacto_vendedor");
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar proveedores: ' . $e->getMessage());
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
            'id_proveedor'       => 'id',
            'nombre_proveedor'   => 'nombreProveedor',
            'rif_proveedor'      => 'rifProveedor',
            'contacto_vendedor'  => 'contactoVendedor',
            'telefono_proveedor' => 'telefonoProveedor',
            'activo'             => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreProveedor(): string { return $this->nombreProveedor; }
    public function getRifProveedor(): ?string { return $this->rifProveedor; }
    public function getContactoVendedor(): ?string { return $this->contactoVendedor; }
    public function getTelefonoProveedor(): ?string { return $this->telefonoProveedor; }
    public function isActivo(): bool { return $this->activo === 1; }

    // --- Setters ---
    public function setNombreProveedor(string $nombreProveedor): self
    {
        $this->nombreProveedor = trim($nombreProveedor);
        return $this;
    }

    public function setRifProveedor(?string $rifProveedor): self
    {
        $this->rifProveedor = $rifProveedor ? trim($rifProveedor) : null;
        return $this;
    }

    public function setContactoVendedor(?string $contactoVendedor): self
    {
        $this->contactoVendedor = $contactoVendedor ? trim($contactoVendedor) : null;
        return $this;
    }

    public function setTelefonoProveedor(?string $telefonoProveedor): self
    {
        $this->telefonoProveedor = $telefonoProveedor ? trim($telefonoProveedor) : null;
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
            'nombre_proveedor'   => $this->nombreProveedor,
            'rif_proveedor'      => $this->rifProveedor,
            'contacto_vendedor'  => $this->contactoVendedor,
            'telefono_proveedor' => $this->telefonoProveedor,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO proveedores (nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor, activo)
                        VALUES (:nombre_proveedor, :rif_proveedor, :contacto_vendedor, :telefono_proveedor, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_proveedor'   => $this->nombreProveedor,
                    ':rif_proveedor'      => $this->rifProveedor,
                    ':contacto_vendedor'  => $this->contactoVendedor,
                    ':telefono_proveedor' => $this->telefonoProveedor,
                    ':activo'             => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'proveedores', $this->id, null, [
                        'nombre_proveedor'   => $this->nombreProveedor,
                        'rif_proveedor'      => $this->rifProveedor,
                        'contacto_vendedor'  => $this->contactoVendedor,
                        'telefono_proveedor' => $this->telefonoProveedor,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE proveedores SET nombre_proveedor = :nombre_proveedor, rif_proveedor = :rif_proveedor,
                        contacto_vendedor = :contacto_vendedor, telefono_proveedor = :telefono_proveedor,
                        activo = :activo WHERE id_proveedor = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                  => $this->id,
                    ':nombre_proveedor'   => $this->nombreProveedor,
                    ':rif_proveedor'      => $this->rifProveedor,
                    ':contacto_vendedor'  => $this->contactoVendedor,
                    ':telefono_proveedor' => $this->telefonoProveedor,
                    ':activo'             => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'proveedores', $this->id, $oldData, [
                        'nombre_proveedor'   => $this->nombreProveedor,
                        'rif_proveedor'      => $this->rifProveedor,
                        'contacto_vendedor'  => $this->contactoVendedor,
                        'telefono_proveedor' => $this->telefonoProveedor,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar proveedor: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM proveedores WHERE id_proveedor = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $prov = new static($row);
        $prov->id = (int)$row['id_proveedor'];
        return $prov;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM proveedores WHERE $column $operator :value AND activo = 1";
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
            $this->nombreProveedor = $found->getNombreProveedor();
            $this->rifProveedor = $found->getRifProveedor();
            $this->contactoVendedor = $found->getContactoVendedor();
            $this->telefonoProveedor = $found->getTelefonoProveedor();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_proveedor AS id, nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor, activo FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener proveedores: ' . $e->getMessage());
            return [];
        }
    }

    public function getByRif(string $rif): ?array
    {
        $stmt = $this->db()->prepare("SELECT id_proveedor AS id FROM proveedores WHERE rif_proveedor = :rif AND activo = 1 LIMIT 1");
        $stmt->execute([':rif' => $rif]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM proveedores WHERE id_proveedor = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM proveedores WHERE id_proveedor = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE proveedores SET activo = 0 WHERE id_proveedor = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'proveedores', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE proveedores SET activo = 1 WHERE id_proveedor = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(string $nombreProveedor, ?string $rifProveedor = null, ?string $contactoVendedor = null, ?string $telefonoProveedor = null)
    {
        $this->fill([
            'nombre_proveedor'   => $nombreProveedor,
            'rif_proveedor'      => $rifProveedor,
            'contacto_vendedor'  => $contactoVendedor,
            'telefono_proveedor' => $telefonoProveedor,
        ]);
        return $this->save();
    }

    public function update(int $id, string $nombreProveedor, ?string $rifProveedor = null, ?string $contactoVendedor = null, ?string $telefonoProveedor = null)
    {
        if (!$this->loadById($id)) {
            throw new \Exception("No existe el proveedor con ID: $id");
        }
        $this->fill([
            'nombre_proveedor'   => $nombreProveedor,
            'rif_proveedor'      => $rifProveedor,
            'contacto_vendedor'  => $contactoVendedor,
            'telefono_proveedor' => $telefonoProveedor,
        ]);
        return $this->save();
    }
}
