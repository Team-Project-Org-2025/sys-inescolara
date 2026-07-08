<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Insumo;
use SysInescolara\models\UnidadMedida;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_supplies'  => get_supplies(),
                'POST_add_ajax'     => add_ajax(),
                'POST_edit_ajax'    => edit_ajax(),
                'POST_delete_ajax'  => delete_ajax(),
                default             => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $unidadMedidaModel = new UnidadMedida();
    $unidades = $unidadMedidaModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/insumos.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de insumos no encontrada.';
        return;
    }
    require $view;
}

function get_supplies(): void { checkModuleAuth(); supplies_getSuppliesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('insumos:crear'); supplies_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('insumos:editar'); supplies_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('insumos:eliminar'); supplies_handleDelete(); }

function supplies_handleAddEdit(string $mode): void
{
    $nombre = trim((string)($_POST['nombre_insumo'] ?? ''));
    if ($nombre === '') {
        throw new \Exception('El nombre del insumo es requerido.');
    }

    $idUnidadMedida = (int)($_POST['id_unidad_medida'] ?? 0);
    if ($idUnidadMedida <= 0) {
        throw new \Exception('La unidad de medida es requerida.');
    }

    $categoria = trim((string)($_POST['categoria'] ?? ''));
    if ($categoria === '') $categoria = null;

    $stock = isset($_POST['stock_actual']) ? floatval($_POST['stock_actual']) : null;
    if ($stock === null) {
        throw new \Exception('El stock actual es requerido.');
    }

    $costo = isset($_POST['costo_unitario_actual']) ? floatval($_POST['costo_unitario_actual']) : null;
    if ($costo === null) {
        throw new \Exception('El costo unitario es requerido.');
    }

    if ($mode === 'add') {
        $insumo = new Insumo([
            'nombre_insumo'          => $nombre,
            'id_unidad_medida'       => $idUnidadMedida,
            'categoria'              => $categoria,
            'stock_actual'           => $stock,
            'costo_unitario_actual'  => $costo,
        ]);
        if (!$insumo->save()) {
            throw new \Exception('Error al guardar el insumo.');
        }
        jsonResponse([
            'success' => true, 'message' => 'Insumo agregado correctamente',
            'supply' => [
                'id' => $insumo->getId(), 'nombre_insumo' => $nombre,
                'id_unidad_medida' => $idUnidadMedida, 'stock_actual' => $stock,
                'costo_unitario_actual' => $costo,
            ],
        ]);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $insumo = Insumo::find($id);
    if (!$insumo) throw new \Exception('No existe el insumo solicitado.');

    $insumo->setNombreInsumo($nombre)
           ->setIdUnidadMedida($idUnidadMedida)
           ->setCategoria($categoria)
           ->setStockActual($stock)
           ->setCostoUnitarioActual($costo);

    if (!$insumo->save()) {
        throw new \Exception('Error al actualizar el insumo.');
    }

    jsonResponse([
        'success' => true, 'message' => 'Insumo actualizado correctamente',
        'supply' => [
            'id' => $id, 'nombre_insumo' => $nombre,
            'id_unidad_medida' => $idUnidadMedida, 'stock_actual' => $stock,
            'costo_unitario_actual' => $costo,
        ],
    ]);
}

function supplies_handleDelete(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $insumo = Insumo::find($id);
    if (!$insumo) throw new \Exception('No existe el insumo');

    if (!$insumo->delete($id)) {
        throw new \Exception('Error al desactivar el insumo.');
    }

    jsonResponse(['success' => true, 'message' => 'Insumo desactivado correctamente', 'supplyId' => $id]);
}

function supplies_getSuppliesAjax(): void
{
    $model = new Insumo();
    $supplies = $model->getAll();
    jsonResponse(['success' => true, 'supplies' => $supplies, 'count' => count($supplies)]);
}
