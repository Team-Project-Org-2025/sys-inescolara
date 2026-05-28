<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Species;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class SpeciesController
{
    use ResponseTrait, PermissionTrait;

    private Species $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Species();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/species.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de especies no encontrada.';
            return;
        }
        require $view;
    }

    public function get_species(): void { $this->checkModuleAuth(); $this->getSpeciesAjax(); }
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
                'GET_get_species'  => $this->getSpeciesAjax(),
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

        if ($mode === 'add') {
            $this->model->add($nombreComun, $nombreTecnico);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'especies', $newId, null, compact('nombreComun', 'nombreTecnico'));
            $this->jsonResponse(['success' => true, 'message' => 'Especie agregada correctamente', 'species' => ['id' => $newId, 'nombre_comun' => $nombreComun, 'nombre_tecnico' => $nombreTecnico]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombreComun, $nombreTecnico);
        AuditLog::record('UPDATE', 'especies', $id, $oldData, compact('nombreComun', 'nombreTecnico'));
        $this->jsonResponse(['success' => true, 'message' => 'Especie actualizada correctamente', 'species' => ['id' => $id, 'nombre_comun' => $nombreComun, 'nombre_tecnico' => $nombreTecnico]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe la especie');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'especies', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Especie eliminada correctamente', 'speciesId' => $id]);
    }

    private function getSpeciesAjax(): void
    {
        $this->jsonResponse(['success' => true, 'species' => $this->model->getAll(), 'count' => 0]);
    }
}
