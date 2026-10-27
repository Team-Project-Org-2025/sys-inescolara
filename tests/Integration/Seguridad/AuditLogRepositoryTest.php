<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\helpers\Auth;
use SysInescolara\Tests\Support\SqliteAuditLog;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Pruebas del SQL real de AuditLog sobre SQLite en memoria.
 *
 * Omisión documentada: `AuditLog::record()` con sesión iniciada ejecuta
 * `new self()`, que siempre instancia el modelo real y abre MySQL
 * (`Database::__construct`). Esa conexión no puede redirigirse al PDO SQLite
 * del doble y, al ejecutarla, escribiría en la base de desarrollo o
 * terminaría el proceso. Por eso se prueba el método en el que delega:
 * `log()` con `Auth::id()`.
 */
final class AuditLogRepositoryTest extends SqliteTestCase
{
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
            CREATE TABLE auditoria_logs (
                id_log               INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario           INTEGER DEFAULT NULL,
                accion               TEXT DEFAULT NULL,
                tabla_afectada       TEXT DEFAULT NULL,
                id_registro_afectado INTEGER DEFAULT NULL,
                valor_anterior       TEXT DEFAULT NULL,
                valor_nuevo          TEXT DEFAULT NULL,
                endpoint_solicitado  TEXT DEFAULT NULL,
                fecha_accion         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
            )
        SQL);

        $this->insert('roles', ['nombre_rol' => 'Administrador']);
        $this->insert('usuarios', [
            'nombre_usuario' => 'admin',
            'password_hash' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ]);

        SqliteAuditLog::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteAuditLog::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function log_inserta_el_registro_con_json_y_endpoint(): void
    {
        $log = new SqliteAuditLog();

        $this->assertTrue($log->log(
            1,
            'UPDATE',
            'roles',
            3,
            ['nombre_rol' => 'Auditor'],
            ['nombre_rol' => 'Supervisor']
        ));

        $fila = $this->fetchRowWhere('auditoria_logs', ['accion' => 'UPDATE']);
        $this->assertNotNull($fila);
        $this->assertSame(1, (int) $fila['id_usuario']);
        $this->assertSame('roles', $fila['tabla_afectada']);
        $this->assertSame(3, (int) $fila['id_registro_afectado']);
        $this->assertSame('{"nombre_rol":"Auditor"}', $fila['valor_anterior']);
        $this->assertSame('{"nombre_rol":"Supervisor"}', $fila['valor_nuevo']);
        $this->assertSame('/phpunit', $fila['endpoint_solicitado']);
        $this->assertNotNull($fila['fecha_accion']);
    }

    #[Test]
    public function log_sin_valores_anteriores_ni_nuevos_guarda_null(): void
    {
        $log = new SqliteAuditLog();

        $this->assertTrue($log->log(1, 'LOGIN', 'usuarios'));

        $fila = $this->fetchRowWhere('auditoria_logs', ['accion' => 'LOGIN']);
        $this->assertNotNull($fila);
        $this->assertNull($fila['valor_anterior']);
        $this->assertNull($fila['valor_nuevo']);
        $this->assertNull($fila['id_registro_afectado']);
    }

    #[Test]
    public function log_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE auditoria_logs');

        $this->assertFalse((new SqliteAuditLog())->log(1, 'CREATE', 'roles', 1));
    }

    #[Test]
    public function getAll_incluye_el_nombre_del_usuario_y_ordena_por_fecha_descendente(): void
    {
        $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'CREATE',
            'tabla_afectada' => 'roles',
            'fecha_accion' => '2024-01-01 10:00:00',
        ]);
        $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'DELETE',
            'tabla_afectada' => 'roles',
            'fecha_accion' => '2024-03-01 10:00:00',
        ]);
        $this->insert('auditoria_logs', [
            'id_usuario' => null,
            'accion' => 'UPDATE',
            'tabla_afectada' => 'roles',
            'fecha_accion' => '2024-02-01 10:00:00',
        ]);

        $logs = (new SqliteAuditLog())->getAll();

        $this->assertCount(3, $logs);
        $this->assertSame(['DELETE', 'UPDATE', 'CREATE'], array_column($logs, 'accion'));
        $this->assertSame('admin', $logs[0]['nombre_usuario']);
        $this->assertNull($logs[1]['nombre_usuario']);
    }

    #[Test]
    public function getAll_sin_tabla_devuelve_arreglo_vacio_degradado(): void
    {
        $this->exec('DROP TABLE auditoria_logs');

        $this->assertSame([], (new SqliteAuditLog())->getAll());
    }

    #[Test]
    public function getById_devuelve_la_fila_con_el_usuario_o_null(): void
    {
        $id = $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'CREATE',
            'tabla_afectada' => 'roles',
            'id_registro_afectado' => 2,
            'fecha_accion' => '2024-01-01 10:00:00',
        ]);
        $modelo = new SqliteAuditLog();

        $fila = $modelo->getById($id);

        $this->assertIsArray($fila);
        $this->assertSame($id, (int) $fila['id_log']);
        $this->assertSame('admin', $fila['nombre_usuario']);
        $this->assertSame('CREATE', $fila['accion']);
        $this->assertNull($modelo->getById(99999));
    }

    #[Test]
    public function record_sin_sesion_iniciada_no_inserta_en_la_bitacora(): void
    {
        $_SESSION = [];

        SqliteAuditLog::record('CREATE', 'roles', 1, null, ['nombre_rol' => 'Auditor']);

        $this->assertDatabaseCount('auditoria_logs', 0);
    }

    #[Test]
    public function con_sesion_iniciada_se_registra_el_log_con_el_id_del_usuario_de_la_sesion(): void
    {
        $_SESSION['user_id'] = 1;
        $modelo = new SqliteAuditLog();

        $this->assertSame(1, Auth::id());
        $this->assertTrue($modelo->log(Auth::id(), 'CREATE', 'roles', 1, null, ['nombre_rol' => 'Auditor']));

        $this->assertDatabaseHas('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'CREATE',
            'tabla_afectada' => 'roles',
        ]);
    }
}
