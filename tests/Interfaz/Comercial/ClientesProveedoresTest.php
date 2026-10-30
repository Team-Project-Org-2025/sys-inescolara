<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\CrudConfigs;
use SysInescolara\Tests\Interfaz\Support\CrudTestCase;

final class ClientesProveedoresTest extends CrudTestCase
{
    #[Test]
    public function crud_de_clientes(): void
    {
        $name = $this->unique('Cliente', 80);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::clientes(),
            [
                'tipo_cedula_cliente' => 'V',
                'cedula_cliente' => (string) random_int(10000000, 99999999),
                'nombre_cliente' => $name,
                'apellido_cliente' => 'E2E',
                'contacto_cliente' => '0412-0000000',
            ],
            [
                'nombre_cliente' => $edited,
                'apellido_cliente' => 'E2E editado',
                'contacto_cliente' => '0414-1111111',
            ],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_proveedores(): void
    {
        $name = $this->unique('Proveedor', 80);
        $edited = $name . ' edit';
        $rif = sprintf('%09d', random_int(0, 999999999));

        $this->runCrud(
            CrudConfigs::proveedores(),
            [
                'nombre_proveedor' => $name,
                'rif_tipo' => 'J',
                'rif_numero' => $rif,
                'contacto_vendedor' => 'Contacto E2E',
                'telefono_proveedor' => '0412-2222222',
            ],
            [
                'nombre_proveedor' => $edited,
                'contacto_vendedor' => 'Contacto E2E editado',
                'telefono_proveedor' => '0414-3333333',
            ],
            $name,
            $edited
        );
    }
}
