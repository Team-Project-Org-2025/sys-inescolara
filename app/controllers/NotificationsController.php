<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Notification;

require_once __DIR__ . '/LoginController.php';
checkAuth();

function index()
{
    require_once __DIR__ . '/DashboardController.php';
    dashboardCheckPermiso('DASHBOARD_VIEW');

    $notifModel = new Notification();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $result = $notifModel->getAll($userId, $page);

    $notifications = $result['data'];
    $total = $result['total'];
    $perPage = $result['perPage'];
    $totalPages = max(1, (int)ceil($total / $perPage));

    $view = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'notifications.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de notificaciones no encontrada.';
        return;
    }

    require $view;
}

function get_unread()
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

function mark_read()
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

function mark_all_read()
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

function delete()
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

function create()
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
