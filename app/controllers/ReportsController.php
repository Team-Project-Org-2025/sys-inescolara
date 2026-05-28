<?php

namespace SysInescolara\controllers;

use SysInescolara\models\DashboardData;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class ReportsController
{
    use ResponseTrait, PermissionTrait;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function index(): void
    {
        $this->checkAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/reports.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de reportes no encontrada.';
            return;
        }
        require $view;
    }

    public function get_report(): void
    {
        $this->checkAuth();
        $this->handleGetReport();
    }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_report' => $this->handleGetReport(),
                default          => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    private function handleGetReport(): void
    {
        $reportType = $_GET['type'] ?? '';
        $validTypes = ['plants_by_species', 'lots_by_status', 'inventory_summary', 'supply_stock', 'recent_sales'];

        if (!in_array($reportType, $validTypes, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Tipo de reporte inválido'], 400);
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

        $this->jsonResponse([
            'success' => true,
            'data' => $data,
            'labels' => $labels[$reportType] ?? [],
            'type' => $reportType,
        ]);
    }

    private function checkAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }
    }
}
