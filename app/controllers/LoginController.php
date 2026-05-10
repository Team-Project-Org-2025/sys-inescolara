<?php


use SysInescolara\models\User;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3) . '/');
}

$GLOBALS['userModel'] = new User();


function checkAuth()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: /admin/login/show');
        exit();
    }
}


function show()
{
    if (isset($_SESSION['user_id'])) {
        header('Location: /admin/login/dashboard');
        exit();
    }

    $error = null;
    require_once ROOT_PATH . 'app/views/admin/login.php';
}

function login()
{
    $userModel = $GLOBALS['userModel'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /admin/login/show');
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = null;

    if ($email === '' || $password === '') {
        $error = "Por favor, complete todos los campos.";
        require_once ROOT_PATH . 'app/views/admin/login.php';
        return;
    }


    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $error = "El correo electrónico no tiene un formato válido.";
        require_once ROOT_PATH . 'app/views/admin/login.php';
        return;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,}$/', $password)) {
        $error = "La contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.";
        require_once ROOT_PATH . 'app/views/admin/login.php';
        return;
    }

    $user = $userModel->authenticate($email, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'] ?? $user['user_id'] ?? null;
        $_SESSION['user_email'] = $user['email'] ?? $user['user_email'] ?? null;
        $_SESSION['user_nombre'] = $user['nombre'] ?? $user['user_nombre'] ?? null;
        $_SESSION['is_admin'] = true;

        header('Location: /admin/login/dashboard');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
        require_once ROOT_PATH . 'app/views/admin/login.php';
    }
}


function dashboard()
{
    checkAuth();
    require_once ROOT_PATH . 'app/views/admin/home-admin.php';
}

function logout()
{
    session_unset();
    session_destroy();

    header('Location: /admin/login/show');
    exit();
}


function logout_ajax()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Petición inválida']);
        exit();
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_unset();
    session_destroy();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente',
        'redirect' => '/admin/login/show'
    ]);
    exit();
}

function check_session()
{
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('HTTP/1.1 400 Bad Request');
        exit();
    }

    header('Content-Type: application/json');

    $active = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

    echo json_encode([
        'active' => $active,
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_nombre' => $_SESSION['user_nombre'] ?? null
    ]);
    exit();
}
