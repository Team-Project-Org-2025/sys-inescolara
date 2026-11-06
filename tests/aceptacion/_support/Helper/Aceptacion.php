<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;

/**
 * Acciones reutilizables de la suite de aceptación.
 */
class Aceptacion extends Module
{
    public function login(string $user = '', string $password = ''): void
    {
        $wd = $this->webDriver();
        $user = $user !== '' ? $user : (getenv('ACEPTACION_USER') ?: 'e2e_admin');
        $password = $password !== '' ? $password : (getenv('ACEPTACION_PASSWORD') ?: 'E2eAdmin123!');

        $wd->amOnPage('/login');
        $wd->waitForElement('#loginForm', 20);
        $wd->fillField('#email', $user);
        $wd->fillField('#password', $password);
        $this->solveCaptcha();
        $wd->click('#loginBtn');
        $wd->waitForElement('#sidebar', 30);
    }

    public function loginExpectingError(string $user, string $password): string
    {
        $wd = $this->webDriver();

        $wd->amOnPage('/login');
        $wd->waitForElement('#loginForm', 20);
        $wd->fillField('#email', $user);
        $wd->fillField('#password', $password);
        $this->solveCaptcha();
        $wd->click('#loginBtn');
        $wd->waitForElementVisible('#loginError', 25);

        return trim((string) $wd->grabTextFrom('#loginError'));
    }

    /**
     * Inyecta un token en el campo que reCAPTCHA v2 crea dinámicamente.
     * El servidor de aceptación usa la secret key de prueba de Google,
     * por lo que cualquier token no vacío valida correctamente.
     */
    public function solveCaptcha(): void
    {
        $this->webDriver()->executeJS(
            "let el = document.querySelector('textarea[name=\"g-recaptcha-response\"]');"
            . "if (!el) {"
            . "  el = document.createElement('textarea');"
            . "  el.name = 'g-recaptcha-response';"
            . "  el.style.display = 'none';"
            . "  document.body.appendChild(el);"
            . "}"
            . "el.value = 'PASSED';"
        );
    }

    public function logout(): void
    {
        $this->webDriver()->amOnPage('/login/logout');
        $this->webDriver()->waitForElement('#loginForm', 20);
    }

    /**
     * Espera a que DataTables termine su carga AJAX.
     */
    public function waitForDataTable(string $tableId, int $timeout = 30): void
    {
        $wd = $this->webDriver();
        $wd->waitForElement('#' . $tableId, $timeout);

        $id = json_encode($tableId, JSON_THROW_ON_ERROR);
        $wd->waitForJS(
            "const id = {$id};"
            . "const t = document.getElementById(id);"
            . "if (!t || !document.getElementById(id + '_wrapper')) return false;"
            . "const info = document.getElementById(id + '_info');"
            . "return !info || !/Cargando|Procesando|Loading/i.test(info.textContent);",
            $timeout
        );
    }

    /**
     * Espera a que el texto aparezca dentro del cuerpo de una DataTable.
     */
    public function waitForTableText(string $tableId, string $text, int $timeout = 20): void
    {
        $id = json_encode($tableId, JSON_THROW_ON_ERROR);
        $value = json_encode($text, JSON_THROW_ON_ERROR);
        $this->webDriver()->waitForJS(
            "const el = document.getElementById({$id});"
            . "return !!el && (el.innerText || '').includes({$value});",
            $timeout
        );
    }

    /**
     * Espera a que el texto desaparezca del cuerpo de una DataTable.
     */
    public function waitForTableTextGone(string $tableId, string $text, int $timeout = 20): void
    {
        $id = json_encode($tableId, JSON_THROW_ON_ERROR);
        $value = json_encode($text, JSON_THROW_ON_ERROR);
        $this->webDriver()->waitForJS(
            "const el = document.getElementById({$id});"
            . "return !!el && !(el.innerText || '').includes({$value});",
            $timeout
        );
    }

    /**
     * Espera a que un modal de Bootstrap esté realmente abierto.
     */
    public function waitForModal(string $modalId, int $timeout = 20): void
    {
        $selector = json_encode('#' . $modalId . '.show', JSON_THROW_ON_ERROR);
        $this->webDriver()->waitForJS(
            "return document.querySelector({$selector}) !== null;",
            $timeout
        );
    }

    /**
     * Escribe en el buscador de una DataTable y espera el refiltrado.
     */
    public function searchDataTable(string $tableId, string $term): void
    {
        $wd = $this->webDriver();
        $wd->fillField('#' . $tableId . '_filter input', $term);
        $wd->wait(1);
    }

    /**
     * Pulsa un botón de acción (por clase) dentro de la fila que contiene el texto.
     */
    public function clickRowAction(string $tableId, string $rowText, string $buttonClass): void
    {
        $literal = '"' . str_replace('"', '\\"', $rowText) . '"';
        $xpath = "//table[@id='{$tableId}']//tr[contains(., {$literal})]"
            . "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$buttonClass} ')]";

        $this->webDriver()->click($xpath);
    }

    /**
     * Comprueba que la página actual no sea un 404/500 de la aplicación.
     */
    public function dontSeeApplicationError(): void
    {
        $wd = $this->webDriver();
        $title = (string) $wd->executeJS('return document.title || "";');
        $body = (string) $wd->executeJS('return (document.body && document.body.innerText) || "";');

        $needles = [
            'Error 404',
            'no existe',
            'Error interno',
            'Fatal error',
            'DataTables warning',
            'Ajax error',
            'No tienes permisos',
        ];

        foreach ($needles as $needle) {
            if (stripos($title, $needle) !== false || stripos($body, $needle) !== false) {
                $this->fail('La página muestra un error de la aplicación: ' . $needle);
            }
        }
    }

    private function webDriver(): WebDriver
    {
        /** @var WebDriver $wd */
        $wd = $this->getModule('WebDriver');

        return $wd;
    }
}
