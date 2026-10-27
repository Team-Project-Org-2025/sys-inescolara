<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Ventas;

use PDO;

/**
 * PDO SQLite que ignora `SET NAMES ...`.
 *
 * CuentaCobrar ejecuta `SET NAMES 'utf8mb4' ...` antes de cada consulta, una
 * sentencia que SQLite no entiende. Este doble de conexión la convierte en un
 * no-op para poder ejecutar el resto del SQL real del modelo. Registra también
 * las funciones MySQL que usa CuentaCobrar (CONCAT, CURDATE, NOW, MONTH, YEAR).
 */
final class SqliteMysqlPdo extends PDO
{
    public static function create(): self
    {
        $pdo = new self('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->sqliteCreateFunction('now', static fn (): string => date('Y-m-d H:i:s'), 0);
        $pdo->sqliteCreateFunction('curdate', static fn (): string => date('Y-m-d'), 0);

        $pdo->sqliteCreateFunction(
            'concat',
            static function (mixed ...$values): ?string {
                foreach ($values as $value) {
                    if ($value === null) {
                        return null;
                    }
                }

                return implode('', array_map(static fn (mixed $value): string => (string) $value, $values));
            },
            -1
        );

        $pdo->sqliteCreateFunction(
            'month',
            static fn (mixed $date): ?int => $date === null ? null : (int) date('n', strtotime((string) $date)),
            1
        );

        $pdo->sqliteCreateFunction(
            'year',
            static fn (mixed $date): ?int => $date === null ? null : (int) date('Y', strtotime((string) $date)),
            1
        );

        return $pdo;
    }

    public function exec(string $statement): int|false
    {
        if (preg_match('/^\s*SET\s+NAMES\b/i', $statement) === 1) {
            return 0;
        }

        return parent::exec($statement);
    }
}
