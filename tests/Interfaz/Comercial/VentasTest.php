<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class VentasTest extends InterfazTestCase
{
    #[Test]
    public function registra_una_venta_de_contado_con_pago_completo(): void
    {
        $cliente = Fixtures::cliente();
        $lote = Fixtures::lote();

        $this->visit('dashboard/ventas');
        $this->waitForDataTable('ventasTable');
        $this->click('button[data-bs-target="#ventaModal"]');
        $this->waitForVisibleCss('#ventaModal.show');
        $this->waitForSelectOptions('#productoSelect', 25);

        $this->setValue('#idClienteHidden', $cliente['id']);
        $this->selectFirstAvailable('#ventaForm [name="id_usuario"]');
        $this->selectFirstAvailable('#ventaForm [name="tipo_venta"]');
        $this->setSelectValue('#productoSelect', 'planta|' . $lote['id']);
        $this->waitForVisibleCss('#productosContainer .card');
        $this->setValue('#productosContainer .cantidad-producto', '1');

        $this->click('#btnSiguiente');
        $this->waitForVisibleCss('#step2Panel:not(.d-none)');
        $this->click('#pagarCompleto');
        $this->click('#btnGuardarVenta');

        $this->waitForSwal(25);
        $this->assertPageContains('Venta registrada');
        $this->click('.swal2-cancel');
        $this->waitUntilGone('#ventaModal.show', 20);
        $this->waitForDataTable('ventasTable');
        $this->assertRowExists('ventasTable', $cliente['name']);
    }
}
