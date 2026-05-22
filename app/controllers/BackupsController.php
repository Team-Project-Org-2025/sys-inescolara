<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Backup;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function backupCheckAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado', 'redirect' => BASE_URL . 'login']);
            exit();
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

$GLOBALS['backupModel'] = new Backup();

function index(): void
{
    $backupModel = $GLOBALS['backupModel'] ?? new Backup();
    backupCheckAuth();
    handleRequest($backupModel);

    $view = ROOT_PATH . 'app/views/dashboard/backups.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de respaldos no encontrada.';
        return;
    }

    require $view;
}

function get_backups(): void
{
    $backupModel = $GLOBALS['backupModel'] ?? new Backup();
    backupCheckAuth();
    getBackupsAjax($backupModel);
}

function create_backup(): void
{
    $backupModel = $GLOBALS['backupModel'] ?? new Backup();
    backupCheckAuth();
    createBackupAjax($backupModel);
}

function restore_backup(): void
{
    $backupModel = $GLOBALS['backupModel'] ?? new Backup();
    backupCheckAuth();
    restoreBackupAjax($backupModel);
}

function delete_backup(): void
{
    $backupModel = $GLOBALS['backupModel'] ?? new Backup();
    backupCheckAuth();
    deleteBackupAjax($backupModel);
}

function handleRequest(Backup $backupModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_backups'     => fn() => getBackupsAjax($backupModel),
                'POST_create_backup'  => fn() => createBackupAjax($backupModel),
                'POST_restore_backup' => fn() => restoreBackupAjax($backupModel),
                'POST_delete_backup'  => fn() => deleteBackupAjax($backupModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
            }

            jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
        } else {
            $routes = [
                'GET_download_backup' => fn() => downloadBackup($backupModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
            }
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function handleError(Exception $e, bool $isAjax): void
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}

function getBackupsAjax(Backup $backupModel): void
{
    $backups = $backupModel->list();
    jsonResponse([
        'success' => true,
        'backups' => $backups,
        'count' => count($backups),
    ]);
}

function createBackupAjax(Backup $backupModel): void
{
    $dbConfigs = $backupModel->getDbConfigs();
    $results = [];

    foreach ($dbConfigs as $key => $config) {
        $result = $backupModel->create(
            $config['host'],
            $config['port'],
            $config['name'],
            $config['user'],
            $config['pass'],
            $config['label']
        );

        if (!$result['success']) {
            jsonResponse([
                'success' => false,
                'message' => "Error al respaldar {$config['label']}: {$result['message']}",
            ]);
            return;
        }

        $results[] = $result;
    }

    AuditLog::record('CREATE', 'backups', null, null, [
        'files' => array_map(fn($r) => $r['filename'], $results),
    ]);

    $totalSize = array_sum(array_map(fn($r) => $r['size'], $results));

    jsonResponse([
        'success' => true,
        'message' => 'Respaldo completo creado exitosamente.',
        'files' => array_map(fn($r) => $r['filename'], $results),
        'total_size' => $totalSize,
    ]);
}

function restoreBackupAjax(Backup $backupModel): void
{
    $filename = $_POST['filename'] ?? '';
    if ($filename === '') {
        jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
        return;
    }

    $result = $backupModel->restore($filename);

    if ($result['success']) {
        AuditLog::record('UPDATE', 'backups', null, null, [
            'action' => 'restore',
            'file' => $filename,
            'result' => 'success',
        ]);
    }

    jsonResponse($result);
}

function deleteBackupAjax(Backup $backupModel): void
{
    $filename = $_POST['filename'] ?? '';
    if ($filename === '') {
        jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
        return;
    }

    $deleted = $backupModel->delete($filename);

    if ($deleted) {
        AuditLog::record('DELETE', 'backups', null, null, [
            'file' => $filename,
        ]);
        jsonResponse(['success' => true, 'message' => 'Respaldo eliminado correctamente.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'No se pudo eliminar el archivo.'], 500);
    }
}

function downloadBackup(Backup $backupModel): void
{
    $filename = $_GET['file'] ?? '';
    if ($filename === '') {
        http_response_code(400);
        echo 'Nombre de archivo requerido.';
        exit();
    }

    $filepath = $backupModel->getFilePath($filename);
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
