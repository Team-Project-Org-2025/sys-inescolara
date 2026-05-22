<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\DashboardData;

require_once __DIR__ . '/LoginController.php';

checkAuth();

handleRequest();

function handleRequest(): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_report' => fn() => handleGetReport(),
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
            exit();
        }
    } catch (\Throwable $e) {
        jsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

function handleGetReport(): void
{
    $reportType = $_GET['type'] ?? '';
    $validTypes = ['plants_by_species', 'lots_by_status', 'inventory_summary', 'supply_stock', 'recent_sales'];

    if (!in_array($reportType, $validTypes, true)) {
        jsonResponse(['success' => false, 'message' => 'Tipo de reporte inválido'], 400);
        return;
    }

    $dashboardData = new DashboardData();
    $data = $dashboardData->getReportData($reportType);

    $labels = [
        'plants_by_species' => ['Especie', 'Total Plantas'],
        'lots_by_status' => ['Estado', 'Total Lotes', 'Total Plantas'],
        'inventory_summary' => ['Nivel de Stock', 'Total Lotes', 'Total Plantas'],
        'supply_stock' => ['ID', 'Insumo', 'Stock Actual', 'Unidad', 'Costo Unit.'],
        'recent_sales' => ['ID Venta', 'Cliente', 'Monto Total', 'Fecha'],
    ];

    jsonResponse([
        'success' => true,
        'data' => $data,
        'labels' => $labels[$reportType] ?? [],
        'type' => $reportType,
    ]);
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
