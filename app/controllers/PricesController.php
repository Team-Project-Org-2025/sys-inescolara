<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\PriceCalculation;
use SysInescolara\models\Batch;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_prices'     => prices_getPricesAjax(),
                'POST_add_ajax'      => prices_handleAddEdit('add'),
                'POST_edit_ajax'     => prices_handleAddEdit('edit'),
                'POST_delete_ajax'   => prices_handleDelete(),
                'GET_get_active'     => prices_getActiveAjax(),
                default              => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $batchModel = new Batch();
    $batches = $batchModel->getAll();

    $priceModel = new PriceCalculation();
    $batchIdsWithPrices = $priceModel->getBatchIdsWithPrices();

    $view = ROOT_PATH . 'app/views/dashboard/prices.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de precios no encontrada.';
        return;
    }
    require $view;
}

function get_prices(): void { checkModuleAuth(); prices_getPricesAjax(); }
function get_active(): void { checkModuleAuth(); prices_getActiveAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PRECIOS_CREATE'); prices_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PRECIOS_EDIT'); prices_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PRECIOS_DELETE'); prices_handleDelete(); }

function prices_handleAddEdit(string $mode): void
{
    $model = new PriceCalculation();
    $batchModel = new Batch();

    $idLote = (int)($_POST['id_lote'] ?? 0);
    if ($idLote <= 0) throw new \Exception('El lote es requerido.');
    if (!$batchModel->exists($idLote)) throw new \Exception('El lote seleccionado no existe.');

    $costoManoObra = isset($_POST['costo_mano_obra']) ? floatval($_POST['costo_mano_obra']) : 0;
    $costoTotalInsumo = isset($_POST['costo_total_insumo']) ? floatval($_POST['costo_total_insumo']) : 0;
    $porcentajeGanancia = isset($_POST['porcentaje_ganancia']) ? floatval($_POST['porcentaje_ganancia']) : 0;

    $precioFinalSugerido = isset($_POST['precio_final_sugerido']) ? floatval($_POST['precio_final_sugerido']) : 0;
    if ($precioFinalSugerido <= 0) throw new \Exception('El precio final sugerido debe ser mayor a cero.');

    $fechaCalculo = trim((string)($_POST['fecha_calculo'] ?? ''));
    if ($fechaCalculo === '') $fechaCalculo = date('Y-m-d');

    $vigente = (int)($_POST['vigente'] ?? 0);

    if ($mode === 'add') {
        if ($model->existsByBatch($idLote)) {
            throw new \Exception('Este lote ya tiene un cálculo de precio. Puede editarlo o eliminarlo, pero no crear otro.');
        }
        $model->add($idLote, $costoManoObra, $costoTotalInsumo, $porcentajeGanancia, $precioFinalSugerido, $fechaCalculo, $vigente);
        $newId = $model->getLastInsertId() ?? 0;

        if ($vigente) {
            $lote = $batchModel->getById($idLote);
            $idPlanta = $lote['id_planta'] ?? 0;
            if ($idPlanta > 0) {
                $model->setVigente($newId, (int)$idPlanta);
            }
        }

        AuditLog::record('CREATE', 'calculo_precio', $newId, null, compact('idLote', 'costoManoObra', 'costoTotalInsumo', 'porcentajeGanancia', 'precioFinalSugerido'));
        jsonResponse(['success' => true, 'message' => 'Cálculo de precio agregado correctamente', 'price' => ['id' => $newId]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    if ($model->existsByBatch($idLote, $id)) {
        throw new \Exception('El lote seleccionado ya tiene otro cálculo de precio.');
    }

    $oldData = $model->getById($id);
    $model->update($id, $idLote, $costoManoObra, $costoTotalInsumo, $porcentajeGanancia, $precioFinalSugerido, $fechaCalculo, $vigente);

    if ($vigente) {
        $lote = $batchModel->getById($idLote);
        $idPlanta = $lote['id_planta'] ?? 0;
        if ($idPlanta > 0) {
            $model->setVigente($id, (int)$idPlanta);
        }
    }

    AuditLog::record('UPDATE', 'calculo_precio', $id, $oldData, compact('idLote', 'costoManoObra', 'costoTotalInsumo', 'porcentajeGanancia', 'precioFinalSugerido'));
    jsonResponse(['success' => true, 'message' => 'Cálculo de precio actualizado correctamente', 'price' => ['id' => $id]]);
}

function prices_handleDelete(): void
{
    $model = new PriceCalculation();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el cálculo de precio');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'calculo_precio', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Cálculo de precio eliminado correctamente', 'priceId' => $id]);
}

function prices_getPricesAjax(): void
{
    $model = new PriceCalculation();
    $prices = $model->getAll();
    jsonResponse(['success' => true, 'prices' => $prices, 'count' => count($prices)]);
}

function prices_getActiveAjax(): void
{
    $model = new PriceCalculation();
    $active = $model->getActivePrices();
    jsonResponse(['success' => true, 'active' => $active]);
}
