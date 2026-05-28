<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Employee;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class EmployeesController
{
    use ResponseTrait, PermissionTrait;

    private Employee $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Employee();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $view = ROOT_PATH . 'app/views/dashboard/employees.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de empleados no encontrada.';
            return;
        }
        require $view;
    }

    public function get_employees(): void { $this->checkModuleAuth(); $this->getEmployeesAjax(); }
    public function add_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('TRABAJADORES_CREATE'); $this->handleAddEdit('add'); }
    public function edit_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('TRABAJADORES_EDIT'); $this->handleAddEdit('edit'); }
    public function delete_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('TRABAJADORES_DELETE'); $this->handleDelete(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_employees'  => $this->getEmployeesAjax(),
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
        $nombre = trim((string)($_POST['nombre_trabajador'] ?? ''));
        if ($nombre === '') throw new \Exception('El nombre del trabajador es requerido.');

        $apellido = trim((string)($_POST['apellido_trabajador'] ?? ''));
        if ($apellido === '') $apellido = null;
        $cedula = trim((string)($_POST['cedula_trabajador'] ?? ''));
        if ($cedula === '') $cedula = null;
        $telefono = trim((string)($_POST['telefono_trabajador'] ?? ''));
        if ($telefono === '') $telefono = null;

        if ($mode === 'add') {
            $this->model->add($nombre, $apellido, $cedula, $telefono);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'trabajadores', $newId, null, compact('nombre', 'apellido', 'cedula', 'telefono'));
            $this->jsonResponse(['success' => true, 'message' => 'Trabajador agregado correctamente', 'employee' => ['id' => $newId, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombre, $apellido, $cedula, $telefono);
        AuditLog::record('UPDATE', 'trabajadores', $id, $oldData, compact('nombre', 'apellido', 'cedula', 'telefono'));
        $this->jsonResponse(['success' => true, 'message' => 'Trabajador actualizado correctamente', 'employee' => ['id' => $id, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if (!$this->model->exists($id)) throw new \Exception('No existe el trabajador');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'trabajadores', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Trabajador eliminado correctamente', 'employeeId' => $id]);
    }

    private function getEmployeesAjax(): void
    {
        $this->jsonResponse(['success' => true, 'employees' => $this->model->getAll(), 'count' => 0]);
    }
}
