<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class Role extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_rol'  => ['type' => 'nombre', 'required' => true],
        'descripcion' => ['type' => null,     'required' => false],
    ];

    public function __construct()
    {
        parent::__construct('security');
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT r.id_rol AS id, r.nombre_rol, r.descripcion_rol,
                           (SELECT COUNT(*) FROM rol_permisos rp WHERE rp.id_rol = r.id_rol) AS total_permisos
                    FROM roles r
                    ORDER BY r.id_rol ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener roles: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_rol AS id, nombre_rol, descripcion_rol FROM roles WHERE id_rol = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener rol: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id_rol = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM roles WHERE nombre_rol = :name";
            $params = [':name' => $name];
            if ($excludeId !== null) {
                $sql .= " AND id_rol != :exclude";
                $params[':exclude'] = $excludeId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = :id");
            $stmtCheck->execute([':id' => $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception('No se puede eliminar el rol: hay usuarios asignados a él.');
            }
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id_rol = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('Error al eliminar rol: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function add(string $nombreRol, ?string $descripcion = null, array $permisoIds = []): bool
    {
        $this->validateData([
            'nombre_rol' => $nombreRol,
            'descripcion' => $descripcion,
        ]);
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO roles (nombre_rol, descripcion_rol) VALUES (:nombre, :descripcion)");
            $stmt->execute([':nombre' => $nombreRol, ':descripcion' => $descripcion]);
            $newId = (int)$this->db->lastInsertId();

            if (!empty($permisoIds)) {
                $this->setRolePermissions($newId, $permisoIds);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Error al crear rol: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, string $nombreRol, ?string $descripcion = null, array $permisoIds = []): bool
    {
        $this->validateData([
            'nombre_rol' => $nombreRol,
            'descripcion' => $descripcion,
        ]);
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE roles SET nombre_rol = :nombre, descripcion_rol = :descripcion WHERE id_rol = :id");
            $stmt->execute([':nombre' => $nombreRol, ':descripcion' => $descripcion, ':id' => $id]);

            $this->setRolePermissions($id, $permisoIds);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Error al actualizar rol: ' . $e->getMessage());
            return false;
        }
    }

    public function getRolePermissions(int $roleId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_permiso FROM rol_permisos WHERE id_rol = :rid");
            $stmt->execute([':rid' => $roleId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_permiso');
        } catch (\Throwable $e) {
            error_log('Error al obtener permisos del rol: ' . $e->getMessage());
            return [];
        }
    }

    private function setRolePermissions(int $roleId, array $permisoIds): void
    {
        try {
            $stmtDel = $this->db->prepare("DELETE FROM rol_permisos WHERE id_rol = :rid");
            $stmtDel->execute([':rid' => $roleId]);

            if (!empty($permisoIds)) {
                $stmtIns = $this->db->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (:rid, :pid)");
                foreach ($permisoIds as $pid) {
                    $stmtIns->execute([':rid' => $roleId, ':pid' => (int)$pid]);
                }
            }
        } catch (\Throwable $e) {
            error_log('Error al asignar permisos al rol: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAllPermissions(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_permiso, codigo_permiso, descripcion_permiso FROM permisos ORDER BY codigo_permiso ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error al obtener permisos: ' . $e->getMessage());
            return [];
        }
    }
}
