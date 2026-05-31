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
        $nombreEspecie = trim((string)($_POST['nombre_especie'] ?? ''));
        if ($nombreEspecie === '') throw new \Exception('El nombre de la especie es requerido.');
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        if ($descripcion === '') $descripcion = null;

        if ($mode === 'add') {
            $this->model->add($nombreEspecie, $descripcion);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'especie', $newId, null, compact('nombreEspecie', 'descripcion'));
            $this->jsonResponse(['success' => true, 'message' => 'Especie agregada correctamente', 'species' => ['id' => $newId, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombreEspecie, $descripcion);
        AuditLog::record('UPDATE', 'especie', $id, $oldData, compact('nombreEspecie', 'descripcion'));
        $this->jsonResponse(['success' => true, 'message' => 'Especie actualizada correctamente', 'species' => ['id' => $id, 'nombre_especie' => $nombreEspecie, 'descripcion' => $descripcion]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe la especie');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'especie', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Especie eliminada correctamente', 'speciesId' => $id]);
    }

    private function getSpeciesAjax(): void
    {
        $this->jsonResponse(['success' => true, 'species' => $this->model->getAll(), 'count' => 0]);
    }
}
