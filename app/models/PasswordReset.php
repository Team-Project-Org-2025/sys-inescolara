<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class PasswordReset extends Database
{
    public function __construct()
    {
        parent::__construct('security');
        $this->bootstrapTable();
    }

    private function bootstrapTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `usuario_id` INT NOT NULL,
            `token` VARCHAR(64) NOT NULL UNIQUE,
            `correo` VARCHAR(100) NOT NULL,
            `expira_en` DATETIME NOT NULL,
            `usado` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_token` (`token`),
            INDEX `idx_usuario` (`usuario_id`),
            INDEX `idx_expira` (`expira_en`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db()->exec($sql);
    }

    public function createToken(int $usuarioId, string $correo): string
    {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $now = date('Y-m-d H:i:s');

        $this->deleteExpiredTokens();

        $stmt = $this->db()->prepare(
            "INSERT INTO password_resets (usuario_id, token, correo, expira_en)
             VALUES (:uid, :token, :correo, :expira)"
        );
        $stmt->execute([
            ':uid' => $usuarioId,
            ':token' => $token,
            ':correo' => $correo,
            ':expira' => $expira,
        ]);

        error_log("PasswordReset: token creado para usuario {$usuarioId} | expira_en={$expira} | php_now={$now} | token_preview=" . substr($token, 0, 12) . "...");

        return $token;
    }

    public function validateToken(string $token): ?array
    {
        $tokenPreview = substr($token, 0, 12) . '...';
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db()->prepare(
            "SELECT * FROM password_resets
             WHERE token = :token
               AND usado = 0
               AND expira_en > :now
             LIMIT 1"
        );
        $stmt->execute([':token' => $token, ':now' => $now]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $debug = $this->db()->prepare("SELECT id, expira_en, usado, :now AS db_now FROM password_resets WHERE token = :token2 LIMIT 1");
            $debug->execute([':token2' => $token, ':now' => $now]);
            $existing = $debug->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                error_log("PasswordReset: token {$tokenPreview} encontrado pero NO pasó validación | expira_en={$existing['expira_en']} | usado={$existing['usado']} | php_now={$now}");
            } else {
                error_log("PasswordReset: token {$tokenPreview} NO encontrado en DB | php_now={$now}");
            }
        }

        return $row ?: null;
    }

    public function markAsUsed(string $token): void
    {
        $stmt = $this->db()->prepare("UPDATE password_resets SET usado = 1 WHERE token = :token");
        $stmt->execute([':token' => $token]);
    }

    public function deleteExpiredTokens(): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db()->prepare("DELETE FROM password_resets WHERE expira_en <= :now OR usado = 1");
        $stmt->execute([':now' => $now]);
    }

    public function debugToken(string $token): string
    {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db()->prepare("SELECT id, expira_en, usado, created_at, :now AS php_now FROM password_resets WHERE token = :t LIMIT 1");
            $stmt->execute([':t' => $token, ':now' => $now]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return "expira_en={$row['expira_en']}, usado={$row['usado']}, creado={$row['created_at']}, php_now={$row['php_now']}";
            }
            return "TOKEN_NO_EXISTE";
        } catch (\Throwable $e) {
            return "ERROR: " . $e->getMessage();
        }
    }
}
