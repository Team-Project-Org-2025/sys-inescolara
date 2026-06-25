<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Cliente extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_cliente'      => ['type' => 'nombre',       'required' => true],
        'apellido_cliente'    => ['type' => 'nombre',       'required' => false],
        'tipo_cedula_cliente' => ['type' => 'tipo_cedula',  'required' => false],
        'cedula_cliente'      => ['type' => 'cedula_numero','required' => false],
        'contacto_cliente'    => ['type' => null,           'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->bootstrapDefaults();
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
        } catch (\Throwable $e) {
            error_log('Error al migrar cliente: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
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
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener clientes: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM cliente WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM cliente WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE cliente SET activo = 0 WHERE id_cliente = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'cliente', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE cliente SET activo = 1 WHERE id_cliente = :id");
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

    public function add(string $nombreCliente, ?string $apellidoCliente = null, ?string $tipoCedulaCliente = null, ?string $cedulaCliente = null, ?string $contactoCliente = null)
    {
        $this->validateData([
            'nombre_cliente'      => $nombreCliente,
            'apellido_cliente'    => $apellidoCliente,
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente'      => $cedulaCliente,
            'contacto_cliente'    => $contactoCliente,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO cliente (tipo_cedula_cliente, cedula_cliente, nombre_cliente, apellido_cliente, contacto_cliente) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tipoCedulaCliente, $cedulaCliente, $nombreCliente, $apellidoCliente, $contactoCliente]);

        $newId = (int) $this->db()->lastInsertId();

        AuditLog::record('CREATE', 'cliente', $newId, null, [
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente'      => $cedulaCliente,
            'nombre_cliente'      => $nombreCliente,
            'apellido_cliente'    => $apellidoCliente,
            'contacto_cliente'    => $contactoCliente,
        ]);

        return true;
    }

    public function update(int $id, string $nombreCliente, ?string $apellidoCliente = null, ?string $tipoCedulaCliente = null, ?string $cedulaCliente = null, ?string $contactoCliente = null)
    {
        $this->validateData([
            'nombre_cliente'      => $nombreCliente,
            'apellido_cliente'    => $apellidoCliente,
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente'      => $cedulaCliente,
            'contacto_cliente'    => $contactoCliente,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe el cliente con ID: $id");
        }

        $oldData = $this->getById($id);

        $stmt = $this->db()->prepare("UPDATE cliente SET tipo_cedula_cliente = ?, cedula_cliente = ?, nombre_cliente = ?, apellido_cliente = ?, contacto_cliente = ? WHERE id_cliente = ?");
        $stmt->execute([$tipoCedulaCliente, $cedulaCliente, $nombreCliente, $apellidoCliente, $contactoCliente, $id]);

        AuditLog::record('UPDATE', 'cliente', $id, $oldData, [
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente'      => $cedulaCliente,
            'nombre_cliente'      => $nombreCliente,
            'apellido_cliente'    => $apellidoCliente,
            'contacto_cliente'    => $contactoCliente,
        ]);

        return true;
    }
}
