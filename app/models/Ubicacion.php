<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class Ubicacion extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_ubicacion' => ['type' => 'nombre', 'required' => true],
        'descripcion'      => ['type' => null,     'required' => false],
        'zona'             => ['type' => 'nombre', 'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id_ubicacion AS id, nombre_ubicacion, descripcion, zona, activo FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener ubicaciones: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT id_ubicacion AS id, nombre_ubicacion, descripcion, zona FROM ubicacion WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener ubicación por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM ubicacion WHERE id_ubicacion = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasAssociatedLots(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM lote WHERE id_ubicacion = :id AND activo = 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
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
            $stmt = $this->db()->prepare("UPDATE ubicacion SET activo = 0 WHERE id_ubicacion = ?");
            $stmt->execute([$id]);
            AuditLog::record('DEACTIVATE', 'ubicacion', $id, $oldData, null);
            return true;
        } catch (\Throwable $e) {
            error_log('Error al desactivar ubicación: ' . $e->getMessage());
            throw $e;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE ubicacion SET activo = 1 WHERE id_ubicacion = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('Error al restaurar ubicación: ' . $e->getMessage());
            return false;
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

    public function add(string $nombreUbicacion, ?string $descripcion = null, ?string $zona = null): bool
    {
        $this->validateData([
            'nombre_ubicacion' => $nombreUbicacion,
            'descripcion' => $descripcion,
            'zona' => $zona,
        ]);
        try {
            $stmt = $this->db()->prepare("INSERT INTO ubicacion (nombre_ubicacion, descripcion, zona) VALUES (?, ?, ?)");
            $stmt->execute([trim($nombreUbicacion), $descripcion, $zona]);

            $newId = (int) $this->db()->lastInsertId();

            AuditLog::record('CREATE', 'ubicacion', $newId, null, [
                'nombre_ubicacion' => $nombreUbicacion,
                'descripcion'      => $descripcion,
                'zona'             => $zona,
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('Error al agregar ubicación: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, string $nombreUbicacion, ?string $descripcion = null, ?string $zona = null): bool
    {
        $this->validateData([
            'nombre_ubicacion' => $nombreUbicacion,
            'descripcion' => $descripcion,
            'zona' => $zona,
        ]);
        try {
            if (!$this->exists($id)) {
                throw new \Exception("No existe la ubicación con ID: $id");
            }

            $oldData = $this->getById($id);

            $stmt = $this->db()->prepare("UPDATE ubicacion SET nombre_ubicacion = ?, descripcion = ?, zona = ? WHERE id_ubicacion = ?");
            $stmt->execute([trim($nombreUbicacion), $descripcion, $zona, $id]);

            AuditLog::record('UPDATE', 'ubicacion', $id, $oldData, [
                'nombre_ubicacion' => $nombreUbicacion,
                'descripcion'      => $descripcion,
                'zona'             => $zona,
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('Error al actualizar ubicación: ' . $e->getMessage());
            throw $e;
        }
    }
}
