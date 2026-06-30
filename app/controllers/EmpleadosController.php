<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Empleado;
use SysInescolara\models\Role;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_employees' => get_employees(),
                'GET_get_detail'    => get_detail(),
                'POST_add_ajax'     => add_ajax(),
                'POST_edit_ajax'    => edit_ajax(),
                'POST_delete_ajax'  => delete_ajax(),
                default             => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    try {
        $roleModel = new Role();
        $roles = $roleModel->getAll();
        $cargoOptions = array_map(fn($r) => $r['nombre_rol'], $roles);
        sort($cargoOptions);
    } catch (\Throwable $e) {
        $cargoOptions = [];
    }

    $view = ROOT_PATH . 'app/views/dashboard/empleados.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }
    require $view;
}

function get_employees(): void { checkModuleAuth(); employees_getEmployeesAjax(); }
function get_detail(): void { checkModuleAuth(); employees_getDetailAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('empleados:crear'); employees_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('empleados:editar'); employees_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('empleados:eliminar'); employees_handleDelete(); }

function employees_handleAddEdit(string $mode): void
{
    $model = new Empleado();
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
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($mode === 'add') {
        $model->add($nombre, $apellido, $cedula, $telefono, $cargo, $activo);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse(['success' => true, 'message' => 'Trabajador agregado correctamente', 'employee' => ['id' => $newId, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono, 'cargo' => $cargo, 'activo' => $activo]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model->update($id, $nombre, $apellido, $cedula, $telefono, $cargo, $activo);
    jsonResponse(['success' => true, 'message' => 'Trabajador actualizado correctamente', 'employee' => ['id' => $id, 'nombre_trabajador' => $nombre, 'apellido_trabajador' => $apellido, 'cedula_trabajador' => $cedula, 'telefono_trabajador' => $telefono, 'cargo' => $cargo, 'activo' => $activo]]);
}

function employees_handleDelete(): void
{
    $model = new Empleado();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe el trabajador');

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Trabajador desactivado correctamente', 'employeeId' => $id]);
}

function employees_getDetailAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);

    $model = new Empleado();
    $employee = $model->getById($id);
    if (!$employee) jsonResponse(['success' => false, 'message' => 'Empleado no encontrado'], 404);

    jsonResponse(['success' => true, 'employee' => $employee]);
}

function employees_getEmployeesAjax(): void
{
    $model = new Empleado();
    jsonResponse(['success' => true, 'employees' => $model->getAll(), 'count' => 0]);
}
