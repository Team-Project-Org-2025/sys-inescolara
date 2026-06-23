<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Proveedor;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_suppliers' => suppliers_getSuppliersAjax(),
                'POST_add_ajax'    => suppliers_handleAddEdit('add'),
                'POST_edit_ajax'   => suppliers_handleAddEdit('edit'),
                'POST_delete_ajax' => suppliers_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/proveedores.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de proveedores no encontrada.';
        return;
    }
    require $view;
}

function get_suppliers(): void { checkModuleAuth(); suppliers_getSuppliersAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PROVEEDORES_CREATE'); suppliers_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PROVEEDORES_EDIT'); suppliers_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PROVEEDORES_DELETE'); suppliers_handleDelete(); }

function suppliers_handleAddEdit(string $mode): void
{
    $model = new Proveedor();
    $nombre = trim((string)($_POST['nombre_proveedor'] ?? ''));
    if ($nombre === '') throw new \Exception('El nombre del proveedor es requerido.');
    $rif = trim((string)($_POST['rif_proveedor'] ?? ''));
    if ($rif === '') $rif = null;

    if ($rif !== null) {
        $existing = $model->getByRif($rif);
        $idActual = (int)($_POST['id'] ?? 0);
        if ($existing && $existing['id'] !== $idActual) {
            throw new \Exception('El RIF ingresado ya está registrado en otro proveedor.');
        }
    }
    $contacto = trim((string)($_POST['contacto_vendedor'] ?? ''));
    if ($contacto === '') $contacto = null;
    $telefono = trim((string)($_POST['telefono_proveedor'] ?? ''));
    if ($telefono === '') $telefono = null;

    if ($mode === 'add') {
        $model->add($nombre, $rif, $contacto, $telefono);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse(['success' => true, 'message' => 'Proveedor agregado correctamente', 'proveedor' => ['id' => $newId, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model->update($id, $nombre, $rif, $contacto, $telefono);
    jsonResponse(['success' => true, 'message' => 'Proveedor actualizado correctamente', 'proveedor' => ['id' => $id, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
}

function suppliers_handleDelete(): void
{
    $model = new Proveedor();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el proveedor');

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Proveedor desactivado correctamente', 'supplierId' => $id]);
}

function suppliers_getSuppliersAjax(): void
{
    $model = new Proveedor();
    jsonResponse(['success' => true, 'suppliers' => $model->getAll(), 'count' => 0]);
}
