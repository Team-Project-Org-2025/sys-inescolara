<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Cliente extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreCliente = '';
    private ?string $apellidoCliente = null;
    private ?string $tipoCedulaCliente = null;
    private ?string $cedulaCliente = null;
    private ?string $contactoCliente = null;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_cliente'      => ['type' => 'nombre',       'required' => true],
        'apellido_cliente'    => ['type' => 'nombre',       'required' => false],
        'tipo_cedula_cliente' => ['type' => 'tipo_cedula',  'required' => false],
        'cedula_cliente'      => ['type' => 'cedula_numero','required' => false],
        'contacto_cliente'    => ['type' => null,           'required' => false],
    ];

    protected array $fillable = ['nombre_cliente', 'apellido_cliente', 'tipo_cedula_cliente', 'cedula_cliente', 'contacto_cliente', 'activo'];
    protected array $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        $this->bootstrapDefaults();
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    private function bootstrapDefaults(): void
    {
        try {
            $columns = $this->db()->query("SHOW COLUMNS FROM cliente")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('nombre_cliente', $columns)) {
                if (in_array('nombre', $columns)) {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN nombre_cliente VARCHAR(100) AFTER `id_cliente`");
                    $this->db()->exec("UPDATE cliente SET nombre_cliente = `nombre` WHERE nombre_cliente IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN nombre_cliente VARCHAR(100) NOT NULL DEFAULT ''");
                }
            }

            if (!in_array('apellido_cliente', $columns)) {
                $this->db()->exec("ALTER TABLE cliente ADD COLUMN apellido_cliente VARCHAR(100) NOT NULL DEFAULT '' AFTER nombre_cliente");
                $this->db()->exec("UPDATE cliente SET apellido_cliente = SUBSTRING_INDEX(nombre_cliente, ' ', -1) WHERE apellido_cliente = '' AND nombre_cliente LIKE '% %'");
                $this->db()->exec("UPDATE cliente SET nombre_cliente = TRIM(SUBSTRING_INDEX(nombre_cliente, ' ', 1)) WHERE nombre_cliente LIKE '% %'");
            }

            if (!in_array('tipo_cedula_cliente', $columns)) {
                $this->db()->exec("ALTER TABLE cliente ADD COLUMN tipo_cedula_cliente VARCHAR(1) DEFAULT NULL AFTER id_cliente");
            }

            if (!in_array('cedula_cliente', $columns)) {
                $this->db()->exec("ALTER TABLE cliente ADD COLUMN cedula_cliente VARCHAR(10) DEFAULT NULL AFTER tipo_cedula_cliente");
            }

            if (!in_array('contacto_cliente', $columns)) {
                if (in_array('informacion_contacto', $columns)) {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN contacto_cliente VARCHAR(100) AFTER apellido_cliente");
                    $this->db()->exec("UPDATE cliente SET contacto_cliente = informacion_contacto WHERE contacto_cliente IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN contacto_cliente VARCHAR(100) DEFAULT NULL AFTER apellido_cliente");
                }
            }
        } catch (Throwable $e) {
            error_log('Error al migrar cliente: ' . $e->getMessage());
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
            'id_cliente'          => 'id',
            'nombre_cliente'      => 'nombreCliente',
            'apellido_cliente'    => 'apellidoCliente',
            'tipo_cedula_cliente' => 'tipoCedulaCliente',
            'cedula_cliente'      => 'cedulaCliente',
            'contacto_cliente'    => 'contactoCliente',
            'activo'              => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreCliente(): string { return $this->nombreCliente; }
    public function getApellidoCliente(): ?string { return $this->apellidoCliente; }
    public function getTipoCedulaCliente(): ?string { return $this->tipoCedulaCliente; }
    public function getCedulaCliente(): ?string { return $this->cedulaCliente; }
    public function getContactoCliente(): ?string { return $this->contactoCliente; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreCliente(string $nombreCliente): self
    {
        $this->nombreCliente = trim($nombreCliente);
        return $this;
    }

    public function setApellidoCliente(?string $apellidoCliente): self
    {
        $this->apellidoCliente = $apellidoCliente ? trim($apellidoCliente) : null;
        return $this;
    }

    public function setTipoCedulaCliente(?string $tipoCedulaCliente): self
    {
        $this->tipoCedulaCliente = $tipoCedulaCliente ? strtoupper(trim($tipoCedulaCliente)) : null;
        return $this;
    }

    public function setCedulaCliente(?string $cedulaCliente): self
    {
        $this->cedulaCliente = $cedulaCliente ? trim($cedulaCliente) : null;
        return $this;
    }

    public function setContactoCliente(?string $contactoCliente): self
    {
        $this->contactoCliente = $contactoCliente ? trim($contactoCliente) : null;
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
            'nombre_cliente'      => $this->nombreCliente,
            'apellido_cliente'    => $this->apellidoCliente,
            'tipo_cedula_cliente' => $this->tipoCedulaCliente,
            'cedula_cliente'      => $this->cedulaCliente,
            'contacto_cliente'    => $this->contactoCliente,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO cliente (tipo_cedula_cliente, cedula_cliente, nombre_cliente, apellido_cliente, contacto_cliente, activo) 
                        VALUES (:tipo_cedula_cliente, :cedula_cliente, :nombre_cliente, :apellido_cliente, :contacto_cliente, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':tipo_cedula_cliente' => $this->tipoCedulaCliente,
                    ':cedula_cliente'      => $this->cedulaCliente,
                    ':nombre_cliente'      => $this->nombreCliente,
                    ':apellido_cliente'    => $this->apellidoCliente,
                    ':contacto_cliente'    => $this->contactoCliente,
                    ':activo'              => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'cliente', $this->id, null, [
                        'tipo_cedula_cliente' => $this->tipoCedulaCliente,
                        'cedula_cliente'      => $this->cedulaCliente,
                        'nombre_cliente'      => $this->nombreCliente,
                        'apellido_cliente'    => $this->apellidoCliente,
                        'contacto_cliente'    => $this->contactoCliente,
                    ]);
                }
                return $success;
            } else {
                if (!$this->exists($this->id)) {
                    throw new \Exception("No existe el cliente con ID: {$this->id}");
                }

                $oldData = $this->getById($this->id);
                $sql = "UPDATE cliente SET tipo_cedula_cliente = :tipo_cedula_cliente, 
                        cedula_cliente = :cedula_cliente, nombre_cliente = :nombre_cliente, 
                        apellido_cliente = :apellido_cliente, contacto_cliente = :contacto_cliente, 
                        activo = :activo WHERE id_cliente = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                  => $this->id,
                    ':tipo_cedula_cliente' => $this->tipoCedulaCliente,
                    ':cedula_cliente'      => $this->cedulaCliente,
                    ':nombre_cliente'      => $this->nombreCliente,
                    ':apellido_cliente'    => $this->apellidoCliente,
                    ':contacto_cliente'    => $this->contactoCliente,
                    ':activo'              => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'cliente', $this->id, $oldData, [
                        'tipo_cedula_cliente' => $this->tipoCedulaCliente,
                        'cedula_cliente'      => $this->cedulaCliente,
                        'nombre_cliente'      => $this->nombreCliente,
                        'apellido_cliente'    => $this->apellidoCliente,
                        'contacto_cliente'    => $this->contactoCliente,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar cliente: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM cliente WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $cliente = new static($row);
        $cliente->id = (int)$row['id_cliente'];
        return $cliente;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT
                    id_cliente AS id,
                    tipo_cedula_cliente,
                    cedula_cliente,
                    CONCAT(tipo_cedula_cliente, '-', cedula_cliente) AS cedula_completa,
                    nombre_cliente,
                    apellido_cliente,
                    CONCAT(nombre_cliente, ' ', apellido_cliente) AS nombre_completo,
                    contacto_cliente,
                    activo
                FROM cliente WHERE activo = 1 ORDER BY nombre_cliente ASC, apellido_cliente ASC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM cliente WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT * FROM cliente WHERE id_cliente = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener cliente por ID: ' . $e->getMessage());
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
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM cliente WHERE id_cliente = :id");
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
            $stmt = $this->db()->prepare("UPDATE cliente SET activo = 0 WHERE id_cliente = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'cliente', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error al desactivar cliente: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE cliente SET activo = 1 WHERE id_cliente = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar cliente: ' . $e->getMessage());
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
            $this->nombreCliente = $found->getNombreCliente();
            $this->apellidoCliente = $found->getApellidoCliente();
            $this->tipoCedulaCliente = $found->getTipoCedulaCliente();
            $this->cedulaCliente = $found->getCedulaCliente();
            $this->contactoCliente = $found->getContactoCliente();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }
}
