<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Task;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function tasksCheckAuth(): void
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

$GLOBALS['taskModel'] = new Task();

function index(): void
{
    $taskModel = $GLOBALS['taskModel'] ?? new Task();
    tasksCheckAuth();
    handleRequest($taskModel);

    $view = ROOT_PATH . 'app/views/dashboard/task.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }

    require $view;
}

function get_tasks(): void
{
    $taskModel = $GLOBALS['taskModel'] ?? new Task();
    tasksCheckAuth();
    getTasksAjax($taskModel);
}

function add_ajax(): void
{
    $taskModel = $GLOBALS['taskModel'] ?? new Task();
    tasksCheckAuth();
    handleAddEditAjax($taskModel, 'add');
}

function edit_ajax(): void
{
    $taskModel = $GLOBALS['taskModel'] ?? new Task();
    tasksCheckAuth();
    handleAddEditAjax($taskModel, 'edit');
}

function delete_ajax(): void
{
    $taskModel = $GLOBALS['taskModel'] ?? new Task();
    tasksCheckAuth();
    handleDeleteAjax($taskModel);
}

function handleRequest(Task $taskModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_tasks'    => fn() => getTasksAjax($taskModel),
                'POST_add_ajax'    => fn() => handleAddEditAjax($taskModel, 'add'),
                'POST_edit_ajax'   => fn() => handleAddEditAjax($taskModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($taskModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
                exit();
            }

            jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
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

function handleAddEditAjax(Task $taskModel, string $mode): void
{
    $nombre = trim((string)($_POST['nombre_tarea'] ?? ''));
    if ($nombre === '') {
        throw new Exception('El nombre de la tarea es obligatorio.');
    }

    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') {
        $descripcion = null;
    }

    if ($mode === 'add') {
        $taskModel->add($nombre, $descripcion);
        $newId = $taskModel->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'tareas', $newId, null, [
            'nombre_tarea' => $nombre,
            'descripcion' => $descripcion,
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Tarea agregada correctamente',
            'task' => [
                'id' => $newId,
                'nombre_tarea' => $nombre
            ],
        ]);
    }

    // Modo edit
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');

    $oldData = $taskModel->getById($id);
    if (!$oldData) throw new Exception('La tarea no existe');

    $taskModel->update($id, $nombre, $descripcion);
    AuditLog::record('UPDATE', 'tareas', $id, $oldData, [
        'nombre_tarea' => $nombre,
        'descripcion' => $descripcion,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Tarea actualizada correctamente',
        'task' => ['id' => $id],
    ]);
}

function handleDeleteAjax(Task $taskModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');
    if (!$taskModel->exists($id)) throw new Exception('No existe la tarea');

    $oldData = $taskModel->getById($id);
    $taskModel->delete($id);
    AuditLog::record('DELETE', 'tareas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Tarea eliminada correctamente', 'taskId' => $id]);
}

function getTasksAjax(Task $taskModel): void
{
    $tasks = $taskModel->getAll();
    jsonResponse(['success' => true, 'tasks' => $tasks, 'count' => count($tasks)]);
}