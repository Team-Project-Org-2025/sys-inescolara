<?php
// app/models/User.php
namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;
use Exception;

class User extends Database
{

    public function __construct()
    {
        parent::__construct('security');
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function authenticate($nombreUsuario, $password)
    {
        try {
            $sql = "SELECT id, nombre_usuario, contrasena, rol_id FROM usuarios WHERE nombre_usuario = :nombre_usuario";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['nombre_usuario' => $nombreUsuario]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['contrasena'])) {

                    if (password_needs_rehash($user['contrasena'], PASSWORD_DEFAULT)) {
                        $this->updatePasswordHash($user['id'], $password);
                    }

                    unset($user['contrasena']);
                    return $user;
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("Error de autenticación: " . $e->getMessage());
            return null;
        }
    }

    private function updatePasswordHash(int $userId, string $plainPassword): void
    {
        try {
            $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE usuarios SET contrasena = :hash WHERE id = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $userId]);
        } catch (\Throwable $e) {
            error_log("Error actualizando hash: " . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre_usuario, rol_id FROM usuarios ORDER BY id ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log("Error al obtener todos los usuarios: " . $e->getMessage());
            return [];
        }
    }


    public function userExists(int $id = null, string $nombreUsuario = null): bool
    {
        try {
            if ($id !== null) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
            } elseif ($nombreUsuario !== null) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = :nombre_usuario");
                $stmt->execute([':nombre_usuario' => $nombreUsuario]);
            } else {
                return false;
            }
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            error_log("Error en userExists: " . $e->getMessage());
            return false;
        }
    }


    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre_usuario, rol_id FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log("Error en getById: " . $e->getMessage());
            return null;
        }
    }

    public function add(string $nombreUsuario, string $password, int $rolId)
    {
        if ($this->userExists(null, $nombreUsuario)) {
            throw new Exception("Ya existe un usuario con este nombre de usuario.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new Exception("Error al hashear la contraseña");
        }

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre_usuario, contrasena, rol_id)
            VALUES (:nombre_usuario, :contrasena, :rol_id)
        ");

        return $stmt->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':contrasena' => $passwordHash,
            ':rol_id' => $rolId
        ]);
    }


    public function update(int $id, string $nombreUsuario, int $rolId, string $password = null)
    {
        if (!$this->userExists($id)) {
            throw new Exception("No existe el usuario con ID: $id");
        }

        $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, rol_id = :rol_id";
        $params = [
            ':id' => $id,
            ':nombre_usuario' => $nombreUsuario,
            ':rol_id' => $rolId
        ];

        if ($password !== null && $password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new Exception("Error al hashear la contraseña");
            }

            $sql .= ", contrasena = :contrasena";
            $params[':contrasena'] = $passwordHash;
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }


    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log("Error en delete: " . $e->getMessage());
            return false;
        }
    }

    public function isPasswordStrong(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,}$/', $password) === 1;
    }
}
