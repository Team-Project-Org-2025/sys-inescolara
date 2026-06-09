<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Reports;
use SysInescolara\helpers\PdfHelper;

function index(): void
{
    reports_checkAuth();
    $action = $_GET['action'] ?? '';

    if ($action === 'generate_pdf') {
        reports_handleGeneratePdf();
        return;
    }

    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_modules'     => reports_handleGetModules(),
                'GET_get_filters'     => reports_handleGetFilters(),
                'GET_get_report_data' => reports_handleGetReportData(),
                default               => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Throwable $e) {
            jsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/reports.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de reportes no existe.';
        return;
    }
    require $view;
}

function get_modules(): void
{
    reports_checkAuth();
    reports_handleGetModules();
}

function get_filters(): void
{
    reports_checkAuth();
    reports_handleGetFilters();
}

function get_report_data(): void
{
    reports_checkAuth();
    reports_handleGetReportData();
}

function generate_pdf(): void
{
    reports_checkAuth();
    reports_handleGeneratePdf();
}

function reports_handleGetModules(): void
{
    $reports = new Reports();
    jsonResponse([
        'success' => true,
        'modules' => $reports->getModules(),
    ]);
}

function reports_handleGetFilters(): void
{
    $module = $_GET['module'] ?? '';
    if (empty($module)) {
        jsonResponse(['success' => false, 'message' => 'Módulo no especificado'], 400);
        return;
    }

    $reports = new Reports();
    $filters = $reports->getModuleFilters($module);

    jsonResponse([
        'success' => true,
        'filters' => $filters,
    ]);
}

function reports_handleGetReportData(): void
{
    $module = $_GET['module'] ?? '';
    if (empty($module)) {
        jsonResponse(['success' => false, 'message' => 'Módulo no especificado'], 400);
        return;
    }

    $filters = extractReportFilters($_GET);

    $reports = new Reports();
    $data = $reports->getReportData($module, $filters);

    $keys = !empty($data['rows']) ? array_keys($data['rows'][0]) : [];

    jsonResponse([
        'success' => true,
        'columns' => $data['columns'],
        'keys' => $keys,
        'rows' => $data['rows'],
        'chart' => $data['chart'],
        'module' => $module,
    ]);
}

function reports_handleGeneratePdf(): void
{
    $module = $_GET['module'] ?? '';
    if (empty($module)) {
        http_response_code(400);
        echo 'Módulo no especificado';
        exit();
    }

    $reports = new Reports();
    $modules = $reports->getModules();
    $moduleNames = [];
    foreach ($modules as $m) {
        $moduleNames[$m['id']] = $m['nombre'];
    }
    $moduleName = $moduleNames[$module] ?? $module;

    $filters = extractReportFilters($_GET);
    $data = $reports->getReportData($module, $filters);

    $filterLabels = [];
    foreach ($filters as $k => $v) {
        if ($v !== '' && $v !== null && !str_starts_with($k, '_')) {
            $filterLabels[] = htmlspecialchars($k) . ': ' . htmlspecialchars((string)$v);
        }
    }

    $fechaGeneracion = date('d/m/Y h:i A');
    $usuario = $_SESSION['nombre_usuario'] ?? 'Usuario';

    ob_start();
    require ROOT_PATH . 'app/views/dashboard/reports_pdf.php';
    $html = ob_get_clean();

    try {
        $pdf = new PdfHelper();
        $output = $pdf->fromHtml($html);
        $filename = 'reporte-' . $module . '-' . date('Ymd') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit();
    } catch (\Exception $e) {
        error_log('Error al generar PDF reporte: ' . $e->getMessage());
        http_response_code(500);
        echo 'Error al generar el PDF.';
        exit();
    }
}

function reports_checkAuth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

function extractReportFilters(array $params): array
{
    $filters = [];
    foreach ($params as $key => $value) {
        if ($key === 'module' || $key === 'action' || $key === '_' || $key === 'PHPSESSID') {
            continue;
        }
        $filters[$key] = $value;
    }

    if (isset($_GET['fecha_desde']) && !isset($filters['fecha_venta_desde'])) {
        $filters['fecha_venta_desde'] = $_GET['fecha_desde'];
    }
    if (isset($_GET['fecha_hasta']) && !isset($filters['fecha_venta_hasta'])) {
        $filters['fecha_venta_hasta'] = $_GET['fecha_hasta'];
    }

    return $filters;
}
