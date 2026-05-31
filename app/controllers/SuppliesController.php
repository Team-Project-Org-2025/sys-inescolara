<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Supplies;
use SysInescolara\models\UnidadMedida;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class SuppliesController
{
    use ResponseTrait, PermissionTrait;

    private Supplies $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Supplies();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $unidadMedidaModel = new UnidadMedida();
        $unidades = $unidadMedidaModel->getAll();

        $view = ROOT_PATH . 'app/views/dashboard/supplies.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de insumos no encontrada.';
            return;
        }
        require $view;
    }

    public function get_supplies(): void
    {
        $this->checkModuleAuth();
        $this->getSuppliesAjax();
    }

    public function add_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('INSUMOS_CREATE');
        $this->handleAddEdit('add');
    }

    public function edit_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('INSUMOS_EDIT');
        $this->handleAddEdit('edit');
    }

    public function delete_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('INSUMOS_DELETE');
        $this->handleDelete();
    }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_supplies'  => $this->getSuppliesAjax(),
                'POST_add_ajax'     => $this->handleAddEdit('add'),
                'POST_edit_ajax'    => $this->handleAddEdit('edit'),
                'POST_delete_ajax'  => $this->handleDelete(),
                default             => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function handleAddEdit(string $mode): void
    {
        $nombre = trim((string)($_POST['nombre_insumo'] ?? ''));
        if ($nombre === '') {
            throw new \Exception('El nombre del insumo es requerido.');
        }
        $id_unidad_medida = (int)($_POST['id_unidad_medida'] ?? 0);
        if ($id_unidad_medida <= 0) {
            throw new \Exception('La unidad de medida es requerida.');
        }
        $categoria = trim((string)($_POST['categoria'] ?? ''));
        if ($categoria === '') $categoria = null;
        $stock = isset($_POST['stock_actual']) ? floatval($_POST['stock_actual']) : null;
        if ($stock === null) {
            throw new \Exception('El stock actual es requerido.');
        }
        $costo = isset($_POST['costo_unitario_actual']) ? floatval($_POST['costo_unitario_actual']) : null;
        if ($costo === null) {
            throw new \Exception('El costo unitario es requerido.');
        }

        if ($mode === 'add') {
            $this->model->add($nombre, $id_unidad_medida, $categoria, $stock, $costo);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'insumo', $newId, null, [
                'nombre_insumo' => $nombre, 'id_unidad_medida' => $id_unidad_medida,
                'categoria' => $categoria, 'stock_actual' => $stock, 'costo_unitario_actual' => $costo,
            ]);
            $this->jsonResponse([
                'success' => true, 'message' => 'Insumo agregado correctamente',
                'supply' => ['id' => $newId, 'nombre_insumo' => $nombre, 'id_unidad_medida' => $id_unidad_medida, 'stock_actual' => $stock, 'costo_unitario_actual' => $costo],
            ]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombre, $id_unidad_medida, $categoria, $stock, $costo);
        AuditLog::record('UPDATE', 'insumo', $id, $oldData, [
            'nombre_insumo' => $nombre, 'id_unidad_medida' => $id_unidad_medida,
            'categoria' => $categoria, 'stock_actual' => $stock, 'costo_unitario_actual' => $costo,
        ]);
        $this->jsonResponse([
            'success' => true, 'message' => 'Insumo actualizado correctamente',
            'supply' => ['id' => $id, 'nombre_insumo' => $nombre, 'id_unidad_medida' => $id_unidad_medida, 'stock_actual' => $stock, 'costo_unitario_actual' => $costo],
        ]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe el insumo');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'insumo', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Insumo eliminado correctamente', 'supplyId' => $id]);
    }

    private function getSuppliesAjax(): void
    {
        $supplies = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'supplies' => $supplies, 'count' => count($supplies)]);
    }
}
