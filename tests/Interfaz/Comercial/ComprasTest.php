<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class ComprasTest extends InterfazTestCase
{
    #[Test]
    public function registra_una_compra_con_un_insumo(): void
    {
        $proveedor = Fixtures::proveedor();
        $insumo = Fixtures::insumo();
        $observacion = $this->unique('Compra', 120);

        $this->visit('dashboard/compras');
        $this->waitForDataTable('comprasTable');
        $this->click('#btnAddCompra');
        $this->waitForVisibleCss('#compraModal.show');

        $this->setSelectValue('#frmProveedor', $proveedor['id']);
        $this->wait(15)->until(function (): bool {
            return (bool) $this->driver()->executeScript(
                "return document.querySelectorAll('#itemsBody .item-select option').length > 1;"
            );
        });
        $this->setSelectValue('#itemsBody .item-select', $insumo['id']);
        $this->fill('#itemsBody .item-cantidad', '2');
        $this->fill('#itemsBody .item-costo', '1.50');
        $this->fill('#frmObservacion', $observacion);
        $this->click('#compraForm button[type="submit"]');

        $this->waitUntilGone('#compraModal.show');
        $this->assertToastContains('Compra registrada');
        $this->waitForDataTable('comprasTable');
        $this->assertRowExists('comprasTable', $proveedor['name']);
    }
}
