<?php
// app/models/Usuario.php
namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Exception;
use SysInescolara\models\AuditLog;

class Usuario extends Database
{
    use ValidationTrait;

    protected array $validationRules = [
        'nombre_usuario'    => ['type' => null,  'required' => true],
        'id_rol'            => ['type' => null,  'required' => true],
        'correo_electronico'=> ['type' => 'email','required' => false],
        'avatar'            => ['type' => null,  'required' => false],
    ];


    public function __construct()
    {
        parent::__construct('security');
        $this->bootstrapDefaults();
    }

    private function bootstrapDefaults(): void
    {
        try {
            // Migración: columna avatar si no existe
            try {
                $stmt = $this->db()->query("SHOW COLUMNS FROM usuarios LIKE 'avatar'");
                if (!$stmt->fetch()) {
                    $this->db()->exec("ALTER TABLE usuarios ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER correo_electronico");
                }
            } catch (\Throwable $e) {
                error_log('Error al migrar columna avatar: ' . $e->getMessage());
            }

            // Nuevo sistema de permisos basado en módulos + acciones
            // Tabla: modulos
            $this->db()->exec("CREATE TABLE IF NOT EXISTS modulos (
                id_modulo INT(11) NOT NULL AUTO_INCREMENT,
                nombre_modulo VARCHAR(100) NOT NULL,
                descripcion_modulo VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id_modulo),
                UNIQUE KEY uq_nombre_modulo (nombre_modulo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Tabla: permisos (básicos: ver, crear, editar, eliminar)
            $this->db()->exec("CREATE TABLE IF NOT EXISTS permisos (
                id_permiso INT(11) NOT NULL AUTO_INCREMENT,
                nombre_permiso VARCHAR(20) NOT NULL,
                PRIMARY KEY (id_permiso),
                UNIQUE KEY uq_nombre_permiso (nombre_permiso)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Tabla: rol_modulo_permiso
            $this->db()->exec("CREATE TABLE IF NOT EXISTS rol_modulo_permiso (
                id_rol_modulo_permiso INT(11) NOT NULL AUTO_INCREMENT,
                id_rol INT(11) NOT NULL,
                id_modulo INT(11) NOT NULL,
                id_permiso INT(11) NOT NULL,
                PRIMARY KEY (id_rol_modulo_permiso),
                UNIQUE KEY uk_rol_modulo_permiso (id_rol, id_modulo, id_permiso),
                CONSTRAINT fk_rmp_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE,
                CONSTRAINT fk_rmp_modulo FOREIGN KEY (id_modulo) REFERENCES modulos (id_modulo) ON DELETE CASCADE,
                CONSTRAINT fk_rmp_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Tabla: usuario_modulo_permiso
            $this->db()->exec("CREATE TABLE IF NOT EXISTS usuario_modulo_permiso (
                id_usuario_modulo_permiso INT(11) NOT NULL AUTO_INCREMENT,
                id_usuario INT(11) NOT NULL,
                id_modulo INT(11) NOT NULL,
                id_permiso INT(11) NOT NULL,
                PRIMARY KEY (id_usuario_modulo_permiso),
                UNIQUE KEY uk_usuario_modulo_permiso (id_usuario, id_modulo, id_permiso),
                CONSTRAINT fk_ump_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
                CONSTRAINT fk_ump_modulo FOREIGN KEY (id_modulo) REFERENCES modulos (id_modulo) ON DELETE CASCADE,
                CONSTRAINT fk_ump_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Asegurar roles
            $adminExists = (int)$this->db()->query("SELECT COUNT(*) FROM roles WHERE id_rol = 1")->fetchColumn();
            if (!$adminExists) {
                $this->db()->exec("INSERT INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (1, 'Administrador', 'Acceso total al sistema')");
            }
            $trabajadorExists = (int)$this->db()->query("SELECT COUNT(*) FROM roles WHERE id_rol = 2")->fetchColumn();
            if (!$trabajadorExists) {
                $this->db()->exec("INSERT IGNORE INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (2, 'Trabajador', 'Acceso a inventario, plantas, clientes y ventas')");
            }

            // Asegurar 4 permisos básicos
            $permisosBasicos = ['ver', 'crear', 'editar', 'eliminar'];
            foreach ($permisosBasicos as $np) {
                $this->db()->exec("INSERT IGNORE INTO permisos (nombre_permiso) VALUES ('$np')");
            }

            // Asegurar módulos
            $modulos = [
                ['plantas', 'Administrar plantas'],
                ['especies', 'Administrar especies'],
                ['ubicaciones', 'Administrar ubicaciones'],
                ['inventario', 'Gestión de inventario'],
                ['lotes', 'Gestión de lotes'],
                ['trazabilidad', 'Monitoreo y seguimiento de ejemplares'],
                ['insumos', 'Gestión de insumos'],
                ['herramientas', 'Gestión de herramientas'],
                ['unidades_medida', 'Gestión de unidades de medida'],
                ['mermas', 'Registro de mermas y bajas'],
                ['ventas', 'Procesar ventas'],
                ['precios', 'Gestión de precios'],
                ['clientes', 'Gestión de clientes'],
                ['cuentas_cobrar', 'Cuentas por cobrar'],
                ['cuentas_pagar', 'Cuentas por pagar'],
                ['compras', 'Gestión de compras'],
                ['ornatos', 'Gestión de ornatos'],
                ['ampliacion', 'Ampliación de especies'],
                ['proveedores', 'Gestión de proveedores'],
                ['tareas', 'Asignación y seguimiento de tareas'],
                ['empleados', 'Gestión de empleados'],
                ['seed_collection', 'Gestión de recolección de semillas'],
                ['asistente', 'Asistente IA'],
                ['reports', 'Reportes y estadísticas'],
                ['usuarios', 'Administración de usuarios'],
                ['roles', 'Administración de roles'],
                ['auditlog', 'Bitácora de auditoría'],
                ['backups', 'Respaldo y restauración'],
            ];
            foreach ($modulos as $m) {
                $stmt = $this->db()->prepare("SELECT COUNT(*) FROM modulos WHERE nombre_modulo = :nom");
                $stmt->execute([':nom' => $m[0]]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $ins = $this->db()->prepare("INSERT INTO modulos (nombre_modulo, descripcion_modulo) VALUES (:nom, :desc)");
                    $ins->execute([':nom' => $m[0], ':desc' => $m[1]]);
                }
            }

            // Asignar todos los permisos al rol Administrador (1)
            $modulosAll = $this->db()->query("SELECT id_modulo FROM modulos")->fetchAll(PDO::FETCH_COLUMN);
            $permisosAll = $this->db()->query("SELECT id_permiso FROM permisos")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($modulosAll as $idMod) {
                foreach ($permisosAll as $idPerm) {
                    $this->db()->exec("INSERT IGNORE INTO rol_modulo_permiso (id_rol, id_modulo, id_permiso) VALUES (1, $idMod, $idPerm)");
                }
            }

            // Asignar permisos limitados al rol Trabajador (2)
            $trabajadorModulos = ['dashboard', 'inventario', 'plantas', 'especies', 'clientes', 'tareas', 'ventas', 'asistente'];
            $trabajadorAcciones = ['ver', 'crear', 'editar'];
            foreach ($trabajadorModulos as $nomMod) {
                $stmt = $this->db()->prepare("SELECT id_modulo FROM modulos WHERE nombre_modulo = :nom");
                $stmt->execute([':nom' => $nomMod]);
                $idMod = $stmt->fetchColumn();
                if ($idMod) {
                    foreach ($trabajadorAcciones as $acc) {
                        $stmtP = $this->db()->prepare("SELECT id_permiso FROM permisos WHERE nombre_permiso = :acc");
                        $stmtP->execute([':acc' => $acc]);
                        $idPerm = $stmtP->fetchColumn();
                        if ($idPerm) {
                            $this->db()->exec("INSERT IGNORE INTO rol_modulo_permiso (id_rol, id_modulo, id_permiso) VALUES (2, $idMod, $idPerm)");
                        }
                    }
                }
            }

            // Asegurar usuario admin por defecto si no existe
            $usersCount = (int)$this->db()->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            if ($usersCount === 0) {
                $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
                $stmt = $this->db()->prepare("
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
        $trabajadorNombre = '';
        if (!empty($user['nombre_trabajador']) || !empty($user['apellido_trabajador'])) {
            $trabajadorNombre = trim(($user['nombre_trabajador'] ?? '') . ' ' . ($user['apellido_trabajador'] ?? ''));
        }
        return [
            'id' => (int)($user['id_usuario'] ?? $user['id'] ?? 0),
            'nombre_usuario' => $user['nombre_usuario'] ?? null,
            'correo_electronico' => $user['correo_electronico'] ?? null,
            'avatar' => $user['avatar'] ?? null,
            'rol_id' => $user['id_rol'] ?? $user['rol_id'] ?? null,
            'nombre_rol' => $user['nombre_rol'] ?? null,
            'estatus' => $user['estatus'] ?? null,
            'id_trabajador_ref' => $user['id_trabajador_ref'] ?? null,
            'trabajador_nombre' => $trabajadorNombre,
        ];
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function authenticate($identificador, $password)
    {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, avatar, password_hash, id_rol, correo_electronico, estatus FROM usuarios WHERE nombre_usuario = :nombre_usuario OR correo_electronico = :correo_electronico";
            $stmt = $this->db()->prepare($sql);
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
            $stmt = $this->db()->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = :id");
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
            $stmt = $this->db()->prepare("SELECT id_usuario, nombre_usuario, correo_electronico, avatar, id_rol, estatus FROM usuarios WHERE correo_electronico = :email LIMIT 1");
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
            $stmt = $this->db()->prepare("UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id");
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
            $stmt = $this->db()->prepare("UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $userId]);
        } catch (\Throwable $e) {
            error_log("Error actualizando hash: " . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $stmt = $this->db()->query("
                SELECT u.id_usuario, u.nombre_usuario, u.avatar, u.id_rol, u.id_trabajador_ref,
                       r.nombre_rol, u.correo_electronico, u.estatus,
                       t.nombre_trabajador, t.apellido_trabajador
                FROM usuarios u
                LEFT JOIN roles r ON r.id_rol = u.id_rol
                LEFT JOIN `sysinescolara`.`trabajadores` t ON u.id_trabajador_ref = t.id_trabajador
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
                $stmt = $this->db()->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = :id");
                $stmt->execute([':id' => $id]);
            } elseif ($nombreUsuario !== null) {
                $stmt = $this->db()->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = :nombre_usuario");
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
            $stmt = $this->db()->prepare("
                SELECT u.id_usuario, u.nombre_usuario, u.avatar, u.id_rol, u.id_trabajador_ref,
                       u.correo_electronico, u.estatus,
                       t.nombre_trabajador, t.apellido_trabajador
                FROM usuarios u
                LEFT JOIN `sysinescolara`.`trabajadores` t ON u.id_trabajador_ref = t.id_trabajador
                WHERE u.id_usuario = :id
            ");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $this->normalizeUserRow($user) : null;
        } catch (\Throwable $e) {
            error_log("Error en getById: " . $e->getMessage());
            return null;
        }
    }

    public function add(string $nombreUsuario, string $password, int $rolId, ?string $correoElectronico = null, ?string $avatar = null, ?int $idTrabajadorRef = null)
    {
        $this->validateData([
            'nombre_usuario' => $nombreUsuario,
            'id_rol' => $rolId,
            'correo_electronico' => $correoElectronico,
            'avatar' => $avatar,
        ]);
        if ($this->userExists(null, $nombreUsuario)) {
            throw new Exception("Ya existe un usuario con este nombre de usuario.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new Exception("Error al hashear la contraseña");
        }

        $stmt = $this->db()->prepare("
            INSERT INTO usuarios (nombre_usuario, password_hash, id_rol, correo_electronico, avatar, id_trabajador_ref)
            VALUES (:nombre_usuario, :password_hash, :id_rol, :correo_electronico, :avatar, :id_trabajador_ref)
        ");

        $result = $stmt->execute([
            ':nombre_usuario' => $nombreUsuario,
            ':password_hash' => $passwordHash,
            ':id_rol' => $rolId,
            ':correo_electronico' => $correoElectronico,
            ':avatar' => $avatar,
            ':id_trabajador_ref' => $idTrabajadorRef,
        ]);
        if ($result) {
            AuditLog::record('CREATE', 'usuarios', $this->db()->lastInsertId(), null, [
                'nombre_usuario' => $nombreUsuario, 'rol_id' => $rolId,
                'correo_electronico' => $correoElectronico,
            ]);
        }
        return $result;
    }


    public function update(int $id, string $nombreUsuario, int $rolId, ?string $correoElectronico = null, ?string $password = null, ?string $avatar = null, ?int $idTrabajadorRef = null)
    {
        $this->validateData([
            'nombre_usuario' => $nombreUsuario,
            'id_rol' => $rolId,
            'correo_electronico' => $correoElectronico,
            'avatar' => $avatar,
        ]);
        if (!$this->userExists($id)) {
            throw new Exception("No existe el usuario con ID: $id");
        }

        $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, id_rol = :id_rol, correo_electronico = :correo_electronico, id_trabajador_ref = :id_trabajador_ref";
        $params = [
            ':id' => $id,
            ':nombre_usuario' => $nombreUsuario,
            ':id_rol' => $rolId,
            ':correo_electronico' => $correoElectronico,
            ':id_trabajador_ref' => $idTrabajadorRef,
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

        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare($sql);
        $result = $stmt->execute($params);
        AuditLog::record('UPDATE', 'usuarios', $id, $oldData, [
            'nombre_usuario' => $nombreUsuario, 'rol_id' => $rolId,
            'correo_electronico' => $correoElectronico,
        ]);
        return $result;
    }


    public function delete(int $id)
    {
        try {
            $oldData = $this->getById($id);
            $stmt = $this->db()->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $result = $stmt->execute([':id' => $id]);
            AuditLog::record('DELETE', 'usuarios', $id, $oldData, null);
            return $result;
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
            $stmt = $this->db()->prepare($sql);
            return $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log("Error en updateProfile: " . $e->getMessage());
            return false;
        }
    }

    public function getRoles(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_rol, nombre_rol, descripcion_rol FROM roles ORDER BY id_rol ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error al obtener roles: " . $e->getMessage());
            return [];
        }
    }

    public function getAllPermissions(): array
    {
        try {
            $modulos = $this->db()->query("SELECT id_modulo, nombre_modulo, descripcion_modulo FROM modulos ORDER BY nombre_modulo ASC")->fetchAll(PDO::FETCH_ASSOC);
            $acciones = $this->db()->query("SELECT id_permiso, nombre_permiso FROM permisos ORDER BY id_permiso ASC")->fetchAll(PDO::FETCH_ASSOC);
            return ['modulos' => $modulos, 'acciones' => $acciones];
        } catch (\Throwable $e) {
            error_log("Error al obtener todos los permisos: " . $e->getMessage());
            return ['modulos' => [], 'acciones' => []];
        }
    }

    public function getUserPermissions(int $userId): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT ump.id_modulo, ump.id_permiso, m.nombre_modulo, p.nombre_permiso
                FROM usuario_modulo_permiso ump
                JOIN modulos m ON ump.id_modulo = m.id_modulo
                JOIN permisos p ON ump.id_permiso = p.id_permiso
                WHERE ump.id_usuario = :uid
            ");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error al obtener permisos de usuario: " . $e->getMessage());
            return [];
        }
    }

    public function setUserPermissions(int $userId, array $permisos): void
    {
        try {
            $this->db()->beginTransaction();
            $stmtDel = $this->db()->prepare("DELETE FROM usuario_modulo_permiso WHERE id_usuario = :uid");
            $stmtDel->execute([':uid' => $userId]);
            if (!empty($permisos)) {
                $stmtIns = $this->db()->prepare("INSERT INTO usuario_modulo_permiso (id_usuario, id_modulo, id_permiso) VALUES (:uid, :mid, :pid)");
                foreach ($permisos as $p) {
                    $stmtIns->execute([':uid' => $userId, ':mid' => (int)$p['id_modulo'], ':pid' => (int)$p['id_permiso']]);
                }
            }
            $this->db()->commit();
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log("Error al guardar permisos de usuario: " . $e->getMessage());
        }
    }

    public function getRolePermissions(int $rolId, ?int $userId = null): array
    {
        try {
            // Admin tiene bypass, devolver array vacío (Auth::isAdmin() maneja el acceso)
            if ($rolId === 1) {
                return [];
            }

            // Solo usar permisos individuales del usuario (el rol es solo una etiqueta)
            if ($userId !== null) {
                $stmt = $this->db()->prepare("
                    SELECT CONCAT(m.nombre_modulo, ':', p.nombre_permiso) AS permiso
                    FROM usuario_modulo_permiso ump
                    JOIN modulos m ON ump.id_modulo = m.id_modulo
                    JOIN permisos p ON ump.id_permiso = p.id_permiso
                    WHERE ump.id_usuario = :uid
                ");
                $stmt->execute([':uid' => $userId]);
                return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permiso');
            }

            return [];
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
