<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Acceptance;

use Tests\Aceptacion\Support\AcceptanceTester;

/**
 * CP-ACE-3xx · Flujos CRUD de aceptación sobre la interfaz real.
 *
 * Complementan la carga de módulos comprobando que un usuario administrador
 * puede crear, consultar y dar de baja lógica un registro desde la UI.
 */
final class CatalogosCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function crearYeliminarUnaEspecie(AcceptanceTester $I): void
    {
        $name = 'ACE Especie ' . $this->sufijoAlfabetico();

        $I->amOnPage('/dashboard/especies');
        $I->waitForDataTable('especiesTable');
        $I->click('#btnAddSpecies');
        $I->waitForModal('addSpeciesModal', 20);
        $I->fillField('#addSpeciesForm [name="nombre_especie"]', $name);
        $I->fillField('#addSpeciesForm [name="descripcion"]', 'Especie creada por la suite de aceptación');
        $I->click('#addSpeciesForm button[type="submit"]');
        $I->waitForElementNotVisible('#addSpeciesModal.show', 25);
        $I->waitForDataTable('especiesTable');
        $I->searchDataTable('especiesTable', $name);
        $I->waitForTableText('especiesTable', $name);
        $I->see($name);

        $I->clickRowAction('especiesTable', $name, 'btn-delete');
        $I->waitForElementVisible('.swal2-confirm', 20);
        $I->click('.swal2-confirm');
        $I->waitForDataTable('especiesTable');
        $I->searchDataTable('especiesTable', $name);
        $I->waitForTableTextGone('especiesTable', $name);
        $I->dontSee($name);
    }

    public function crearYeliminarUnaUbicacion(AcceptanceTester $I): void
    {
        $name = 'ACE Ubicacion ' . $this->sufijoAlfabetico();

        $I->amOnPage('/dashboard/ubicaciones');
        $I->waitForDataTable('ubicacionesTable');
        $I->click('#btnAddLocation');
        $I->waitForModal('addLocationModal', 20);
        $I->fillField('#addLocationModal [name="nombre_ubicacion"]', $name);
        $I->fillField('#addLocationModal [name="descripcion"]', 'Ubicación creada por aceptación');
        $I->click('#addLocationForm button[type="submit"]');
        $I->waitForElementNotVisible('#addLocationModal.show', 25);
        $I->waitForDataTable('ubicacionesTable');
        $I->searchDataTable('ubicacionesTable', $name);
        $I->waitForTableText('ubicacionesTable', $name);
        $I->see($name);

        $I->clickRowAction('ubicacionesTable', $name, 'btn-delete');
        $I->waitForElementVisible('.swal2-confirm', 20);
        $I->click('.swal2-confirm');
        $I->waitForDataTable('ubicacionesTable');
        $I->searchDataTable('ubicacionesTable', $name);
        $I->waitForTableTextGone('ubicacionesTable', $name);
        $I->dontSee($name);
    }

    public function crearYeliminarUnCliente(AcceptanceTester $I): void
    {
        $name = 'ACE Cliente ' . $this->sufijoAlfabetico();
        $cedula = (string) random_int(10000000, 29999999);

        $I->amOnPage('/dashboard/clientes');
        $I->waitForDataTable('clientesTable');
        $I->click('#btnAddClient');
        $I->waitForModal('addClientModal', 20);
        $I->selectOption('#addClientForm [name="tipo_cedula_cliente"]', 'V');
        $I->fillField('#addClientForm [name="cedula_cliente"]', $cedula);
        $I->fillField('#addClientForm [name="nombre_cliente"]', $name);
        $I->fillField('#addClientForm [name="apellido_cliente"]', 'Aceptación');
        $I->fillField('#addClientForm [name="contacto_cliente"]', '04121234567');
        $I->click('#addClientForm button[type="submit"]');
        $I->waitForElementNotVisible('#addClientModal.show', 25);
        $I->waitForDataTable('clientesTable');
        $I->searchDataTable('clientesTable', $name);
        $I->waitForTableText('clientesTable', $name);
        $I->see($name);

        $I->clickRowAction('clientesTable', $name, 'btn-delete');
        $I->waitForElementVisible('.swal2-confirm', 20);
        $I->click('.swal2-confirm');
        $I->waitForDataTable('clientesTable');
        $I->searchDataTable('clientesTable', $name);
        $I->waitForTableTextGone('clientesTable', $name);
        $I->dontSee($name);
    }

    /**
     * Los campos de nombre solo aceptan letras y espacios (validation.js),
     * así que el sufijo único debe ser alfabético.
     */
    private function sufijoAlfabetico(int $length = 8): string
    {
        $suffix = '';
        for ($i = 0; $i < $length; $i++) {
            $suffix .= chr(random_int(65, 90));
        }

        return $suffix;
    }
}
