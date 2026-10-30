<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class CuentasPagarTest extends InterfazTestCase
{
    #[Test]
    public function registra_un_pago_parcial_de_una_cuenta_por_pagar(): void
    {
        $cuenta = Fixtures::cuentaPagar();

        $this->visit('dashboard/cuentas-pagar');
        $this->waitForDataTable('cuentasTable');
        $row = $this->tableRowWithText('cuentasTable', $cuenta['proveedor']['name']);
        $this->clickIn($row, '.btn-pagar');
        $this->waitForVisibleCss('#pagoModal.show');

        $this->fill('#pagoMonto', '5.00');
        $this->setValue('#pagoFecha', date('Y-m-d'));
        $this->selectFirstAvailable('#pagoTipo');
        $this->click('#pagoForm button[type="submit"]');

        $this->waitUntilGone('#pagoModal.show');
        $this->assertToastContains('Pago registrado');
        $this->assertRowExists('cuentasTable', $cuenta['proveedor']['name']);
    }
}
