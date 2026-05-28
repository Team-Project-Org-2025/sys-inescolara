<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Plant;
use SysInescolara\models\Species;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class PlantsController
{
    use ResponseTrait, PermissionTrait;

    private Plant $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Plant();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $speciesModel = new Species();
        $species = $speciesModel->getAll();

        $view = ROOT_PATH . 'app/views/dashboard/plants.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de plantas no encontrada.';
            return;
        }
        require $view;
    }

    public function get_plants(): void { $this->checkModuleAuth(); $this->getPlantsAjax(); }
    public function add_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PLANTAS_CREATE'); $this->handleAddEdit('add'); }
    public function edit_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PLANTAS_EDIT'); $this->handleAddEdit('edit'); }
    public function delete_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('PLANTAS_DELETE'); $this->handleDelete(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_plants'   => $this->getPlantsAjax(),
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
        $nombreComun = trim((string)($_POST['nombre_comun'] ?? ''));
        if ($nombreComun === '') throw new \Exception('El nombre común es requerido.');
        $nombreTecnico = trim((string)($_POST['nombre_tecnico'] ?? ''));
        if ($nombreTecnico === '') $nombreTecnico = null;
        $especieId = !empty($_POST['especie_id']) ? (int)$_POST['especie_id'] : null;

        $imagen = null;
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/plants');
            $result = $uploader->upload($_FILES['imagen'], 'plant');
            if (!$result['success']) throw new \Exception(implode(', ', $result['errors']));
            $imagen = $result['data']['url'];
        }

        if ($mode === 'add') {
            $this->model->add($nombreComun, $nombreTecnico, $especieId, $imagen);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'plantas', $newId, null, compact('nombreComun', 'nombreTecnico', 'especieId', 'imagen'));
            $this->jsonResponse(['success' => true, 'message' => 'Planta agregada correctamente', 'plant' => ['id' => $newId, 'nombre_comun' => $nombreComun, 'imagen' => $imagen]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        if ($imagen === null) {
            $oldData = $this->model->getById($id);
            $imagen = $oldData['imagen'] ?? null;
        } else {
            $oldData = $this->model->getById($id);
            if (!empty($oldData['imagen'])) {
                (new \SysInescolara\helpers\ImageUploader())->delete($oldData['imagen']);
            }
        }

        $this->model->update($id, $nombreComun, $nombreTecnico, $especieId, $imagen);
        AuditLog::record('UPDATE', 'plantas', $id, $oldData, compact('nombreComun', 'nombreTecnico', 'especieId', 'imagen'));
        $this->jsonResponse(['success' => true, 'message' => 'Planta actualizada correctamente', 'plant' => ['id' => $id, 'imagen' => $imagen]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe la planta');

        $oldData = $this->model->getById($id);
        if (!empty($oldData['imagen'])) {
            (new \SysInescolara\helpers\ImageUploader())->delete($oldData['imagen']);
        }
        $this->model->delete($id);
        AuditLog::record('DELETE', 'plantas', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Planta eliminada correctamente', 'plantId' => $id]);
    }

    private function getPlantsAjax(): void
    {
        $this->jsonResponse(['success' => true, 'plants' => $this->model->getAll(), 'count' => 0]);
    }
}
