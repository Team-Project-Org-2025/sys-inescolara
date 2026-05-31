<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Task;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_tasks'   => tasks_getTasksAjax(),
                'POST_add_ajax'   => tasks_handleAddEdit('add'),
                'POST_edit_ajax'  => tasks_handleAddEdit('edit'),
                'POST_delete_ajax' => tasks_handleDelete(),
                default           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/task.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }
    require $view;
}

function get_tasks(): void { checkModuleAuth(); tasks_getTasksAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_CREATE'); tasks_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_EDIT'); tasks_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_DELETE'); tasks_handleDelete(); }

function tasks_handleAddEdit(string $mode): void
{
    $model = new Task();
    $nombre = trim((string)($_POST['nombre_tarea'] ?? ''));
    if ($nombre === '') {
        throw new \Exception('El nombre de la tarea es obligatorio.');
    }

    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') {
        $descripcion = null;
    }

    if ($mode === 'add') {
        $model->add($nombre, $descripcion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'tareas', $newId, null, [
            'nombre_tarea' => $nombre, 'descripcion' => $descripcion,
        ]);
        jsonResponse([
            'success' => true, 'message' => 'Tarea agregada correctamente',
            'task' => ['id' => $newId, 'nombre_tarea' => $nombre],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    if (!$oldData) throw new \Exception('La tarea no existe');

    $model->update($id, $nombre, $descripcion);
    AuditLog::record('UPDATE', 'tareas', $id, $oldData, [
        'nombre_tarea' => $nombre, 'descripcion' => $descripcion,
    ]);
    jsonResponse([
        'success' => true, 'message' => 'Tarea actualizada correctamente',
        'task' => ['id' => $id],
    ]);
}

function tasks_handleDelete(): void
{
    $model = new Task();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la tarea');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'tareas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Tarea eliminada correctamente', 'taskId' => $id]);
}

function tasks_getTasksAjax(): void
{
    $model = new Task();
    $tasks = $model->getAll();
    jsonResponse(['success' => true, 'tasks' => $tasks, 'count' => count($tasks)]);
}
