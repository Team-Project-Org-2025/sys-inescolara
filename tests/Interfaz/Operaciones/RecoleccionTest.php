<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class RecoleccionTest extends InterfazTestCase
{
    #[Test]
    public function registra_y_completa_una_recoleccion_con_insumo(): void
    {
        $usuario = Fixtures::usuario();
        $insumo = Fixtures::insumo();
        $ubicacion = Fixtures::ubicacion('Externo');
        $observacion = $this->unique('Recoleccion', 120);

        $this->visit('dashboard/seed-collection');
        $this->waitForDataTable('recoleccionTable');
        $this->driver()->executeScript('window.insumosData = arguments[0];', [[
            [
                'id' => (int) $insumo['id'],
                'nombre' => $insumo['name'],
                'categoria' => 'E2E',
                'stock' => 10,
                'unidad' => 'Unidad',
                'simbolo' => 'u',
            ],
        ]]);
        $this->click('#btnAddRecoleccion');
        $this->waitForVisibleCss('#recoleccionModal.show');

        $this->setSelectValue('#recoleccionForm [name="id_usuario"]', $usuario['id']);
        $this->setSelectValue('#recoleccionForm [name="id_ubicacion"]', $ubicacion['id']);
        $this->setValue('#recoleccionForm [name="fecha_asignacion"]', date('Y-m-d'));
        $this->fill('#recoleccionForm [name="observacion"]', $observacion);
        $this->click('#recoleccionForm button[type="submit"]');

        $this->waitUntilGone('#recoleccionModal.show');
        $this->assertToastContains('Recolección registrada');
        $this->waitUntilGone('.swal2-toast', 10);
        $this->waitForDataTable('recoleccionTable');
        $this->assertRowExists('recoleccionTable', $usuario['name']);

        $row = $this->tableRowWithText('recoleccionTable', $usuario['name']);
        $this->clickIn($row, '.btn-registrar-insumo');
        $this->waitForVisibleCss('#insumoModal.show');
        $this->setSelectValue('#insumosTableBody .insumo-id-insumo', $insumo['id']);
        $this->fill('#insumosTableBody .insumo-cantidad', '1');
        $this->click('#insumoForm button[type="submit"]');

        $this->waitUntilGone('#insumoModal.show');
        $this->assertToastContains('completada');
        $this->waitForDataTable('recoleccionTable');
        $row = $this->tableRowWithText('recoleccionTable', $usuario['name']);
        $this->assertStringContainsString('Realizada', $row->getText());
    }
}
