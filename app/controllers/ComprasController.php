<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Purchase;
use SysInescolara\models\Supplier;
use SysInescolara\models\Supplies;
use SysInescolara\models\Location;
use SysInescolara\models\Plant;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_compras'      => compras_getComprasAjax(),
                'GET_get_details'      => compras_getDetailsAjax(),
                'POST_add_ajax'        => compras_handleAddEdit('add'),
                'POST_edit_ajax'       => compras_handleAddEdit('edit'),
                'POST_delete_ajax'     => compras_handleDelete(),
                'POST_completar_ajax'  => compras_handleCompletar(),
                'POST_cancelar_ajax'   => compras_handleCancelar(),
                'POST_quick_add_planta' => compras_quickAddPlanta(),
                default                => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $supplierModel = new Supplier();
    $proveedores = $supplierModel->getAll();
    $supplyModel = new Supplies();
    $insumos = $supplyModel->getAll();
    $locationModel = new Location();
    $ubicaciones = $locationModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/compras.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de compras no encontrada.';
        return;
    }
    require $view;
}

function get_compras(): void { checkModuleAuth(); compras_getComprasAjax(); }
function get_details(): void { checkModuleAuth(); compras_getDetailsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('COMPRAS_CREATE'); compras_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('COMPRAS_EDIT'); compras_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('COMPRAS_DELETE'); compras_handleDelete(); }
function completar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('COMPRAS_COMPLETE'); compras_handleCompletar(); }
function cancelar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('COMPRAS_COMPLETE'); compras_handleCancelar(); }
function quick_add_planta(): void { checkModuleAuth(); compras_quickAddPlanta(); }

function compras_handleAddEdit(string $mode): void
{
    $model = new Purchase();

    $idProveedor = (int)($_POST['id_proveedor'] ?? 0);
    if ($idProveedor <= 0) throw new \Exception('El proveedor es requerido.');

    $fechaCompra = trim((string)($_POST['fecha_compra'] ?? ''));
    if ($fechaCompra === '') throw new \Exception('La fecha es requerida.');

    $tipoComprobante = trim((string)($_POST['tipo_comprobante'] ?? ''));
    if ($tipoComprobante === '') $tipoComprobante = 'Factura';

    $numeroComprobante = trim((string)($_POST['numero_comprobante'] ?? ''));
    if ($numeroComprobante === '') $numeroComprobante = null;

    $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
    $iva = isset($_POST['iva']) ? floatval($_POST['iva']) : 0;
    $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

    if ($subtotal < 0 || $iva < 0 || $total <= 0) throw new \Exception('Valores inválidos.');

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    if (!is_array($items) || empty($items)) throw new \Exception('Debe agregar al menos un item.');

    if ($mode === 'add') {
        $model->add($idProveedor, $fechaCompra, $tipoComprobante, $numeroComprobante, $subtotal, $iva, $total, $observacion);
        $newId = $model->getLastInsertId() ?? 0;

        foreach ($items as $item) {
            $tipoItem = $item['tipo_item'] ?? '';
            $idItem = (int)($item['id_item'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);
            $costoUnitario = (float)($item['costo_unitario'] ?? 0);
            $subtotalItem = $cantidad * $costoUnitario;
            $categoriaLote = $tipoItem === 'planta' ? ($item['categoria_lote'] ?? null) : null;
            $idUbicacionItem = $tipoItem === 'planta' ? (!empty($item['id_ubicacion']) ? (int)$item['id_ubicacion'] : null) : null;
            $model->addDetail($newId, $tipoItem, $idItem, $cantidad, $costoUnitario, $subtotalItem, $categoriaLote, $idUbicacionItem);
        }

        AuditLog::record('CREATE', 'compra', $newId, null, [
            'id_proveedor' => $idProveedor, 'total' => $total, 'items' => count($items),
        ]);

        jsonResponse(['success' => true, 'message' => 'Compra registrada correctamente.', 'id' => $newId]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $idProveedor, $fechaCompra, $tipoComprobante, $numeroComprobante, $subtotal, $iva, $total, $observacion);

    $model->deleteDetails($id);
    foreach ($items as $item) {
        $tipoItem = $item['tipo_item'] ?? '';
        $idItem = (int)($item['id_item'] ?? 0);
        $cantidad = (float)($item['cantidad'] ?? 0);
        $costoUnitario = (float)($item['costo_unitario'] ?? 0);
        $subtotalItem = $cantidad * $costoUnitario;
        $categoriaLote = $tipoItem === 'planta' ? ($item['categoria_lote'] ?? null) : null;
        $idUbicacionItem = $tipoItem === 'planta' ? (!empty($item['id_ubicacion']) ? (int)$item['id_ubicacion'] : null) : null;
        $model->addDetail($id, $tipoItem, $idItem, $cantidad, $costoUnitario, $subtotalItem, $categoriaLote, $idUbicacionItem);
    }

    AuditLog::record('UPDATE', 'compra', $id, $oldData, [
        'id_proveedor' => $idProveedor, 'total' => $total, 'items' => count($items),
    ]);

    jsonResponse(['success' => true, 'message' => 'Compra actualizada correctamente.']);
}

function compras_handleDelete(): void
{
    $model = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la compra');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DEACTIVATE', 'compra', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Compra eliminada correctamente.']);
}

function compras_handleCompletar(): void
{
    $model = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la compra');

    $compra = $model->getById($id);
    if ($compra['estado'] !== 'pendiente') throw new \Exception('Solo se pueden completar compras pendientes.');

    $model->aplicarStock($id);
    $model->updateEstado($id, 'completada');

    AuditLog::record('UPDATE', 'compra', $id, ['estado' => 'pendiente'], ['estado' => 'completada', 'stock_aplicado' => true]);
    jsonResponse(['success' => true, 'message' => 'Compra completada y stock actualizado.']);
}

function compras_handleCancelar(): void
{
    $model = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la compra');

    $compra = $model->getById($id);
    if ($compra['estado'] !== 'pendiente') throw new \Exception('Solo se pueden cancelar compras pendientes.');

    $model->updateEstado($id, 'cancelada');

    AuditLog::record('UPDATE', 'compra', $id, ['estado' => 'pendiente'], ['estado' => 'cancelada']);
    jsonResponse(['success' => true, 'message' => 'Compra cancelada.']);
}

function compras_getComprasAjax(): void
{
    $model = new Purchase();
    $compras = $model->getAll();
    jsonResponse(['success' => true, 'compras' => $compras, 'count' => count($compras)]);
}

function compras_getDetailsAjax(): void
{
    $idCompra = (int)($_GET['id_compra'] ?? 0);
    if ($idCompra <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de compra inválido.'], 400);
        return;
    }
    $model = new Purchase();
    $details = $model->getDetails($idCompra);
    $compra = $model->getById($idCompra);
    jsonResponse(['success' => true, 'details' => $details, 'compra' => $compra]);
}

function compras_quickAddPlanta(): void
{
    $nombre = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombre === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre de la planta es requerido.'], 400);
        return;
    }

    $model = new Plant();
    $model->add($nombre, $nombre);
    $newId = $model->getLastInsertId() ?? 0;

    if ($newId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Error al crear la planta.'], 500);
        return;
    }

    $planta = $model->getById($newId);
    AuditLog::record('CREATE', 'planta', $newId, null, ['nombre_comun' => $nombre, 'origen' => 'compra_rapida']);

    jsonResponse([
        'success' => true,
        'message' => 'Planta creada correctamente.',
        'planta' => ['id' => $planta['id_planta'] ?? $newId, 'nombre_comun' => $planta['nombre_comun'] ?? $nombre],
    ]);
}
