<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use PDO;

class AuditLog extends Database implements ReadableInterface
{
    public function __construct()
    {
        parent::__construct('security');
    }

    public function log(int $userId, string $action, string $table, ?int $recordId = null, mixed $oldValue = null, mixed $newValue = null): bool
    {
        try {
            date_default_timezone_set('America/Caracas');
            $fecha = date('Y-m-d H:i:s');

            $stmt = $this->db()->prepare("
                INSERT INTO auditoria_logs 
                    (id_usuario, accion, tabla_afectada, id_registro_afectado, valor_anterior, valor_nuevo, endpoint_solicitado, fecha_accion)
                VALUES 
                    (:id_usuario, :accion, :tabla, :id_registro, :valor_anterior, :valor_nuevo, :endpoint, :fecha_accion)
            ");

            return $stmt->execute([
                ':id_usuario'       => $userId,
                ':accion'           => $action,
                ':tabla'            => $table,
                ':id_registro'      => $recordId,
                ':valor_anterior'   => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
                ':valor_nuevo'      => $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
                ':endpoint'         => $_SERVER['REQUEST_URI'] ?? null,
                ':fecha_accion'     => $fecha,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al registrar en auditoría: ' . $e->getMessage());
            return false;
        }
    }

    public function getAll(array $params = []): array
    {
        try {
            $where = [];
            $bind = [];

            if (!empty($params['fecha_desde'])) {
                $where[] = "al.fecha_accion >= :fecha_desde";
                $bind[':fecha_desde'] = $params['fecha_desde'] . ' 00:00:00';
            }
            if (!empty($params['fecha_hasta'])) {
                $where[] = "al.fecha_accion <= :fecha_hasta";
                $bind[':fecha_hasta'] = $params['fecha_hasta'] . ' 23:59:59';
            }
            if (!empty($params['id_usuario'])) {
                $where[] = "al.id_usuario = :id_usuario";
                $bind[':id_usuario'] = (int)$params['id_usuario'];
            }
            if (!empty($params['accion'])) {
                $where[] = "al.accion = :accion";
                $bind[':accion'] = $params['accion'];
            }
            if (!empty($params['tabla_afectada'])) {
                $where[] = "al.tabla_afectada = :tabla_afectada";
                $bind[':tabla_afectada'] = $params['tabla_afectada'];
            }
            if (!empty($params['search'])) {
                $where[] = "(u.nombre_usuario LIKE :search OR al.tabla_afectada LIKE :search OR al.accion LIKE :search)";
                $bind[':search'] = '%' . $params['search'] . '%';
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $limit = '';
            $length = (int)($params['length'] ?? 0);
            if ($length === -1) {
                // Sin límite (exportación CSV)
            } elseif ($length > 0) {
                $limit = 'LIMIT ' . $length;
                if (!empty($params['start'])) {
                    $limit .= ' OFFSET ' . (int)$params['start'];
                }
            } else {
                $limit = 'LIMIT 1000';
            }

            $orderBy = 'ORDER BY al.fecha_accion DESC';
            if (!empty($params['order_column']) && !empty($params['order_dir'])) {
                $columns = ['fecha_accion', 'nombre_usuario', 'accion', 'tabla_afectada', 'id_log'];
                $colIdx = (int)$params['order_column'];
                $dir = strtoupper($params['order_dir']) === 'ASC' ? 'ASC' : 'DESC';
                if (isset($columns[$colIdx])) {
                    $orderBy = "ORDER BY al.{$columns[$colIdx]} {$dir}";
                }
            }

            $sql = "
                SELECT 
                    al.*,
                    u.nombre_usuario
                FROM auditoria_logs al
                LEFT JOIN usuarios u ON al.id_usuario = u.id_usuario
                {$whereSql}
                {$orderBy}
                {$limit}
            ";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($bind);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener auditoría: ' . $e->getMessage());
            return [];
        }
    }

    public function countFiltered(array $params = []): int
    {
        try {
            $where = [];
            $bind = [];

            if (!empty($params['fecha_desde'])) {
                $where[] = "al.fecha_accion >= :fecha_desde";
                $bind[':fecha_desde'] = $params['fecha_desde'] . ' 00:00:00';
            }
            if (!empty($params['fecha_hasta'])) {
                $where[] = "al.fecha_accion <= :fecha_hasta";
                $bind[':fecha_hasta'] = $params['fecha_hasta'] . ' 23:59:59';
            }
            if (!empty($params['id_usuario'])) {
                $where[] = "al.id_usuario = :id_usuario";
                $bind[':id_usuario'] = (int)$params['id_usuario'];
            }
            if (!empty($params['accion'])) {
                $where[] = "al.accion = :accion";
                $bind[':accion'] = $params['accion'];
            }
            if (!empty($params['tabla_afectada'])) {
                $where[] = "al.tabla_afectada = :tabla_afectada";
                $bind[':tabla_afectada'] = $params['tabla_afectada'];
            }
            if (!empty($params['search'])) {
                $where[] = "(u.nombre_usuario LIKE :search OR al.tabla_afectada LIKE :search OR al.accion LIKE :search)";
                $bind[':search'] = '%' . $params['search'] . '%';
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT COUNT(*)
                FROM auditoria_logs al
                LEFT JOIN usuarios u ON al.id_usuario = u.id_usuario
                {$whereSql}
            ";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($bind);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Error al contar auditoría filtrada: ' . $e->getMessage());
            return 0;
        }
    }

    public function countAll(): int
    {
        try {
            $stmt = $this->db()->query("SELECT COUNT(*) FROM auditoria_logs");
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Error al contar auditoría total: ' . $e->getMessage());
            return 0;
        }
    }

    public function getUsers(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT DISTINCT u.id_usuario, u.nombre_usuario
                FROM auditoria_logs al
                JOIN usuarios u ON al.id_usuario = u.id_usuario
                ORDER BY u.nombre_usuario ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener usuarios de auditoría: ' . $e->getMessage());
            return [];
        }
    }

    public function getActions(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT DISTINCT accion FROM auditoria_logs ORDER BY accion ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener acciones de auditoría: ' . $e->getMessage());
            return [];
        }
    }

    public function getTables(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT DISTINCT tabla_afectada FROM auditoria_logs ORDER BY tabla_afectada ASC
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener tablas de auditoría: ' . $e->getMessage());
            return [];
        }
    }

    public static function record(string $action, string $table, ?int $recordId = null, mixed $oldValue = null, mixed $newValue = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!\SysInescolara\helpers\Auth::check()) {
            return;
        }
        try {
            $log = new self();
            $log->log(\SysInescolara\helpers\Auth::id(), $action, $table, $recordId, $oldValue, $newValue);
        } catch (\Throwable $e) {
            error_log('Audit record error: ' . $e->getMessage());
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT 
                    al.*,
                    u.nombre_usuario
                FROM auditoria_logs al
                LEFT JOIN usuarios u ON al.id_usuario = u.id_usuario
                WHERE al.id_log = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener log: ' . $e->getMessage());
            return null;
        }
    }
}
