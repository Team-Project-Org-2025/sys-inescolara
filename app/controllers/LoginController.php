<?php


use SysInescolara\models\User;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3) . '/');
}

$GLOBALS['userModel'] = new User();

function renderLoginView(?string $error = null, array $old = []): void
{
    // Obtener la Site Key de reCAPTCHA desde el .env (cargado por Database)
    $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';

    $title = 'Iniciar Sesión';
    $layout = ROOT_PATH . 'app/views/layouts/auth.php';
    $view = ROOT_PATH . 'app/views/auth/login.php';

    ob_start();
    require $view;
    $content = ob_get_clean();

    require $layout;
}

function index()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        login();
        return;
    }

    show();
}


function checkAuth()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autorizado', 'redirect' => BASE_URL . 'login']);
            exit();
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}


function show()
{
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'dashboard');
        exit();
    }

    $old = [];
    if (!empty($_COOKIE['remember_email'])) {
        $old['email'] = $_COOKIE['remember_email'];
    }

    renderLoginView(null, $old);
}

function login()
{
    $userModel = $GLOBALS['userModel'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    $identificador = trim($_POST['nombre_usuario'] ?? ($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $error = null;

    if ($identificador === '' || $password === '') {
        renderLoginView("Por favor, complete todos los campos.", ['email' => $identificador]);
        return;
    }

    // --- Validación del token reCAPTCHA ---
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (empty($recaptchaToken)) {
        renderLoginView("Por favor, completa la verificación de seguridad.", ['email' => $identificador]);
        return;
    }

    $recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret' => $recaptchaSecret,
                'response' => $recaptchaToken,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
        ],
    ]));
    $verifyData = json_decode($verifyResponse, true);

    if (!($verifyData['success'] ?? false)) {
        renderLoginView("Verificación de seguridad fallida. Intenta de nuevo.", ['email' => $identificador]);
        return;
    }
    // --- Fin validación reCAPTCHA ---

    $user = $userModel->authenticate($identificador, $password);

    if ($user) {
        session_regenerate_id(true);
        $userId = $user['id'] ?? null;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_nombre'] = $user['nombre_usuario'] ?? null;
        $_SESSION['user_email'] = $user['correo_electronico'] ?? null;
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;
        $_SESSION['user_rol_id'] = $user['rol_id'] ?? null;
        $_SESSION['user_permisos'] = $userModel->getRolePermissions((int)($user['rol_id'] ?? 0));

        AuditLog::record('LOGIN', 'usuarios', $userId, null, [
            'nombre_usuario' => $user['nombre_usuario'] ?? null,
        ]);

        // Remember me: guardar cookie por 30 días si marcó la opción
        if (!empty($_POST['remember'])) {
            setcookie('remember_email', $identificador, time() + 86400 * 30, '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

        header('Location: ' . BASE_URL . 'dashboard');
        exit();
    } else {
        renderLoginView("Usuario o contraseña incorrectos.", ['email' => $identificador]);
    }
}


function dashboard()
{
    checkAuth();
    header('Location: ' . BASE_URL . 'dashboard');
    exit();
}

function logout()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        AuditLog::record('LOGOUT', 'usuarios', $userId, null, null);
    }

    session_unset();
    session_destroy();

    header('Location: ' . BASE_URL . 'login');
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

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        AuditLog::record('LOGOUT', 'usuarios', $userId, null, null);
    }

    session_unset();
    session_destroy();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente',
        'redirect' => BASE_URL . 'login'
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
        'user_nombre' => $_SESSION['user_nombre'] ?? null,
        'user_avatar' => $_SESSION['user_avatar'] ?? null,
        'user_rol_id' => $_SESSION['user_rol_id'] ?? null
    ]);
    exit();
}
