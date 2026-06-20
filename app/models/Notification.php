<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Notification extends Database
{
    public function __construct()
    {
        parent::__construct('security');
    }

    public function getUnreadCount(int $userId): int
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :id AND leida = 0");
            $stmt->execute([':id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Error al contar notificaciones: ' . $e->getMessage());
            return 0;
        }
    }

    public function getRecent(int $userId, int $limit = 8): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT id_notificacion, titulo, mensaje, tipo, leida, link, fecha_creacion
                FROM notificaciones
                WHERE id_usuario = :id AND leida = 0
                ORDER BY fecha_creacion DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('Error al obtener notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    public function getAll(int $userId, int $page = 1, int $perPage = 20): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $stmt = $this->db()->prepare("
                SELECT SQL_CALC_FOUND_ROWS id_notificacion, titulo, mensaje, tipo, leida, link, fecha_creacion
                FROM notificaciones
                WHERE id_usuario = :id
                ORDER BY fecha_creacion DESC
                LIMIT :offset, :perPage
            ");
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $total = (int) $this->db()->query("SELECT FOUND_ROWS()")->fetchColumn();
            return ['data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
        } catch (\Throwable $e) {
            error_log('Error al obtener todas las notificaciones: ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage];
        }
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = :id AND id_usuario = :uid");
            return $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
        } catch (\Throwable $e) {
            error_log('Error al marcar notificación como leída: ' . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead(int $userId): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = :uid AND leida = 0");
            return $stmt->execute([':uid' => $userId]);
        } catch (\Throwable $e) {
            error_log('Error al marcar todas como leídas: ' . $e->getMessage());
            return false;
        }
    }

    public function create(int $userId, string $titulo, ?string $mensaje = null, string $tipo = 'info', ?string $link = null): bool
    {
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, link)
                VALUES (:uid, :titulo, :mensaje, :tipo, :link)
            ");
            return $stmt->execute([
                ':uid' => $userId,
                ':titulo' => $titulo,
                ':mensaje' => $mensaje,
                ':tipo' => $tipo,
                ':link' => $link,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al crear notificación: ' . $e->getMessage());
            return false;
        }
    }

    public function existsByTitle(int $userId, string $titulo): bool
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT COUNT(*) FROM notificaciones
                WHERE id_usuario = :uid AND titulo = :titulo AND leida = 0
            ");
            $stmt->execute([':uid' => $userId, ':titulo' => $titulo]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            error_log('Error al verificar notificación: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->db()->prepare("DELETE FROM notificaciones WHERE id_notificacion = :id AND id_usuario = :uid");
            return $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
        } catch (\Throwable $e) {
            error_log('Error al eliminar notificación: ' . $e->getMessage());
            return false;
        }
    }
}
