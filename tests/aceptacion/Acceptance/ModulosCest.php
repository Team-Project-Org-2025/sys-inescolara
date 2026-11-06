<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Acceptance;

use Tests\Aceptacion\Support\AcceptanceTester;
use Tests\Aceptacion\Support\ModuleCatalog;

/**
 * CP-ACE-1xx · Pruebas de aceptación de los 31 requisitos funcionales.
 *
 * Cada caso abre la pantalla real del módulo, verifica el encabezado, el
 * contenido clave y que la tabla principal (DataTables) termine de cargar
 * por AJAX sin errores de aplicación.
 */
final class ModulosCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function rf01PlantasCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-01');
    }

    public function rf02EspeciesCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-02');
    }

    public function rf03UbicacionesCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-03');
    }

    public function rf04InventarioCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-04');
    }

    public function rf05LotesCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-05');
    }

    public function rf06TrazabilidadCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-06');
    }

    public function rf07InsumosCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-07');
    }

    public function rf08HerramientasCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-08');
    }

    public function rf09UnidadesMedidaCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-09');
    }

    public function rf10MermasCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-10');
    }

    public function rf11RegistroInsumosSoporteCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-11');
    }

    public function rf12UsoHerramientasSoporteCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-12');
    }

    public function rf13VentasCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-13');
    }

    public function rf14PreciosCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-14');
    }

    public function rf15ClientesCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-15');
    }

    public function rf16CuentasCobrarCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-16');
    }

    public function rf17CuentasPagarCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-17');
    }

    public function rf18ComprasCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-18');
    }

    public function rf19OrnatosCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-19');
    }

    public function rf20AmpliacionCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-20');
    }

    public function rf21ProveedoresCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-21');
    }

    public function rf22TareasCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-22');
    }

    public function rf23RecoleccionSemillasCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-23');
    }

    public function rf24ReportesCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-24');
    }

    public function rf25DashboardCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-25');
    }

    public function rf26UsuariosCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-26');
    }

    public function rf27RolesCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-27');
    }

    public function rf28AuditoriaCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-28');
    }

    public function rf29RespaldosCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-29');
    }

    public function rf30NotificacionesSoporteCargaYEsOperativo(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-30');
    }

    public function rf31RecuperacionPasswordSoporteCargaYEsOperativa(AcceptanceTester $I): void
    {
        $this->assertModulo($I, 'RF-31');
    }

    private function assertModulo(AcceptanceTester $I, string $rf): void
    {
        $module = ModuleCatalog::all()[$rf];

        $I->amOnPage($module['path']);
        $I->waitForElement('body', 30);
        $I->dontSeeApplicationError();

        if (($module['special'] ?? null) === 'notifications') {
            $I->click('#notificationsBtn');
            $I->waitForElementVisible('#notifDropdown', 20);
            $I->see('Notificaciones');

            return;
        }

        if (($module['special'] ?? null) === 'assignment-tools') {
            $I->click('#btnAssignTask');
            $I->waitForModal('assignTaskModal', 20);
            $I->see('Herramientas a Utilizar');

            return;
        }

        if (!empty($module['heading'])) {
            $I->see($module['heading'], 'h1');
        }

        if (!empty($module['body'])) {
            $I->see($module['body']);
        }

        if (!empty($module['table'])) {
            $I->waitForDataTable($module['table'], 35);
        }

        $I->dontSeeApplicationError();
    }
}
