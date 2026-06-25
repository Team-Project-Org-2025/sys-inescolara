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

    protected array $validationRules = [
        'nombre_especie' => ['type' => 'nombre', 'required' => true],
        'descripcion'    => ['type' => null,     'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_especie AS id, nombre_especie, descripcion, activo FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error obtener especies: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function hasActivePlants(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM plantas WHERE id_especie = ? AND activo = 1");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM especie WHERE id_especie = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE especie SET activo = 0 WHERE id_especie = ?");
        $stmt->execute([$id]);
        AuditLog::record('DEACTIVATE', 'especie', $id, $oldData, null);
        return true;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE especie SET activo = 1 WHERE id_especie = :id");
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

    public function add(string $nombreEspecie, ?string $descripcion = null)
    {
        $this->validateData([
            'nombre_especie' => $nombreEspecie,
            'descripcion' => $descripcion,
        ]);
        $stmt = $this->db()->prepare("INSERT INTO especie (nombre_especie, descripcion) VALUES (?, ?)");
        $stmt->execute([$nombreEspecie, $descripcion]);

        $newId = (int) $this->db()->lastInsertId();

        AuditLog::record('CREATE', 'especie', $newId, null, [
            'nombre_especie' => $nombreEspecie,
            'descripcion'    => $descripcion,
        ]);

        return true;
    }

    public function update(int $id, string $nombreEspecie, ?string $descripcion = null)
    {
        $this->validateData([
            'nombre_especie' => $nombreEspecie,
            'descripcion' => $descripcion,
        ]);
        if (!$this->exists($id)) {
            throw new \Exception("No existe la especie con ID: $id");
        }

        $oldData = $this->getById($id);

        $stmt = $this->db()->prepare("UPDATE especie SET nombre_especie = ?, descripcion = ? WHERE id_especie = ?");
        $stmt->execute([$nombreEspecie, $descripcion, $id]);

        AuditLog::record('UPDATE', 'especie', $id, $oldData, [
            'nombre_especie' => $nombreEspecie,
            'descripcion'    => $descripcion,
        ]);

        return true;
    }
}
