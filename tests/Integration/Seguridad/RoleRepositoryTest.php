<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Seguridad;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteRole;
use SysInescolara\Tests\Support\SqliteTestCase;

final class RoleRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec(<<<'SQL'
            CREATE TABLE roles (
                id_rol          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_rol      TEXT DEFAULT NULL,
                descripcion_rol TEXT DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE usuarios (
                id_usuario     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario TEXT NOT NULL UNIQUE,
                password_hash  TEXT NOT NULL,
                id_rol         INTEGER DEFAULT NULL,
                estatus        TEXT DEFAULT 'Activo',
                FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
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
                fecha_accion         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        SQL);

        SqliteRole::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteRole::boot(null);
        parent::tearDown();
    }

    private function crearUsuarioEnRol(string $nombre, int $rolId): int
    {
        return $this->insert('usuarios', [
            'nombre_usuario' => $nombre,
            'password_hash' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'id_rol' => $rolId,
        ]);
    }

    #[Test]
    public function getAll_devuelve_los_roles_ordenados_con_alias_id(): void
    {
        $this->insert('roles', ['nombre_rol' => 'Trabajador', 'descripcion_rol' => 'Acceso limitado']);
        $this->insert('roles', ['nombre_rol' => 'Administrador', 'descripcion_rol' => 'Acceso total']);

        $roles = (new SqliteRole())->getAll();

        $this->assertCount(2, $roles);
        $this->assertSame(['Trabajador', 'Administrador'], array_column($roles, 'nombre_rol'));
        $this->assertSame(1, $roles[0]['id']);
        $this->assertSame('Acceso total', $roles[1]['descripcion_rol']);
    }

    #[Test]
    public function getById_devuelve_la_fila_con_alias_id_o_null(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor', 'descripcion_rol' => 'Solo lectura']);
        $modelo = new SqliteRole();

        $rol = $modelo->getById($id);

        $this->assertIsArray($rol);
        $this->assertSame($id, $rol['id']);
        $this->assertSame('Auditor', $rol['nombre_rol']);
        $this->assertSame('Solo lectura', $rol['descripcion_rol']);
        $this->assertNull($modelo->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_rol_existe(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor']);
        $modelo = new SqliteRole();

        $this->assertTrue($modelo->exists($id));
        $this->assertFalse($modelo->exists(99999));
    }

    #[Test]
    public function existsByName_detecta_duplicados_y_permite_excluir_un_id(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor']);
        $modelo = new SqliteRole();

        $this->assertTrue($modelo->existsByName('Auditor'));
        $this->assertFalse($modelo->existsByName('Auditor', $id));
        $this->assertFalse($modelo->existsByName('Fantasma'));
    }

    #[Test]
    public function add_inserta_el_rol_y_devuelve_true(): void
    {
        $modelo = new SqliteRole();

        $this->assertTrue($modelo->add('Auditor', 'Solo lectura'));

        $this->assertDatabaseHas('roles', [
            'nombre_rol' => 'Auditor',
            'descripcion_rol' => 'Solo lectura',
        ]);
        $this->assertSame(1, $modelo->getLastInsertId());
    }

    #[Test]
    public function add_sin_descripcion_guarda_null(): void
    {
        $this->assertTrue((new SqliteRole())->add('Auditor'));

        $fila = $this->fetchRowWhere('roles', ['nombre_rol' => 'Auditor']);
        $this->assertNotNull($fila);
        $this->assertNull($fila['descripcion_rol']);
    }

    #[Test]
    public function add_con_nombre_invalido_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        (new SqliteRole())->add('R0l');
    }

    #[Test]
    public function update_modifica_el_rol_existente(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor', 'descripcion_rol' => 'Solo lectura']);

        $this->assertTrue((new SqliteRole())->update($id, 'Supervisor', 'Revisa inventario'));

        $this->assertDatabaseHas('roles', [
            'id_rol' => $id,
            'nombre_rol' => 'Supervisor',
            'descripcion_rol' => 'Revisa inventario',
        ]);
    }

    #[Test]
    public function update_con_nombre_invalido_lanza_excepcion_de_validacion(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        (new SqliteRole())->update($id, 'A');
    }

    #[Test]
    public function delete_elimina_un_rol_sin_usuarios_asignados(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor']);

        $this->assertTrue((new SqliteRole())->delete($id));

        $this->assertDatabaseMissing('roles', ['id_rol' => $id]);
        $this->assertDatabaseCount('roles', 0);
    }

    #[Test]
    public function delete_rechaza_un_rol_con_usuarios_asignados(): void
    {
        $id = $this->insert('roles', ['nombre_rol' => 'Auditor']);
        $this->crearUsuarioEnRol('juan', $id);

        try {
            (new SqliteRole())->delete($id);
            $this->fail('Se esperaba una excepción al eliminar un rol con usuarios asignados.');
        } catch (Exception $e) {
            $this->assertSame('No se puede eliminar el rol: hay usuarios asignados a él.', $e->getMessage());
        }

        $this->assertDatabaseHas('roles', ['id_rol' => $id]);
    }

    #[Test]
    public function delete_permite_eliminar_un_rol_cuyos_usuarios_fueron_reasignados(): void
    {
        $auditor = $this->insert('roles', ['nombre_rol' => 'Auditor']);
        $trabajador = $this->insert('roles', ['nombre_rol' => 'Trabajador']);
        $usuarioId = $this->crearUsuarioEnRol('juan', $auditor);

        $this->pdo->prepare('UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?')
            ->execute([$trabajador, $usuarioId]);

        $this->assertTrue((new SqliteRole())->delete($auditor));
        $this->assertDatabaseMissing('roles', ['id_rol' => $auditor]);
    }

    #[Test]
    public function add_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE roles');

        $this->assertFalse((new SqliteRole())->add('Auditor'));
    }
}
