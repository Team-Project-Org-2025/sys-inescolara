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
                'GET_get_suppliers' => get_suppliers(),
                'POST_add_ajax'    => add_ajax(),
                'POST_edit_ajax'   => edit_ajax(),
                'POST_delete_ajax' => delete_ajax(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\InvalidArgumentException $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
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
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('proveedores:crear'); suppliers_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('proveedores:editar'); suppliers_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('proveedores:eliminar'); suppliers_handleDelete(); }

function suppliers_handleAddEdit(string $mode): void
{
    $model = new Proveedor();
    $nombre = trim((string)($_POST['nombre_proveedor'] ?? ''));
    if ($nombre === '') throw new \InvalidArgumentException('El nombre del proveedor es requerido.');
    $rif = trim((string)($_POST['rif_proveedor'] ?? ''));
    if ($rif === '') throw new \InvalidArgumentException('El RIF del proveedor es requerido.');

    $existing = $model->getByRif($rif);
    $idActual = (int)($_POST['id'] ?? 0);
    if ($existing && $existing['id'] !== $idActual) {
        throw new \InvalidArgumentException('El RIF ingresado ya está registrado en otro proveedor.');
    }
    $contacto = trim((string)($_POST['contacto_vendedor'] ?? ''));
    if ($contacto === '') $contacto = null;
    $telefono = trim((string)($_POST['telefono_proveedor'] ?? ''));
    if ($telefono === '') $telefono = null;

    if ($mode === 'add') {
        $success = $model->add($nombre, $rif, $contacto, $telefono);
        if (!$success) {
            jsonResponse(['success' => false, 'message' => 'No se pudo guardar el proveedor. Verifique que el RIF no esté registrado.'], 400);
        }
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse(['success' => true, 'message' => 'Proveedor agregado correctamente', 'proveedor' => ['id' => $newId, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \InvalidArgumentException('ID inválido');

    $success = $model->update($id, $nombre, $rif, $contacto, $telefono);
    if (!$success) {
        jsonResponse(['success' => false, 'message' => 'No se pudo actualizar el proveedor. Verifique que el RIF no esté registrado.'], 400);
    }
    jsonResponse(['success' => true, 'message' => 'Proveedor actualizado correctamente', 'proveedor' => ['id' => $id, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
}

function suppliers_handleDelete(): void
{
    $model = new Proveedor();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \InvalidArgumentException('ID inválido');
    if (!$model->exists($id)) throw new \InvalidArgumentException('No existe el proveedor');

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Proveedor desactivado correctamente', 'supplierId' => $id]);
}

function suppliers_getSuppliersAjax(): void
{
    $model = new Proveedor();
    jsonResponse(['success' => true, 'suppliers' => $model->getAll(), 'count' => 0]);
}
