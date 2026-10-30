<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use PDO;

final class Db
{
    private static ?PDO $app = null;
    private static ?PDO $security = null;

    public static function app(): PDO
    {
        if (self::$app === null) {
            self::$app = self::connect(
                Config::dbHost(),
                Config::dbPort(),
                Config::dbName(),
                Config::dbUser(),
                Config::dbPassword()
            );
        }
        return self::$app;
    }

    public static function security(): PDO
    {
        if (self::$security === null) {
            self::$security = self::connect(
                Config::securityDbHost(),
                Config::securityDbPort(),
                Config::securityDbName(),
                Config::securityDbUser(),
                Config::securityDbPassword()
            );
        }
        return self::$security;
    }

    public static function scalar(string $sql, array $params = [], string $connection = 'app'): mixed
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function row(string $sql, array $params = [], string $connection = 'app'): ?array
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = [], string $connection = 'app'): array
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function execute(string $sql, array $params = [], string $connection = 'app'): int
    {
        $stmt = self::pdo($connection)->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastId(string $connection = 'app'): int
    {
        return (int) self::pdo($connection)->lastInsertId();
    }

    private static function pdo(string $connection): PDO
    {
        return $connection === 'security' ? self::security() : self::app();
    }

    private static function connect(string $host, string $port, string $name, string $user, string $password): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
