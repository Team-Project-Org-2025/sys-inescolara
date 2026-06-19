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

        return $token;
    }

    public function validateToken(string $token): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM password_resets
             WHERE token = :token
               AND usado = 0
               AND expira_en > NOW()
             LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function markAsUsed(string $token): void
    {
        $stmt = $this->db()->prepare("UPDATE password_resets SET usado = 1 WHERE token = :token");
        $stmt->execute([':token' => $token]);
    }

    public function deleteExpiredTokens(): void
    {
        $this->db()->exec("DELETE FROM password_resets WHERE expira_en <= NOW() OR usado = 1");
    }
}
