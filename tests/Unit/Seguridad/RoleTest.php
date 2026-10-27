<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Seguridad;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbRole;

/**
 * Role solo almacena estado en la base de datos: no hay getters, setters ni
 * fill(). Se cubren las validaciones de nombre y las lecturas degradadas.
 */
final class RoleTest extends TestCase
{
    #[Test]
    public function add_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_rol');

        (new NoDbRole())->add('');
    }

    #[Test]
    public function add_con_nombre_de_un_solo_caracter_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        (new NoDbRole())->add('A');
    }

    #[Test]
    public function add_con_numeros_en_el_nombre_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        (new NoDbRole())->add('Rol1');
    }

    #[Test]
    public function update_con_nombre_invalido_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        (new NoDbRole())->update(1, 'Admin_1');
    }

    #[Test]
    public function add_con_datos_validos_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbRole())->add('Auditor', 'Solo lectura'));
    }

    #[Test]
    public function update_con_datos_validos_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbRole())->update(1, 'Auditor', 'Solo lectura'));
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbRole())->getAll());
    }

    #[Test]
    public function getById_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbRole())->getById(1));
    }

    #[Test]
    public function exists_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbRole())->exists(1));
    }

    #[Test]
    public function existsByName_sin_base_de_datos_devuelve_false_degradado(): void
    {
        $role = new NoDbRole();

        $this->assertFalse($role->existsByName('Administrador'));
        $this->assertFalse($role->existsByName('Administrador', 1));
    }

    #[Test]
    public function delete_sin_base_de_datos_propaga_la_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbRole())->delete(1);
    }

    #[Test]
    public function getLastInsertId_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbRole())->getLastInsertId());
    }
}
