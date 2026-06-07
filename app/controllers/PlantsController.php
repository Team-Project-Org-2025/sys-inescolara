<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Plant;
use SysInescolara\models\Species;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_plants'   => plants_getPlantsAjax(),
                'POST_add_ajax'    => plants_handleAddEdit('add'),
                'POST_edit_ajax'   => plants_handleAddEdit('edit'),
                'POST_delete_ajax' => plants_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $speciesModel = new Species();
    $species = $speciesModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/plants.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }
    require $view;
}

function get_plants(): void { checkModuleAuth(); plants_getPlantsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_CREATE'); plants_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_EDIT'); plants_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_DELETE'); plants_handleDelete(); }

function plants_handleAddEdit(string $mode): void
{
    $model = new Plant();
    $nombreComun = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombreComun === '') throw new \Exception('El nombre común es requerido.');
    $nombreTecnico = trim((string)($_POST['nombre_tecnico'] ?? ''));
    if ($nombreTecnico === '') $nombreTecnico = null;
    $especieId = !empty($_POST['especie_id']) ? (int)$_POST['especie_id'] : null;

    $imagen = null;
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/plants');
        $result = $uploader->upload($_FILES['imagen'], 'plant');
        if (!$result['success']) throw new \Exception(implode(', ', $result['errors']));
        $imagen = $result['data']['url'];
    }

    if ($mode === 'add') {
        $model->add($nombreComun, $nombreTecnico, $especieId, $imagen);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'plantas', $newId, null, compact('nombreComun', 'nombreTecnico', 'especieId', 'imagen'));
        jsonResponse(['success' => true, 'message' => 'Planta agregada correctamente', 'plant' => ['id' => $newId, 'nombre_comun' => $nombreComun, 'imagen' => $imagen]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    if ($imagen === null) {
        $oldData = $model->getById($id);
        $imagen = $oldData['imagen'] ?? null;
    } else {
        $oldData = $model->getById($id);
        if (!empty($oldData['imagen'])) {
            (new \SysInescolara\helpers\ImageUploader())->delete($oldData['imagen']);
        }
    }

    $model->update($id, $nombreComun, $nombreTecnico, $especieId, $imagen);
    AuditLog::record('UPDATE', 'plantas', $id, $oldData, compact('nombreComun', 'nombreTecnico', 'especieId', 'imagen'));
    jsonResponse(['success' => true, 'message' => 'Planta actualizada correctamente', 'plant' => ['id' => $id, 'imagen' => $imagen]]);
}

function plants_handleDelete(): void
{
    $model = new Plant();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la planta');

    $oldData = $model->getById($id);
    try {
        $model->delete($id);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000 && str_contains($e->getMessage(), '1451')) {
            jsonResponse(['success' => false, 'message' => 'No se puede eliminar esta planta porque tiene lotes asociados.', 'type' => 'foreign_key']);
            return;
        }
        throw $e;
    }
    if (!empty($oldData['imagen'])) {
        (new \SysInescolara\helpers\ImageUploader())->delete($oldData['imagen']);
    }
    AuditLog::record('DEACTIVATE', 'plantas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Planta desactivada correctamente', 'plantId' => $id]);
}

function plants_getPlantsAjax(): void
{
    $model = new Plant();
    jsonResponse(['success' => true, 'plants' => $model->getAll(), 'count' => 0]);
}
