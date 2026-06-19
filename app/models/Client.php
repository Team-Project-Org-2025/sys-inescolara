<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Client extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_cliente'   => ['type' => 'nombre', 'required' => true],
        'contacto_cliente' => ['type' => null,     'required' => false],
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

            if (!in_array('contacto_cliente', $columns)) {
                if (in_array('informacion_contacto', $columns)) {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN contacto_cliente VARCHAR(100) AFTER nombre_cliente");
                    $this->db()->exec("UPDATE cliente SET contacto_cliente = informacion_contacto WHERE contacto_cliente IS NULL");
                } else {
                    $this->db()->exec("ALTER TABLE cliente ADD COLUMN contacto_cliente VARCHAR(100) DEFAULT NULL AFTER nombre_cliente");
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al migrar cliente: ' . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_cliente AS id, nombre_cliente, contacto_cliente, activo FROM cliente WHERE activo = 1 ORDER BY nombre_cliente ASC";
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
        $stmt = $this->db()->prepare("UPDATE cliente SET activo = 0 WHERE id_cliente = :id");
        return $stmt->execute([':id' => $id]);
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

    public function add(string $nombreCliente, ?string $contactoCliente = null)
    {
        $this->validateData([
            'nombre_cliente' => $nombreCliente,
            'contacto_cliente' => $contactoCliente,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO cliente (nombre_cliente, contacto_cliente) VALUES (:nombre_cliente, :contacto_cliente)");
        return $stmt->execute([
            ':nombre_cliente' => $nombreCliente,
            ':contacto_cliente' => $contactoCliente,
        ]);
    }

    public function update(int $id, string $nombreCliente, ?string $contactoCliente = null)
    {
        $this->validateData([
            'nombre_cliente' => $nombreCliente,
            'contacto_cliente' => $contactoCliente,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe el cliente con ID: $id");
        }
        $stmt = $this->db()->prepare("UPDATE cliente SET nombre_cliente = :nombre_cliente, contacto_cliente = :contacto_cliente WHERE id_cliente = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nombre_cliente' => $nombreCliente,
            ':contacto_cliente' => $contactoCliente,
        ]);
    }
}
