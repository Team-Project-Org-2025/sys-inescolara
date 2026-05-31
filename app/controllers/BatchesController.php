<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Batch;
use SysInescolara\models\Plant;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_batches'  => batches_getBatchesAjax(),
                'POST_add_ajax'    => batches_handleAddEdit('add'),
                'POST_edit_ajax'   => batches_handleAddEdit('edit'),
                'POST_delete_ajax' => batches_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $plantModel = new Plant();
    $plants = $plantModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/batches.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de lotes no encontrada.';
        return;
    }
    require $view;
}

function get_batches(): void { checkModuleAuth(); batches_getBatchesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_CREATE'); batches_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_EDIT'); batches_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_DELETE'); batches_handleDelete(); }

function batches_handleAddEdit(string $mode): void
{
    $model = new Batch();
    $id_planta = (int)($_POST['id_planta'] ?? 0);
    $fecha_siembra = trim((string)($_POST['fecha_siembra'] ?? ''));
    $cantidad_inicial = (int)($_POST['cantidad_inicial'] ?? 0);
    $cantidad_actual = (int)($_POST['cantidad_actual'] ?? 0);
    $estado = trim((string)($_POST['estado'] ?? ''));
    $origen = trim((string)($_POST['origen'] ?? ''));
    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    if ($id_planta <= 0) throw new \Exception('Selecciona una planta.');
    if ($fecha_siembra === '') throw new \Exception('La fecha de siembra es requerida.');
    if ($cantidad_inicial <= 0) throw new \Exception('La cantidad inicial debe ser mayor a 0.');
    if ($cantidad_actual < 0) throw new \Exception('La cantidad actual no puede ser negativa.');
    if ($estado === '') $estado = 'Activo';

    $imagen = null;
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/batches');
        $result = $uploader->upload($_FILES['imagen'], 'batch');
        if (!$result['success']) {
            throw new \Exception(implode(', ', $result['errors']));
        }
        $imagen = $result['data']['url'];
    }

    if ($mode === 'add') {
        $model->add($id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $origen, $observacion, $imagen);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'lote', $newId, null, [
            'id_planta' => $id_planta, 'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial, 'cantidad_actual' => $cantidad_actual,
            'estado' => $estado, 'origen' => $origen, 'observacion' => $observacion, 'imagen' => $imagen,
        ]);
        jsonResponse([
            'success' => true, 'message' => 'Lote agregado correctamente',
            'batch' => ['id' => $newId, 'id_planta' => $id_planta, 'imagen' => $imagen],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    if ($imagen === null) {
        $oldData = $model->getById($id);
        $imagen = $oldData['imagen'] ?? null;
    } else {
        $oldData = $model->getById($id);
        if (!empty($oldData['imagen'])) {
            $uploader = new \SysInescolara\helpers\ImageUploader();
            $uploader->delete($oldData['imagen']);
        }
    }

    $model->update($id, $id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $origen, $observacion, $imagen);
    AuditLog::record('UPDATE', 'lote', $id, $oldData, [
        'id_planta' => $id_planta, 'fecha_siembra' => $fecha_siembra,
        'cantidad_inicial' => $cantidad_inicial, 'cantidad_actual' => $cantidad_actual,
        'estado' => $estado, 'origen' => $origen, 'observacion' => $observacion, 'imagen' => $imagen,
    ]);
    jsonResponse([
        'success' => true, 'message' => 'Lote actualizado correctamente',
        'batch' => ['id' => $id, 'imagen' => $imagen],
    ]);
}

function batches_handleDelete(): void
{
    $model = new Batch();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el lote');

    $oldData = $model->getById($id);
    if (!empty($oldData['imagen'])) {
        $uploader = new \SysInescolara\helpers\ImageUploader();
        $uploader->delete($oldData['imagen']);
    }

    $model->delete($id);
    AuditLog::record('DELETE', 'lote', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Lote eliminado correctamente', 'batchId' => $id]);
}

function batches_getBatchesAjax(): void
{
    $model = new Batch();
    $batches = $model->getAll();
    jsonResponse(['success' => true, 'batches' => $batches, 'count' => count($batches)]);
}
