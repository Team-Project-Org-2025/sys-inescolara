<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Notification;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class NotificationsController
{
    use ResponseTrait, PermissionTrait;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function get_unread(): void
    {
        header('Content-Type: application/json');
        try {
            $notifModel = new Notification();
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                echo json_encode(['success' => false, 'count' => 0]);
                exit();
            }
            $count = $notifModel->getUnreadCount($userId);
            $recent = $notifModel->getRecent($userId, 5);
            echo json_encode(['success' => true, 'count' => $count, 'notifications' => $recent]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
        }
        exit();
    }

    public function mark_read(): void
    {
        header('Content-Type: application/json');
        try {
            $id = (int)($_POST['id'] ?? 0);
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$id || !$userId) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                exit();
            }
            $notifModel = new Notification();
            $ok = $notifModel->markAsRead($id, $userId);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function mark_all_read(): void
    {
        header('Content-Type: application/json');
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                echo json_encode(['success' => false]);
                exit();
            }
            $notifModel = new Notification();
            $ok = $notifModel->markAllAsRead($userId);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function delete(): void
    {
        header('Content-Type: application/json');
        try {
            $id = (int)($_POST['id'] ?? 0);
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$id || !$userId) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                exit();
            }
            $notifModel = new Notification();
            $ok = $notifModel->delete($id, $userId);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function create(): void
    {
        header('Content-Type: application/json');
        try {
            $userId = (int)($_POST['user_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $mensaje = trim($_POST['mensaje'] ?? '');
            $tipo = trim($_POST['tipo'] ?? 'info');
            $link = trim($_POST['link'] ?? '');

            if (!$userId || empty($titulo)) {
                echo json_encode(['success' => false, 'message' => 'user_id y titulo son requeridos']);
                exit();
            }

            $notifModel = new Notification();
            $ok = $notifModel->create($userId, $titulo, $mensaje, $tipo, $link ?: null);

            echo json_encode(['success' => $ok, 'message' => $ok ? 'Notificación creada' : 'Error al crear']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}
