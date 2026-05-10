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
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }
        header('Location: /login/show');
        exit();
    }
}


function show()
{
    if (isset($_SESSION['user_id'])) {
        header('Location: /dashboard');
        exit();
    }

    $error = null;
    // Assuming the view is in auth/login.php based on folder structure
    $viewPath = ROOT_PATH . 'app/views/auth/login.php';
    if (!file_exists($viewPath)) {
        // Fallback if not moved yet
        $viewPath = ROOT_PATH . 'app/views/admin/login.php';
    }
    require_once $viewPath;
}

function login()
{
    $userModel = $GLOBALS['userModel'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /login/show');
        exit();
    }

    $nombre_usuario = trim($_POST['nombre_usuario'] ?? ($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $error = null;

    if ($nombre_usuario === '' || $password === '') {
        $error = "Por favor, complete todos los campos.";
        $viewPath = file_exists(ROOT_PATH . 'app/views/auth/login.php') ? ROOT_PATH . 'app/views/auth/login.php' : ROOT_PATH . 'app/views/admin/login.php';
        require_once $viewPath;
        return;
    }

    $user = $userModel->authenticate($nombre_usuario, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'] ?? $user['user_id'] ?? null;
        $_SESSION['user_nombre'] = $user['nombre_usuario'] ?? $user['nombre'] ?? null;
        $_SESSION['is_admin'] = true;

        header('Location: /dashboard');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
        $viewPath = file_exists(ROOT_PATH . 'app/views/auth/login.php') ? ROOT_PATH . 'app/views/auth/login.php' : ROOT_PATH . 'app/views/admin/login.php';
        require_once $viewPath;
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
