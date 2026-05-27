<?php

namespace SysInescolara\controllers;

use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class AuditLogController
{
    use ResponseTrait, PermissionTrait;

    private AuditLog $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new AuditLog();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/auditlog.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de bitácora no encontrada.';
            return;
        }
        require $view;
    }

    public function get_auditlogs(): void
    {
        $this->checkModuleAuth();
        $this->getAuditLogsAjax();
    }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_auditlogs' => $this->getAuditLogsAjax(),
                default             => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function getAuditLogsAjax(): void
    {
        $logs = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'auditlogs' => $logs, 'count' => count($logs)]);
    }
}
