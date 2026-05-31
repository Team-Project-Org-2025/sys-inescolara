<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Species;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_species'  => species_getSpeciesAjax(),
                'POST_add_ajax'    => species_handleAddEdit('add'),
                'POST_edit_ajax'   => species_handleAddEdit('edit'),
                'POST_delete_ajax' => species_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/species.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de especies no encontrada.';
        return;
    }
    require $view;
}

function get_species(): void { checkModuleAuth(); species_getSpeciesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_CREATE'); species_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_EDIT'); species_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_DELETE'); species_handleDelete(); }

function species_handleAddEdit(string $mode): void
{
    $model = new Species();
    $nombreEspecie = trim((string)($_POST['nombre_especie'] ?? ''));
    if ($nombreEspecie === '') throw new \Exception('El nombre de la especie es requerido.');
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    if ($mode === 'add') {
        $model->add($nombreEspecie, $descripcion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'especie', $newId, null, compact('nombreEspecie', 'descripcion'));
        jsonResponse(['success' => true, 'message' => 'Especie agregada correctamente', 'species' => ['id' => $newId, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $nombreEspecie, $descripcion);
    AuditLog::record('UPDATE', 'especie', $id, $oldData, compact('nombreEspecie', 'descripcion'));
    jsonResponse(['success' => true, 'message' => 'Especie actualizada correctamente', 'species' => ['id' => $id, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
}

function species_handleDelete(): void
{
    $model = new Species();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la especie');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'especie', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Especie eliminada correctamente', 'speciesId' => $id]);
}

function species_getSpeciesAjax(): void
{
    $model = new Species();
    jsonResponse(['success' => true, 'species' => $model->getAll(), 'count' => 0]);
}
