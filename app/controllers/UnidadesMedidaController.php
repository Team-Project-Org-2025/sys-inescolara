<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\UnidadMedida;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_units'     => get_units(),
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

    $view = ROOT_PATH . 'app/views/dashboard/unidades-medida.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de unidades de medida no encontrada.';
        return;
    }
    require $view;
}

function get_units(): void { checkModuleAuth(); units_getUnitsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('unidades_medida:crear'); units_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('unidades_medida:editar'); units_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('unidades_medida:eliminar'); units_handleDelete(); }

function units_handleAddEdit(string $mode): void
{
    $model = new UnidadMedida();
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    if ($nombre === '') throw new \Exception('El nombre de la unidad es requerido.');

    if ($mode === 'add') {
        $model->add($nombre);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'unidad_medida', $newId, null, ['nombre' => $nombre]);
        jsonResponse(['success' => true, 'message' => 'Unidad agregada correctamente', 'unit' => ['id' => $newId, 'nombre_unidad_medida' => $nombre]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $nombre);
    AuditLog::record('UPDATE', 'unidad_medida', $id, $oldData, ['nombre' => $nombre]);
    jsonResponse(['success' => true, 'message' => 'Unidad actualizada correctamente', 'unit' => ['id' => $id, 'nombre_unidad_medida' => $nombre]]);
}

function units_handleDelete(): void
{
    $model = new UnidadMedida();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la unidad de medida');

    try {
        $oldData = $model->getById($id);
        $model->delete($id);
        AuditLog::record('DEACTIVATE', 'unidad_medida', $id, $oldData, null);
        jsonResponse(['success' => true, 'message' => 'Unidad desactivada correctamente', 'unitId' => $id]);
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1451')) {
            throw new \Exception('No se puede eliminar la unidad porque está siendo usada por uno o más insumos.');
        }
        throw $e;
    }
}

function units_getUnitsAjax(): void
{
    $model = new UnidadMedida();
    $units = $model->getAll();
    jsonResponse(['success' => true, 'units' => $units, 'count' => count($units)]);
}
