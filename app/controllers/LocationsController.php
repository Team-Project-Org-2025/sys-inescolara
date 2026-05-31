<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Location;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_locations'  => locations_getLocationsAjax(),
                'POST_add_ajax'      => locations_handleAddEdit('add'),
                'POST_edit_ajax'     => locations_handleAddEdit('edit'),
                'POST_delete_ajax'   => locations_handleDelete(),
                default              => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/locations.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ubicaciones no encontrada.';
        return;
    }
    require $view;
}

function get_locations(): void { checkModuleAuth(); locations_getLocationsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('UBICACIONES_CREATE'); locations_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('UBICACIONES_EDIT'); locations_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('UBICACIONES_DELETE'); locations_handleDelete(); }

function locations_getLocationsAjax(): void
{
    $model = new Location();
    $locations = $model->getAll();
    jsonResponse(['success' => true, 'locations' => $locations, 'count' => count($locations)]);
}

function locations_handleAddEdit(string $mode): void
{
    $model = new Location();
    $nombreUbicacion = trim((string)($_POST['nombre_ubicacion'] ?? ''));
    if ($nombreUbicacion === '') {
        throw new \Exception('El nombre de la ubicación es requerido.');
    }
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;
    $zona = trim((string)($_POST['zona'] ?? ''));
    if ($zona === '') $zona = null;

    if ($mode === 'add') {
        $model->add($nombreUbicacion, $descripcion, $zona);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'ubicacion', $newId, null, [
            'nombre_ubicacion' => $nombreUbicacion, 'descripcion' => $descripcion, 'zona' => $zona,
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Ubicación agregada correctamente',
            'location' => [
                'id' => $newId, 'nombre_ubicacion' => $nombreUbicacion,
                'descripcion' => $descripcion, 'zona' => $zona,
            ],
        ]);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    $oldData = $model->getById($id);
    if (!$oldData) throw new \Exception('La ubicación que intenta editar no existe.');
    $model->update($id, $nombreUbicacion, $descripcion, $zona);
    AuditLog::record('UPDATE', 'ubicacion', $id, $oldData, [
        'nombre_ubicacion' => $nombreUbicacion, 'descripcion' => $descripcion, 'zona' => $zona,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Ubicación actualizada correctamente',
        'location' => ['id' => $id, 'nombre_ubicacion' => $nombreUbicacion, 'descripcion' => $descripcion, 'zona' => $zona],
    ]);
}

function locations_handleDelete(): void
{
    $model = new Location();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID de ubicación inválido');
    $oldData = $model->getById($id);
    if (!$oldData) throw new \Exception('No existe la ubicación solicitada.');
    $model->delete($id);
    AuditLog::record('DELETE', 'ubicacion', $id, $oldData, null);
    jsonResponse([
        'success' => true,
        'message' => 'Ubicación eliminada correctamente',
        'locationId' => $id,
    ]);
}
