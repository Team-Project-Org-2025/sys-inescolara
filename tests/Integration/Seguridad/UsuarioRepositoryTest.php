<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Seguridad;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteTestCase;
use SysInescolara\Tests\Support\SqliteUsuario;

/**
 * Ejercita el SQL real de Usuario sobre SQLite en memoria.
 *
 * El esquema de seguridad se crea a mano: no se puede instanciar
 * `new Usuario(true)` porque su bootstrap usa SHOW COLUMNS / ALTER TABLE.
 */
final class UsuarioRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec(<<<'SQL'
            CREATE TABLE roles (
                id_rol         INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_rol     TEXT DEFAULT NULL,
                descripcion_rol TEXT DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE usuarios (
                id_usuario          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario      TEXT NOT NULL UNIQUE,
                password_hash       TEXT NOT NULL,
                correo_electronico  TEXT DEFAULT NULL UNIQUE,
                avatar              TEXT DEFAULT NULL,
                id_rol              INTEGER DEFAULT NULL,
                estatus             TEXT DEFAULT 'Activo',
                intentos_fallidos   INTEGER DEFAULT 0,
                ultimo_acceso       TEXT DEFAULT NULL,
                created_at          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                nombre_trabajador   TEXT DEFAULT NULL,
                apellido_trabajador TEXT DEFAULT NULL,
                cedula_trabajador   TEXT DEFAULT NULL,
                telefono_trabajador TEXT DEFAULT NULL,
                cargo               TEXT DEFAULT NULL,
                FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE modulos (
                id_modulo          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_modulo      TEXT NOT NULL UNIQUE,
                descripcion_modulo TEXT DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE permisos (
                id_permiso     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_permiso TEXT NOT NULL UNIQUE
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE rol_modulo_permiso (
                id_rol_modulo_permiso INTEGER PRIMARY KEY AUTOINCREMENT,
                id_rol                INTEGER NOT NULL,
                id_modulo             INTEGER NOT NULL,
                id_permiso            INTEGER NOT NULL,
                UNIQUE (id_rol, id_modulo, id_permiso),
                FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE,
                FOREIGN KEY (id_modulo) REFERENCES modulos (id_modulo) ON DELETE CASCADE,
                FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE usuario_modulo_permiso (
                id_usuario_modulo_permiso INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario                INTEGER NOT NULL,
                id_modulo                 INTEGER NOT NULL,
                id_permiso                INTEGER NOT NULL,
                UNIQUE (id_usuario, id_modulo, id_permiso),
                FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
                FOREIGN KEY (id_modulo) REFERENCES modulos (id_modulo) ON DELETE CASCADE,
                FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE
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

        SqliteUsuario::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteUsuario::boot(null);
        parent::tearDown();
    }

    private function crearRol(string $nombre, ?string $descripcion = null): int
    {
        return $this->insert('roles', [
            'nombre_rol' => $nombre,
            'descripcion_rol' => $descripcion,
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function crearUsuario(
        string $nombre,
        string $password,
        int $rolId,
        ?string $correo = null,
        array $extra = []
    ): int {
        return $this->insert('usuarios', array_merge([
            'nombre_usuario' => $nombre,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id_rol' => $rolId,
            'correo_electronico' => $correo,
        ], $extra));
    }

    #[Test]
    public function authenticate_con_credenciales_correctas_devuelve_el_usuario_normalizado(): void
    {
        $rolId = $this->crearRol('Administrador');
        $id = $this->crearUsuario('admin', 'Clave123!', $rolId, 'admin@test.com');

        $usuario = (new SqliteUsuario())->authenticate('admin', 'Clave123!');

        $this->assertIsArray($usuario);
        $this->assertSame($id, $usuario['id']);
        $this->assertSame('admin', $usuario['nombre_usuario']);
        $this->assertSame($rolId, $usuario['rol_id']);
        $this->assertSame('admin@test.com', $usuario['correo_electronico']);
        $this->assertSame('Activo', $usuario['estatus']);
        $this->assertNull($usuario['nombre_rol']);
        $this->assertSame('', $usuario['trabajador_nombre']);
    }

    #[Test]
    public function authenticate_acepta_el_correo_electronico_como_identificador(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $this->crearUsuario('juan', 'Clave123!', $rolId, 'juan@test.com');

        $usuario = (new SqliteUsuario())->authenticate('juan@test.com', 'Clave123!');

        $this->assertIsArray($usuario);
        $this->assertSame('juan', $usuario['nombre_usuario']);
    }

    #[Test]
    public function authenticate_con_password_incorrecta_devuelve_null(): void
    {
        $rolId = $this->crearRol('Administrador');
        $this->crearUsuario('admin', 'Clave123!', $rolId, 'admin@test.com');

        $this->assertNull((new SqliteUsuario())->authenticate('admin', 'OtraClave123!'));
    }

    #[Test]
    public function authenticate_con_usuario_inexistente_devuelve_null(): void
    {
        $this->assertNull((new SqliteUsuario())->authenticate('fantasma', 'Clave123!'));
    }

    #[Test]
    public function verifyPassword_comprueba_el_hash_almacenado(): void
    {
        $rolId = $this->crearRol('Administrador');
        $id = $this->crearUsuario('admin', 'Clave123!', $rolId);
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->verifyPassword($id, 'Clave123!'));
        $this->assertFalse($modelo->verifyPassword($id, 'Clave1234!'));
        $this->assertFalse($modelo->verifyPassword(99999, 'Clave123!'));
    }

    #[Test]
    public function getUserByEmail_devuelve_el_usuario_o_null(): void
    {
        $rolId = $this->crearRol('Administrador');
        $id = $this->crearUsuario('admin', 'Clave123!', $rolId, 'admin@test.com');
        $modelo = new SqliteUsuario();

        $usuario = $modelo->getUserByEmail('admin@test.com');

        $this->assertIsArray($usuario);
        $this->assertSame($id, $usuario['id']);
        $this->assertSame('admin@test.com', $usuario['correo_electronico']);
        $this->assertNull($modelo->getUserByEmail('nadie@test.com'));
    }

    #[Test]
    public function getAll_devuelve_los_usuarios_ordenados_con_el_nombre_del_rol(): void
    {
        $adminRol = $this->crearRol('Administrador');
        $trabajadorRol = $this->crearRol('Trabajador');
        $this->crearUsuario('admin', 'Clave123!', $adminRol, 'admin@test.com');
        $this->crearUsuario('juan', 'Clave123!', $trabajadorRol, 'juan@test.com');

        $usuarios = (new SqliteUsuario())->getAll();

        $this->assertCount(2, $usuarios);
        $this->assertSame(['admin', 'juan'], array_column($usuarios, 'nombre_usuario'));
        $this->assertSame(['Administrador', 'Trabajador'], array_column($usuarios, 'nombre_rol'));
    }

    #[Test]
    public function getAll_devuelve_los_datos_de_trabajador_concatenados(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $this->crearUsuario('juan', 'Clave123!', $rolId, 'juan@test.com', [
            'nombre_trabajador' => 'Juan',
            'apellido_trabajador' => 'Pérez',
            'cedula_trabajador' => '12345678',
            'telefono_trabajador' => '04141234567',
            'cargo' => 'Jardinero',
        ]);

        $usuario = (new SqliteUsuario())->getAll()[0];

        $this->assertSame('Juan Pérez', $usuario['trabajador_nombre']);
        $this->assertSame('Jardinero', $usuario['cargo']);
        $this->assertSame('12345678', $usuario['cedula_trabajador']);
    }

    #[Test]
    public function getById_devuelve_la_fila_normalizada_o_null(): void
    {
        $rolId = $this->crearRol('Administrador');
        $id = $this->crearUsuario('admin', 'Clave123!', $rolId, 'admin@test.com');
        $modelo = new SqliteUsuario();

        $usuario = $modelo->getById($id);

        $this->assertIsArray($usuario);
        $this->assertSame($id, $usuario['id']);
        $this->assertSame('admin', $usuario['nombre_usuario']);
        $this->assertSame($rolId, $usuario['rol_id']);
        $this->assertNull($modelo->getById(99999));
    }

    #[Test]
    public function userExists_funciona_por_id_y_por_nombre(): void
    {
        $rolId = $this->crearRol('Administrador');
        $id = $this->crearUsuario('admin', 'Clave123!', $rolId);
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->userExists($id));
        $this->assertTrue($modelo->userExists(null, 'admin'));
        $this->assertFalse($modelo->userExists(99999));
        $this->assertFalse($modelo->userExists(null, 'fantasma'));
        $this->assertFalse($modelo->userExists());
    }

    #[Test]
    public function add_crea_un_usuario_con_la_password_hasheada(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->add('juan', 'Clave123!', $rolId, 'juan@test.com', 'avatar.png'));

        $fila = $this->fetchRowWhere('usuarios', ['nombre_usuario' => 'juan']);
        $this->assertNotNull($fila);
        $this->assertSame('juan@test.com', $fila['correo_electronico']);
        $this->assertSame('avatar.png', $fila['avatar']);
        $this->assertSame($rolId, (int) $fila['id_rol']);
        $this->assertSame('Activo', $fila['estatus']);
        $this->assertNotSame('Clave123!', $fila['password_hash']);
        $this->assertTrue(password_verify('Clave123!', $fila['password_hash']));
        $this->assertSame((int) $fila['id_usuario'], $modelo->getLastInsertId());
    }

    #[Test]
    public function add_rechaza_un_nombre_de_usuario_duplicado(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $this->crearUsuario('juan', 'Clave123!', $rolId);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ya existe un usuario con este nombre de usuario.');

        (new SqliteUsuario())->add('juan', 'Clave123!', $rolId);
    }

    #[Test]
    public function add_valida_el_formato_del_correo_electronico(): void
    {
        $rolId = $this->crearRol('Trabajador');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Correo electrónico inválido');

        (new SqliteUsuario())->add('juan', 'Clave123!', $rolId, 'juan-sin-arroba');
    }

    #[Test]
    public function update_cambia_los_datos_y_conserva_la_password_si_no_se_indica(): void
    {
        $adminRol = $this->crearRol('Administrador');
        $trabajadorRol = $this->crearRol('Trabajador');
        $id = $this->crearUsuario('juan', 'Clave123!', $trabajadorRol, 'juan@test.com');
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->update($id, 'juanp', $adminRol, 'juanp@test.com'));

        $fila = $this->fetchRow('usuarios', $id, 'id_usuario');
        $this->assertSame('juanp', $fila['nombre_usuario']);
        $this->assertSame('juanp@test.com', $fila['correo_electronico']);
        $this->assertSame($adminRol, (int) $fila['id_rol']);
        $this->assertTrue(password_verify('Clave123!', $fila['password_hash']));
    }

    #[Test]
    public function update_cambia_la_password_cuando_se_indica(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $id = $this->crearUsuario('juan', 'Clave123!', $rolId, 'juan@test.com');
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->update($id, 'juan', $rolId, 'juan@test.com', 'Nueva123!'));

        $fila = $this->fetchRow('usuarios', $id, 'id_usuario');
        $this->assertFalse(password_verify('Clave123!', $fila['password_hash']));
        $this->assertTrue(password_verify('Nueva123!', $fila['password_hash']));
    }

    #[Test]
    public function update_de_un_usuario_inexistente_lanza_excepcion(): void
    {
        $rolId = $this->crearRol('Trabajador');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe el usuario con ID: 99999');

        (new SqliteUsuario())->update(99999, 'juan', $rolId);
    }

    #[Test]
    public function delete_elimina_el_usuario_y_devuelve_true(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $id = $this->crearUsuario('juan', 'Clave123!', $rolId);
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->delete($id));
        $this->assertDatabaseMissing('usuarios', ['id_usuario' => $id]);
        $this->assertDatabaseCount('usuarios', 0);
    }

    #[Test]
    public function delete_de_un_usuario_inexistente_no_falla(): void
    {
        $this->assertTrue((new SqliteUsuario())->delete(99999));
        $this->assertDatabaseCount('usuarios', 0);
    }

    #[Test]
    public function updateProfile_actualiza_sus_datos_sin_cambiar_el_rol(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $id = $this->crearUsuario('juan', 'Clave123!', $rolId, 'juan@test.com');
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->updateProfile($id, 'juanp', 'juanp@test.com', 'Nueva123!', 'foto.png'));

        $fila = $this->fetchRow('usuarios', $id, 'id_usuario');
        $this->assertSame('juanp', $fila['nombre_usuario']);
        $this->assertSame('juanp@test.com', $fila['correo_electronico']);
        $this->assertSame('foto.png', $fila['avatar']);
        $this->assertSame($rolId, (int) $fila['id_rol']);
        $this->assertTrue(password_verify('Nueva123!', $fila['password_hash']));
    }

    #[Test]
    public function updateProfile_de_un_usuario_inexistente_devuelve_false(): void
    {
        $this->assertFalse((new SqliteUsuario())->updateProfile(99999, 'juan', 'juan@test.com'));
    }

    #[Test]
    public function getRoles_devuelve_los_roles_ordenados_por_id(): void
    {
        $this->crearRol('Trabajador', 'Acceso limitado');
        $this->crearRol('Administrador', 'Acceso total');

        $roles = (new SqliteUsuario())->getRoles();

        $this->assertSame(['Trabajador', 'Administrador'], array_column($roles, 'nombre_rol'));
        $this->assertSame('Acceso total', $roles[1]['descripcion_rol']);
    }

    #[Test]
    public function getAllPermissions_separa_modulos_y_acciones_basicas(): void
    {
        $ventas = $this->insert('modulos', ['nombre_modulo' => 'ventas', 'descripcion_modulo' => 'Procesar ventas']);
        $this->insert('modulos', ['nombre_modulo' => 'inventario', 'descripcion_modulo' => 'Gestión de inventario']);
        foreach (['ver', 'crear', 'editar', 'eliminar', 'exportar'] as $nombre) {
            $this->insert('permisos', ['nombre_permiso' => $nombre]);
        }

        $permisos = (new SqliteUsuario())->getAllPermissions();

        $this->assertSame(['inventario', 'ventas'], array_column($permisos['modulos'], 'nombre_modulo'));
        $this->assertSame(['ver', 'crear', 'editar', 'eliminar'], array_column($permisos['acciones'], 'nombre_permiso'));
        $this->assertSame($ventas, (int) $permisos['modulos'][1]['id_modulo']);
    }

    #[Test]
    public function getAllPermissions_sin_tablas_devuelve_estructura_vacia(): void
    {
        $this->exec('DROP TABLE modulos');

        $this->assertSame(
            ['modulos' => [], 'acciones' => []],
            (new SqliteUsuario())->getAllPermissions()
        );
    }

    #[Test]
    public function getUserPermissions_devuelve_solo_los_permisos_del_usuario(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $juan = $this->crearUsuario('juan', 'Clave123!', $rolId);
        $ana = $this->crearUsuario('ana', 'Clave123!', $rolId);
        $moduloId = $this->insert('modulos', ['nombre_modulo' => 'inventario']);
        $permisoId = $this->insert('permisos', ['nombre_permiso' => 'ver']);
        $this->insert('usuario_modulo_permiso', [
            'id_usuario' => $juan,
            'id_modulo' => $moduloId,
            'id_permiso' => $permisoId,
        ]);

        $permisos = (new SqliteUsuario())->getUserPermissions($juan);

        $this->assertCount(1, $permisos);
        $this->assertSame('inventario', $permisos[0]['nombre_modulo']);
        $this->assertSame('ver', $permisos[0]['nombre_permiso']);
        $this->assertSame([], (new SqliteUsuario())->getUserPermissions($ana));
    }

    #[Test]
    public function setUserPermissions_reemplaza_los_permisos_anteriores(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $juan = $this->crearUsuario('juan', 'Clave123!', $rolId);
        $inventario = $this->insert('modulos', ['nombre_modulo' => 'inventario']);
        $ventas = $this->insert('modulos', ['nombre_modulo' => 'ventas']);
        $ver = $this->insert('permisos', ['nombre_permiso' => 'ver']);
        $crear = $this->insert('permisos', ['nombre_permiso' => 'crear']);
        $this->insert('usuario_modulo_permiso', [
            'id_usuario' => $juan,
            'id_modulo' => $inventario,
            'id_permiso' => $ver,
        ]);

        (new SqliteUsuario())->setUserPermissions($juan, [
            ['id_modulo' => $ventas, 'id_permiso' => $crear],
        ]);

        $this->assertDatabaseCount('usuario_modulo_permiso', 1);
        $this->assertDatabaseHas('usuario_modulo_permiso', [
            'id_usuario' => $juan,
            'id_modulo' => $ventas,
            'id_permiso' => $crear,
        ]);
    }

    #[Test]
    public function setUserPermissions_con_arreglo_vacio_borra_los_permisos(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $juan = $this->crearUsuario('juan', 'Clave123!', $rolId);
        $moduloId = $this->insert('modulos', ['nombre_modulo' => 'inventario']);
        $permisoId = $this->insert('permisos', ['nombre_permiso' => 'ver']);
        $this->insert('usuario_modulo_permiso', [
            'id_usuario' => $juan,
            'id_modulo' => $moduloId,
            'id_permiso' => $permisoId,
        ]);

        (new SqliteUsuario())->setUserPermissions($juan, []);

        $this->assertDatabaseCount('usuario_modulo_permiso', 0);
    }

    #[Test]
    public function getRolePermissions_para_el_administrador_devuelve_arreglo_vacio(): void
    {
        $adminRol = $this->crearRol('Administrador');
        $admin = $this->crearUsuario('admin', 'Clave123!', $adminRol);
        $moduloId = $this->insert('modulos', ['nombre_modulo' => 'inventario']);
        $permisoId = $this->insert('permisos', ['nombre_permiso' => 'ver']);
        $this->insert('usuario_modulo_permiso', [
            'id_usuario' => $admin,
            'id_modulo' => $moduloId,
            'id_permiso' => $permisoId,
        ]);

        $this->assertSame([], (new SqliteUsuario())->getRolePermissions($adminRol, $admin));
    }

    #[Test]
    public function getRolePermissions_devuelve_los_permisos_individuales_del_usuario(): void
    {
        // El rol 1 está reservado para el administrador (bypass sin consulta).
        $this->crearRol('Administrador');
        $rolId = $this->crearRol('Trabajador');
        $juan = $this->crearUsuario('juan', 'Clave123!', $rolId);
        $moduloId = $this->insert('modulos', ['nombre_modulo' => 'inventario']);
        $permisoId = $this->insert('permisos', ['nombre_permiso' => 'ver']);
        $this->insert('usuario_modulo_permiso', [
            'id_usuario' => $juan,
            'id_modulo' => $moduloId,
            'id_permiso' => $permisoId,
        ]);

        $this->assertSame(
            ['inventario:ver'],
            (new SqliteUsuario())->getRolePermissions($rolId, $juan)
        );
    }

    #[Test]
    public function getRolePermissions_sin_usuario_devuelve_arreglo_vacio(): void
    {
        $rolId = $this->crearRol('Trabajador');

        $this->assertSame([], (new SqliteUsuario())->getRolePermissions($rolId));
    }

    #[Test]
    public function getRolePermissions_sin_tablas_devuelve_arreglo_vacio(): void
    {
        $this->exec('DROP TABLE usuario_modulo_permiso');

        $this->assertSame([], (new SqliteUsuario())->getRolePermissions(2, 1));
    }

    #[Test]
    public function isPasswordStrong_se_evalua_sin_tocar_la_base_de_datos(): void
    {
        $modelo = new SqliteUsuario();

        $this->assertTrue($modelo->isPasswordStrong('Clave123!'));
        $this->assertFalse($modelo->isPasswordStrong('clave123!'));
    }

    #[Test]
    public function getLastInsertId_refleja_la_ultima_insercion(): void
    {
        $rolId = $this->crearRol('Trabajador');
        $id = $this->crearUsuario('juan', 'Clave123!', $rolId);

        $this->assertSame($id, (new SqliteUsuario())->getLastInsertId());
    }
}
