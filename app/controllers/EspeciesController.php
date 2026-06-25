<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Especie;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_species'  => get_species(),
                'POST_add_ajax'    => add_ajax(),
                'POST_edit_ajax'   => edit_ajax(),
                'POST_delete_ajax' => delete_ajax(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/especies.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de especies no encontrada.';
        return;
    }
    require $view;
}

function get_species(): void { checkModuleAuth(); species_getSpeciesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('especies:crear'); species_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('especies:editar'); species_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('especies:eliminar'); species_handleDelete(); }

function species_handleAddEdit(string $mode): void
{
    $model = new Especie();
    $nombreEspecie = trim((string)($_POST['nombre_especie'] ?? ''));
    if ($nombreEspecie === '') throw new \Exception('El nombre de la especie es requerido.');
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    if ($mode === 'add') {
        $model->add($nombreEspecie, $descripcion);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse(['success' => true, 'message' => 'Especie agregada correctamente', 'especie' => ['id' => $newId, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model->update($id, $nombreEspecie, $descripcion);
    jsonResponse(['success' => true, 'message' => 'Especie actualizada correctamente', 'especie' => ['id' => $id, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
}

function species_handleDelete(): void
{
    $model = new Especie();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la especie');

    if ($model->hasActivePlants($id)) {
        jsonResponse([
            'success' => false,
            'message' => 'No se puede eliminar la especie porque tiene plantas activas asociadas.',
        ]);
        return;
    }

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Especie desactivada correctamente', 'especieId' => $id]);
}

function species_getSpeciesAjax(): void
{
    $model = new Especie();
    jsonResponse(['success' => true, 'especies' => $model->getAll(), 'count' => 0]);
}
