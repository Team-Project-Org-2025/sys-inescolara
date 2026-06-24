<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Inventory;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_consolidated'  => get_consolidated(),
                default                 => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/inventario.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inventario no encontrada.';
        return;
    }
    require $view;
}

function get_consolidated(): void { checkModuleAuth(); inventory_getConsolidatedAjax(); }

function inventory_getConsolidatedAjax(): void
{
    $model = new Inventory();
    $data = $model->getConsolidated();
    jsonResponse(['success' => true, 'data' => $data, 'count' => count($data)]);
}
