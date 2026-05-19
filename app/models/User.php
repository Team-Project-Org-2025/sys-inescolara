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
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            $rolesCount = (int)$this->db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
            if ($rolesCount === 0) {
                $stmt = $this->db->prepare("INSERT INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (1, 'Administrador', 'Acceso total al sistema')");
                $stmt->execute();
            }

            $usersCount = (int)$this->db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            if ($usersCount === 0) {
                $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("
                    INSERT INTO usuarios
                    (id_usuario, nombre_usuario, password_hash, correo_electronico, id_rol, id_trabajador_ref, estatus, intentos_fallidos, ultimo_acceso, created_at)
                    VALUES
                    (1, 'admin', :password_hash, 'admin@inecolara.gob.ve', 1, NULL, 'Activo', 0, NULL, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([':password_hash' => $hash]);
            }
        } catch (\Throwable $e) {
            error_log('Bootstrap de usuarios falló: ' . $e->getMessage());
        }
    }

    private function normalizeUserRow(array $user): array
    {
        return [
            'id' => (int)($user['id_usuario'] ?? $user['id'] ?? 0),
            'nombre_usuario' => $user['nombre_usuario'] ?? null,
            'correo_electronico' => $user['correo_electronico'] ?? null,
            'rol_id' => $user['id_rol'] ?? $user['rol_id'] ?? null,
            'estatus' => $user['estatus'] ?? null,
        ];
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function authenticate($identificador, $password)
    {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, password_hash, id_rol, correo_electronico, estatus FROM usuarios WHERE nombre_usuario = :nombre_usuario OR correo_electronico = :correo_electronico";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre_usuario' => $identificador,
                ':correo_electronico' => $identificador,
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {

                    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                        $this->updatePasswordHash((int)$user['id_usuario'], $password);
                    }

                    return $this->normalizeUserRow($user);
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
            $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $userId]);
        } catch (\Throwable $e) {
            error_log("Error actualizando hash: " . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id_usuario, nombre_usuario, id_rol, correo_electronico, estatus FROM usuarios ORDER BY id_usuario ASC");
            if (!$stmt) {
                return [];
            }

            return array_map([$this, 'normalizeUserRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            error_log("Error al obtener todos los usuarios: " . $e->getMessage());
            return [];
        }
    }


    public function userExists(int $id = null, string $nombreUsuario = null): bool
    {
        try {
            if ($id !== null) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = :id");
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
            $stmt = $this->db->prepare("SELECT id_usuario, nombre_usuario, id_rol, correo_electronico, estatus FROM usuarios WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $this->normalizeUserRow($user) : null;
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
            INSERT INTO usuarios (nombre_usuario, password_hash, id_rol)
            VALUES (:nombre_usuario, :password_hash, :id_rol)
        ");

        return $stmt->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':password_hash' => $passwordHash,
            ':id_rol' => $rolId
        ]);
    }


    public function update(int $id, string $nombreUsuario, int $rolId, string $password = null)
    {
        if (!$this->userExists($id)) {
            throw new Exception("No existe el usuario con ID: $id");
        }

        $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, id_rol = :id_rol";
        $params = [
            ':id' => $id,
            ':nombre_usuario' => $nombreUsuario,
            ':id_rol' => $rolId
        ];

        if ($password !== null && $password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new Exception("Error al hashear la contraseña");
            }

            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = $passwordHash;
        }

        $sql .= " WHERE id_usuario = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }


    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
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
