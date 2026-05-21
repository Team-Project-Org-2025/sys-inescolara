<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Employee;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function employeesCheckAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado', 'redirect' => BASE_URL . 'login']);
            exit();
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

$GLOBALS['employeeModel'] = new Employee();

function index(): void
{
    $employeeModel = $GLOBALS['employeeModel'] ?? new Employee();
    employeesCheckAuth();
    handleRequest($employeeModel);

    $view = ROOT_PATH . 'app/views/dashboard/employees.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }

    require $view;
}

function get_employees(): void
{
    $employeeModel = $GLOBALS['employeeModel'] ?? new Employee();
    employeesCheckAuth();
    getEmployeesAjax($employeeModel);
}

function add_ajax(): void
{
    $employeeModel = $GLOBALS['employeeModel'] ?? new Employee();
    employeesCheckAuth();
    handleAddEditAjax($employeeModel, 'add');
}

function edit_ajax(): void
{
    $employeeModel = $GLOBALS['employeeModel'] ?? new Employee();
    employeesCheckAuth();
    handleAddEditAjax($employeeModel, 'edit');
}

function delete_ajax(): void
{
    $employeeModel = $GLOBALS['employeeModel'] ?? new Employee();
    employeesCheckAuth();
    handleDeleteAjax($employeeModel);
}

function handleRequest(Employee $employeeModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_employees'   => fn() => getEmployeesAjax($employeeModel),
                'POST_add_ajax'       => fn() => handleAddEditAjax($employeeModel, 'add'),
                'POST_edit_ajax'      => fn() => handleAddEditAjax($employeeModel, 'edit'),
                'POST_delete_ajax'    => fn() => handleDeleteAjax($employeeModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
            }

            jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function handleError(Exception $e, bool $isAjax): void
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}

function handleAddEditAjax(Employee $employeeModel, string $mode): void
{
    $nombreTrabajador = trim((string)($_POST['nombre_trabajador'] ?? ''));
    if ($nombreTrabajador === '') {
        throw new Exception('El nombre del trabajador es requerido.');
    }

    $apellidoTrabajador = trim((string)($_POST['apellido_trabajador'] ?? ''));
    if ($apellidoTrabajador === '') {
        $apellidoTrabajador = null;
    }

    $cedulaTrabajador = trim((string)($_POST['cedula_trabajador'] ?? ''));
    if ($cedulaTrabajador === '') {
        $cedulaTrabajador = null;
    }

    $telefonoTrabajador = trim((string)($_POST['telefono_trabajador'] ?? ''));
    if ($telefonoTrabajador === '') {
        $telefonoTrabajador = null;
    }

    if ($mode === 'add') {
        $employeeModel->add($nombreTrabajador, $apellidoTrabajador, $cedulaTrabajador, $telefonoTrabajador);
        $newId = $employeeModel->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'trabajadores', $newId, null, [
            'nombre_trabajador' => $nombreTrabajador,
            'apellido_trabajador' => $apellidoTrabajador,
            'cedula_trabajador' => $cedulaTrabajador,
            'telefono_trabajador' => $telefonoTrabajador,
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Trabajador agregado correctamente',
            'employee' => [
                'id' => $newId,
                'nombre_trabajador' => $nombreTrabajador,
                'apellido_trabajador' => $apellidoTrabajador,
                'cedula_trabajador' => $cedulaTrabajador,
                'telefono_trabajador' => $telefonoTrabajador,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $oldData = $employeeModel->getById($id);
    $employeeModel->update($id, $nombreTrabajador, $apellidoTrabajador, $cedulaTrabajador, $telefonoTrabajador);
    AuditLog::record('UPDATE', 'trabajadores', $id, $oldData, [
        'nombre_trabajador' => $nombreTrabajador,
        'apellido_trabajador' => $apellidoTrabajador,
        'cedula_trabajador' => $cedulaTrabajador,
        'telefono_trabajador' => $telefonoTrabajador,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Trabajador actualizado correctamente',
        'employee' => [
            'id' => $id,
            'nombre_trabajador' => $nombreTrabajador,
            'apellido_trabajador' => $apellidoTrabajador,
            'cedula_trabajador' => $cedulaTrabajador,
            'telefono_trabajador' => $telefonoTrabajador,
        ],
    ]);
}

function handleDeleteAjax(Employee $employeeModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    if (!$employeeModel->exists($id)) {
        throw new Exception('No existe el trabajador');
    }

    $oldData = $employeeModel->getById($id);
    $employeeModel->delete($id);
    AuditLog::record('DELETE', 'trabajadores', $id, $oldData, null);
    jsonResponse([
        'success' => true,
        'message' => 'Trabajador eliminado correctamente',
        'employeeId' => $id,
    ]);
}

function getEmployeesAjax(Employee $employeeModel): void
{
    $employees = $employeeModel->getAll();
    jsonResponse([
        'success' => true,
        'employees' => $employees,
        'count' => count($employees),
    ]);
}
