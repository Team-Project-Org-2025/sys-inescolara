<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class OrnatosTest extends InterfazTestCase
{
    #[Test]
    public function registra_un_ornato_con_un_lote(): void
    {
        $cliente = Fixtures::cliente();
        $lote = Fixtures::lote();
        $ubicacion = $this->unique('Ornato', 50);

        $this->visit('dashboard/ornatos');
        $this->waitForDataTable('tablaOrnatos');
        $this->click('#btnNuevoOrnato');
        $this->waitForVisibleCss('#modalOrnato.show');

        $this->setValue('#idClienteOrnato', $cliente['id']);
        $this->selectFirstAvailable('#inputTipo');
        $this->setValue('#inputFecha', date('Y-m-d'));
        $this->setSelectValue('#cuerpoDetalle .select-lote', $lote['id']);
        $this->setValue('#cuerpoDetalle .input-cantidad', '2');
        $this->setValue('#cuerpoDetalle .input-precio', '5.00');
        $this->fill('#inputUbicacion', $ubicacion);
        $this->click('#formOrnato button[type="submit"]');

        $this->waitUntilGone('#modalOrnato.show');
        $this->assertToastContains('Ornato registrado');
        $this->waitForDataTable('tablaOrnatos');
        $this->assertRowExists('tablaOrnatos', $cliente['name']);
    }
}
