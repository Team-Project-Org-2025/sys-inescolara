<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Task;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_tasks'       => tasks_getTasksAjax(),
                'POST_add_ajax'       => tasks_handleAddEdit('add'),
                'POST_edit_ajax'      => tasks_handleAddEdit('edit'),
                'POST_delete_ajax'    => tasks_handleDelete(),
                'GET_get_assignments' => tasks_getAssignmentsAjax(),
                'GET_get_assignment'  => tasks_getAssignmentDetailAjax(),
                'POST_assign_ajax'    => tasks_assignAjax(),
                'POST_complete_ajax'  => tasks_completeAssignmentAjax(),
                'POST_cancel_ajax'    => tasks_cancelAssignmentAjax(),
                default               => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
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
function get_assignments(): void { checkModuleAuth(); tasks_getAssignmentsAjax(); }
function get_assignment(): void { checkModuleAuth(); tasks_getAssignmentDetailAjax(); }
function assign_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_ASSIGN'); tasks_assignAjax(); }
function complete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_EDIT'); tasks_completeAssignmentAjax(); }
function cancel_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_DELETE'); tasks_cancelAssignmentAjax(); }

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
    AuditLog::record('DEACTIVATE', 'tareas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Tarea desactivada correctamente', 'taskId' => $id]);
}

function tasks_getTasksAjax(): void
{
    $model = new Task();
    $tasks = $model->getAll();
    jsonResponse(['success' => true, 'tasks' => $tasks, 'count' => count($tasks)]);
}

// -- Asignación de tareas --

function tasks_assignAjax(): void
{
    $data = getRequestData();

    $assignmentData = [
        'id_trabajador'    => (int)($data['id_trabajador'] ?? 0),
        'id_tarea'         => (int)($data['id_tarea'] ?? 0),
        'id_lote'          => (int)($data['id_lote'] ?? 0),
        'fecha_asignacion' => $data['fecha_asignacion'] ?? date('Y-m-d'),
        'estatus_tarea'    => 'pendiente',
    ];

    if (!$assignmentData['id_trabajador'] || !$assignmentData['id_tarea'] || !$assignmentData['id_lote']) {
        jsonResponse(['success' => false, 'message' => 'Se requieren trabajador, tarea y lote.'], 400);
    }

    $rawConsumos = $data['consumptions'] ?? [];
    $consumptions = [];
    foreach ($rawConsumos as $c) {
        $consumptions[] = [
            'id_insumo'      => (int)($c['id_insumo'] ?? 0),
            'cantidad_usada' => (float)($c['cantidad_usada'] ?? 0),
            'costo_unitario' => (float)($c['costo_unitario'] ?? 0),
            'fecha_consumo'  => $c['fecha_consumo'] ?? date('Y-m-d'),
        ];
    }

    $model = new Task();
    $asignacionId = $model->assignTaskWithConsumptions($assignmentData, $consumptions);

    AuditLog::record('CREATE', 'asignar_tarea', $asignacionId, null, $assignmentData);
    if (!empty($consumptions)) {
        AuditLog::record('CREATE', 'consumo_insumos', $asignacionId, null, ['count' => count($consumptions)]);
    }

    jsonResponse(['success' => true, 'message' => 'Tarea asignada correctamente', 'id_asignacion' => $asignacionId]);
}

function tasks_completeAssignmentAjax(): void
{
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $fechaCumplimiento = $data['fecha_cumplimiento'] ?? date('Y-m-d');
    $horasDedicadas = (float)($data['horas_dedicadas'] ?? 0);

    $model = new Task();
    $oldData = $model->getAssignmentById($id);
    if (!$oldData) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $model->completeAssignment($id, $fechaCumplimiento, $horasDedicadas);
    AuditLog::record('UPDATE', 'asignar_tarea', $id, $oldData, [
        'estatus_tarea' => 'completada',
        'fecha_cumplimiento' => $fechaCumplimiento,
        'horas_dedicadas' => $horasDedicadas,
    ]);
    jsonResponse(['success' => true, 'message' => 'Tarea completada correctamente']);
}

function tasks_cancelAssignmentAjax(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $model = new Task();
    $oldData = $model->getAssignmentById($id);
    if (!$oldData) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $model->cancelAssignment($id);
    AuditLog::record('UPDATE', 'asignar_tarea', $id, $oldData, ['estatus_tarea' => 'cancelada']);
    jsonResponse(['success' => true, 'message' => 'Tarea cancelada correctamente']);
}

function tasks_getAssignmentsAjax(): void
{
    $model = new Task();
    $assignments = $model->getAssignments();
    jsonResponse(['success' => true, 'assignments' => $assignments, 'count' => count($assignments)]);
}

function tasks_getAssignmentDetailAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $model = new Task();
    $assignment = $model->getAssignmentById($id);
    if (!$assignment) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $consumptions = $model->getConsumptions($id);
    $toolUsages = $model->getToolUsages($id);

    jsonResponse([
        'success' => true,
        'assignment' => $assignment,
        'consumptions' => $consumptions,
        'tool_usages' => $toolUsages,
    ]);
}
