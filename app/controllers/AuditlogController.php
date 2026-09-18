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
                'GET_get_auditlogs'      => get_auditlogs(),
                'GET_get_auditlogs_data' => get_auditlogs_data(),
                'GET_export_csv'         => export_csv(),
                default                  => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    require_once ROOT_PATH . 'vendor/autoload.php';
    $model = new AuditLog();
    $users = $model->getUsers();
    $actions = $model->getActions();
    $tables = $model->getTables();

    $view = ROOT_PATH . 'app/views/dashboard/auditlog.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de bitácora no encontrada.';
        return;
    }
    require $view;
}

function get_auditlogs(): void { checkModuleAuth(); checkPermisoOrFail('auditlog:ver'); auditlog_getAuditLogsAjax(); }
function get_auditlogs_data(): void { checkModuleAuth(); checkPermisoOrFail('auditlog:ver'); auditlog_getAuditLogsDataAjax(); }
function export_csv(): void { checkModuleAuth(); checkPermisoOrFail('auditlog:ver'); auditlog_exportCsv(); }

function auditlog_getAuditLogsAjax(): void
{
    $model = new AuditLog();
    $logs = $model->getAll();
    jsonResponse(['success' => true, 'auditlogs' => $logs, 'count' => count($logs)]);
}

function auditlog_getAuditLogsDataAjax(): void
{
    $params = [
        'start'         => (int)($_GET['start'] ?? 0),
        'length'        => (int)($_GET['length'] ?? 25),
        'search'        => $_GET['search']['value'] ?? '',
        'order_column'  => (int)($_GET['order'][0]['column'] ?? 0),
        'order_dir'     => $_GET['order'][0]['dir'] ?? 'desc',
        'fecha_desde'   => $_GET['fecha_desde'] ?? '',
        'fecha_hasta'   => $_GET['fecha_hasta'] ?? '',
        'id_usuario'    => $_GET['id_usuario'] ?? '',
        'accion'        => $_GET['accion'] ?? '',
        'tabla_afectada'=> $_GET['tabla_afectada'] ?? '',
    ];

    $model = new AuditLog();
    $data = $model->getAll($params);
    $recordsFiltered = $model->countFiltered($params);
    $recordsTotal = $model->countAll();

    jsonResponse([
        'draw'            => (int)($_GET['draw'] ?? 1),
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data,
    ]);
}

function auditlog_exportCsv(): void
{
    $params = [
        'fecha_desde'   => $_GET['fecha_desde'] ?? '',
        'fecha_hasta'   => $_GET['fecha_hasta'] ?? '',
        'id_usuario'    => $_GET['id_usuario'] ?? '',
        'accion'        => $_GET['accion'] ?? '',
        'tabla_afectada'=> $_GET['tabla_afectada'] ?? '',
        'search'        => $_GET['search'] ?? '',
        'length'        => -1, // sin límite para exportar todo
    ];

    $model = new AuditLog();
    $data = $model->getAll($params);

    $filename = 'bitacora_' . date('Y-m-d_H-i-s') . '.csv';

    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM para UTF-8 en Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados
    fputcsv($output, ['Fecha', 'Usuario', 'Acción', 'Tabla', 'ID Registro', 'Valor Anterior', 'Valor Nuevo', 'Endpoint'], ';');

    foreach ($data as $row) {
        $actionLabels = [
            'CREATE' => 'Creación', 'UPDATE' => 'Actualización', 'DELETE' => 'Eliminación',
            'LOGIN' => 'Inicio de sesión', 'LOGOUT' => 'Cierre de sesión',
        ];
        fputcsv($output, [
            $row['fecha_accion'] ?? '',
            $row['nombre_usuario'] ?? 'Sistema',
            $actionLabels[$row['accion']] ?? $row['accion'] ?? '',
            $row['tabla_afectada'] ?? '',
            $row['id_registro_afectado'] ?? '',
            $row['valor_anterior'] ?? '',
            $row['valor_nuevo'] ?? '',
            $row['endpoint_solicitado'] ?? '',
        ], ';');
    }

    fclose($output);
    exit();
}
