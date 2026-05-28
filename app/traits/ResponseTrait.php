<?php

namespace SysInescolara\traits;

trait ResponseTrait
{
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    protected function handleError(\Exception $e, bool $isAjax): void
    {
        if ($isAjax) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        http_response_code(500);
        echo 'Error: ' . htmlspecialchars($e->getMessage());
        exit();
    }

    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
