<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class AmpliacionTest extends InterfazTestCase
{
    #[Test]
    public function registra_una_ampliacion_con_salida_y_entrada(): void
    {
        $cliente = Fixtures::cliente();
        $lote = Fixtures::lote();
        $planta = Fixtures::planta();
        $ubicacion = Fixtures::ubicacion();

        $this->visit('dashboard/ampliacion');
        $this->waitForDataTable('ampliacionTable');
        usleep(800000);
        $this->waitForAjaxIdle();
        $this->click('#btnAddAmpliacion');
        $this->waitForVisibleCss('#ampliacionModal.show');

        $this->setValue('#idClienteAmp', $cliente['id']);
        $this->selectFirstAvailable('#id_usuario_gestor');
        $this->setValue('#fecha_movimiento', date('Y-m-d'));

        $this->click('#btnAddSalidaRow');
        $this->waitForSelectOptions('#salidaTableBody .salida-lote');
        $this->setSelectValue('#salidaTableBody .salida-lote', $lote['id']);
        $this->setValue('#salidaTableBody .salida-cantidad', '2');

        $this->click('#btnAddEntradaRow');
        $this->waitForSelectOptions('#entradaTableBody .entrada-planta');
        $this->waitForSelectOptions('#entradaTableBody .entrada-ubicacion');
        $this->setSelectValue('#entradaTableBody .entrada-planta', $planta['id']);
        $this->setSelectValue('#entradaTableBody .entrada-ubicacion', $ubicacion['id']);
        $this->setValue('#entradaTableBody .entrada-cantidad', '2');
        $this->fill('#observacion', $this->unique('Ampliacion', 120));

        $this->click('#ampliacionForm button[type="submit"]');
        $this->waitUntilGone('#ampliacionModal.show');
        $this->assertToastContains('Ampliación de especies registrada');
        $this->waitForDataTable('ampliacionTable');
        $this->assertRowExists('ampliacionTable', $cliente['name']);
    }
}
