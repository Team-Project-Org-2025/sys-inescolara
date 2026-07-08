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
    $nombreEspecie = trim((string)($_POST['nombre_especie'] ?? ''));
    if ($nombreEspecie === '') {
        throw new \Exception('El nombre de la especie es requerido.');
    }
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    if ($mode === 'add') {
        $especie = new Especie([
            'nombre_especie' => $nombreEspecie,
            'descripcion'    => $descripcion,
        ]);
        if (!$especie->save()) {
            throw new \Exception('Error al guardar la especie.');
        }
        jsonResponse([
            'success' => true,
            'message' => 'Especie agregada correctamente',
            'especie' => [
                'id'              => $especie->getId(),
                'nombre_especie'  => $especie->getNombreEspecie(),
                'descripcion'     => $especie->getDescripcion()
            ]
        ]);
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('ID inválido');
        }
        $especie = Especie::find($id);
        if (!$especie) {
            throw new \Exception('No existe la especie');
        }
        $especie->setNombreEspecie($nombreEspecie)
                ->setDescripcion($descripcion);
        if (!$especie->save()) {
            throw new \Exception('Error al actualizar la especie.');
        }
        jsonResponse([
            'success' => true,
            'message' => 'Especie actualizada correctamente',
            'especie' => [
                'id'              => $especie->getId(),
                'nombre_especie'  => $especie->getNombreEspecie(),
                'descripcion'     => $especie->getDescripcion()
            ]
        ]);
    }
}

function species_handleDelete(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new \Exception('ID inválido');
    }
    $especie = Especie::find($id);
    if (!$especie) {
        throw new \Exception('No existe la especie');
    }
    if (!$especie->delete($id)) {
        throw new \Exception('Error al desactivar la especie.');
    }
    jsonResponse(['success' => true, 'message' => 'Especie desactivada correctamente', 'especieId' => $id]);
}

function species_getSpeciesAjax(): void
{
    $model = new Especie();
    $especies = $model->getAll();
    jsonResponse(['success' => true, 'especies' => $especies, 'count' => count($especies)]);
}