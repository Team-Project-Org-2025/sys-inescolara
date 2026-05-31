<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Client;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_clients'  => clients_getClientsAjax(),
                'POST_add_ajax'    => clients_handleAddEdit('add'),
                'POST_edit_ajax'   => clients_handleAddEdit('edit'),
                'POST_delete_ajax' => clients_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/clients.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de clientes no encontrada.';
        return;
    }
    require $view;
}

function get_clients(): void { checkModuleAuth(); clients_getClientsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('CLIENTES_CREATE'); clients_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('CLIENTES_EDIT'); clients_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('CLIENTES_DELETE'); clients_handleDelete(); }

function clients_handleAddEdit(string $mode): void
{
    $model = new Client();
    $nombreCliente = trim((string)($_POST['nombre_cliente'] ?? ''));
    if ($nombreCliente === '') throw new \Exception('El nombre del cliente es requerido.');

    $contactoCliente = trim((string)($_POST['contacto_cliente'] ?? ''));
    if ($contactoCliente === '') $contactoCliente = null;

    if ($mode === 'add') {
        $model->add($nombreCliente, $contactoCliente);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'cliente', $newId, null, ['nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]);
        jsonResponse(['success' => true, 'message' => 'Cliente agregado correctamente', 'client' => ['id' => $newId, 'nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $nombreCliente, $contactoCliente);
    AuditLog::record('UPDATE', 'cliente', $id, $oldData, ['nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]);
    jsonResponse(['success' => true, 'message' => 'Cliente actualizado correctamente', 'client' => ['id' => $id, 'nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]]);
}

function clients_handleDelete(): void
{
    $model = new Client();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el cliente');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'cliente', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Cliente eliminado correctamente', 'clientId' => $id]);
}

function clients_getClientsAjax(): void
{
    $model = new Client();
    jsonResponse(['success' => true, 'clients' => $model->getAll(), 'count' => 0]);
}
