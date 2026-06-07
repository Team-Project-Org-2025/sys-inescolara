<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\PriceCalculation;
use SysInescolara\models\Batch;
use SysInescolara\models\Plant;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_prices'     => prices_getPricesAjax(),
                'POST_add_ajax'      => prices_handleAddEdit('add'),
                'POST_edit_ajax'     => prices_handleAddEdit('edit'),
                'POST_delete_ajax'   => prices_handleDelete(),
                'GET_get_active'     => prices_getActiveAjax(),
                'GET_get_costo_insumos'   => prices_getCostoInsumosAjax(),
                'GET_calcular_por_planta' => prices_calcularPorPlantaAjax(),
                'POST_guardar_por_planta' => prices_guardarPorPlantaAjax(),
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

    $plantModel = new Plant();
    $plants = $plantModel->getAll();

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
function get_costo_insumos(): void { checkModuleAuth(); prices_getCostoInsumosAjax(); }
function calcular_por_planta(): void { checkModuleAuth(); prices_calcularPorPlantaAjax(); }
function guardar_por_planta(): void { checkModuleAuth(); checkPermisoOrFail('PRECIOS_CREATE'); prices_guardarPorPlantaAjax(); }
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

function prices_getCostoInsumosAjax(): void
{
    $idLote = (int)($_GET['id_lote'] ?? 0);
    if ($idLote <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de lote inválido'], 400);
    }
    $model = new PriceCalculation();
    $total = $model->getCostoInsumosByLote($idLote);
    jsonResponse(['success' => true, 'costo_total_insumo' => $total]);
}

function prices_calcularPorPlantaAjax(): void
{
    $idPlanta = (int)($_GET['id_planta'] ?? 0);
    $categoria = !empty($_GET['categoria']) ? $_GET['categoria'] : null;
    if ($idPlanta <= 0) {
        jsonResponse(['success' => false, 'message' => 'Seleccione una planta.'], 400);
    }
    $model = new PriceCalculation();
    $result = $model->calcularCostoPorPlanta($idPlanta, $categoria);
    jsonResponse(['success' => true, 'data' => $result]);
}

function prices_guardarPorPlantaAjax(): void
{
    $data = getRequestData();
    $idPlanta = (int)($data['id_planta'] ?? 0);
    $porcentajeGanancia = (float)($data['porcentaje_ganancia'] ?? 0);
    $categoria = !empty($data['categoria']) ? $data['categoria'] : null;
    $precioFinalSugerido = (float)($data['precio_final_sugerido'] ?? 0);
    $fechaCalculo = $data['fecha_calculo'] ?? date('Y-m-d');
    $vigente = (int)($data['vigente'] ?? 0);
    $loteIds = isset($data['lote_ids']) ? (array)$data['lote_ids'] : [];
    $loteCostos = isset($data['lote_costos']) && is_array($data['lote_costos']) ? $data['lote_costos'] : [];

    if ($idPlanta <= 0 || $precioFinalSugerido <= 0) {
        jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
    }

    $model = new PriceCalculation();
    $batchModel = new Batch();
    $saved = 0;

    foreach ($loteIds as $idLote) {
        $idLote = (int)$idLote;
        if ($idLote <= 0) continue;

        $costoTotal = isset($loteCostos[$idLote]) ? (float)$loteCostos[$idLote] : 0;

        if ($model->existsByBatch($idLote)) {
            $existing = $model->getAll();
            $existingId = null;
            foreach ($existing as $e) {
                if ((int)$e['id_lote'] === $idLote) {
                    $existingId = (int)$e['id'];
                    break;
                }
            }
            if ($existingId) {
                $model->update($existingId, $idLote, 0, $costoTotal, $porcentajeGanancia, $precioFinalSugerido, $fechaCalculo, $vigente);
            }
        } else {
            $model->add($idLote, 0, $costoTotal, $porcentajeGanancia, $precioFinalSugerido, $fechaCalculo, $vigente);
            $existingId = $model->getLastInsertId() ?? 0;
        }

        if ($vigente && $existingId) {
            $model->setVigente($existingId, $idPlanta);
        }
        $saved++;
    }

    AuditLog::record('CREATE', 'calculo_precio_por_planta', $idPlanta, null, [
        'lotes' => $saved, 'ganancia' => $porcentajeGanancia, 'precio' => $precioFinalSugerido,
    ]);

    jsonResponse(['success' => true, 'message' => "Precio guardado para $saved lote(s) correctamente."]);
}
