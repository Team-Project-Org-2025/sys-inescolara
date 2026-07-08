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
        $cliente = new Cliente([
            'nombre_cliente'      => $nombreCliente,
            'apellido_cliente'    => $apellidoCliente,
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente'      => $cedulaCliente,
            'contacto_cliente'    => $contactoCliente,
        ]);
        if (!$cliente->save()) {
            throw new \Exception('Error al guardar el cliente.');
        }
        jsonResponse([
            'success' => true, 'message' => 'Cliente agregado correctamente',
            'client' => [
                'id' => $cliente->getId(), 'nombre_cliente' => $nombreCliente,
                'apellido_cliente' => $apellidoCliente,
                'nombre_completo' => trim("$nombreCliente $apellidoCliente"),
                'tipo_cedula_cliente' => $tipoCedulaCliente,
                'cedula_cliente' => $cedulaCliente,
                'cedula_completa' => $tipoCedulaCliente ? "$tipoCedulaCliente-$cedulaCliente" : null,
                'contacto_cliente' => $contactoCliente,
            ],
        ]);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $cliente = Cliente::find($id);
    if (!$cliente) throw new \Exception('No existe el cliente solicitado.');

    $cliente->setNombreCliente($nombreCliente)
            ->setApellidoCliente($apellidoCliente)
            ->setTipoCedulaCliente($tipoCedulaCliente)
            ->setCedulaCliente($cedulaCliente)
            ->setContactoCliente($contactoCliente);

    if (!$cliente->save()) {
        throw new \Exception('Error al actualizar el cliente.');
    }

    jsonResponse([
        'success' => true, 'message' => 'Cliente actualizado correctamente',
        'client' => [
            'id' => $id, 'nombre_cliente' => $nombreCliente,
            'apellido_cliente' => $apellidoCliente,
            'nombre_completo' => trim("$nombreCliente $apellidoCliente"),
            'tipo_cedula_cliente' => $tipoCedulaCliente,
            'cedula_cliente' => $cedulaCliente,
            'cedula_completa' => $tipoCedulaCliente ? "$tipoCedulaCliente-$cedulaCliente" : null,
            'contacto_cliente' => $contactoCliente,
        ],
    ]);
}

function clients_handleDelete(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $cliente = Cliente::find($id);
    if (!$cliente) throw new \Exception('No existe el cliente');

    if (!$cliente->delete($id)) {
        throw new \Exception('Error al desactivar el cliente.');
    }

    jsonResponse(['success' => true, 'message' => 'Cliente desactivado correctamente', 'clientId' => $id]);
}

function clients_getClientsAjax(): void
{
    $model = new Cliente();
    jsonResponse(['success' => true, 'clientes' => $model->getAll(), 'count' => 0]);
}
