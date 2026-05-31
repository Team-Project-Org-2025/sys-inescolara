<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\DashboardData;

function index(): void
{
    reports_checkAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_report' => reports_handleGetReport(),
                default          => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/reports.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de reportes no encontrada.';
        return;
    }
    require $view;
}

function get_report(): void
{
    reports_checkAuth();
    reports_handleGetReport();
}

function reports_handleGetReport(): void
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

function reports_checkAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}
