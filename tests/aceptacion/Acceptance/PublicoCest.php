<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Acceptance;

use Tests\Aceptacion\Support\AcceptanceTester;

/**
 * CP-ACE-2xx · Páginas públicas del vivero.
 *
 * Verifican que el frontend público sea accesible sin autenticación.
 */
final class PublicoCest
{
    public function laPaginaDeInicioEsPublicaYMuestraLaPropuesta(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->waitForElement('body', 20);

        $I->see('Propagamos vida para el Estado Lara');
        $I->dontSeeApplicationError();
    }

    public function elCatalogoPublicoListaEspecies(AcceptanceTester $I): void
    {
        $I->amOnPage('/catalogo');
        $I->waitForElement('body', 20);

        $I->see('Especies disponibles para reforestación y ornamentación');
        $I->dontSeeApplicationError();
    }

    public function laPaginaDeServiciosDescribeLaOferta(AcceptanceTester $I): void
    {
        $I->amOnPage('/servicios');
        $I->waitForElement('body', 20);

        $I->see('Producción y suministro para programas ambientales');
        $I->dontSeeApplicationError();
    }

    public function laPaginaNosotrosPresentaLaInstitucion(AcceptanceTester $I): void
    {
        $I->amOnPage('/nosotros');
        $I->waitForElement('body', 20);

        $I->see('Instituto de Ecosocialismo del Estado Lara');
        $I->dontSeeApplicationError();
    }

    public function laPaginaDeContactoMuestraLosCanales(AcceptanceTester $I): void
    {
        $I->amOnPage('/contacto');
        $I->waitForElement('body', 20);

        $I->see('Estamos para servirte');
        $I->dontSeeApplicationError();
    }
}
