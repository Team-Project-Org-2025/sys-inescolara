<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base para pruebas de integración con SQLite en memoria.
 *
 * Registra equivalentes de las funciones MySQL que usan los modelos
 * (NOW, CURDATE, CONCAT, MONTH, YEAR, DATEDIFF, DATE_FORMAT, IF) para poder
 * ejecutar el SQL real sin depender de MariaDB/MySQL.
 */
abstract class SqliteTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $this->registerMysqlFunctions();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    protected function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    /**
     * Adjunta una base de seguridad en memoria para que el SQL real con
     * `SysInescolara-Seguridad`.usuarios pueda ejecutarse en SQLite.
     */
    protected function attachSecurityDatabase(): void
    {
        $this->pdo->exec('ATTACH DATABASE ":memory:" AS "SysInescolara-Seguridad"');
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $this->assertIdentifier($table);
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchRow(string $table, int $id, string $primaryKey = 'id'): ?array
    {
        $this->assertIdentifier($table);
        $this->assertIdentifier($primaryKey);

        $statement = $this->pdo->prepare(
            sprintf('SELECT * FROM `%s` WHERE `%s` = ?', $table, $primaryKey)
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $where
     * @return array<string, mixed>|null
     */
    protected function fetchRowWhere(string $table, array $where): ?array
    {
        [$clause, $params] = $this->buildWhere($where);
        $this->assertIdentifier($table);

        $statement = $this->pdo->prepare(sprintf('SELECT * FROM `%s` WHERE %s', $table, $clause));
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function countRows(string $table): int
    {
        $this->assertIdentifier($table);

        return (int) $this->pdo->query(sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();
    }

    /**
     * @param array<string, mixed> $where
     */
    protected function assertDatabaseHas(string $table, array $where): void
    {
        self::assertNotNull(
            $this->fetchRowWhere($table, $where),
            sprintf('No se encontró una fila en `%s` con %s', $table, json_encode($where))
        );
    }

    /**
     * @param array<string, mixed> $where
     */
    protected function assertDatabaseMissing(string $table, array $where): void
    {
        self::assertNull(
            $this->fetchRowWhere($table, $where),
            sprintf('Se encontró una fila inesperada en `%s` con %s', $table, json_encode($where))
        );
    }

    /**
     * @param array<string, mixed> $where
     */
    protected function assertDatabaseCount(string $table, int $expected, array $where = []): void
    {
        if ($where === []) {
            self::assertSame($expected, $this->countRows($table));
            return;
        }

        [$clause, $params] = $this->buildWhere($where);
        $statement = $this->pdo->prepare(sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, $clause));
        $statement->execute($params);

        self::assertSame($expected, (int) $statement->fetchColumn());
    }

    private function registerMysqlFunctions(): void
    {
        $this->pdo->sqliteCreateFunction('now', static fn (): string => date('Y-m-d H:i:s'), 0);
        $this->pdo->sqliteCreateFunction('curdate', static fn (): string => date('Y-m-d'), 0);

        $this->pdo->sqliteCreateFunction(
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

        $this->pdo->sqliteCreateFunction(
            'month',
            static fn (mixed $date): ?int => $date === null ? null : (int) date('n', strtotime((string) $date)),
            1
        );

        $this->pdo->sqliteCreateFunction(
            'year',
            static fn (mixed $date): ?int => $date === null ? null : (int) date('Y', strtotime((string) $date)),
            1
        );

        $this->pdo->sqliteCreateFunction(
            'datediff',
            static fn (mixed $left, mixed $right): ?int => ($left === null || $right === null)
                ? null
                : (int) ((strtotime((string) $left) - strtotime((string) $right)) / 86400),
            2
        );

        $this->pdo->sqliteCreateFunction(
            'date_format',
            static function (mixed $date, mixed $format): ?string {
                if ($date === null || $format === null) {
                    return null;
                }

                $phpFormat = strtr((string) $format, [
                    '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n',
                    '%d' => 'd', '%e' => 'j', '%H' => 'H', '%h' => 'h',
                    '%i' => 'i', '%s' => 's', '%W' => 'l', '%M' => 'F',
                    '%b' => 'M', '%a' => 'D',
                ]);

                return date($phpFormat, strtotime((string) $date));
            },
            2
        );

        $this->pdo->sqliteCreateFunction(
            'if',
            static fn (mixed $condition, mixed $whenTrue, mixed $whenFalse): mixed => $condition ? $whenTrue : $whenFalse,
            3
        );

        $this->pdo->sqliteCreateFunction(
            'greatest',
            static fn (mixed ...$values): mixed => max($values),
            -1
        );

        $this->pdo->sqliteCreateFunction(
            'least',
            static fn (mixed ...$values): mixed => min($values),
            -1
        );
    }

    /**
     * @param array<string, mixed> $where
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhere(array $where): array
    {
        $parts = [];
        $params = [];

        foreach ($where as $column => $value) {
            $this->assertIdentifier($column);
            if ($value === null) {
                $parts[] = sprintf('`%s` IS NULL', $column);
                continue;
            }

            $parts[] = sprintf('`%s` = ?', $column);
            $params[] = $value;
        }

        return [$parts === [] ? '1 = 1' : implode(' AND ', $parts), $params];
    }

    private function assertIdentifier(string $identifier): void
    {
        self::assertMatchesRegularExpression(
            '/^[A-Za-z_][A-Za-z0-9_]*$/',
            $identifier,
            sprintf('Identificador SQL inválido en la prueba: %s', $identifier)
        );
    }
}
