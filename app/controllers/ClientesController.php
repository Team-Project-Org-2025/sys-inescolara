<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Cliente;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_clients'  => get_clients(),
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

    $view = ROOT_PATH . 'app/views/dashboard/clientes.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de clientes no encontrada.';
        return;
    }
    require $view;
}

function get_clients(): void { checkModuleAuth(); clients_getClientsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('clientes:crear'); clients_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('clientes:editar'); clients_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('clientes:eliminar'); clients_handleDelete(); }

function clients_handleAddEdit(string $mode): void
{
    $model = new Cliente();
    $nombreCliente = trim((string)($_POST['nombre_cliente'] ?? ''));
    if ($nombreCliente === '') throw new \Exception('El nombre del cliente es requerido.');

    $apellidoCliente = trim((string)($_POST['apellido_cliente'] ?? ''));
    if ($apellidoCliente === '') $apellidoCliente = null;

    $tipoCedulaCliente = trim((string)($_POST['tipo_cedula_cliente'] ?? ''));
    if ($tipoCedulaCliente === '') $tipoCedulaCliente = null;

    $cedulaCliente = trim((string)($_POST['cedula_cliente'] ?? ''));
    if ($cedulaCliente === '') $cedulaCliente = null;

    $contactoCliente = trim((string)($_POST['contacto_cliente'] ?? ''));
    if ($contactoCliente === '') $contactoCliente = null;

    if ($mode === 'add') {
        $model->add($nombreCliente, $apellidoCliente, $tipoCedulaCliente, $cedulaCliente, $contactoCliente);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse(['success' => true, 'message' => 'Cliente agregado correctamente', 'client' => ['id' => $newId, 'nombre_cliente' => $nombreCliente, 'apellido_cliente' => $apellidoCliente, 'nombre_completo' => trim("$nombreCliente $apellidoCliente"), 'tipo_cedula_cliente' => $tipoCedulaCliente, 'cedula_cliente' => $cedulaCliente, 'cedula_completa' => $tipoCedulaCliente ? "$tipoCedulaCliente-$cedulaCliente" : null, 'contacto_cliente' => $contactoCliente]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model->update($id, $nombreCliente, $apellidoCliente, $tipoCedulaCliente, $cedulaCliente, $contactoCliente);
    jsonResponse(['success' => true, 'message' => 'Cliente actualizado correctamente', 'client' => ['id' => $id, 'nombre_cliente' => $nombreCliente, 'apellido_cliente' => $apellidoCliente, 'nombre_completo' => trim("$nombreCliente $apellidoCliente"), 'tipo_cedula_cliente' => $tipoCedulaCliente, 'cedula_cliente' => $cedulaCliente, 'cedula_completa' => $tipoCedulaCliente ? "$tipoCedulaCliente-$cedulaCliente" : null, 'contacto_cliente' => $contactoCliente]]);
}

function clients_handleDelete(): void
{
    $model = new Cliente();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el cliente');

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Cliente desactivado correctamente', 'clientId' => $id]);
}

function clients_getClientsAjax(): void
{
    $model = new Cliente();
    jsonResponse(['success' => true, 'clientes' => $model->getAll(), 'count' => 0]);
}
