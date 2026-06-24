<?php

function jsonResponse(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function handleError(\Exception $e, bool $isAjax): void
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}

function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function checkModuleAuth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!\SysInescolara\helpers\Auth::check()) {
        if (isAjaxRequest()) {
            jsonResponse([
                'success' => false,
                'message' => 'No autorizado',
                'redirect' => BASE_URL . 'login',
            ], 401);
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

function checkPermisoOrFail(string $codigo): void
{
    if (\SysInescolara\helpers\Auth::isAdmin()) return;
    if (!\SysInescolara\helpers\Auth::hasPermiso($codigo)) {
        jsonResponse(['success' => false, 'message' => 'No tienes permiso para realizar esta acción.'], 403);
    }
}

function checkModuleAccess(string $modulo, string $accion): void
{
    if (\SysInescolara\helpers\Auth::isAdmin()) return;
    if (!\SysInescolara\helpers\Auth::hasModuleAccess($modulo, $accion)) {
        jsonResponse(['success' => false, 'message' => 'No tienes permiso para realizar esta acción.'], 403);
    }
}

function checkCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (isAjaxRequest()) return;
    $data = getRequestData();
    $token = $data['_csrf_token'] ?? '';
    \SysInescolara\helpers\Csrf::validate($token);
}

function getRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return $_GET;
    }

    return $_POST;
}

