<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_auditlogs' => auditlog_getAuditLogsAjax(),
                default             => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/auditlog.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de bitácora no encontrada.';
        return;
    }
    require $view;
}

function get_auditlogs(): void { checkModuleAuth(); checkPermisoOrFail('AUDIT_VIEW'); auditlog_getAuditLogsAjax(); }

function auditlog_getAuditLogsAjax(): void
{
    $model = new AuditLog();
    $logs = $model->getAll();
    jsonResponse(['success' => true, 'auditlogs' => $logs, 'count' => count($logs)]);
}
