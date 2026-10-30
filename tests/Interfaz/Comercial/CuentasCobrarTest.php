<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class CuentasCobrarTest extends InterfazTestCase
{
    #[Test]
    public function registra_un_pago_parcial_de_una_venta_a_credito(): void
    {
        $venta = Fixtures::ventaCredito();

        $this->visit('dashboard/cuentas_cobrar');
        $this->waitForDataTable('cuentasTable');
        $row = $this->tableRowWithText('cuentasTable', $venta['cliente']['name']);
        $this->clickIn($row, '.btn-pagar');
        $this->waitForVisibleCss('#paymentModal.show');

        $this->fill('#paymentForm [name="monto"]', '5.00');
        $this->selectOption('#payMetodo', 'Efectivo');
        $this->setValue('#paymentForm [name="fecha_pago"]', date('Y-m-d'));
        $this->selectFirstAvailable('#paymentForm [name="id_usuario"]');
        $this->click('#paymentForm button[type="submit"]');

        $this->waitUntilGone('#paymentModal.show');
        $this->assertToastContains('Pago registrado');
        $this->assertRowExists('cuentasTable', $venta['cliente']['name']);
    }
}
