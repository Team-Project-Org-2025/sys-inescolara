<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Autenticacion;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Pages\LoginPage;
use SysInescolara\Tests\Interfaz\Support\Config;
use SysInescolara\Tests\Interfaz\Support\Db;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;
use SysInescolara\Tests\Interfaz\Support\Session;

final class AutenticacionTest extends InterfazTestCase
{
    protected bool $requiresAuth = false;

    #[Test]
    public function el_formulario_de_login_muestra_usuario_password_y_captcha(): void
    {
        Session::logout();
        $page = (new LoginPage($this->driver()))->openLogin();

        $this->assertTrue($this->driver()->findElement($this->byCss('#email'))->isDisplayed());
        $this->assertTrue($this->driver()->findElement($this->byCss('#password'))->isDisplayed());
        $this->assertTrue($this->driver()->findElement($this->byCss('#loginBtn'))->isDisplayed());
        $this->assertNotEmpty($this->driver()->findElements($this->byCss('.g-recaptcha')));
        $this->assertPageContains('Iniciar Sesión');
    }

    #[Test]
    public function credenciales_invalidas_muestran_error_sin_entrar(): void
    {
        Session::logout();
        $page = new LoginPage($this->driver());
        $error = $page->loginExpectingError('usuario_e2e_inexistente', 'ClaveIncorrecta123!');

        $this->assertStringContainsString('incorrectos', mb_strtolower($error));
        $this->assertStringContainsString('/login', $this->path());
        Session::markLoggedIn(false);
    }

    #[Test]
    public function credenciales_validas_redirigen_al_dashboard(): void
    {
        Session::logout();
        (new LoginPage($this->driver()))->login(Config::user(), Config::password());

        $this->waitForUrlContains('dashboard');
        $this->assertStringContainsString('/dashboard', $this->path());
        $this->assertPageContains('Bienvenido');
        $this->assertNotEmpty($this->driver()->findElements($this->byCss('#sidebar')));
    }

    #[Test]
    public function cerrar_sesion_regresa_al_login(): void
    {
        Session::ensureLoggedIn();
        $this->visit('dashboard');
        $this->waitForCss('#sidebar');

        (new LoginPage($this->driver()))->logout();

        $this->assertStringContainsString('/login', $this->path());
        $this->waitForCss('#loginForm');
        $this->assertTrue($this->driver()->findElement($this->byCss('#loginForm'))->isDisplayed());
    }

    #[Test]
    public function una_ruta_protegida_redirige_al_login_sin_sesion(): void
    {
        Session::logout();
        $this->visit('dashboard/plantas');
        $this->waitForUrlContains('login', 20);

        $this->assertStringContainsString('/login', $this->path());
        $this->assertPageContains('Iniciar Sesión');
        Session::markLoggedIn(false);
    }

    #[Test]
    public function restablecimiento_de_contrasena_mediante_token(): void
    {
        Session::logout();
        $email = 'e2e_admin@example.test';
        $newPassword = 'E2eAdmin456!';
        $hashBackup = (string) Db::scalar(
            'SELECT password_hash FROM usuarios WHERE nombre_usuario = ?',
            [Config::user()],
            'security'
        );

        try {
            $this->visit('recuperar-password');
            $this->waitForCss('#recuperarForm');
            $this->fill('#correo', $email);
            (new LoginPage($this->driver()))->solveCaptcha();
            $this->click('#recuperarBtn');

            $token = null;
            $deadline = microtime(true) + 30;
            while (microtime(true) < $deadline) {
                $token = Db::scalar(
                    'SELECT token FROM password_resets WHERE correo = ? AND usado = 0 ORDER BY id DESC LIMIT 1',
                    [$email],
                    'security'
                );
                if ($token) {
                    break;
                }
                usleep(500000);
            }

            $this->assertNotEmpty($token, 'No se generó el token de recuperación en la base de datos.');
            $this->visit('recuperar-password/cambiar?token=' . urlencode((string) $token));
            $this->waitForCss('#resetForm');
            $this->fill('#password', $newPassword);
            $this->fill('#password2', $newPassword);
            $this->click('#resetBtn');
            $this->waitForUrlContains('reset=ok', 30);
            $this->assertPageContains('Contraseña actualizada');

            (new LoginPage($this->driver()))->login(Config::user(), $newPassword);
            $this->assertStringContainsString('/dashboard', $this->path());
        } finally {
            Db::execute(
                'UPDATE usuarios SET password_hash = ? WHERE nombre_usuario = ?',
                [$hashBackup, Config::user()],
                'security'
            );
            Session::logout();
        }
    }
}
