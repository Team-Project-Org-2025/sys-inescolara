<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Pages;

use SysInescolara\Tests\Interfaz\Support\Session;

final class LoginPage extends BasePage
{
    public function openLogin(): static
    {
        $this->open('login');
        $this->waitForCss('#loginForm');
        return $this;
    }

    public function solveCaptcha(): void
    {
        $this->driver->executeScript(
            "let el = document.querySelector('textarea[name=\"g-recaptcha-response\"]');"
            . "if (!el) { el = document.createElement('textarea'); el.name = 'g-recaptcha-response'; el.style.display = 'none'; document.body.appendChild(el); }"
            . "el.value = 'PASSED';"
        );
    }

    public function fillCredentials(string $user, string $password): void
    {
        $this->fill('#email', $user);
        $this->fill('#password', $password);
    }

    public function submit(): void
    {
        $this->click('#loginBtn');
    }

    public function login(string $user, string $password): void
    {
        $this->openLogin();
        $this->fillCredentials($user, $password);
        $this->solveCaptcha();
        $this->submit();

        $this->wait(25)->until(function (): bool {
            $url = (string) $this->driver->getCurrentURL();
            if (str_contains($url, '/dashboard')) {
                return true;
            }
            return $this->driver->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector('#loginError')) !== [];
        });

        if (str_contains((string) $this->driver->getCurrentURL(), '/login')) {
            $error = trim((string) $this->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('#loginError'))->getText());
            $this->failIfCaptchaRejected($error);
            throw new \RuntimeException('No se pudo iniciar sesión: ' . $error);
        }

        Session::markLoggedIn(true);
    }

    public function loginExpectingError(string $user, string $password): string
    {
        $this->openLogin();
        $this->fillCredentials($user, $password);
        $this->solveCaptcha();
        $this->submit();
        $this->waitForVisibleCss('#loginError', 25);
        $error = trim((string) $this->driver->findElement($this->byCss('#loginError'))->getText());
        $this->failIfCaptchaRejected($error);

        return $error;
    }

    /**
     * El servidor rechaza los inicios de sesión cuando reCAPTCHA está
     * configurado con claves reales: la suite no puede resolver el reto. Se
     * avisa con la causa real en lugar de dejar un fallo confuso.
     */
    private function failIfCaptchaRejected(string $error): void
    {
        if (stripos($error, 'verificación de seguridad') === false) {
            return;
        }

        throw new \RuntimeException(
            'El entorno rechazó el inicio de sesión por reCAPTCHA. '
            . 'Para las pruebas E2E usa las claves de prueba de Google en el .env de la raíz: '
            . 'RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI y '
            . 'RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
        );
    }

    public function logout(): void
    {
        $this->open('login/logout');
        $this->waitForCss('#loginForm', 20);
        Session::markLoggedIn(false);
    }
}
