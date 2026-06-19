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

    public function getAll(): array
    {
        try {
            $stmt = $this->db()->query("
                SELECT 
                    al.*,
                    u.nombre_usuario
                FROM auditoria_logs al
                LEFT JOIN usuarios u ON al.id_usuario = u.id_usuario
                ORDER BY al.fecha_accion DESC
                LIMIT 1000
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener auditoría: ' . $e->getMessage());
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
