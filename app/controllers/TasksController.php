<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Task;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class TasksController
{
    use ResponseTrait, PermissionTrait;

    private Task $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Task();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/task.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de tareas no encontrada.';
            return;
        }
        require $view;
    }

    public function get_tasks(): void
    {
        $this->checkModuleAuth();
        $this->getTasksAjax();
    }

    public function add_ajax(): void
    {
        $this->checkModuleAuth();
        $this->handleAddEdit('add');
    }

    public function edit_ajax(): void
    {
        $this->checkModuleAuth();
        $this->handleAddEdit('edit');
    }

    public function delete_ajax(): void
    {
        $this->checkModuleAuth();
        $this->handleDelete();
    }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_tasks'   => $this->getTasksAjax(),
                'POST_add_ajax'   => $this->handleAddEdit('add'),
                'POST_edit_ajax'  => $this->handleAddEdit('edit'),
                'POST_delete_ajax' => $this->handleDelete(),
                default           => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function handleAddEdit(string $mode): void
    {
        $nombre = trim((string)($_POST['nombre_tarea'] ?? ''));
        if ($nombre === '') {
            throw new \Exception('El nombre de la tarea es obligatorio.');
        }

        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        if ($descripcion === '') {
            $descripcion = null;
        }

        if ($mode === 'add') {
            $this->model->add($nombre, $descripcion);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'tareas', $newId, null, [
                'nombre_tarea' => $nombre, 'descripcion' => $descripcion,
            ]);
            $this->jsonResponse([
                'success' => true, 'message' => 'Tarea agregada correctamente',
                'task' => ['id' => $newId, 'nombre_tarea' => $nombre],
            ]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        if (!$oldData) throw new \Exception('La tarea no existe');

        $this->model->update($id, $nombre, $descripcion);
        AuditLog::record('UPDATE', 'tareas', $id, $oldData, [
            'nombre_tarea' => $nombre, 'descripcion' => $descripcion,
        ]);
        $this->jsonResponse([
            'success' => true, 'message' => 'Tarea actualizada correctamente',
            'task' => ['id' => $id],
        ]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe la tarea');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'tareas', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Tarea eliminada correctamente', 'taskId' => $id]);
    }

    private function getTasksAjax(): void
    {
        $tasks = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'tasks' => $tasks, 'count' => count($tasks)]);
    }
}
