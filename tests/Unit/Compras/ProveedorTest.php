<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Compras;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbProveedor;

final class ProveedorTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $proveedor = new NoDbProveedor();

        $this->assertNull($proveedor->getId());
        $this->assertSame('', $proveedor->getNombreProveedor());
        $this->assertNull($proveedor->getRifProveedor());
        $this->assertNull($proveedor->getContactoVendedor());
        $this->assertNull($proveedor->getTelefonoProveedor());
        $this->assertTrue($proveedor->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_recortan_espacios(): void
    {
        $proveedor = new NoDbProveedor();

        $this->assertSame($proveedor, $proveedor->setNombreProveedor('  Ferretería Los Andes  '));
        $this->assertSame('Ferretería Los Andes', $proveedor->getNombreProveedor());

        $this->assertSame($proveedor, $proveedor->setRifProveedor('  J-12345678-9  '));
        $this->assertSame('J-12345678-9', $proveedor->getRifProveedor());

        $this->assertSame($proveedor, $proveedor->setContactoVendedor('  Ana Pérez  '));
        $this->assertSame('Ana Pérez', $proveedor->getContactoVendedor());

        $this->assertSame($proveedor, $proveedor->setTelefonoProveedor('  04141234567  '));
        $this->assertSame('04141234567', $proveedor->getTelefonoProveedor());

        $this->assertSame($proveedor, $proveedor->setActivo(false));
        $this->assertFalse($proveedor->isActivo());

        $proveedor->setActivo(true);
        $this->assertTrue($proveedor->isActivo());
    }

    #[Test]
    public function los_setters_opcionales_convierten_cadenas_vacias_en_null(): void
    {
        $proveedor = new NoDbProveedor([
            'nombre_proveedor'   => 'Agro Sur',
            'rif_proveedor'      => 'V-12345678-9',
            'contacto_vendedor'  => 'Luis',
            'telefono_proveedor' => '04141234567',
        ]);

        $proveedor->setRifProveedor('');
        $proveedor->setContactoVendedor('');
        $proveedor->setTelefonoProveedor('');

        $this->assertNull($proveedor->getRifProveedor());
        $this->assertNull($proveedor->getContactoVendedor());
        $this->assertNull($proveedor->getTelefonoProveedor());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $proveedor = new NoDbProveedor();
        $proveedor->fill([
            'nombre_proveedor' => 'Agro Sur',
            'rif_proveedor'    => 'V-12345678-9',
            'id_proveedor'     => 99,
            'desconocido'      => 'ignorado',
            'activo'           => 0,
        ]);

        $this->assertSame('Agro Sur', $proveedor->getNombreProveedor());
        $this->assertSame('V-12345678-9', $proveedor->getRifProveedor());
        $this->assertNull($proveedor->getId());
        $this->assertFalse($proveedor->isActivo());
    }

    #[Test]
    public function el_constructor_aplica_fill(): void
    {
        $proveedor = new NoDbProveedor([
            'nombre_proveedor'  => 'Semillas del Llano',
            'contacto_vendedor' => 'María Gómez',
        ]);

        $this->assertSame('Semillas del Llano', $proveedor->getNombreProveedor());
        $this->assertSame('María Gómez', $proveedor->getContactoVendedor());
        $this->assertNull($proveedor->getId());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $proveedor = new NoDbProveedor();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_proveedor');

        $proveedor->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $proveedor = new NoDbProveedor(['nombre_proveedor' => 'A']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $proveedor->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $proveedor = new NoDbProveedor(['nombre_proveedor' => 'Agro 123']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $proveedor->save();
    }

    #[Test]
    public function save_con_rif_invalido_lanza_excepcion(): void
    {
        $proveedor = new NoDbProveedor([
            'nombre_proveedor' => 'Ferretería Los Andes',
            'rif_proveedor'    => '12345678',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RIF inválido (formato: J-12345678-9)');

        $proveedor->save();
    }

    #[Test]
    public function save_con_telefono_invalido_lanza_excepcion(): void
    {
        $proveedor = new NoDbProveedor([
            'nombre_proveedor'   => 'Ferretería Los Andes',
            'telefono_proveedor' => '0414',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Teléfono inválido (11 dígitos)');

        $proveedor->save();
    }

    #[Test]
    public function save_con_datos_validos_requiere_base_de_datos(): void
    {
        $proveedor = new NoDbProveedor([
            'nombre_proveedor'   => 'Ferretería Los Andes',
            'rif_proveedor'      => 'J-12345678-9',
            'contacto_vendedor'  => 'Ana Pérez',
            'telefono_proveedor' => '04141234567',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $proveedor->save();
    }

    #[Test]
    public function add_con_datos_invalidos_lanza_excepcion_de_validacion(): void
    {
        $proveedor = new NoDbProveedor();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_proveedor');

        $proveedor->add('');
    }

    #[Test]
    public function add_con_datos_validos_requiere_base_de_datos(): void
    {
        $proveedor = new NoDbProveedor();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $proveedor->add('Agro Sur', 'V-12345678-9', 'Luis', '04141234567');
    }

    #[Test]
    public function update_sin_base_de_datos_lanza_excepcion_al_buscar_el_proveedor(): void
    {
        $proveedor = new NoDbProveedor();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $proveedor->update(1, 'Agro Sur');
    }

    #[Test]
    public function find_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbProveedor::find(1);
    }

    #[Test]
    public function all_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbProveedor::all();
    }

    #[Test]
    public function where_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbProveedor::where('nombre_proveedor', 'Agro Sur');
    }

    #[Test]
    public function get_by_rif_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbProveedor())->getByRif('J-12345678-9');
    }

    #[Test]
    public function delete_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbProveedor())->delete(1);
    }

    #[Test]
    public function restore_sin_base_de_datos_lanza_excepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbProveedor())->restore(1);
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbProveedor())->getAll());
    }

    #[Test]
    public function getLastInsertId_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbProveedor())->getLastInsertId());
    }
}
