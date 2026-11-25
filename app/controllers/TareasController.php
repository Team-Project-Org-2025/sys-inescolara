<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Tarea;
use SysInescolara\models\Usuario;
use SysInescolara\models\Insumo;
use SysInescolara\models\Herramienta;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_assignments' => get_assignments(),
                'GET_get_assignment'  => get_assignment(),
                'GET_get_tools_refresh' => get_tools_refresh(),
                'GET_get_insumos_refresh' => get_insumos_refresh(),
                'POST_assign_ajax'    => assign_ajax(),
                'POST_edit_ajax'      => edit_ajax(),
                'POST_complete_ajax'  => complete_ajax(),
                'POST_cancel_ajax'    => cancel_ajax(),
                default               => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Throwable $e) {
            handleError($e, true);
        }
        return;
    }

    $userModel = new Usuario();
    $trabajadores = $userModel->getAll();
    $suppliesModel = new Insumo();
    $insumos = $suppliesModel->getAll();
    $toolModel = new Herramienta();
    $herramientas = $toolModel->getAllWithAvailability();

    $view = ROOT_PATH . 'app/views/dashboard/tareas.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }
    require $view;
}

function get_assignments(): void { checkModuleAuth(); tasks_getAssignmentsAjax(); }
function get_assignment(): void { checkModuleAuth(); tasks_getAssignmentDetailAjax(); }
function get_tools_refresh(): void { checkModuleAuth(); $m = new Herramienta(); jsonResponse(['success' => true, 'tools' => $m->getAllWithAvailability()]); }
function get_insumos_refresh(): void { checkModuleAuth(); $m = new Insumo(); jsonResponse(['success' => true, 'insumos' => $m->getAll()]); }
function assign_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:crear'); tasks_assignAjax(); }
function complete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:editar'); tasks_completeAssignmentAjax(); }
function cancel_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:eliminar'); tasks_cancelAssignmentAjax(); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:editar'); tasks_editAjax(); }

function tasks_assignAjax(): void
{
    $data = getRequestData();

    $nombreTarea = trim((string)($data['nombre_tarea'] ?? ''));
    if ($nombreTarea === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre de la tarea es obligatorio.'], 400);
    }

    $descripcion = trim((string)($data['descripcion'] ?? ''));
    if ($descripcion === '') {
        $descripcion = null;
    }

    $assignmentData = [
        'nombre_tarea'     => $nombreTarea,
        'descripcion'      => $descripcion,
        'id_usuario'       => (int)($data['id_usuario'] ?? 0),
        'fecha_asignacion' => $data['fecha_asignacion'] ?? date('Y-m-d'),
        'estatus_tarea'    => 'pendiente',
    ];

    if (!$assignmentData['id_usuario']) {
        jsonResponse(['success' => false, 'message' => 'Se requiere un trabajador.'], 400);
    }

    $rawTools = $data['tools'] ?? [];
    $tools = [];
    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        $cantidad = (float)($t['cantidad'] ?? 0);
        if ($idHerramienta <= 0 || $cantidad <= 0) continue;
        $tools[] = [
            'id_herramienta' => $idHerramienta,
            'cantidad'       => $cantidad,
            'fecha_uso'      => $t['fecha_uso'] ?? date('Y-m-d'),
            'observacion'    => $t['observacion'] ?? '',
        ];
    }

    $model = new Tarea();
    $asignacionId = $model->assignTask($assignmentData, $tools);

    try {
        $notifModel = new \SysInescolara\models\Notification();
        $notifModel->create(
            $assignmentData['id_usuario'],
            'Nueva tarea asignada',
            "Se te ha asignado la tarea: {$assignmentData['nombre_tarea']}",
            'task_assigned',
            'dashboard/tareas'
        );
    } catch (\Throwable $e) {
        error_log('Error al crear notificación: ' . $e->getMessage());
    }

    $toolModel = new Herramienta();
    $freshHerramientas = $toolModel->getAllWithAvailability();

    jsonResponse([
        'success' => true,
        'message' => 'Tarea asignada correctamente',
        'id_asignacion' => $asignacionId,
        'herramientas' => $freshHerramientas,
    ]);
}

function tasks_editAjax(): void
{
    $data = getRequestData();

    $idAsignacion = (int)($data['id_asignacion'] ?? 0);
    if ($idAsignacion <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de asignación inválido.'], 400);
    }

    $nombreTarea = trim((string)($data['nombre_tarea'] ?? ''));
    if ($nombreTarea === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre de la tarea es obligatorio.'], 400);
    }

    $descripcion = trim((string)($data['descripcion'] ?? ''));
    if ($descripcion === '') {
        $descripcion = null;
    }

    $assignmentData = [
        'nombre_tarea'     => $nombreTarea,
        'descripcion'      => $descripcion,
        'id_usuario'       => (int)($data['id_usuario'] ?? 0),
        'fecha_asignacion' => $data['fecha_asignacion'] ?? date('Y-m-d'),
    ];

    if (!$assignmentData['id_usuario']) {
        jsonResponse(['success' => false, 'message' => 'Se requiere un trabajador.'], 400);
    }

    $rawTools = $data['tools'] ?? [];
    $tools = [];
    $toolModel = new Herramienta();

    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        $cantidad = (float)($t['cantidad'] ?? 0);
        if ($idHerramienta <= 0 || $cantidad <= 0) continue;

        $herramienta = $toolModel->getById($idHerramienta);
        if (!$herramienta) {
            jsonResponse(['success' => false, 'message' => "Herramienta ID $idHerramienta no encontrada."], 400);
        }

        $tools[] = [
            'id_herramienta' => $idHerramienta,
            'cantidad'       => $cantidad,
            'fecha_uso'      => $t['fecha_uso'] ?? date('Y-m-d'),
            'observacion'    => $t['observacion'] ?? '',
        ];
    }

    $model = new Tarea();
    $model->updateAssignment($idAsignacion, $assignmentData, $tools);

    $freshHerramientas = $toolModel->getAllWithAvailability();

    jsonResponse([
        'success' => true,
        'message' => 'Tarea actualizada correctamente',
        'id_asignacion' => $idAsignacion,
        'herramientas' => $freshHerramientas,
    ]);
}

function tasks_completeAssignmentAjax(): void
{
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $fechaCumplimiento = $data['fecha_cumplimiento'] ?? date('Y-m-d');
    $horasDedicadas = isset($data['horas_dedicadas']) && $data['horas_dedicadas'] !== ''
        ? (float)$data['horas_dedicadas']
        : null;

    $model = new Tarea();
    $assignment = $model->getAssignmentById($id);
    if (!$assignment) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $rawConsumos = $data['consumptions'] ?? [];
    $consumptions = [];
    $suppliesModel = new Insumo();
    foreach ($rawConsumos as $c) {
        $idInsumo = (int)($c['id_insumo'] ?? 0);
        $cantidad = (float)($c['cantidad'] ?? 0);
        if ($idInsumo <= 0 || $cantidad <= 0) continue;

        $insumo = $suppliesModel->getById($idInsumo);
        if (!$insumo) {
            jsonResponse(['success' => false, 'message' => "El insumo ID $idInsumo no existe."], 400);
        }

        $consumptions[] = [
            'id_insumo'  => $idInsumo,
            'cantidad'   => $cantidad,
            'id_lote'    => !empty($c['id_lote']) ? (int)$c['id_lote'] : null,
        ];
    }

    $toolEstados = $data['tool_estados'] ?? [];

    $model->completeAssignment($id, $fechaCumplimiento, $horasDedicadas, $consumptions, $toolEstados);

    try {
        $notifModel = new \SysInescolara\models\Notification();
        $notifModel->markTaskAssignedAsRead((int)$assignment['id_usuario'], $assignment['nombre_tarea']);
    } catch (\Throwable $e) {
        error_log('Error al marcar notificación como leída: ' . $e->getMessage());
    }

    jsonResponse(['success' => true, 'message' => 'Tarea completada correctamente']);
}

function tasks_cancelAssignmentAjax(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $model = new Tarea();
    $assignment = $model->getAssignmentById($id);
    if (!$assignment) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $model->cancelAssignment($id);

    try {
        $notifModel = new \SysInescolara\models\Notification();
        $notifModel->markTaskAssignedAsRead((int)$assignment['id_usuario'], $assignment['nombre_tarea']);
    } catch (\Throwable $e) {
        error_log('Error al marcar notificación como leída: ' . $e->getMessage());
    }

    jsonResponse(['success' => true, 'message' => 'Asignación cancelada correctamente']);
}

function tasks_getAssignmentsAjax(): void
{
    $model = new Tarea();
    $assignments = $model->getAssignments();
    jsonResponse(['success' => true, 'assignments' => $assignments, 'count' => count($assignments)]);
}

function tasks_getAssignmentDetailAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $model = new Tarea();
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
