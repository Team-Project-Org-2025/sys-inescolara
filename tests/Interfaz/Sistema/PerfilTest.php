<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Sistema;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Config;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class PerfilTest extends InterfazTestCase
{
    #[Test]
    public function guarda_los_datos_del_perfil_con_la_contrasena_actual(): void
    {
        $this->visit('dashboard/perfil');
        $this->waitForCss('.perfil-form');
        $this->fill('#current_password', Config::password());
        $this->click('.perfil-btn');

        $this->waitForVisibleCss('.perfil-alert-success', 20);
        $this->assertPageContains('Perfil actualizado');
    }
}
