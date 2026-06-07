<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Task;
use SysInescolara\models\AuditLog;
use SysInescolara\models\Employee;
use SysInescolara\models\Batch;
use SysInescolara\models\Supplies;
use SysInescolara\models\Tool;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
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

    $employeeModel = new Employee();
    $trabajadores = $employeeModel->getAll();
    $batchModel = new Batch();
    $lotes = $batchModel->getAll();
    $suppliesModel = new Supplies();
    $insumos = $suppliesModel->getAll();
    $toolModel = new Tool();
    $herramientas = $toolModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/task.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }
    require $view;
}

function get_assignments(): void { checkModuleAuth(); tasks_getAssignmentsAjax(); }
function get_assignment(): void { checkModuleAuth(); tasks_getAssignmentDetailAjax(); }
function assign_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_ASSIGN'); tasks_assignAjax(); }
function complete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_EDIT'); tasks_completeAssignmentAjax(); }
function cancel_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TAREAS_DELETE'); tasks_cancelAssignmentAjax(); }

// -- Asignación de tareas --

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
        'id_trabajador'    => (int)($data['id_trabajador'] ?? 0),
        'id_lote'          => (int)($data['id_lote'] ?? 0),
        'fecha_asignacion' => $data['fecha_asignacion'] ?? date('Y-m-d'),
        'estatus_tarea'    => 'pendiente',
    ];

    if (!$assignmentData['id_trabajador'] || !$assignmentData['id_lote']) {
        jsonResponse(['success' => false, 'message' => 'Se requieren trabajador y lote.'], 400);
    }

    $rawConsumos = $data['consumptions'] ?? [];
    $consumptions = [];
    $suppliesModel = new Supplies();
    foreach ($rawConsumos as $c) {
        $idInsumo = (int)($c['id_insumo'] ?? 0);
        $cantidad = (float)($c['cantidad_usada'] ?? 0);
        if ($idInsumo <= 0 || $cantidad <= 0) {
            continue;
        }
        $insumo = $suppliesModel->getById($idInsumo);
        if (!$insumo) {
            jsonResponse(['success' => false, 'message' => "El insumo ID $idInsumo no existe."], 400);
        }
        $stockDisponible = (float)($insumo['stock_actual'] ?? 0);
        if ($cantidad > $stockDisponible) {
            jsonResponse([
                'success' => false,
                'message' => "Stock insuficiente para {$insumo['nombre_insumo']}. Disponible: $stockDisponible, solicitado: $cantidad.",
            ], 400);
        }
        $consumptions[] = [
            'id_insumo'      => $idInsumo,
            'cantidad_usada' => $cantidad,
            'costo_unitario' => (float)($insumo['costo_unitario_actual'] ?? 0),
            'stock_actual'   => (float)($insumo['stock_actual'] ?? 0),
            'fecha_consumo'  => $c['fecha_consumo'] ?? date('Y-m-d'),
        ];
    }

    $model = new Task();
    $asignacionId = $model->assignTaskWithConsumptions($assignmentData, $consumptions);

    AuditLog::record('CREATE', 'asignar_tarea', $asignacionId, null, $assignmentData);
    if (!empty($consumptions)) {
        AuditLog::record('CREATE', 'consumo_insumos', $asignacionId, null, ['count' => count($consumptions)]);
    }

    // Save tool usages
    $rawTools = $data['tools'] ?? [];
    $toolModel = new Tool();
    $toolCount = 0;
    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        if ($idHerramienta <= 0) continue;
        $herramienta = $toolModel->getById($idHerramienta);
        if (!$herramienta || ($herramienta['estado'] ?? '') !== 'disponible') {
            jsonResponse([
                'success' => false,
                'message' => "La herramienta '{$herramienta['nombre_herramienta']}' no está disponible.",
            ], 400);
        }
        $toolModel->recordUsageWithStateUpdate([
            'id_asignacion'               => $asignacionId,
            'id_herramienta'              => $idHerramienta,
            'fecha_uso'                   => $t['fecha_uso'] ?? date('Y-m-d'),
            'observacion'                 => $t['observacion'] ?? '',
            'estado_herramienta_post_uso' => 'ok',
        ]);
        $toolCount++;
    }
    if ($toolCount > 0) {
        AuditLog::record('CREATE', 'uso_herramienta', $asignacionId, null, ['count' => $toolCount]);
    }

    jsonResponse(['success' => true, 'message' => 'Tarea asignada correctamente', 'id_asignacion' => $asignacionId]);
}

function tasks_completeAssignmentAjax(): void
{
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $fechaCumplimiento = $data['fecha_cumplimiento'] ?? date('Y-m-d');

    $model = new Task();
    $oldData = $model->getAssignmentById($id);
    if (!$oldData) jsonResponse(['success' => false, 'message' => 'Asignación no encontrada'], 404);

    $model->completeAssignment($id, $fechaCumplimiento);

    $toolEstados = $data['tool_estados'] ?? [];
    if (!empty($toolEstados)) {
        $model->updateToolEstados($id, $toolEstados);
    }

    AuditLog::record('UPDATE', 'asignar_tarea', $id, $oldData, [
        'estatus_tarea' => 'completada',
        'fecha_cumplimiento' => $fechaCumplimiento,
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
