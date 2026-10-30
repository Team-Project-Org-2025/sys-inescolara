<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class TrazabilidadTest extends InterfazTestCase
{
    #[Test]
    public function registra_cuarentena_y_cambia_estado(): void
    {
        $lote = Fixtures::lote();
        $observacion = $this->unique('Cuarentena', 80);

        $this->visit('dashboard/trazabilidad');
        $this->waitForDataTable('trazabilidadTable');
        $this->click('#btnAddTrazabilidad');
        $this->waitForVisibleCss('#trazabilidadModal.show');

        $this->waitForSelectOptions('#id_lote');
        $this->setSelectValue('#id_lote', $lote['id']);
        $this->fill('#cantidad', '5');
        $this->selectOption('#id_estado', 'cuarentena');
        $this->setValue('#fecha_registro', date('Y-m-d'));
        $this->fill('#observacion', $observacion);

        $this->click('#trazabilidadForm button[type="submit"]');
        $this->waitUntilGone('#trazabilidadModal.show');
        $this->assertToastContains('Cuarentena registrada');
        $this->waitForDataTable('trazabilidadTable');
        $this->assertRowExists('trazabilidadTable', $lote['planta']['name']);

        $row = $this->tableRowWithText('trazabilidadTable', $lote['planta']['name']);
        $this->clickIn($row, '.btn-edit');
        $this->waitForVisibleCss('#trazabilidadModal.show');
        $this->selectOption('#id_estado', 'muerto');
        $this->click('#trazabilidadForm button[type="submit"]');
        $this->waitUntilGone('#trazabilidadModal.show');
        $this->assertToastContains('actualizado');
        $this->assertRowExists('trazabilidadTable', $lote['planta']['name']);
    }
}
