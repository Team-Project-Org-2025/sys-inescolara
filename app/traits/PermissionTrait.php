<?php

namespace SysInescolara\traits;

trait PermissionTrait
{
    protected function checkModuleAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'No autorizado',
                    'redirect' => BASE_URL . 'login',
                ]);
                exit();
            }
            header('Location: ' . BASE_URL . 'login');
            exit();
        }
    }

    protected function checkPermisoOrFail(string $codigo): void
    {
        $permisos = $_SESSION['user_permisos'] ?? [];
        if (!in_array($codigo, $permisos, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para realizar esta acción.']);
            exit();
        }
    }
}
