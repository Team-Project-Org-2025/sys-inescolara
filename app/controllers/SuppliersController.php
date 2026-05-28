<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Supplier;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class SuppliersController
{
    use ResponseTrait, PermissionTrait;

    private Supplier $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Supplier();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/suppliers.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de proveedores no encontrada.';
            return;
        }
        require $view;
    }

    public function get_suppliers(): void { $this->checkModuleAuth(); $this->getSuppliersAjax(); }
    public function add_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PROVEEDORES_CREATE'); $this->handleAddEdit('add'); }
    public function edit_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PROVEEDORES_EDIT'); $this->handleAddEdit('edit'); }
    public function delete_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PROVEEDORES_DELETE'); $this->handleDelete(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_suppliers' => $this->getSuppliersAjax(),
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
        $nombre = trim((string)($_POST['nombre_proveedor'] ?? ''));
        if ($nombre === '') throw new \Exception('El nombre del proveedor es requerido.');
        $rif = trim((string)($_POST['rif_proveedor'] ?? ''));
        if ($rif === '') $rif = null;
        $contacto = trim((string)($_POST['contacto_vendedor'] ?? ''));
        if ($contacto === '') $contacto = null;
        $telefono = trim((string)($_POST['telefono_proveedor'] ?? ''));
        if ($telefono === '') $telefono = null;

        if ($mode === 'add') {
            $this->model->add($nombre, $rif, $contacto, $telefono);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'proveedores', $newId, null, compact('nombre', 'rif', 'contacto', 'telefono'));
            $this->jsonResponse(['success' => true, 'message' => 'Proveedor agregado correctamente', 'supplier' => ['id' => $newId, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombre, $rif, $contacto, $telefono);
        AuditLog::record('UPDATE', 'proveedores', $id, $oldData, compact('nombre', 'rif', 'contacto', 'telefono'));
        $this->jsonResponse(['success' => true, 'message' => 'Proveedor actualizado correctamente', 'supplier' => ['id' => $id, 'nombre_proveedor' => $nombre, 'rif_proveedor' => $rif, 'contacto_vendedor' => $contacto, 'telefono_proveedor' => $telefono]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe el proveedor');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'proveedores', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Proveedor eliminado correctamente', 'supplierId' => $id]);
    }

    private function getSuppliersAjax(): void
    {
        $this->jsonResponse(['success' => true, 'suppliers' => $this->model->getAll(), 'count' => 0]);
    }
}
