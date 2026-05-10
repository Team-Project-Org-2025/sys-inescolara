<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\User;
use SysInescolara\helpers\Validation;

require_once __DIR__ . '/LoginController.php';

// Aseguramos que la sesión y autenticación se verifiquen
checkAuth();

// Instanciamos el modelo directamente, sin AdminContext ya que no existe
$userModel = new User();

function index()
{
    global $dolarBCVRate;
    require __DIR__ . '/../views/admin/users-admin.php';
}

handleRequest($userModel);

// ============================================
// CORE REQUEST HANDLER
// ============================================

function handleRequest($userModel)
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'POST_add_ajax' => fn() => handleAddEditAjax($userModel, 'add'),
                'POST_edit_ajax' => fn() => handleAddEditAjax($userModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($userModel),
                'GET_get_users' => fn() => getUsersAjax($userModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
        } else {
            $routes = [
                'POST_add' => fn() => handleAddEdit($userModel, 'add'),
                'POST_edit' => fn() => handleAddEdit($userModel, 'edit'),
                'GET_delete' => fn() => handleDelete($userModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            }
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function handleError($e, $isAjax)
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()], 500);
    } else {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// VALIDATION
// ============================================

function validateUserData($data, $mode)
{
    $rules = [
        'nombre_usuario' => ['type' => null, 'required' => true],
        'password' => ['type' => 'password', 'required' => ($mode === 'add')],
        'id' => ['type' => null, 'required' => ($mode === 'edit')]
    ];

    $validation = Validation::validate($data, $rules);

    if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
    }
}

// ============================================
// NON-AJAX HANDLERS
// ============================================

function handleAddEdit($userModel, $mode)
{
    try {
        $fields = ['nombre_usuario'];
        if ($mode === 'add') $fields[] = 'password';
        if ($mode === 'edit') $fields[] = 'id';

        foreach ($fields as $f) {
            if ($mode === 'edit' && $f === 'password' && empty($_POST[$f])) continue;
            if (empty($_POST[$f])) {
                throw new Exception("El campo '$f' es requerido");
            }
        }

        $id = intval($_POST['id'] ?? 0);
        $nombreUsuario = trim($_POST['nombre_usuario']);
        $password = $_POST['password'] ?? null;
        $rolId = intval($_POST['rol_id'] ?? 1); // Por defecto 1 hasta que exista el módulo de roles

        if ($mode === 'add') {
            if ($userModel->userExists(null, $nombreUsuario)) {
                header("Location: users-admin.php?error=usuario_duplicado&nombre_usuario=$nombreUsuario");
                exit();
            }
            $userModel->add($nombreUsuario, $password, $rolId);
            header("Location: users-admin.php?success=add");
            exit();
        } else {
            $userModel->update($id, $nombreUsuario, $rolId, $password);
            header("Location: users-admin.php?success=edit");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function handleDelete($userModel)
{
    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("ID inválido");
        }

        $id = intval($_GET['id']);
        $userModel->delete($id);
        header("Location: users-admin.php?success=delete");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

function handleAddEditAjax($userModel, $mode)
{
    try {
        validateUserData($_POST, $mode);

        $id = intval($_POST['id'] ?? 0);
        $nombreUsuario = trim($_POST['nombre_usuario']);
        $password = $_POST['password'] ?? null;
        $rolId = intval($_POST['rol_id'] ?? 1); // Por defecto 1 hasta que exista el módulo de roles

        if ($mode === 'add') {
            if ($userModel->userExists(null, $nombreUsuario)) {
                throw new Exception("El nombre de usuario ya está registrado");
            }
            $userModel->add($nombreUsuario, $password, $rolId);

            $user = [
                'id' => $userModel->getLastInsertId() ?? 0,
                'nombre_usuario' => $nombreUsuario,
                'rol_id' => $rolId
            ];
            $msg = 'Usuario agregado';
        } else {
            $userModel->update($id, $nombreUsuario, $rolId, $password);
            $user = ['id' => $id, 'nombre_usuario' => $nombreUsuario, 'rol_id' => $rolId];
            $msg = 'Usuario actualizado';
        }

        jsonResponse(['success' => true, 'message' => $msg, 'user' => $user]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleDeleteAjax($userModel)
{
    try {
        $validation = Validation::validateField($_POST['id'] ?? '', 'id');
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        $id = intval($_POST['id']);
        if (!$userModel->userExists($id)) {
            throw new Exception("No existe el usuario");
        }

        $userModel->delete($id);
        jsonResponse(['success' => true, 'message' => 'Usuario eliminado', 'userId' => $id]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function getUsersAjax($userModel)
{
    try {
        if (isset($_GET['id'])) {
            $user = $userModel->getById(intval($_GET['id']));
            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }
            jsonResponse(['success' => true, 'users' => [$user]]);
        }

        $users = $userModel->getAll();
        jsonResponse(['success' => true, 'users' => $users, 'count' => count($users)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
