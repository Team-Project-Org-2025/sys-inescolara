<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Seguridad;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbUsuario;

/**
 * Usuario no expone getters/setters ni fill(): sus atributos viven solo en la
 * base de datos. Estas pruebas cubren la lógica pura (fortaleza de contraseña
 * y validaciones) y el comportamiento degradado de cada consulta sin MySQL.
 */
final class UsuarioTest extends TestCase
{
    #[Test]
    public function isPasswordStrong_acepta_contrasenas_con_mayuscula_minuscula_numero_y_simbolo(): void
    {
        $usuario = new NoDbUsuario();

        $this->assertTrue($usuario->isPasswordStrong('Clave123!'));
        $this->assertTrue($usuario->isPasswordStrong('aB3$xyz_'));
        $this->assertTrue($usuario->isPasswordStrong('Abcdefg1-'));
    }

    #[Test]
    public function isPasswordStrong_rechaza_contrasenas_a_las_que_les_falta_una_categoria(): void
    {
        $usuario = new NoDbUsuario();

        $this->assertFalse($usuario->isPasswordStrong('clave123!'));
        $this->assertFalse($usuario->isPasswordStrong('CLAVE123!'));
        $this->assertFalse($usuario->isPasswordStrong('ClaveSinNumero!'));
        $this->assertFalse($usuario->isPasswordStrong('Clave1234'));
        $this->assertFalse($usuario->isPasswordStrong('Ab1!'));
        $this->assertFalse($usuario->isPasswordStrong(''));
    }

    #[Test]
    public function add_sin_nombre_usuario_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_usuario');

        (new NoDbUsuario())->add('', 'Clave123!', 1);
    }

    #[Test]
    public function add_con_correo_invalido_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Correo electrónico inválido');

        (new NoDbUsuario())->add('nuevo', 'Clave123!', 1, 'correo-invalido');
    }

    #[Test]
    public function add_con_datos_validos_sin_base_de_datos_lanza_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbUsuario())->add('nuevo', 'Clave123!', 1, 'nuevo@test.com');
    }

    #[Test]
    public function update_de_un_usuario_inexistente_lanza_excepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe el usuario con ID: 7');

        (new NoDbUsuario())->update(7, 'nuevo', 1);
    }

    #[Test]
    public function update_con_correo_invalido_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Correo electrónico inválido');

        (new NoDbUsuario())->update(7, 'nuevo', 1, 'sin-arroba');
    }

    #[Test]
    public function authenticate_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUsuario())->authenticate('admin', 'Clave123!'));
    }

    #[Test]
    public function verifyPassword_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbUsuario())->verifyPassword(1, 'Clave123!'));
    }

    #[Test]
    public function getUserByEmail_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUsuario())->getUserByEmail('admin@test.com'));
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbUsuario())->getAll());
    }

    #[Test]
    public function getById_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUsuario())->getById(1));
    }

    #[Test]
    public function userExists_sin_base_de_datos_devuelve_false(): void
    {
        $usuario = new NoDbUsuario();

        $this->assertFalse($usuario->userExists(1));
        $this->assertFalse($usuario->userExists(null, 'admin'));
        $this->assertFalse($usuario->userExists());
    }

    #[Test]
    public function getRoles_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbUsuario())->getRoles());
    }

    #[Test]
    public function getAllPermissions_sin_base_de_datos_devuelve_estructura_vacia(): void
    {
        $this->assertSame(
            ['modulos' => [], 'acciones' => []],
            (new NoDbUsuario())->getAllPermissions()
        );
    }

    #[Test]
    public function getUserPermissions_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbUsuario())->getUserPermissions(1));
    }

    #[Test]
    public function getRolePermissions_para_el_rol_administrador_devuelve_arreglo_vacio_sin_tocar_la_base(): void
    {
        $this->assertSame([], (new NoDbUsuario())->getRolePermissions(1));
    }

    #[Test]
    public function getRolePermissions_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $usuario = new NoDbUsuario();

        $this->assertSame([], $usuario->getRolePermissions(2));
        $this->assertSame([], $usuario->getRolePermissions(2, 5));
    }

    #[Test]
    public function delete_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbUsuario())->delete(1));
    }

    #[Test]
    public function updateProfile_de_un_usuario_inexistente_devuelve_false(): void
    {
        $this->assertFalse((new NoDbUsuario())->updateProfile(7, 'nuevo'));
    }

    #[Test]
    public function getLastInsertId_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUsuario())->getLastInsertId());
    }
}
