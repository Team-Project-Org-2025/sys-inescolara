<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Tarea;
use SysInescolara\models\Empleado;
use SysInescolara\models\Lote;
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
                'POST_assign_ajax'    => assign_ajax(),
                'POST_edit_ajax'      => edit_ajax(),
                'POST_complete_ajax'  => complete_ajax(),
                'POST_cancel_ajax'    => cancel_ajax(),
                default               => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $employeeModel = new Empleado();
    $trabajadores = $employeeModel->getAll();
    $batchModel = new Lote();
    $lotes = $batchModel->getAll();
    $suppliesModel = new Insumo();
    $insumos = $suppliesModel->getAll();
    $toolModel = new Herramienta();
    $herramientas = $toolModel->getAll();

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
function assign_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:crear'); tasks_assignAjax(); }
function complete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:editar'); tasks_completeAssignmentAjax(); }
function cancel_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:eliminar'); tasks_cancelAssignmentAjax(); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('tareas:editar'); tasks_editAjax(); }

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
    $suppliesModel = new Insumo();
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

    $model = new Tarea();
    $asignacionId = $model->assignTaskWithConsumptions($assignmentData, $consumptions);

    // Guardar uso de herramientas
    $rawTools = $data['tools'] ?? [];
    $toolModel = new Herramienta();

    // Contar herramientas repetidas en la misma solicitud
    $toolCounts = [];
    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        if ($idHerramienta <= 0) continue;
        $toolCounts[$idHerramienta] = ($toolCounts[$idHerramienta] ?? 0) + 1;
    }

    // Validar disponibilidad por cantidad
    foreach ($toolCounts as $idHerramienta => $solicitadas) {
        $herramienta = $toolModel->getById($idHerramienta);
        if (!$herramienta || ($herramienta['estado'] ?? '') !== 'disponible') {
            jsonResponse([
                'success' => false,
                'message' => "La herramienta '{$herramienta['nombre_herramienta']}' no está disponible.",
            ], 400);
        }
        $usosActivos = $model->countActiveToolUsages($idHerramienta);
        $disponibles = (int)($herramienta['cantidad'] ?? 0);
        if ($usosActivos + $solicitadas > $disponibles) {
            jsonResponse([
                'success' => false,
                'message' => "No hay suficientes {$herramienta['nombre_herramienta']} disponibles. En uso: $usosActivos, solicitadas: $solicitadas, disponibles: $disponibles.",
            ], 400);
        }
    }

    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        if ($idHerramienta <= 0) continue;
        $toolModel->recordUsageWithStateUpdate([
            'id_asignacion'               => $asignacionId,
            'id_herramienta'              => $idHerramienta,
            'fecha_uso'                   => $t['fecha_uso'] ?? date('Y-m-d'),
            'observacion'                 => $t['observacion'] ?? '',
            'estado_herramienta_post_uso' => 'disponible',
        ]);
    }

    jsonResponse(['success' => true, 'message' => 'Tarea asignada correctamente', 'id_asignacion' => $asignacionId]);
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
        'id_trabajador'    => (int)($data['id_trabajador'] ?? 0),
        'id_lote'          => (int)($data['id_lote'] ?? 0),
        'fecha_asignacion' => $data['fecha_asignacion'] ?? date('Y-m-d'),
    ];

    if (!$assignmentData['id_trabajador'] || !$assignmentData['id_lote']) {
        jsonResponse(['success' => false, 'message' => 'Se requieren trabajador y lote.'], 400);
    }

    $rawConsumos = $data['consumptions'] ?? [];
    $consumptions = [];
    $suppliesModel = new Insumo();
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
        $consumptions[] = [
            'id_insumo'      => $idInsumo,
            'cantidad_usada' => $cantidad,
            'costo_unitario' => (float)($insumo['costo_unitario_actual'] ?? 0),
            'fecha_consumo'  => $c['fecha_consumo'] ?? date('Y-m-d'),
        ];
    }

    $rawTools = $data['tools'] ?? [];
    $tools = [];
    $toolModel = new Herramienta();

    // Contar herramientas repetidas en la misma solicitud
    $toolCounts = [];
    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        if ($idHerramienta <= 0) continue;
        $toolCounts[$idHerramienta] = ($toolCounts[$idHerramienta] ?? 0) + 1;
    }

    // Validar disponibilidad por cantidad (excluyendo la asignación actual)
    $model = new Tarea();
    foreach ($toolCounts as $idHerramienta => $solicitadas) {
        $herramienta = $toolModel->getById($idHerramienta);
        if (!$herramienta) {
            jsonResponse(['success' => false, 'message' => "Herramienta ID $idHerramienta no encontrada."], 400);
        }
        $usosActivos = $model->countActiveToolUsages($idHerramienta, $idAsignacion);
        $disponibles = (int)($herramienta['cantidad'] ?? 0);
        if ($usosActivos + $solicitadas > $disponibles) {
            jsonResponse([
                'success' => false,
                'message' => "No hay suficientes {$herramienta['nombre_herramienta']} disponibles. En uso: $usosActivos, solicitadas: $solicitadas, disponibles: $disponibles.",
            ], 400);
        }
    }

    foreach ($rawTools as $t) {
        $idHerramienta = (int)($t['id_herramienta'] ?? 0);
        if ($idHerramienta <= 0) continue;
        $tools[] = [
            'id_herramienta'  => $idHerramienta,
            'nombre_herramienta' => '',
            'fecha_uso'       => $t['fecha_uso'] ?? date('Y-m-d'),
            'observacion'     => $t['observacion'] ?? '',
        ];
    }

    $model->updateAssignmentWithConsumptions($idAsignacion, $assignmentData, $consumptions, $tools);

    jsonResponse(['success' => true, 'message' => 'Tarea actualizada correctamente', 'id_asignacion' => $idAsignacion]);
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

    $model->completeAssignment($id, $fechaCumplimiento, $horasDedicadas);

    $toolEstados = $data['tool_estados'] ?? [];
    if (!empty($toolEstados)) {
        $model->updateToolEstados($id, $toolEstados);
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
    jsonResponse(['success' => true, 'message' => 'Tarea cancelada correctamente']);
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