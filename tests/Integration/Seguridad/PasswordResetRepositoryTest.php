<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqlitePasswordReset;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * NOW() está emulado en SQLite; las fechas se fijan de forma explícita para
 * que la prueba no dependa del reloj (expirados en 2020, vigentes en 2099).
 */
final class PasswordResetRepositoryTest extends SqliteTestCase
{
    private const EXPIRADO = '2020-01-01 00:00:00';

    private const VIGENTE = '2099-01-01 00:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->exec(<<<'SQL'
            CREATE TABLE roles (
                id_rol     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_rol TEXT DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE usuarios (
                id_usuario     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario TEXT NOT NULL UNIQUE,
                password_hash  TEXT NOT NULL,
                id_rol         INTEGER DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE password_resets (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                token      TEXT NOT NULL UNIQUE,
                correo     TEXT NOT NULL,
                expira_en  TEXT NOT NULL,
                usado      INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
            )
        SQL);

        $this->insert('roles', ['nombre_rol' => 'Administrador']);
        $this->insert('usuarios', [
            'nombre_usuario' => 'admin',
            'password_hash' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ]);

        SqlitePasswordReset::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqlitePasswordReset::boot(null);
        parent::tearDown();
    }

    private function insertarToken(string $token, string $expira, int $usado = 0, int $usuarioId = 1): int
    {
        return $this->insert('password_resets', [
            'usuario_id' => $usuarioId,
            'token' => $token,
            'correo' => 'admin@test.com',
            'expira_en' => $expira,
            'usado' => $usado,
        ]);
    }

    #[Test]
    public function createToken_genera_un_token_hexadecimal_de_64_caracteres(): void
    {
        $token = (new SqlitePasswordReset())->createToken(1, 'admin@test.com');

        $this->assertSame(64, strlen($token));
        $this->assertSame(1, preg_match('/^[0-9a-f]{64}$/', $token));
        $this->assertDatabaseHas('password_resets', [
            'usuario_id' => 1,
            'token' => $token,
            'correo' => 'admin@test.com',
            'usado' => 0,
        ]);
    }

    #[Test]
    public function createToken_programa_la_expiracion_en_el_futuro(): void
    {
        $token = (new SqlitePasswordReset())->createToken(1, 'admin@test.com');

        $fila = $this->fetchRowWhere('password_resets', ['token' => $token]);
        $this->assertNotNull($fila);
        $this->assertGreaterThan(date('Y-m-d H:i:s'), $fila['expira_en']);
    }

    #[Test]
    public function createToken_elimina_los_tokens_expirados_y_usados(): void
    {
        $this->insertarToken('expirado', self::EXPIRADO);
        $this->insertarToken('usado', self::VIGENTE, 1);
        $this->insertarToken('vigente', self::VIGENTE);

        $nuevo = (new SqlitePasswordReset())->createToken(1, 'admin@test.com');

        $this->assertDatabaseCount('password_resets', 2);
        $this->assertDatabaseMissing('password_resets', ['token' => 'expirado']);
        $this->assertDatabaseMissing('password_resets', ['token' => 'usado']);
        $this->assertDatabaseHas('password_resets', ['token' => 'vigente']);
        $this->assertDatabaseHas('password_resets', ['token' => $nuevo]);
    }

    #[Test]
    public function validateToken_devuelve_la_fila_del_token_vigente(): void
    {
        $this->insertarToken('vigente', self::VIGENTE);

        $fila = (new SqlitePasswordReset())->validateToken('vigente');

        $this->assertIsArray($fila);
        $this->assertSame('vigente', $fila['token']);
        $this->assertSame('admin@test.com', $fila['correo']);
        $this->assertSame(0, (int) $fila['usado']);
        $this->assertNotNull($fila['db_now']);
    }

    #[Test]
    public function validateToken_devuelve_null_para_un_token_inexistente(): void
    {
        $this->assertNull((new SqlitePasswordReset())->validateToken('no-existe'));
    }

    #[Test]
    public function validateToken_devuelve_null_para_un_token_usado(): void
    {
        $this->insertarToken('usado', self::VIGENTE, 1);

        $this->assertNull((new SqlitePasswordReset())->validateToken('usado'));
    }

    #[Test]
    public function validateToken_devuelve_null_para_un_token_expirado(): void
    {
        $this->insertarToken('expirado', self::EXPIRADO);

        $this->assertNull((new SqlitePasswordReset())->validateToken('expirado'));
    }

    #[Test]
    public function markAsUsed_marca_el_token_como_usado(): void
    {
        $this->insertarToken('vigente', self::VIGENTE);
        $modelo = new SqlitePasswordReset();

        $modelo->markAsUsed('vigente');

        $this->assertDatabaseHas('password_resets', ['token' => 'vigente', 'usado' => 1]);
        $this->assertNull($modelo->validateToken('vigente'));
    }

    #[Test]
    public function deleteExpiredTokens_conserva_solo_los_tokens_vigentes_y_sin_usar(): void
    {
        $this->insertarToken('expirado', self::EXPIRADO);
        $this->insertarToken('usado', self::VIGENTE, 1);
        $this->insertarToken('vigente', self::VIGENTE);

        (new SqlitePasswordReset())->deleteExpiredTokens();

        $this->assertDatabaseCount('password_resets', 1);
        $this->assertDatabaseHas('password_resets', ['token' => 'vigente']);
    }
}
