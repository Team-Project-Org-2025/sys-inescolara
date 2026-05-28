<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Client;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class ClientsController
{
    use ResponseTrait, PermissionTrait;

    private Client $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Client();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/clients.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de clientes no encontrada.';
            return;
        }
        require $view;
    }

    public function get_clients(): void { $this->checkModuleAuth(); $this->getClientsAjax(); }
    public function add_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('CLIENTES_CREATE'); $this->handleAddEdit('add'); }
    public function edit_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('CLIENTES_EDIT'); $this->handleAddEdit('edit'); }
    public function delete_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('CLIENTES_DELETE'); $this->handleDelete(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_clients'  => $this->getClientsAjax(),
                'POST_add_ajax'    => $this->handleAddEdit('add'),
                'POST_edit_ajax'   => $this->handleAddEdit('edit'),
                'POST_delete_ajax' => $this->handleDelete(),
                default            => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function handleAddEdit(string $mode): void
    {
        $nombreCliente = trim((string)($_POST['nombre_cliente'] ?? ''));
        if ($nombreCliente === '') throw new \Exception('El nombre del cliente es requerido.');

        $contactoCliente = trim((string)($_POST['contacto_cliente'] ?? ''));
        if ($contactoCliente === '') $contactoCliente = null;

        if ($mode === 'add') {
            $this->model->add($nombreCliente, $contactoCliente);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'cliente', $newId, null, ['nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]);
            $this->jsonResponse(['success' => true, 'message' => 'Cliente agregado correctamente', 'client' => ['id' => $newId, 'nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombreCliente, $contactoCliente);
        AuditLog::record('UPDATE', 'cliente', $id, $oldData, ['nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]);
        $this->jsonResponse(['success' => true, 'message' => 'Cliente actualizado correctamente', 'client' => ['id' => $id, 'nombre_cliente' => $nombreCliente, 'contacto_cliente' => $contactoCliente]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe el cliente');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'cliente', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Cliente eliminado correctamente', 'clientId' => $id]);
    }

    private function getClientsAjax(): void
    {
        $this->jsonResponse(['success' => true, 'clients' => $this->model->getAll(), 'count' => 0]);
    }
}
