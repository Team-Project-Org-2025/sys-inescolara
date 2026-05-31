<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Backup;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if ($action !== '') {
        try {
            if (isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                    'GET_get_backups'      => backups_getBackupsAjax(),
                    'POST_create_backup'   => backups_createBackupAjax(),
                    'POST_restore_backup'  => backups_restoreBackupAjax(),
                    'POST_delete_backup'   => backups_deleteBackupAjax(),
                    default                => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
                };
            } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download_backup') {
                backups_downloadBackup();
            }
        } catch (\Exception $e) {
            handleError($e, isAjaxRequest());
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/backups.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de respaldos no encontrada.';
        return;
    }
    require $view;
}

function get_backups(): void { checkModuleAuth(); backups_getBackupsAjax(); }
function create_backup(): void { checkModuleAuth(); backups_createBackupAjax(); }
function restore_backup(): void { checkModuleAuth(); backups_restoreBackupAjax(); }
function delete_backup(): void { checkModuleAuth(); backups_deleteBackupAjax(); }

function backups_getBackupsAjax(): void
{
    $model = new Backup();
    $backups = $model->list();
    jsonResponse(['success' => true, 'backups' => $backups, 'count' => count($backups)]);
}

function backups_createBackupAjax(): void
{
    $model = new Backup();
    $dbConfigs = $model->getDbConfigs();
    $results = [];
    foreach ($dbConfigs as $key => $config) {
        $result = $model->create($config['host'], $config['port'], $config['name'], $config['user'], $config['pass'], $config['label']);
        if (!$result['success']) {
            jsonResponse(['success' => false, 'message' => "Error al respaldar {$config['label']}: {$result['message']}"]);
            return;
        }
        $results[] = $result;
    }
    AuditLog::record('CREATE', 'backups', null, null, ['files' => array_map(fn($r) => $r['filename'], $results)]);
    $totalSize = array_sum(array_map(fn($r) => $r['size'], $results));
    jsonResponse(['success' => true, 'message' => 'Respaldo completo creado exitosamente.', 'files' => array_map(fn($r) => $r['filename'], $results), 'total_size' => $totalSize]);
}

function backups_restoreBackupAjax(): void
{
    $model = new Backup();
    $filename = $_POST['filename'] ?? '';
    if ($filename === '') {
        jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
        return;
    }
    $result = $model->restore($filename);
    if ($result['success']) {
        AuditLog::record('UPDATE', 'backups', null, null, ['action' => 'restore', 'file' => $filename, 'result' => 'success']);
    }
    jsonResponse($result);
}

function backups_deleteBackupAjax(): void
{
    $model = new Backup();
    $filename = $_POST['filename'] ?? '';
    if ($filename === '') {
        jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
        return;
    }
    if ($model->delete($filename)) {
        AuditLog::record('DELETE', 'backups', null, null, ['file' => $filename]);
        jsonResponse(['success' => true, 'message' => 'Respaldo eliminado correctamente.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'No se pudo eliminar el archivo.'], 500);
    }
}

function backups_downloadBackup(): void
{
    $model = new Backup();
    $filename = $_GET['file'] ?? '';
    if ($filename === '') {
        http_response_code(400);
        echo 'Nombre de archivo requerido.';
        exit();
    }
    $filepath = $model->getFilePath($filename);
    if ($filepath === null) {
        http_response_code(404);
        echo 'Archivo no encontrado.';
        exit();
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('X-Robots-Tag: noindex');
    readfile($filepath);
    exit();
}
