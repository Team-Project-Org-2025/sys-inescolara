<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Notification;

function get_unread(): void
{
    try {
        $notifModel = new Notification();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            jsonResponse(['success' => false, 'count' => 0]);
        }
        $count = $notifModel->getUnreadCount($userId);
        $recent = $notifModel->getRecent($userId, 5);
        jsonResponse(['success' => true, 'count' => $count, 'notifications' => $recent]);
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
    }
}

function mark_read(): void
{
    try {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$id || !$userId) {
            jsonResponse(['success' => false, 'message' => 'ID inválido']);
        }
        $notifModel = new Notification();
        $ok = $notifModel->markAsRead($id, $userId);
        jsonResponse(['success' => $ok]);
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

function mark_all_read(): void
{
    try {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            jsonResponse(['success' => false]);
        }
        $notifModel = new Notification();
        $ok = $notifModel->markAllAsRead($userId);
        jsonResponse(['success' => $ok]);
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

function delete_notification(): void
{
    try {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$id || !$userId) {
            jsonResponse(['success' => false, 'message' => 'ID inválido']);
        }
        $notifModel = new Notification();
        $ok = $notifModel->delete($id, $userId);
        jsonResponse(['success' => $ok]);
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

function create_notification(): void
{
    try {
        $userId = (int)($_POST['user_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'info');
        $link = trim($_POST['link'] ?? '');

        if (!$userId || empty($titulo)) {
            jsonResponse(['success' => false, 'message' => 'user_id y titulo son requeridos']);
        }

        $notifModel = new Notification();
        $ok = $notifModel->create($userId, $titulo, $mensaje, $tipo, $link ?: null);

        jsonResponse(['success' => $ok, 'message' => $ok ? 'Notificación creada' : 'Error al crear']);
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}
