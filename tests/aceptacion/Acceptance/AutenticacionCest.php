<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Acceptance;

use Tests\Aceptacion\Support\AcceptanceTester;

/**
 * CP-ACE-00X · Autenticación y control de acceso.
 *
 * Requisitos cubiertos: RF-26 (autenticación) y RF-31 (recuperación).
 */
final class AutenticacionCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Cada caso parte de una sesión limpia (clear_cookies: true).
    }

    public function elFormularioDeLoginMuestraUsuarioPasswordYCaptcha(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->waitForElement('#loginForm', 20);
        $I->seeElement('#email');
        $I->seeElement('#password');
        $I->seeElement('#loginBtn');
        $I->seeElement('.g-recaptcha');
        $I->see('Iniciar Sesión');
    }

    public function credencialesInvalidasMuestranErrorSinEntrar(AcceptanceTester $I): void
    {
        $error = $I->loginExpectingError('usuario_aceptacion_inexistente', 'ClaveIncorrecta123!');

        $I->assertStringContainsString('incorrectos', mb_strtolower($error));
        $I->seeInCurrentUrl('/login');
    }

    public function credencialesValidasRedirigenAlDashboard(AcceptanceTester $I): void
    {
        $I->login();

        $I->seeInCurrentUrl('/dashboard');
        $I->seeElement('#sidebar');
        $I->see('Bienvenido');
    }

    public function cerrarSesionRegresaAlLogin(AcceptanceTester $I): void
    {
        $I->login();
        $I->logout();

        $I->seeInCurrentUrl('/login');
        $I->seeElement('#loginForm');
    }

    public function unaRutaProtegidaRedirigeAlLoginSinSesion(AcceptanceTester $I): void
    {
        $I->amOnPage('/dashboard/plantas');
        $I->waitForElement('#loginForm', 20);

        $I->seeInCurrentUrl('/login');
        $I->see('Iniciar Sesión');
    }

    public function laRecuperacionDePasswordMuestraElFormulario(AcceptanceTester $I): void
    {
        $I->amOnPage('/recuperar-password');
        $I->waitForElement('#recuperarForm', 20);

        $I->seeElement('#correo');
        $I->seeElement('#recuperarBtn');
    }
}
