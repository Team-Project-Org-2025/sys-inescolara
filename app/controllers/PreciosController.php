<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\LotePrecio;
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
                'GET_get_prices'                  => get_prices(),
                'GET_get_detalle'                 => get_detalle(),
                'GET_get_lotes'                   => get_lotes(),
                'GET_get_insumos'                 => get_insumos(),
                'POST_actualizar_precio_ajax'     => actualizar_precio_ajax(),
                'POST_actualizar_ganancia_ajax'   => actualizar_ganancia_ajax(),
                default                           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
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
function actualizar_precio_ajax(): void { checkModuleAuth(); checkPermisoOrFail('precios:editar'); prices_actualizarPrecioAjax(); }
function actualizar_ganancia_ajax(): void { checkModuleAuth(); checkPermisoOrFail('precios:editar'); prices_actualizarGananciaAjax(); }

function prices_getPricesAjax(): void
{
    $prices = LotePrecio::getPreciosLotes();
    jsonResponse(['success' => true, 'prices' => $prices, 'count' => count($prices)]);
}

function prices_getDetalleAjax(): void
{
    $idLote = (int)($_GET['id_lote'] ?? 0);
    if ($idLote <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de lote inválido'], 400);
    }
    $detalles = LotePrecio::getDetalleInsumos($idLote);
    jsonResponse(['success' => true, 'detalles' => $detalles]);
}

function prices_getLotesAjax(): void
{
    $loteModel = new Lote();
    $lotes = $loteModel->all();
    $data = [];
    foreach ($lotes as $l) {
        $costoUnitario = (float)($l['costo_unitario'] ?? 0);
        $porcentajeGanancia = (float)($l['porcentaje_ganancia'] ?? 0);
        $precioFinal = $costoUnitario * (1 + $porcentajeGanancia / 100);

        $data[] = [
            'id_lote'               => (int)$l['id'],
            'id_planta'             => (int)($l['id_planta'] ?? 0),
            'planta_nombre'         => $l['planta_nombre'] ?? '',
            'categoria'             => $l['categoria_nombre'] ?? '',
            'cantidad_actual'       => (int)($l['cantidad_actual'] ?? 0),
            'costo_unitario'        => $costoUnitario,
            'porcentaje_ganancia'   => $porcentajeGanancia,
            'precio_final'          => $precioFinal,
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

function prices_actualizarPrecioAjax(): void
{
    $data = getRequestData();
    $idLote = (int)($data['id_lote'] ?? 0);
    $costoUnitario = (float)($data['costo_unitario'] ?? 0);

    if ($idLote <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de lote inválido'], 400);
    }
    if ($costoUnitario <= 0) {
        jsonResponse(['success' => false, 'message' => 'El costo unitario debe ser mayor a cero'], 400);
    }

    $resultado = LotePrecio::actualizarCostoUnitario($idLote, $costoUnitario);

    jsonResponse([
        'success' => $resultado,
        'message' => $resultado ? 'Costo unitario actualizado correctamente' : 'Error al actualizar el costo unitario',
    ]);
}

function prices_actualizarGananciaAjax(): void
{
    $data = getRequestData();
    $idLote = (int)($data['id_lote'] ?? 0);
    $porcentajeGanancia = (float)($data['porcentaje_ganancia'] ?? 0);

    if ($idLote <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de lote inválido'], 400);
    }
    if ($porcentajeGanancia < 0) {
        jsonResponse(['success' => false, 'message' => 'El porcentaje de ganancia no puede ser negativo'], 400);
    }

    $resultado = LotePrecio::actualizarPorcentajeGanancia($idLote, $porcentajeGanancia);

    jsonResponse([
        'success' => $resultado,
        'message' => $resultado ? 'Porcentaje de ganancia actualizado correctamente' : 'Error al actualizar el porcentaje de ganancia',
    ]);
}
