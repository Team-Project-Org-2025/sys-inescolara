<?php

namespace SysInescolara\helpers;

class Auth
{
    public static function id(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function name(): string
    {
        return $_SESSION['user_nombre'] ?? 'Usuario';
    }

    public static function email(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    public static function avatar(): ?string
    {
        return $_SESSION['user_avatar'] ?? null;
    }

    public static function roleId(): int
    {
        return (int)($_SESSION['user_rol_id'] ?? 0);
    }

    public static function permisos(): array
    {
        return $_SESSION['user_permisos'] ?? [];
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::roleId() === 1;
    }

    public static function hasPermiso(string $codigo): bool
    {
        return in_array($codigo, self::permisos(), true);
    }

    public static function hasModuleAccess(string $modulo, string $accion): bool
    {
        if (self::isAdmin()) return true;
        return self::hasPermiso("$modulo:$accion");
    }

    public static function set(array $data): void
    {
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public static function setField(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function attempt(string $username, string $password): bool
    {
        session_start();
        $userModel = new \SysInescolara\models\Usuario();
        $user = $userModel->authenticate($username, $password);
        if ($user) {
            self::set([
                'user_id' => $user['id'],
                'user_nombre' => $user['nombre_usuario'],
                'user_email' => $user['correo_electronico'] ?? null,
                'user_avatar' => $user['avatar'] ?? null,
                'user_rol_id' => $user['rol_id'],
                'user_permisos' => $userModel->getRolePermissions((int)$user['rol_id'], $user['id']),
            ]);
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
