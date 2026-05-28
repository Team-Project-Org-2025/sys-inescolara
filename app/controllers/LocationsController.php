<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Location;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class LocationsController
{
    use ResponseTrait, PermissionTrait;

    private Location $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Location();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/locations.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de ubicaciones no encontrada.';
            return;
        }
        require $view;
    }

    public function get_locations(): void
    {
        $this->checkModuleAuth();
        $this->getLocationsAjax();
    }

    public function add_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('UBICACIONES_CREATE');
        $this->handleAddEdit('add');
    }

    public function edit_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('UBICACIONES_EDIT');
        $this->handleAddEdit('edit');
    }

    public function delete_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('UBICACIONES_DELETE');
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
                'GET_get_locations'  => $this->getLocationsAjax(),
                'POST_add_ajax'      => $this->handleAddEdit('add'),
                'POST_edit_ajax'     => $this->handleAddEdit('edit'),
                'POST_delete_ajax'   => $this->handleDelete(),
                default              => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function getLocationsAjax(): void
    {
        $locations = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'locations' => $locations, 'count' => count($locations)]);
    }

    private function handleAddEdit(string $mode): void
    {
        $nombreUbicacion = trim((string)($_POST['nombre_ubicacion'] ?? ''));
        if ($nombreUbicacion === '') {
            throw new \Exception('El nombre de la ubicación es requerido.');
        }

        if ($mode === 'add') {
            $this->model->add($nombreUbicacion);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'ubicaciones', $newId, null, [
                'nombre_ubicacion' => $nombreUbicacion,
            ]);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Ubicación agregada correctamente',
                'location' => [
                    'id' => $newId,
                    'nombre_ubicacion' => $nombreUbicacion,
                ],
            ]);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        $oldData = $this->model->getById($id);
        if (!$oldData) throw new \Exception('La ubicación que intenta editar no existe.');
        $this->model->update($id, $nombreUbicacion);
        AuditLog::record('UPDATE', 'ubicaciones', $id, $oldData, [
            'nombre_ubicacion' => $nombreUbicacion,
        ]);
        $this->jsonResponse([
            'success' => true,
            'message' => 'Ubicación actualizada correctamente',
            'location' => ['id' => $id, 'nombre_ubicacion' => $nombreUbicacion],
        ]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID de ubicación inválido');
        $oldData = $this->model->getById($id);
        if (!$oldData) throw new \Exception('No existe la ubicación solicitada.');
        $this->model->delete($id);
        AuditLog::record('DELETE', 'ubicaciones', $id, $oldData, null);
        $this->jsonResponse([
            'success' => true,
            'message' => 'Ubicación eliminada correctamente',
            'locationId' => $id,
        ]);
    }
}
