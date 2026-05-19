<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\helpers\Validation;
use SysInescolara\models\User;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function userCheckAuth(): void
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

$GLOBALS['userModel'] = new User();

function index(): void
{
    $userModel = $GLOBALS['userModel'] ?? new User();

    userCheckAuth();

    handleRequest($userModel);

    $view = ROOT_PATH . 'app/views/dashboard/usuarios.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de usuarios no encontrada.';
        return;
    }

    require $view;
}

function get_users(): void
{
    $userModel = $GLOBALS['userModel'] ?? new User();
    userCheckAuth();
    getUsersAjax($userModel);
}

function add_ajax(): void
{
    $userModel = $GLOBALS['userModel'] ?? new User();
    userCheckAuth();
    handleAddEditAjax($userModel, 'add');
}

function edit_ajax(): void
{
    $userModel = $GLOBALS['userModel'] ?? new User();
    userCheckAuth();
    handleAddEditAjax($userModel, 'edit');
}

function delete_ajax(): void
{
    $userModel = $GLOBALS['userModel'] ?? new User();
    userCheckAuth();
    handleDeleteAjax($userModel);
}

function handleRequest(User $userModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_users' => fn() => getUsersAjax($userModel),
                'POST_add_ajax' => fn() => handleAddEditAjax($userModel, 'add'),
                'POST_edit_ajax' => fn() => handleAddEditAjax($userModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($userModel),
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
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

function validateUserData(array $data, string $mode): void
{
    $rules = [
        'nombre_usuario' => ['type' => null, 'required' => true],
        'password' => ['type' => 'password', 'required' => $mode === 'add'],
        'rol_id' => ['type' => null, 'required' => true],
    ];

    $validation = Validation::validate($data, $rules);
    if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
    }
}

function handleAddEditAjax(User $userModel, string $mode): void
{
    validateUserData($_POST, $mode);

    $id = (int)($_POST['id'] ?? 0);
    $nombreUsuario = trim((string)($_POST['nombre_usuario'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $rolId = (int)($_POST['rol_id'] ?? 1);

    if ($mode === 'add') {
        if ($userModel->userExists(null, $nombreUsuario)) {
            throw new Exception('El nombre de usuario ya está registrado');
        }

        $userModel->add($nombreUsuario, $password, $rolId);

        jsonResponse([
            'success' => true,
            'message' => 'Usuario agregado',
            'user' => [
                'id' => $userModel->getLastInsertId() ?? 0,
                'nombre_usuario' => $nombreUsuario,
                'rol_id' => $rolId,
            ],
        ]);
    }

    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $userModel->update($id, $nombreUsuario, $rolId, $password !== '' ? $password : null);

    jsonResponse([
        'success' => true,
        'message' => 'Usuario actualizado',
        'user' => [
            'id' => $id,
            'nombre_usuario' => $nombreUsuario,
            'rol_id' => $rolId,
        ],
    ]);
}

function handleDeleteAjax(User $userModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    if (!$userModel->userExists($id)) {
        throw new Exception('No existe el usuario');
    }

    $userModel->delete($id);

    jsonResponse([
        'success' => true,
        'message' => 'Usuario eliminado',
        'userId' => $id,
    ]);
}

function getUsersAjax(User $userModel): void
{
    $users = $userModel->getAll();

    jsonResponse([
        'success' => true,
        'users' => $users,
        'count' => count($users),
    ]);
}
