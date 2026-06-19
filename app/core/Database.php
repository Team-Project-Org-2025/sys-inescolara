<?php

namespace SysInescolara\core;

use PDO;
use PDOException;

abstract class Database
{
    private PDO $pdo;

    public function __construct($connection = 'default')
    {
        try {
            $this->loadEnv();

            if ($connection === 'security') {
                $host = getenv('DB_SEC_HOST') !== false ? getenv('DB_SEC_HOST') : (getenv('DB_HOST') ?: 'localhost');
                $port = getenv('DB_SEC_PORT') !== false ? getenv('DB_SEC_PORT') : (getenv('DB_PORT') ?: '3306');
                $dbname = getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : (getenv('DB_NAME') ?: 'SysInescolara-Seguridad');
                $username = getenv('DB_SEC_USER') !== false ? getenv('DB_SEC_USER') : (getenv('DB_USER') ?: 'root');
                $password = getenv('DB_SEC_PASSWORD') !== false ? getenv('DB_SEC_PASSWORD') : (getenv('DB_PASSWORD') ?: '');
            } else {
                $host = getenv('DB_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: '3306';
                $dbname = getenv('DB_NAME') ?: 'sysinescolara';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: '';
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $dbname
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $dbSsl = getenv('DB_SSL') === 'true';
            if ($dbSsl) {
                $caCert = getenv('DB_CA_CERT');
                if ($caCert && file_exists($caCert)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $caCert;
                } else {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            if (getenv('APP_DEBUG') === 'true') {
                error_log("Database connection error: " . $e->getMessage());
                die("Error de conexión: " . $e->getMessage());
            } else {
                die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }
    }

    protected function db(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function loadEnv(): void
    {
        $possiblePaths = [
            dirname(__DIR__, 2) . '/.env',
            __DIR__ . '/../../.env',
            $_SERVER['DOCUMENT_ROOT'] . '/.env'
        ];

        $envFile = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $envFile = $path;
                break;
            }
        }

        if (!$envFile) {
            error_log("Warning: .env file not found in any expected location");
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, '"\'');
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
