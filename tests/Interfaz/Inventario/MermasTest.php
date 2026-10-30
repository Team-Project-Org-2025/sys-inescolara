<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class MermasTest extends InterfazTestCase
{
    #[Test]
    public function registra_y_desactiva_una_merma_desde_cuarentena(): void
    {
        $cuarentena = Fixtures::cuarentena(8);
        $descripcion = $this->unique('Merma', 80);

        $this->visit('dashboard/mermas');
        $this->waitForDataTable('mermasTable');
        $this->click('#btnAddMerma');
        $this->waitForVisibleCss('#addMermaModal.show');

        $this->waitForSelectOptions('#mermaQuarantine');
        $this->setSelectValue('#mermaQuarantine', $cuarentena['id']);
        $this->fill('#mermaCantidad', '2');
        $this->selectOption('#addMermaForm [name="motivo"]', 'Plaga');
        $this->setValue('#addMermaForm [name="fecha_merma"]', date('Y-m-d'));
        $this->fill('#addMermaForm [name="descripcion"]', $descripcion);

        $this->click('#addMermaForm button[type="submit"]');
        $this->waitUntilGone('#addMermaModal.show');
        $this->assertToastContains('Merma registrada');
        $this->waitForDataTable('mermasTable');
        $this->assertRowExists('mermasTable', $cuarentena['planta_name']);

        $row = $this->tableRowWithText('mermasTable', $cuarentena['planta_name']);
        $this->clickIn($row, '.btn-delete');
        $this->waitForSwal();
        $this->click('.swal2-confirm');
        $this->assertToastContains('desactivado');
        $this->assertRowMissing('mermasTable', $cuarentena['planta_name']);
    }
}
