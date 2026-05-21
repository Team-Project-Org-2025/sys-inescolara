<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auditlogCheckAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado', 'redirect' => BASE_URL . 'login']);
            exit();
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

$GLOBALS['auditLogModel'] = new AuditLog();

function index(): void
{
    $auditLogModel = $GLOBALS['auditLogModel'] ?? new AuditLog();
    auditlogCheckAuth();
    handleRequest($auditLogModel);

    $view = ROOT_PATH . 'app/views/dashboard/auditlog.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de bitácora no encontrada.';
        return;
    }

    require $view;
}

function get_auditlogs(): void
{
    $auditLogModel = $GLOBALS['auditLogModel'] ?? new AuditLog();
    auditlogCheckAuth();
    getAuditLogsAjax($auditLogModel);
}

function handleRequest(AuditLog $auditLogModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_auditlogs' => fn() => getAuditLogsAjax($auditLogModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
            }

            jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function handleError(Exception $e, bool $isAjax): void
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}

function getAuditLogsAjax(AuditLog $auditLogModel): void
{
    $logs = $auditLogModel->getAll();
    jsonResponse([
        'success' => true,
        'auditlogs' => $logs,
        'count' => count($logs),
    ]);
}
