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
            // Migración: agregar columna avatar si no existe
            try {
                $stmt = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'avatar'");
                if (!$stmt->fetch()) {
                    $this->db->exec("ALTER TABLE usuarios ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER correo_electronico");
                }
            } catch (\Throwable $e) {
                error_log('Error al migrar columna avatar: ' . $e->getMessage());
            }

            // Migración: tabla usuario_permisos para permisos individuales por usuario
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS usuario_permisos (
                    id_usuario INT NOT NULL,
                    id_permiso INT NOT NULL,
                    PRIMARY KEY (id_usuario, id_permiso),
                    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
                    FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (\Throwable $e) {
                error_log('Error al migrar tabla usuario_permisos: ' . $e->getMessage());
            }

            // Asegurar roles
            $adminExists = (int)$this->db->query("SELECT COUNT(*) FROM roles WHERE id_rol = 1")->fetchColumn();
            if (!$adminExists) {
                $this->db->exec("INSERT INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (1, 'Administrador', 'Acceso total al sistema')");
            }

            $trabajadorExists = (int)$this->db->query("SELECT COUNT(*) FROM roles WHERE id_rol = 2")->fetchColumn();
            if (!$trabajadorExists) {
                $this->db->exec("INSERT IGNORE INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (2, 'Trabajador', 'Acceso a inventario, plantas, clientes y ventas')");
            }

            // Asegurar permisos
            $permisos = [
                ['codigo' => 'DASHBOARD_VIEW', 'desc' => 'Ver panel principal'],
                ['codigo' => 'INVENTARIO_VIEW', 'desc' => 'Ver inventario'],
                ['codigo' => 'VENTAS_ACCESS', 'desc' => 'Acceder a ventas/POS'],
                ['codigo' => 'VENTAS_CREATE', 'desc' => 'Crear ventas'],
                ['codigo' => 'VENTAS_EDIT', 'desc' => 'Editar ventas'],
                ['codigo' => 'VENTAS_DELETE', 'desc' => 'Anular ventas'],
                ['codigo' => 'VENTAS_PDF', 'desc' => 'Exportar comprobante PDF'],
                ['codigo' => 'USUARIOS_MANAGE', 'desc' => 'Gestionar usuarios'],
                ['codigo' => 'PLANTAS_VIEW', 'desc' => 'Ver plantas'],
                ['codigo' => 'PLANTAS_CREATE', 'desc' => 'Crear plantas'],
                ['codigo' => 'PLANTAS_EDIT', 'desc' => 'Editar plantas'],
                ['codigo' => 'PLANTAS_DELETE', 'desc' => 'Eliminar plantas'],
                ['codigo' => 'PROVEEDORES_VIEW', 'desc' => 'Ver proveedores'],
                ['codigo' => 'PROVEEDORES_CREATE', 'desc' => 'Crear proveedores'],
                ['codigo' => 'PROVEEDORES_EDIT', 'desc' => 'Editar proveedores'],
                ['codigo' => 'PROVEEDORES_DELETE', 'desc' => 'Eliminar proveedores'],
                ['codigo' => 'INSUMOS_VIEW', 'desc' => 'Ver insumos'],
                ['codigo' => 'INSUMOS_CREATE', 'desc' => 'Crear insumos'],
                ['codigo' => 'INSUMOS_EDIT', 'desc' => 'Editar insumos'],
                ['codigo' => 'INSUMOS_DELETE', 'desc' => 'Eliminar insumos'],
                ['codigo' => 'TRABAJADORES_VIEW', 'desc' => 'Ver trabajadores'],
                ['codigo' => 'TRABAJADORES_CREATE', 'desc' => 'Crear trabajadores'],
                ['codigo' => 'TRABAJADORES_EDIT', 'desc' => 'Editar trabajadores'],
                ['codigo' => 'TRABAJADORES_DELETE', 'desc' => 'Eliminar trabajadores'],
                ['codigo' => 'CLIENTES_VIEW', 'desc' => 'Ver clientes'],
                ['codigo' => 'CLIENTES_CREATE', 'desc' => 'Crear clientes'],
                ['codigo' => 'CLIENTES_EDIT', 'desc' => 'Editar clientes'],
                ['codigo' => 'CLIENTES_DELETE', 'desc' => 'Eliminar clientes'],
                ['codigo' => 'TAREAS_VIEW', 'desc' => 'Ver tareas'],
                ['codigo' => 'TAREAS_CREATE', 'desc' => 'Crear tareas'],
                ['codigo' => 'TAREAS_EDIT', 'desc' => 'Editar tareas'],
                ['codigo' => 'TAREAS_DELETE', 'desc' => 'Eliminar tareas'],
                ['codigo' => 'UBICACIONES_VIEW', 'desc' => 'Ver ubicaciones'],
                ['codigo' => 'UBICACIONES_CREATE', 'desc' => 'Crear ubicaciones'],
                ['codigo' => 'UBICACIONES_EDIT', 'desc' => 'Editar ubicaciones'],
                ['codigo' => 'UBICACIONES_DELETE', 'desc' => 'Eliminar ubicaciones'],
                ['codigo' => 'ASISTENTE_ACCESS', 'desc' => 'Acceder al asistente IA'],
                ['codigo' => 'HERRAMIENTAS_VIEW', 'desc' => 'Ver herramientas'],
                ['codigo' => 'HERRAMIENTAS_CREATE', 'desc' => 'Crear herramientas'],
                ['codigo' => 'HERRAMIENTAS_EDIT', 'desc' => 'Editar herramientas'],
                ['codigo' => 'HERRAMIENTAS_DELETE', 'desc' => 'Eliminar herramientas'],
                ['codigo' => 'PRECIOS_VIEW', 'desc' => 'Ver precios'],
                ['codigo' => 'PRECIOS_CREATE', 'desc' => 'Crear precios'],
                ['codigo' => 'PRECIOS_EDIT', 'desc' => 'Editar precios'],
                ['codigo' => 'PRECIOS_DELETE', 'desc' => 'Eliminar precios'],
                ['codigo' => 'UNIDADES_MEDIDA_VIEW', 'desc' => 'Ver unidades de medida'],
                ['codigo' => 'UNIDADES_MEDIDA_CREATE', 'desc' => 'Crear unidades de medida'],
                ['codigo' => 'UNIDADES_MEDIDA_EDIT', 'desc' => 'Editar unidades de medida'],
                ['codigo' => 'UNIDADES_MEDIDA_DELETE', 'desc' => 'Eliminar unidades de medida'],
                ['codigo' => 'INVENTARIO_ADJUST', 'desc' => 'Ajustar inventario'],
                ['codigo' => 'TAREAS_ASSIGN', 'desc' => 'Asignar tareas a trabajadores'],
                ['codigo' => 'USO_HERRAMIENTA_CREATE', 'desc' => 'Registrar uso de herramientas'],
                ['codigo' => 'BACKUPS_CREATE', 'desc' => 'Crear respaldos'],
                ['codigo' => 'BACKUPS_DELETE', 'desc' => 'Eliminar y restaurar respaldos'],
                ['codigo' => 'AUDIT_VIEW', 'desc' => 'Ver bitácora de auditoría'],
                ['codigo' => 'RECOLECCION_VIEW', 'desc' => 'Ver recolecciones'],
                ['codigo' => 'RECOLECCION_CREATE', 'desc' => 'Crear recolecciones'],
                ['codigo' => 'RECOLECCION_EDIT', 'desc' => 'Editar recolecciones'],
                ['codigo' => 'RECOLECCION_DELETE', 'desc' => 'Eliminar recolecciones'],
                ['codigo' => 'RECOLECCION_COMPLETE', 'desc' => 'Completar recolecciones y registrar insumos'],
                ['codigo' => 'ORNATOS_VIEW', 'desc' => 'Ver ornatos'],
                ['codigo' => 'ORNATOS_CREATE', 'desc' => 'Crear ornatos'],
                ['codigo' => 'ORNATOS_EDIT', 'desc' => 'Editar ornatos'],
                ['codigo' => 'ORNATOS_DELETE', 'desc' => 'Eliminar ornatos'],
            ];

            $stmtCheckPermiso = $this->db->prepare("SELECT COUNT(*) FROM permisos WHERE codigo_permiso = :codigo");
            $stmtInsertPermiso = $this->db->prepare("INSERT IGNORE INTO permisos (codigo_permiso, descripcion_permiso) VALUES (:codigo, :descripcion)");
            foreach ($permisos as $p) {
                $stmtCheckPermiso->execute([':codigo' => $p['codigo']]);
                if ((int)$stmtCheckPermiso->fetchColumn() === 0) {
                    $stmtInsertPermiso->execute([':codigo' => $p['codigo'], ':descripcion' => $p['desc']]);
                }
            }

            // Mapa permisos -> id
            $allPermisos = $this->db->query("SELECT id_permiso, codigo_permiso FROM permisos")->fetchAll(PDO::FETCH_ASSOC);
            $permMap = [];
            foreach ($allPermisos as $p) {
                $permMap[$p['codigo_permiso']] = $p['id_permiso'];
            }

            // Asegurar rol_permisos para Administrador (rol 1) — todos
            $stmtCheckRP = $this->db->prepare("SELECT COUNT(*) FROM rol_permisos WHERE id_rol = :rol AND id_permiso = :perm");
            $stmtInsertRP = $this->db->prepare("INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) VALUES (:rol, :perm)");
            foreach ($permMap as $pid) {
                $stmtCheckRP->execute([':rol' => 1, ':perm' => $pid]);
                if ((int)$stmtCheckRP->fetchColumn() === 0) {
                    $stmtInsertRP->execute([':rol' => 1, ':perm' => $pid]);
                }
            }

            // Asegurar rol_permisos para Trabajador (rol 2) — limitados
            $trabajadorPermisos = ['DASHBOARD_VIEW', 'INVENTARIO_VIEW', 'VENTAS_ACCESS', 'VENTAS_CREATE', 'VENTAS_PDF', 'PLANTAS_VIEW', 'PLANTAS_CREATE', 'PLANTAS_EDIT', 'CLIENTES_VIEW', 'CLIENTES_CREATE', 'CLIENTES_EDIT', 'TAREAS_VIEW', 'ASISTENTE_ACCESS'];
            foreach ($trabajadorPermisos as $cod) {
                if (isset($permMap[$cod])) {
                    $stmtCheckRP->execute([':rol' => 2, ':perm' => $permMap[$cod]]);
                    if ((int)$stmtCheckRP->fetchColumn() === 0) {
                        $stmtInsertRP->execute([':rol' => 2, ':perm' => $permMap[$cod]]);
                    }
                }
            }

            // Asegurar usuario admin por defecto si no existe
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
            'avatar' => $user['avatar'] ?? null,
            'rol_id' => $user['id_rol'] ?? $user['rol_id'] ?? null,
            'nombre_rol' => $user['nombre_rol'] ?? null,
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
            $sql = "SELECT id_usuario, nombre_usuario, avatar, password_hash, id_rol, correo_electronico, estatus FROM usuarios WHERE nombre_usuario = :nombre_usuario OR correo_electronico = :correo_electronico";
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

    public function verifyPassword(int $id, string $password): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            return password_verify($password, $row['password_hash']);
        } catch (\Throwable $e) {
            error_log("Error en verifyPassword: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_usuario, nombre_usuario, correo_electronico, avatar, id_rol, estatus FROM usuarios WHERE correo_electronico = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $this->normalizeUserRow($user) : null;
        } catch (\Throwable $e) {
            error_log("Error en getUserByEmail: " . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($hash === false) {
                throw new Exception("Error al hashear la contraseña");
            }
            $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id");
            return $stmt->execute([':hash' => $hash, ':id' => $userId]);
        } catch (\Throwable $e) {
            error_log("Error en updatePassword: " . $e->getMessage());
            return false;
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
            $stmt = $this->db->query("
                SELECT u.id_usuario, u.nombre_usuario, u.avatar, u.id_rol, r.nombre_rol, u.correo_electronico, u.estatus 
                FROM usuarios u
                LEFT JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY u.id_usuario ASC
            ");
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
            $stmt = $this->db->prepare("SELECT id_usuario, nombre_usuario, avatar, id_rol, correo_electronico, estatus FROM usuarios WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $this->normalizeUserRow($user) : null;
        } catch (\Throwable $e) {
            error_log("Error en getById: " . $e->getMessage());
            return null;
        }
    }

    public function add(string $nombreUsuario, string $password, int $rolId, ?string $correoElectronico = null, ?string $avatar = null)
    {
        if ($this->userExists(null, $nombreUsuario)) {
            throw new Exception("Ya existe un usuario con este nombre de usuario.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new Exception("Error al hashear la contraseña");
        }

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre_usuario, password_hash, id_rol, correo_electronico, avatar)
            VALUES (:nombre_usuario, :password_hash, :id_rol, :correo_electronico, :avatar)
        ");

        return $stmt->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':password_hash' => $passwordHash,
            ':id_rol' => $rolId,
            ':correo_electronico' => $correoElectronico,
            ':avatar' => $avatar,
        ]);
    }


    public function update(int $id, string $nombreUsuario, int $rolId, ?string $correoElectronico = null, ?string $password = null, ?string $avatar = null)
    {
        if (!$this->userExists($id)) {
            throw new Exception("No existe el usuario con ID: $id");
        }

        $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, id_rol = :id_rol, correo_electronico = :correo_electronico";
        $params = [
            ':id' => $id,
            ':nombre_usuario' => $nombreUsuario,
            ':id_rol' => $rolId,
            ':correo_electronico' => $correoElectronico,
        ];

        if ($password !== null && $password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new Exception("Error al hashear la contraseña");
            }

            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = $passwordHash;
        }

        $sql .= ", avatar = :avatar";
        $params[':avatar'] = $avatar;

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

    public function updateProfile(int $id, string $nombreUsuario, ?string $correoElectronico = null, ?string $password = null, ?string $avatar = null): bool
    {
        try {
            if (!$this->userExists($id)) {
                throw new Exception("No existe el usuario con ID: $id");
            }

            $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, correo_electronico = :correo_electronico";
            $params = [
                ':id' => $id,
                ':nombre_usuario' => $nombreUsuario,
                ':correo_electronico' => $correoElectronico,
            ];

            if ($password !== null && $password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($passwordHash === false) {
                    throw new Exception("Error al hashear la contraseña");
                }
                $sql .= ", password_hash = :password_hash";
                $params[':password_hash'] = $passwordHash;
            }

            $sql .= ", avatar = :avatar";
            $params[':avatar'] = $avatar;

            $sql .= " WHERE id_usuario = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log("Error en updateProfile: " . $e->getMessage());
            return false;
        }
    }

    public function getRoles(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_rol, nombre_rol, descripcion_rol FROM roles ORDER BY id_rol ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error al obtener roles: " . $e->getMessage());
            return [];
        }
    }

    public function getAllPermissions(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_permiso, codigo_permiso, descripcion_permiso FROM permisos ORDER BY codigo_permiso ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error al obtener todos los permisos: " . $e->getMessage());
            return [];
        }
    }

    public function getUserPermissions(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_permiso FROM usuario_permisos WHERE id_usuario = :uid");
            $stmt->execute([':uid' => $userId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_permiso');
        } catch (\Throwable $e) {
            error_log("Error al obtener permisos de usuario: " . $e->getMessage());
            return [];
        }
    }

    public function setUserPermissions(int $userId, array $permisoIds): void
    {
        try {
            $this->db->beginTransaction();
            $stmtDel = $this->db->prepare("DELETE FROM usuario_permisos WHERE id_usuario = :uid");
            $stmtDel->execute([':uid' => $userId]);
            if (!empty($permisoIds)) {
                $stmtIns = $this->db->prepare("INSERT INTO usuario_permisos (id_usuario, id_permiso) VALUES (:uid, :pid)");
                foreach ($permisoIds as $pid) {
                    $stmtIns->execute([':uid' => $userId, ':pid' => (int)$pid]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Error al guardar permisos de usuario: " . $e->getMessage());
        }
    }

    public function getRolePermissions(int $rolId, ?int $userId = null): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.codigo_permiso
                FROM rol_permisos rp
                JOIN permisos p ON p.id_permiso = rp.id_permiso
                WHERE rp.id_rol = :rol_id
            ");
            $stmt->execute([':rol_id' => $rolId]);
            $permisos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'codigo_permiso');

            // Para no-administradores, fusionar con permisos individuales del usuario
            if ($rolId !== 1 && $userId !== null) {
                $userPermIds = $this->getUserPermissions($userId);
                if (!empty($userPermIds)) {
                    $placeholders = implode(',', array_fill(0, count($userPermIds), '?'));
                    $stmt2 = $this->db->prepare("SELECT codigo_permiso FROM permisos WHERE id_permiso IN ($placeholders)");
                    $stmt2->execute(array_map('intval', $userPermIds));
                    $userPermisos = array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'codigo_permiso');
                    $permisos = array_unique(array_merge($permisos, $userPermisos));
                }
            }

            return $permisos;
        } catch (\Throwable $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            return [];
        }
    }

    public function isPasswordStrong(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,}$/', $password) === 1;
    }
}
