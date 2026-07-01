<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\CalculoPrecio;
use SysInescolara\models\Insumo;
use SysInescolara\models\Lote;
use SysInescolara\models\Planta;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_prices'    => get_prices(),
                'GET_get_detalle'   => get_detalle(),
                'GET_get_lotes'     => get_lotes(),
                'GET_get_insumos'   => get_insumos(),
                'POST_add_ajax'     => add_ajax(),
                'POST_edit_ajax'    => edit_ajax(),
                'POST_delete_ajax'  => delete_ajax(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $plantModel = new Planta();
    $plants = $plantModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/precios.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de precios no encontrada.';
        return;
    }
    require $view;
}

function get_prices(): void { checkModuleAuth(); prices_getPricesAjax(); }
function get_detalle(): void { checkModuleAuth(); prices_getDetalleAjax(); }
function get_lotes(): void { checkModuleAuth(); prices_getLotesAjax(); }
function get_insumos(): void { checkModuleAuth(); prices_getInsumosAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('precios:crear'); prices_handleAdd(); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('precios:editar'); prices_handleEdit(); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('precios:eliminar'); prices_handleDelete(); }

function prices_handleAdd(): void
{
    $data = getRequestData();

    $idLote = (int)($data['id_lote'] ?? 0);
    $precioPlantaBase = (float)($data['precio_planta_base'] ?? 0);
    $porcentajeGanancia = (float)($data['porcentaje_ganancia'] ?? 0);
    $costoTotalInsumo = (float)($data['costo_total_insumo'] ?? 0);
    $precioFinalSugerido = (float)($data['precio_final_sugerido'] ?? 0);
    $detalles = $data['detalles'] ?? [];

    if ($idLote <= 0 || $precioPlantaBase <= 0) {
        jsonResponse(['success' => false, 'message' => 'Datos inválidos: lote y precio base requeridos.'], 400);
    }

    $loteModel = new Lote();
    if (!$loteModel->exists($idLote)) {
        jsonResponse(['success' => false, 'message' => 'El lote seleccionado no existe.'], 400);
    }

    $fechaCalculo = !empty($data['fecha_calculo']) ? $data['fecha_calculo'] : date('Y-m-d');

    $precioFinal = $precioFinalSugerido > 0
        ? $precioFinalSugerido
        : ($precioPlantaBase + $costoTotalInsumo) * (1 + $porcentajeGanancia / 100);

    $model = new CalculoPrecio([
        'id_lote'              => $idLote,
        'precio_planta_base'   => $precioPlantaBase,
        'costo_total_insumo'   => $costoTotalInsumo,
        'porcentaje_ganancia'  => $porcentajeGanancia,
        'precio_final_sugerido'=> $precioFinal,
        'fecha_calculo'        => $fechaCalculo,
        'vigente'              => 1,
    ]);

    if (!$model->save()) {
        jsonResponse(['success' => false, 'message' => 'Error al guardar el cálculo.'], 500);
    }

    $idCalculo = $model->getId();

    if (!empty($detalles) && is_array($detalles)) {
        foreach ($detalles as $d) {
            $idInsumo = (int)($d['id_insumo'] ?? 0);
            $monto = (float)($d['monto'] ?? 0);
            if ($idInsumo > 0 && $monto > 0) {
                $model->addDetalle($idCalculo, $idInsumo, $monto);
            }
        }
        $model->recalcularTotalInsumo($idCalculo);
    }

    jsonResponse(['success' => true, 'message' => 'Cálculo de precio creado correctamente', 'id' => $idCalculo]);
}

function prices_handleEdit(): void
{
    $data = getRequestData();

    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $calc = CalculoPrecio::find($id);
    if (!$calc) throw new \Exception('No existe el cálculo de precio solicitado.');

    $idLote = (int)($data['id_lote'] ?? 0);
    $precioPlantaBase = (float)($data['precio_planta_base'] ?? 0);
    $porcentajeGanancia = (float)($data['porcentaje_ganancia'] ?? 0);
    $costoTotalInsumo = (float)($data['costo_total_insumo'] ?? 0);
    $precioFinalSugerido = (float)($data['precio_final_sugerido'] ?? 0);
    $detalles = $data['detalles'] ?? [];

    if ($idLote <= 0) throw new \Exception('El lote es requerido.');
    if ($precioFinalSugerido <= 0) throw new \Exception('El precio final sugerido debe ser mayor a cero.');

    $fechaCalculo = !empty($data['fecha_calculo']) ? $data['fecha_calculo'] : date('Y-m-d');

    $calc->setIdLote($idLote)
         ->setPrecioPlantaBase($precioPlantaBase)
         ->setCostoTotalInsumo($costoTotalInsumo)
         ->setPorcentajeGanancia($porcentajeGanancia)
         ->setPrecioFinalSugerido($precioFinalSugerido)
         ->setFechaCalculo($fechaCalculo);

    if (!$calc->save()) {
        throw new \Exception('Error al actualizar el cálculo de precio.');
    }

    $calc->saveDetalles($id, $detalles);
    $calc->recalcularTotalInsumo($id);

    jsonResponse(['success' => true, 'message' => 'Cálculo de precio actualizado correctamente', 'id' => $id]);
}

function prices_handleDelete(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $calc = CalculoPrecio::find($id);
    if (!$calc) throw new \Exception('No existe el cálculo de precio');

    if (!$calc->delete($id)) {
        throw new \Exception('Error al desactivar el cálculo de precio.');
    }

    jsonResponse(['success' => true, 'message' => 'Cálculo de precio desactivado correctamente', 'priceId' => $id]);
}

function prices_getPricesAjax(): void
{
    $model = new CalculoPrecio();
    $prices = $model->getAll();
    jsonResponse(['success' => true, 'prices' => $prices, 'count' => count($prices)]);
}

function prices_getDetalleAjax(): void
{
    $idCalculo = (int)($_GET['id_calculo'] ?? 0);
    if ($idCalculo <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    $model = new CalculoPrecio();
    $detalles = $model->getDetalles($idCalculo);
    jsonResponse(['success' => true, 'detalles' => $detalles]);
}

function prices_getLotesAjax(): void
{
    $loteModel = new Lote();
    $lotes = $loteModel->all();
    $data = [];
    foreach ($lotes as $l) {
        $data[] = [
            'id_lote'        => (int)$l['id'],
            'id_planta'      => (int)($l['id_planta'] ?? 0),
            'planta_nombre'  => $l['planta_nombre'] ?? '',
            'categoria'      => $l['categoria_nombre'] ?? '',
            'cantidad_actual'=> (int)($l['cantidad_actual'] ?? 0),
        ];
    }
    jsonResponse(['success' => true, 'lotes' => $data]);
}

function prices_getInsumosAjax(): void
{
    $insumoModel = new Insumo();
    $insumos = $insumoModel->getAll();
    $data = [];
    foreach ($insumos as $i) {
        $data[] = [
            'id_insumo'      => (int)$i['id_insumo'],
            'nombre_insumo'  => $i['nombre_insumo'] ?? '',
            'simbolo'        => $i['simbolo'] ?? '',
            'costo_unitario' => (float)($i['costo_unitario_actual'] ?? 0),
        ];
    }
    jsonResponse(['success' => true, 'insumos' => $data]);
}
