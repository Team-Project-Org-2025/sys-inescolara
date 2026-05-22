<?php

declare(strict_types=1);

/**
 * Controlador del Dashboard
 */

function dashboardCheckAuth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    // Si el usuario está autenticado pero no tiene permisos en sesión (o están vacíos), recargarlos
    if (empty($_SESSION['user_permisos'])) {
        require_once ROOT_PATH . 'vendor/autoload.php';
        $userModel = new \SysInescolara\models\User();
        $_SESSION['user_permisos'] = $userModel->getRolePermissions((int)($_SESSION['user_rol_id'] ?? 0));
    }
}

function dashboardCheckPermiso(string $codigo): void
{
    dashboardCheckAuth();
    $permisos = $_SESSION['user_permisos'] ?? [];
    if (!in_array($codigo, $permisos, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title>';
        echo '<link rel="icon" type="image/x-icon" href="' . BASE_URL . 'public/assets/images/favicon.ico">';
        echo '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}.card{text-align:center;padding:3rem;background:#fff;border-radius:1rem;box-shadow:0 4px 24px rgba(0,0,0,.08)}h1{font-size:4rem;margin:0;color:#1b5e20}p{color:#666;margin:1.5rem 0}a{display:inline-block;padding:.75rem 2rem;background:#1b5e20;color:#fff;text-decoration:none;border-radius:.5rem}</style>';
        echo '</head><body><div class="card"><h1>403</h1><p>No tienes permisos para acceder a este módulo.</p>';
        echo '<a href="' . BASE_URL . 'dashboard">Volver al inicio</a></div></body></html>';
        exit();
    }
}

function index(): void
{
    dashboardCheckPermiso('DASHBOARD_VIEW');

    require_once ROOT_PATH . 'vendor/autoload.php';

    $dashboardData = new \SysInescolara\models\DashboardData();

    $stats = $dashboardData->getStats();
    $recentActivity = $dashboardData->getRecentActivity(10);
    $lowStockLots = $dashboardData->getLowStockLots(20);
    $lowStockSupplies = $dashboardData->getLowStockSupplies(10);

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'index.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de dashboard (index) no encontrada.';
        return;
    }

    require $view;
}

function asistente(): void
{
    dashboardCheckPermiso('ASISTENTE_ACCESS');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'asistente.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de asistente no encontrada.';
        return;
    }

    require $view;
}

function inventario(): void
{
    dashboardCheckPermiso('INVENTARIO_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'inventario.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inventario no encontrada.';
        return;
    }

    require $view;
}

function ventas(): void
{
    dashboardCheckPermiso('VENTAS_ACCESS');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'ventas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ventas no encontrada.';
        return;
    }

    require $view;
}

function usuarios(): void
{
    dashboardCheckPermiso('USUARIOS_MANAGE');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $userModel = new \SysInescolara\models\User();
    $roles = $userModel->getRoles();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'usuarios.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de usuarios no encontrada.';
        return;
    }

    require $view;
}

function plants(): void
{
    dashboardCheckPermiso('PLANTAS_VIEW');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $speciesModel = new \SysInescolara\models\Species();
    $species = $speciesModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'plants.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }

    require $view;
}

function batches(): void
{
    dashboardCheckPermiso('PLANTAS_VIEW');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $plantModel = new \SysInescolara\models\Plant();
    $plants = $plantModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'batches.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de lotes no encontrada.';
        return;
    }

    require $view;
}

function suppliers(): void
{
    dashboardCheckPermiso('PROVEEDORES_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'suppliers.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de proveedores no encontrada.';
        return;
    }

    require $view;
}

function supplies(): void
{
    dashboardCheckPermiso('INSUMOS_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'supplies.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de suministros no encontrada.';
        return;
    }

    require $view;
}

function tasks(): void
{
    dashboardCheckPermiso('TAREAS_VIEW');
    $view = ROOT_PATH . 'app/views/dashboard/task.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }
    require $view;
}

function employees(): void
{
    dashboardCheckPermiso('TRABAJADORES_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'employees.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }

    require $view;
}

function clients(): void
{
    dashboardCheckPermiso('CLIENTES_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'clients.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de clientes no encontrada.';
        return;
    }

    require $view;
}

function species(): void
{
    dashboardCheckPermiso('PLANTAS_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'species.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de especies no encontrada.';
        return;
    }

    require $view;
}

function auditlog(): void
{
    dashboardCheckPermiso('USUARIOS_MANAGE');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'auditlog.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de bitácora no encontrada.';
        return;
    }

    require $view;
}

function reports(): void
{
    dashboardCheckPermiso('DASHBOARD_VIEW');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'reports.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de reportes no encontrada.';
        return;
    }

    require $view;
}

function backups(): void
{
    dashboardCheckPermiso('USUARIOS_MANAGE');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'backups.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de respaldos no encontrada.';
        return;
    }

    require $view;
}