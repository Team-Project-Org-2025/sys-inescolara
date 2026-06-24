<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Usuario;
use SysInescolara\models\PasswordReset;
use SysInescolara\models\AuditLog;
use SysInescolara\helpers\Mailer;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2) . '/');
}

function renderPasswordView(string $view, array $data = []): void
{
    $title = $data['title'] ?? 'Recuperar Contraseña';
    $layout = ROOT_PATH . 'app/views/layouts/auth.php';
    $viewPath = ROOT_PATH . 'app/views/auth/' . $view . '.php';

    extract($data);
    ob_start();
    require $viewPath;
    $content = ob_get_clean();
    require $layout;
}

function getBaseUrl(): string
{
    return defined('BASE_URL') ? BASE_URL : '/';
}

function index(): void
{
    renderPasswordView('recuperar', [
        'title' => 'Recuperar Contraseña',
        'success' => $_GET['success'] ?? null,
    ]);
}

function enviar(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . getBaseUrl() . 'recuperar-password');
        exit();
    }

    $csrfToken = $_POST['_csrf_token'] ?? '';
    if ($csrfToken === '' || !hash_equals($_SESSION['_csrf_token'] ?? '', $csrfToken)) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Token de seguridad inválido. Intenta de nuevo.',
            'old' => ['correo' => $_POST['correo'] ?? ''],
        ]);
        return;
    }

    $correo = trim($_POST['correo'] ?? '');
    $error = null;

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Ingresa un correo electrónico válido.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (empty($recaptchaToken)) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Por favor, completa la verificación de seguridad.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    $recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
    $verifyResponse = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
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
    $verifyData = $verifyResponse ? json_decode($verifyResponse, true) : [];

    if (!($verifyData['success'] ?? false)) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Verificación de seguridad fallida. Intenta de nuevo.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    $rateLimitKey = 'pwd_reset_' . md5(strtolower($correo));
    $lastRequest = $_SESSION[$rateLimitKey] ?? 0;
    if (time() - $lastRequest < 300) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Ya enviaste una solicitud recientemente. Espera 5 minutos antes de intentar de nuevo.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    $userModel = new Usuario();
    $user = $userModel->getUserByEmail($correo);

    if (!$user) {
        $_SESSION[$rateLimitKey] = time();
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'No encontramos una cuenta con ese correo electrónico.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    $resetModel = new PasswordReset();
    $token = $resetModel->createToken((int)$user['id'], $correo);

    AuditLog::record('CREATE', 'password_resets', (int)$user['id'], null, [
        'correo' => $correo,
        'token_preview' => substr($token, 0, 8) . '...',
    ]);

    $resetLink = getBaseUrl() . 'recuperar-password/cambiar?token=' . urlencode($token);
    $userName = htmlspecialchars($user['nombre_usuario'] ?? 'Usuario');

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Recuperar Contraseña</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
    <div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <div style="background:#2e7d32;padding:24px;text-align:center;">
            <h2 style="color:#fff;margin:0;font-size:1.25rem;">SYSINECOLARA</h2>
        </div>
        <div style="padding:32px;">
            <p style="font-size:1rem;color:#333;">Hola <strong>{$userName}</strong>,</p>
            <p style="color:#555;line-height:1.6;">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta.
                Haz clic en el botón de abajo para crear una nueva contraseña.
            </p>
            <div style="text-align:center;margin:28px 0;">
                <a href="{$resetLink}" style="display:inline-block;padding:12px 32px;background:#e5a835;color:#1a1f2e;text-decoration:none;border-radius:8px;font-weight:600;font-size:1rem;">
                    Restablecer Contraseña
                </a>
            </div>
            <p style="color:#888;font-size:0.85rem;line-height:1.5;">
                Este enlace expira en <strong>1 hora</strong>.
                Si no solicitaste este cambio, ignora este mensaje.
            </p>
            <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
            <p style="color:#aaa;font-size:0.8rem;text-align:center;">
                INECOLARA — Instituto de Ecosocialismo del Estado Lara
            </p>
        </div>
    </div>
</body>
</html>
HTML;

    $_SESSION[$rateLimitKey] = time();

    $sent = Mailer::send($correo, $userName, 'Recuperación de Contraseña - SYSINECOLARA', $htmlBody);

    if (!$sent) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'Ocurrió un error al enviar el correo. Intenta de nuevo más tarde.',
            'old' => ['correo' => $correo],
        ]);
        return;
    }

    header('Location: ' . getBaseUrl() . 'recuperar-password?success=1');
    exit();
}

function cambiar(): void
{
    $token = trim($_GET['token'] ?? '');

    if ($token === '') {
        header('Location: ' . getBaseUrl() . 'recuperar-password');
        exit();
    }

    $resetModel = new PasswordReset();
    $data = $resetModel->validateToken($token);

    if (!$data) {
        renderPasswordView('recuperar', [
            'title' => 'Recuperar Contraseña',
            'error' => 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.',
        ]);
        return;
    }

    renderPasswordView('reset-password', [
        'title' => 'Cambiar Contraseña',
        'token' => $token,
        'correo' => htmlspecialchars($data['correo']),
    ]);
}

function restablecer(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . getBaseUrl() . 'recuperar-password');
        exit();
    }

    $csrfToken = $_POST['_csrf_token'] ?? '';
    if ($csrfToken === '' || !hash_equals($_SESSION['_csrf_token'] ?? '', $csrfToken)) {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'Token de seguridad inválido. Intenta de nuevo.',
            'token' => $_POST['token'] ?? '',
        ]);
        return;
    }

    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($token === '' || $password === '' || $password2 === '') {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'Todos los campos son obligatorios.',
            'token' => $token,
        ]);
        return;
    }

    if ($password !== $password2) {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'Las contraseñas no coinciden.',
            'token' => $token,
        ]);
        return;
    }

    $userModel = new Usuario();
    if (!$userModel->isPasswordStrong($password)) {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'La contraseña debe tener al menos 8 caracteres, incluir mayúscula, minúscula, número y un carácter especial (@\$!%*?&._-).',
            'token' => $token,
        ]);
        return;
    }

    $resetModel = new PasswordReset();
    $data = $resetModel->validateToken($token);

    if (!$data) {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'El enlace de recuperación es inválido o ha expirado.',
            'token' => $token,
        ]);
        return;
    }

    $userId = (int)$data['usuario_id'];
    $success = $userModel->updatePassword($userId, $password);

    if (!$success) {
        renderPasswordView('reset-password', [
            'title' => 'Cambiar Contraseña',
            'error' => 'Ocurrió un error al actualizar la contraseña.',
            'token' => $token,
        ]);
        return;
    }

    $resetModel->markAsUsed($token);

    $userData = $userModel->getById($userId);
    $correoUsuario = $userData['correo_electronico'] ?? $data['correo'] ?? '';
    $nombreUsuario = $userData['nombre_usuario'] ?? 'Usuario';

    AuditLog::record('UPDATE', 'usuarios', $userId, null, [
        'password_changed' => true,
        'via' => 'password_reset',
    ]);

    $resetLinkNotify = getBaseUrl() . 'login';

    $notifyHtml = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Contraseña Actualizada</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
    <div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <div style="background:#2e7d32;padding:24px;text-align:center;">
            <h2 style="color:#fff;margin:0;font-size:1.25rem;">SYSINECOLARA</h2>
        </div>
        <div style="padding:32px;">
            <p style="font-size:1rem;color:#333;">Hola <strong>{$nombreUsuario}</strong>,</p>
            <p style="color:#555;line-height:1.6;">
                Tu contraseña ha sido actualizada exitosamente.
            </p>
            <p style="color:#555;line-height:1.6;">
                Si no realizaste este cambio, contacta al administrador del sistema de inmediato.
            </p>
            <div style="text-align:center;margin:28px 0;">
                <a href="{$resetLinkNotify}" style="display:inline-block;padding:12px 32px;background:#e5a835;color:#1a1f2e;text-decoration:none;border-radius:8px;font-weight:600;font-size:1rem;">
                    Iniciar Sesión
                </a>
            </div>
            <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
            <p style="color:#aaa;font-size:0.8rem;text-align:center;">
                INECOLARA — Instituto de Ecosocialismo del Estado Lara
            </p>
        </div>
    </div>
</body>
</html>
HTML;

    if ($correoUsuario !== '') {
        Mailer::send($correoUsuario, $nombreUsuario, 'Contraseña Actualizada - SYSINECOLARA', $notifyHtml);
    }

    header('Location: ' . getBaseUrl() . 'login?reset=ok');
    exit();
}
