<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Backup;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class BackupsController
{
    use ResponseTrait, PermissionTrait;

    private Backup $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Backup();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/backups.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de respaldos no encontrada.';
            return;
        }
        require $view;
    }

    public function get_backups(): void { $this->checkModuleAuth(); $this->getBackupsAjax(); }
    public function create_backup(): void { $this->checkModuleAuth(); $this->createBackupAjax(); }
    public function restore_backup(): void { $this->checkModuleAuth(); $this->restoreBackupAjax(); }
    public function delete_backup(): void { $this->checkModuleAuth(); $this->deleteBackupAjax(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if ($action === '') return;

        try {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                    'GET_get_backups'      => $this->getBackupsAjax(),
                    'POST_create_backup'   => $this->createBackupAjax(),
                    'POST_restore_backup'  => $this->restoreBackupAjax(),
                    'POST_delete_backup'   => $this->deleteBackupAjax(),
                    default                => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
                };
            } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download_backup') {
                $this->downloadBackup();
            }
        } catch (\Exception $e) {
            $this->handleError($e, $this->isAjaxRequest());
        }
    }

    private function getBackupsAjax(): void
    {
        $backups = $this->model->list();
        $this->jsonResponse(['success' => true, 'backups' => $backups, 'count' => count($backups)]);
    }

    private function createBackupAjax(): void
    {
        $dbConfigs = $this->model->getDbConfigs();
        $results = [];
        foreach ($dbConfigs as $key => $config) {
            $result = $this->model->create($config['host'], $config['port'], $config['name'], $config['user'], $config['pass'], $config['label']);
            if (!$result['success']) {
                $this->jsonResponse(['success' => false, 'message' => "Error al respaldar {$config['label']}: {$result['message']}"]);
                return;
            }
            $results[] = $result;
        }
        AuditLog::record('CREATE', 'backups', null, null, ['files' => array_map(fn($r) => $r['filename'], $results)]);
        $totalSize = array_sum(array_map(fn($r) => $r['size'], $results));
        $this->jsonResponse(['success' => true, 'message' => 'Respaldo completo creado exitosamente.', 'files' => array_map(fn($r) => $r['filename'], $results), 'total_size' => $totalSize]);
    }

    private function restoreBackupAjax(): void
    {
        $filename = $_POST['filename'] ?? '';
        if ($filename === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
            return;
        }
        $result = $this->model->restore($filename);
        if ($result['success']) {
            AuditLog::record('UPDATE', 'backups', null, null, ['action' => 'restore', 'file' => $filename, 'result' => 'success']);
        }
        $this->jsonResponse($result);
    }

    private function deleteBackupAjax(): void
    {
        $filename = $_POST['filename'] ?? '';
        if ($filename === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Nombre de archivo requerido.'], 400);
            return;
        }
        if ($this->model->delete($filename)) {
            AuditLog::record('DELETE', 'backups', null, null, ['file' => $filename]);
            $this->jsonResponse(['success' => true, 'message' => 'Respaldo eliminado correctamente.']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'No se pudo eliminar el archivo.'], 500);
        }
    }

    private function downloadBackup(): void
    {
        $filename = $_GET['file'] ?? '';
        if ($filename === '') {
            http_response_code(400);
            echo 'Nombre de archivo requerido.';
            exit();
        }
        $filepath = $this->model->getFilePath($filename);
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
}
