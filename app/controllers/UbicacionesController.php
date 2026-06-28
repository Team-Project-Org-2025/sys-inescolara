<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Ubicacion;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_locations'  => get_locations(),
                'POST_add_ajax'      => add_ajax(),
                'POST_edit_ajax'     => edit_ajax(),
                'POST_delete_ajax'   => delete_ajax(),
                default              => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/ubicaciones.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ubicaciones no encontrada.';
        return;
    }
    require $view;
}

function get_locations(): void { checkModuleAuth(); locations_getLocationsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('ubicaciones:crear'); locations_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('ubicaciones:editar'); locations_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('ubicaciones:eliminar'); locations_handleDelete(); }

function locations_getLocationsAjax(): void
{
    $model = new Ubicacion();
    $locations = $model->getAll();
    jsonResponse(['success' => true, 'ubicaciones' => $locations, 'count' => count($locations)]);
}

function locations_handleAddEdit(string $mode): void
{
    $model = new Ubicacion();
    $nombreUbicacion = trim((string)($_POST['nombre_ubicacion'] ?? ''));
    if ($nombreUbicacion === '') {
        throw new \Exception('El nombre de la ubicación es requerido.');
    }
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;
    $tipo = trim((string)($_POST['tipo'] ?? ''));
    if ($tipo === '') $tipo = null;

    if ($mode === 'add') {
        $model->add($nombreUbicacion, $descripcion, $tipo);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse([
            'success' => true,
            'message' => 'Ubicación agregada correctamente',
            'ubicacion' => [
                'id' => $newId, 'nombre_ubicacion' => $nombreUbicacion,
                'descripcion' => $descripcion, 'tipo' => $tipo,
            ],
        ]);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    $data = $model->getById($id);
    if (!$data) throw new \Exception('La ubicación que intenta editar no existe.');
    $model->update($id, $nombreUbicacion, $descripcion, $tipo);
    jsonResponse([
        'success' => true,
        'message' => 'Ubicación actualizada correctamente',
        'ubicacion' => ['id' => $id, 'nombre_ubicacion' => $nombreUbicacion, 'descripcion' => $descripcion, 'tipo' => $tipo],
    ]);
}

function locations_handleDelete(): void
{
    $model = new Ubicacion();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID de ubicación inválido');
    $data = $model->getById($id);
    if (!$data) throw new \Exception('No existe la ubicación solicitada.');
    $model->delete($id);
    jsonResponse([
        'success' => true,
        'message' => 'Ubicación desactivada correctamente',
        'ubicacionId' => $id,
    ]);
}
