<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Permiso extends Database
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];
    private const ROL_ADMIN = 1;

    private static bool $bootstrapped = false;

    public function __construct()
    {
        parent::__construct('security');
        if (!self::$bootstrapped) {
            $this->bootstrapDefaults();
            self::$bootstrapped = true;
        }
    }

    private function bootstrapDefaults(): void
    {
        try {
            // Asegurar las 4 acciones canónicas
            foreach (self::ACCIONES as $accion) {
                $stmt = $this->db()->prepare("SELECT COUNT(*) FROM permisos WHERE nombre_permiso = :nom");
                $stmt->execute([':nom' => $accion]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $ins = $this->db()->prepare("INSERT INTO permisos (nombre_permiso) VALUES (:nom)");
                    $ins->execute([':nom' => $accion]);
                }
            }

            // Registrar el módulo 'permisos' si no existe
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM modulos WHERE nombre_modulo = 'permisos'");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 0) {
                $this->db()->exec("INSERT INTO modulos (nombre_modulo, descripcion_modulo) VALUES ('permisos', 'Gestión de permisos por rol')");
            }

            // Asignar al rol Administrador todos los permisos del módulo 'permisos'
            $stmt = $this->db()->query("SELECT id_modulo FROM modulos WHERE nombre_modulo = 'permisos'");
            $idModulo = $stmt->fetchColumn();
            if ($idModulo) {
                foreach ($this->getAcciones() as $accion) {
                    $ins = $this->db()->prepare("INSERT IGNORE INTO rol_modulo_permiso (id_rol, id_modulo, id_permiso) VALUES (:rol, :mod, :perm)");
                    $ins->execute([':rol' => self::ROL_ADMIN, ':mod' => $idModulo, ':perm' => $accion['id_permiso']]);
                }
            }
        } catch (\Throwable $e) {
            error_log('Bootstrap de permisos falló: ' . $e->getMessage());
        }
    }

    public function getModulos(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_modulo, nombre_modulo, descripcion_modulo FROM modulos ORDER BY nombre_modulo ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener módulos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Acciones canónicas (ver, crear, editar, eliminar).
     * Tolerante a filas duplicadas: agrupa por nombre y usa el menor id como canónico.
     */
    public function getAcciones(): array
    {
        try {
            $placeholders = implode(',', array_fill(0, count(self::ACCIONES), '?'));
            $stmt = $this->db()->prepare("
                SELECT MIN(id_permiso) AS id_permiso, nombre_permiso
                FROM permisos
                WHERE nombre_permiso IN ($placeholders)
                GROUP BY nombre_permiso
                ORDER BY MIN(id_permiso) ASC
            ");
            $stmt->execute(self::ACCIONES);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error al obtener acciones: ' . $e->getMessage());
            return [];
        }
    }

    public function getRoles(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT r.id_rol AS id, r.nombre_rol, r.descripcion_rol,
                       (SELECT COUNT(*) FROM rol_modulo_permiso rmp WHERE rmp.id_rol = r.id_rol) AS permisos_asignados
                FROM roles r
                ORDER BY r.id_rol ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener roles con permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pares [id_modulo, id_permiso] asignados al rol, mapeados a los ids canónicos
     * de cada acción (tolera referencias a ids duplicados de la tabla permisos).
     */
    public function getPermisosRol(int $rolId): array
    {
        try {
            $placeholders = implode(',', array_fill(0, count(self::ACCIONES), '?'));
            $stmt = $this->db()->prepare("
                SELECT DISTINCT rmp.id_modulo, canon.id_permiso
                FROM rol_modulo_permiso rmp
                JOIN permisos p ON rmp.id_permiso = p.id_permiso
                JOIN (
                    SELECT MIN(id_permiso) AS id_permiso, nombre_permiso
                    FROM permisos
                    WHERE nombre_permiso IN ($placeholders)
                    GROUP BY nombre_permiso
                ) canon ON canon.nombre_permiso = p.nombre_permiso
                WHERE rmp.id_rol = ?
            ");
            $stmt->execute(array_merge(self::ACCIONES, [$rolId]));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error al obtener permisos del rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Reemplazo transaccional de los permisos del rol usando solo ids canónicos.
     * Cada guardado autocorrige referencias a ids duplicados de ese rol.
     *
     * @param array $permisos Lista de ['id_modulo' => int, 'id_permiso' => int]
     */
    public function setPermisosRol(int $rolId, array $permisos): void
    {
        if ($rolId === self::ROL_ADMIN) {
            throw new \Exception('No se pueden modificar los permisos del rol Administrador.');
        }

        $modulosValidos = array_map('intval', array_column($this->getModulos(), 'id_modulo'));
        $accionesValidas = array_map('intval', array_column($this->getAcciones(), 'id_permiso'));

        $limpios = [];
        foreach ($permisos as $p) {
            $idModulo = (int)($p['id_modulo'] ?? 0);
            $idPermiso = (int)($p['id_permiso'] ?? 0);
            if (in_array($idModulo, $modulosValidos, true) && in_array($idPermiso, $accionesValidas, true)) {
                $limpios[$idModulo . ':' . $idPermiso] = ['id_modulo' => $idModulo, 'id_permiso' => $idPermiso];
            }
        }
        $limpios = array_values($limpios);

        try {
            $anteriores = $this->getPermisosRol($rolId);

            $this->beginTransaction();
            $stmtDel = $this->db()->prepare("DELETE FROM rol_modulo_permiso WHERE id_rol = :rol");
            $stmtDel->execute([':rol' => $rolId]);
            if (!empty($limpios)) {
                $stmtIns = $this->db()->prepare("INSERT INTO rol_modulo_permiso (id_rol, id_modulo, id_permiso) VALUES (:rol, :mod, :perm)");
                foreach ($limpios as $p) {
                    $stmtIns->execute([':rol' => $rolId, ':mod' => $p['id_modulo'], ':perm' => $p['id_permiso']]);
                }
            }
            $this->commit();

            AuditLog::record('UPDATE', 'rol_modulo_permiso', $rolId, $anteriores, $limpios);
        } catch (\Throwable $e) {
            $this->rollback();
            error_log('Error al guardar permisos del rol: ' . $e->getMessage());
            throw new \Exception('No se pudieron guardar los permisos del rol.');
        }
    }
}
