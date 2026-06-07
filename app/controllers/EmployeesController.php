<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Employee;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_employees' => employees_getEmployeesAjax(),
                'POST_add_ajax'     => employees_handleAddEdit('add'),
                'POST_edit_ajax'    => employees_handleAddEdit('edit'),
                'POST_delete_ajax'  => employees_handleDelete(),
                default             => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/employees.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }
    require $view;
}

function get_employees(): void { checkModuleAuth(); employees_getEmployeesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TRABAJADORES_CREATE'); employees_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TRABAJADORES_EDIT'); employees_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('TRABAJADORES_DELETE'); employees_handleDelete(); }

function employees_handleAddEdit(string $mode): void
{
    $model = new Employee();
    $nombre = trim((string)($_POST['nombre_trabajador'] ?? ''));
    if ($nombre === '') throw new \Exception('El nombre del trabajador es requerido.');

    $apellido = trim((string)($_POST['apellido_trabajador'] ?? ''));
    if ($apellido === '') $apellido = null;
    $cedula = trim((string)($_POST['cedula_trabajador'] ?? ''));
    if ($cedula === '') $cedula = null;
    $telefono = trim((string)($_POST['telefono_trabajador'] ?? ''));
    if ($telefono === '') $telefono = null;
    $cargo = trim((string)($_POST['cargo'] ?? ''));
    if ($cargo === '') $cargo = null;
    $activo = isset($_POST['activo']) ? (bool)$_POST['activo'] : true;

    if ($mode === 'add') {
        $model->add($nombre, $apellido, $cedula, $telefono, $cargo, $activo);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'trabajadores', $newId, null, compact('nombre', 'apellido', 'cedula', 'telefono', 'cargo', 'activo'));
        jsonResponse(['success' => true, 'message' => 'Trabajador agregado correctamente', 'employee' => ['id' => $newId, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono, 'cargo' => $cargo, 'activo' => $activo]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $nombre, $apellido, $cedula, $telefono, $cargo, $activo);
    AuditLog::record('UPDATE', 'trabajadores', $id, $oldData, compact('nombre', 'apellido', 'cedula', 'telefono', 'cargo', 'activo'));
    jsonResponse(['success' => true, 'message' => 'Trabajador actualizado correctamente', 'employee' => ['id' => $id, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono, 'cargo' => $cargo, 'activo' => $activo]]);
}

function employees_handleDelete(): void
{
    $model = new Employee();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el trabajador');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DEACTIVATE', 'trabajadores', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Trabajador desactivado correctamente', 'employeeId' => $id]);
}

function employees_getEmployeesAjax(): void
{
    $model = new Employee();
    jsonResponse(['success' => true, 'employees' => $model->getAll(), 'count' => 0]);
}
