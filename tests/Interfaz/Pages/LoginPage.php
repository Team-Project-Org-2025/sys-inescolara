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
            $error = $this->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('#loginError'))->getText();
            throw new \RuntimeException('No se pudo iniciar sesión: ' . trim((string) $error));
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
        return trim((string) $this->driver->findElement($this->byCss('#loginError'))->getText());
    }

    public function logout(): void
    {
        $this->open('login/logout');
        $this->waitForCss('#loginForm', 20);
        Session::markLoggedIn(false);
    }
}
