<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Batch;
use SysInescolara\models\Plant;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class BatchesController
{
    use ResponseTrait, PermissionTrait;

    private Batch $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new Batch();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $plantModel = new Plant();
        $plants = $plantModel->getAll();

        $view = ROOT_PATH . 'app/views/dashboard/batches.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de lotes no encontrada.';
            return;
        }
        require $view;
    }

    public function get_batches(): void
    {
        $this->checkModuleAuth();
        $this->getBatchesAjax();
    }

    public function add_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('PLANTAS_CREATE');
        $this->handleAddEdit('add');
    }

    public function edit_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('PLANTAS_EDIT');
        $this->handleAddEdit('edit');
    }

    public function delete_ajax(): void
    {
        $this->checkModuleAuth();
        $this->checkPermisoOrFail('PLANTAS_DELETE');
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
                'GET_get_batches'  => $this->getBatchesAjax(),
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
        $id_planta = (int)($_POST['id_planta'] ?? 0);
        $fecha_siembra = trim((string)($_POST['fecha_siembra'] ?? ''));
        $cantidad_inicial = (int)($_POST['cantidad_inicial'] ?? 0);
        $cantidad_actual = (int)($_POST['cantidad_actual'] ?? 0);
        $estado = trim((string)($_POST['estado'] ?? ''));
        $ubicacion = trim((string)($_POST['ubicacion'] ?? ''));

        if ($id_planta <= 0) throw new \Exception('Selecciona una planta.');
        if ($fecha_siembra === '') throw new \Exception('La fecha de siembra es requerida.');
        if ($cantidad_inicial <= 0) throw new \Exception('La cantidad inicial debe ser mayor a 0.');
        if ($cantidad_actual < 0) throw new \Exception('La cantidad actual no puede ser negativa.');
        if ($estado === '') throw new \Exception('El estado es requerido.');
        if ($ubicacion === '') throw new \Exception('La ubicación es requerida.');

        $imagen = null;
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/batches');
            $result = $uploader->upload($_FILES['imagen'], 'batch');
            if (!$result['success']) {
                throw new \Exception(implode(', ', $result['errors']));
            }
            $imagen = $result['data']['url'];
        }

        if ($mode === 'add') {
            $this->model->add($id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'lote', $newId, null, [
                'id_planta' => $id_planta, 'fecha_siembra' => $fecha_siembra,
                'cantidad_inicial' => $cantidad_inicial, 'cantidad_actual' => $cantidad_actual,
                'estado' => $estado, 'ubicacion' => $ubicacion, 'imagen' => $imagen,
            ]);
            $this->jsonResponse([
                'success' => true, 'message' => 'Lote agregado correctamente',
                'batch' => ['id' => $newId, 'id_planta' => $id_planta, 'imagen' => $imagen],
            ]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        if ($imagen === null) {
            $oldData = $this->model->getById($id);
            $imagen = $oldData['imagen'] ?? null;
        } else {
            $oldData = $this->model->getById($id);
            if (!empty($oldData['imagen'])) {
                $uploader = new \SysInescolara\helpers\ImageUploader();
                $uploader->delete($oldData['imagen']);
            }
        }

        $this->model->update($id, $id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen);
        AuditLog::record('UPDATE', 'lote', $id, $oldData, [
            'id_planta' => $id_planta, 'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial, 'cantidad_actual' => $cantidad_actual,
            'estado' => $estado, 'ubicacion' => $ubicacion, 'imagen' => $imagen,
        ]);
        $this->jsonResponse([
            'success' => true, 'message' => 'Lote actualizado correctamente',
            'batch' => ['id' => $id, 'imagen' => $imagen],
        ]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe el lote');

        $oldData = $this->model->getById($id);
        if (!empty($oldData['imagen'])) {
            $uploader = new \SysInescolara\helpers\ImageUploader();
            $uploader->delete($oldData['imagen']);
        }

        $this->model->delete($id);
        AuditLog::record('DELETE', 'lote', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Lote eliminado correctamente', 'batchId' => $id]);
    }

    private function getBatchesAjax(): void
    {
        $batches = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'batches' => $batches, 'count' => count($batches)]);
    }
}
