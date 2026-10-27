<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbCliente;

final class ClienteTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $cliente = new NoDbCliente();

        $this->assertNull($cliente->getId());
        $this->assertSame('', $cliente->getNombreCliente());
        $this->assertNull($cliente->getApellidoCliente());
        $this->assertNull($cliente->getTipoCedulaCliente());
        $this->assertNull($cliente->getCedulaCliente());
        $this->assertNull($cliente->getContactoCliente());
        $this->assertTrue($cliente->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_los_valores(): void
    {
        $cliente = new NoDbCliente();

        $this->assertSame($cliente, $cliente->setNombreCliente('  Ana María  '));
        $this->assertSame('Ana María', $cliente->getNombreCliente());

        $this->assertSame($cliente, $cliente->setApellidoCliente('  Pérez  '));
        $this->assertSame('Pérez', $cliente->getApellidoCliente());

        $this->assertSame($cliente, $cliente->setTipoCedulaCliente(' v '));
        $this->assertSame('V', $cliente->getTipoCedulaCliente());

        $this->assertSame($cliente, $cliente->setCedulaCliente(' 12345678 '));
        $this->assertSame('12345678', $cliente->getCedulaCliente());

        $this->assertSame($cliente, $cliente->setContactoCliente(' 04141234567 '));
        $this->assertSame('04141234567', $cliente->getContactoCliente());

        $this->assertSame($cliente, $cliente->setActivo(false));
        $this->assertFalse($cliente->isActivo());

        $cliente->setActivo(true);
        $this->assertTrue($cliente->isActivo());
    }

    #[Test]
    public function los_setters_convierten_los_valores_vacios_en_null(): void
    {
        $cliente = new NoDbCliente();

        $cliente->setApellidoCliente('Pérez')->setTipoCedulaCliente('V')->setCedulaCliente('12345678');

        $cliente->setApellidoCliente(null);
        $cliente->setTipoCedulaCliente('');
        $cliente->setCedulaCliente(null);
        $cliente->setContactoCliente(null);

        $this->assertNull($cliente->getApellidoCliente());
        $this->assertNull($cliente->getTipoCedulaCliente());
        $this->assertNull($cliente->getCedulaCliente());
        $this->assertNull($cliente->getContactoCliente());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $cliente = new NoDbCliente();
        $cliente->fill([
            'id_cliente'          => 99,
            'nombre_cliente'      => 'Luis',
            'apellido_cliente'    => 'Gómez',
            'tipo_cedula_cliente' => 'E',
            'cedula_cliente'      => '87654321',
            'contacto_cliente'    => '0424-0000000',
            'activo'              => 0,
            'desconocido'         => 'ignorado',
        ]);

        $this->assertNull($cliente->getId());
        $this->assertSame('Luis', $cliente->getNombreCliente());
        $this->assertSame('Gómez', $cliente->getApellidoCliente());
        $this->assertSame('E', $cliente->getTipoCedulaCliente());
        $this->assertSame('87654321', $cliente->getCedulaCliente());
        $this->assertSame('0424-0000000', $cliente->getContactoCliente());
        $this->assertFalse($cliente->isActivo());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $cliente = new NoDbCliente(['apellido_cliente' => 'Pérez']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_cliente');

        $cliente->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $cliente = new NoDbCliente(['nombre_cliente' => 'A']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $cliente->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $cliente = new NoDbCliente(['nombre_cliente' => 'Ana2']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $cliente->save();
    }

    #[Test]
    public function save_con_apellido_invalido_lanza_excepcion(): void
    {
        $cliente = new NoDbCliente([
            'nombre_cliente'   => 'Ana',
            'apellido_cliente' => 'P3rez',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $cliente->save();
    }

    #[Test]
    public function save_con_tipo_de_cedula_invalido_lanza_excepcion(): void
    {
        $cliente = new NoDbCliente([
            'nombre_cliente'      => 'Ana',
            'tipo_cedula_cliente' => 'X',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de C.I. inválido (V, E, J, G, P)');

        $cliente->save();
    }

    #[Test]
    public function save_con_numero_de_cedula_invalido_lanza_excepcion(): void
    {
        $cliente = new NoDbCliente([
            'nombre_cliente'      => 'Ana',
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '123',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Número de C.I. inválido (V/E: 7-8 dígitos, J/G: 9-10 dígitos)');

        $cliente->save();
    }

    #[Test]
    public function save_con_cedula_juridica_valida_devuelve_false_sin_base_de_datos(): void
    {
        $cliente = new NoDbCliente([
            'nombre_cliente'      => 'Vivero',
            'apellido_cliente'    => 'Central',
            'tipo_cedula_cliente' => 'J',
            'cedula_cliente'      => 'J123456789',
        ]);

        $this->assertFalse($cliente->save());
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $cliente = new NoDbCliente([
            'nombre_cliente'      => 'Ana',
            'apellido_cliente'    => 'Pérez',
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => 'V12345678',
            'contacto_cliente'    => '04141234567',
        ]);

        $this->assertFalse($cliente->save());
    }

    #[Test]
    public function getById_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbCliente())->getById(1));
    }

    #[Test]
    public function exists_devuelve_false_sin_base_de_datos(): void
    {
        $this->assertFalse((new NoDbCliente())->exists(1));
    }

    #[Test]
    public function delete_y_restore_devuelven_false_sin_base_de_datos(): void
    {
        $cliente = new NoDbCliente();

        $this->assertFalse($cliente->delete(1));
        $this->assertFalse($cliente->restore(1));
    }

    #[Test]
    public function getLastInsertId_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbCliente())->getLastInsertId());
    }

    #[Test]
    public function getAll_no_puede_consultar_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbCliente())->getAll();
    }

    #[Test]
    public function loadById_no_puede_consultar_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbCliente())->loadById(1);
    }
}
