<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\UnidadMedida;

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
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    if ($nombre === '') throw new \Exception('El nombre de la unidad es requerido.');

    if ($mode === 'add') {
        $unit = new UnidadMedida([
            'nombre_unidad_medida' => $nombre,
        ]);
        if (!$unit->save()) {
            throw new \Exception('Error al guardar la unidad de medida.');
        }
        jsonResponse([
            'success' => true, 'message' => 'Unidad agregada correctamente',
            'unit' => ['id' => $unit->getId(), 'nombre_unidad_medida' => $nombre],
        ]);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $unit = UnidadMedida::find($id);
    if (!$unit) throw new \Exception('No existe la unidad de medida solicitada.');

    $unit->setNombreUnidadMedida($nombre);
    if (!$unit->save()) {
        throw new \Exception('Error al actualizar la unidad de medida.');
    }

    jsonResponse([
        'success' => true, 'message' => 'Unidad actualizada correctamente',
        'unit' => ['id' => $id, 'nombre_unidad_medida' => $nombre],
    ]);
}

function units_handleDelete(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $unit = UnidadMedida::find($id);
    if (!$unit) throw new \Exception('No existe la unidad de medida');

    try {
        if (!$unit->delete($id)) {
            throw new \Exception('Error al desactivar la unidad de medida.');
        }
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
