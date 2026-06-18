<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Inventory;
use SysInescolara\models\Supplies;
use SysInescolara\models\Employee;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_consolidated'  => inventory_getConsolidatedAjax(),
                'GET_get_movements'     => inventory_getMovementsAjax(),
                'GET_get_adjustments'   => inventory_getAdjustmentsAjax(),
                'POST_add_adjustment'   => inventory_addAdjustmentAjax(),
                default                 => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $supplyModel = new Supplies();
    $supplies = $supplyModel->getAll();
    $employeeModel = new Employee();
    $employees = $employeeModel->getAll();

    $showAdjustBtn = \SysInescolara\helpers\Auth::hasPermiso('INVENTARIO_ADJUST');

    $view = ROOT_PATH . 'app/views/dashboard/inventario.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inventario no encontrada.';
        return;
    }
    require $view;
}

function get_consolidated(): void { checkModuleAuth(); inventory_getConsolidatedAjax(); }
function get_movements(): void { checkModuleAuth(); inventory_getMovementsAjax(); }
function get_adjustments(): void { checkModuleAuth(); inventory_getAdjustmentsAjax(); }
function add_adjustment(): void { checkModuleAuth(); checkPermisoOrFail('INVENTARIO_ADJUST'); inventory_addAdjustmentAjax(); }

function inventory_getConsolidatedAjax(): void
{
    $model = new Inventory();
    $data = $model->getConsolidated();
    jsonResponse(['success' => true, 'data' => $data, 'count' => count($data)]);
}

function inventory_getMovementsAjax(): void
{
    $model = new Inventory();
    $data = $model->getMovements();
    jsonResponse(['success' => true, 'data' => $data, 'count' => count($data)]);
}

function inventory_getAdjustmentsAjax(): void
{
    $model = new Inventory();
    $data = $model->getAdjustments();
    jsonResponse(['success' => true, 'data' => $data, 'count' => count($data)]);
}

function inventory_addAdjustmentAjax(): void
{
    $idInsumo = (int)($_POST['id_insumo'] ?? 0);
    $idTrabajador = (int)($_POST['id_trabajador'] ?? 0);
    $tipoAjuste = trim((string)($_POST['tipo_ajuste'] ?? ''));
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    $fecha = trim((string)($_POST['fecha_ajuste'] ?? ''));

    if ($idInsumo <= 0) throw new \Exception('Debe seleccionar un insumo.');
    if ($idTrabajador <= 0) throw new \Exception('Debe seleccionar un trabajador.');
    if (!in_array($tipoAjuste, ['entrada', 'salida'], true)) throw new \Exception('Tipo de ajuste inválido.');
    if ($cantidad <= 0) throw new \Exception('La cantidad debe ser mayor a cero.');
    if ($motivo === '') throw new \Exception('El motivo es requerido.');
    if ($fecha === '') throw new \Exception('La fecha es requerida.');

    $model = new Inventory();
    $supplyModel = new Supplies();

    $oldSupply = $supplyModel->getById($idInsumo);

    try {
        $model->beginTransaction();

        $model->addAdjustment($idInsumo, $idTrabajador, $tipoAjuste, $cantidad, $motivo, $fecha);
        $model->updateSupplyStock($idInsumo, $cantidad, $tipoAjuste);
        $newId = $model->getLastInsertId() ?? 0;

        $model->commit();
    } catch (\Exception $e) {
        $model->rollback();
        throw $e;
    }

    $newSupply = $supplyModel->getById($idInsumo);

    AuditLog::record('CREATE', 'ajuste_inventario', $newId, null, [
        'id_insumo' => $idInsumo,
        'id_trabajador' => $idTrabajador,
        'tipo_ajuste' => $tipoAjuste,
        'cantidad' => $cantidad,
        'motivo' => $motivo,
        'fecha_ajuste' => $fecha,
        'stock_anterior' => $oldSupply['stock_actual'] ?? null,
        'stock_nuevo' => $newSupply['stock_actual'] ?? null,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Ajuste de inventario registrado correctamente.',
        'stock_anterior' => $oldSupply['stock_actual'] ?? 0,
        'stock_nuevo' => $newSupply['stock_actual'] ?? 0,
    ]);
}
