<?php

namespace SysInescolara\helpers;

class Csrf
{
    public static function generate(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validate(string $token): void
    {
        if ($token === '' || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o expirado.']);
            exit;
        }
    }

    public static function render(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::generate() . '">';
    }
}
