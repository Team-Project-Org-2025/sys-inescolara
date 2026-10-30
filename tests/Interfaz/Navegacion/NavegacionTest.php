<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Navegacion;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;
use SysInescolara\Tests\Interfaz\Support\ModuleRegistry;

final class NavegacionTest extends InterfazTestCase
{
    public static function modulos(): array
    {
        $data = [];
        foreach (ModuleRegistry::modules() as $module) {
            $data[$module['name']] = [$module];
        }
        return $data;
    }

    #[Test]
    #[DataProvider('modulos')]
    public function cada_modulo_carga_sin_error(array $module): void
    {
        if ($module['name'] === 'Notificaciones') {
            $this->visit('dashboard');
            $this->waitForCss('#notificationsBtn');
            $this->click('#notificationsBtn');
            $this->waitForVisibleCss('#notifDropdown');
            $this->assertPageContains('Notificaciones');
            return;
        }

        $this->visit($module['path']);
        $this->waitForCss('body');
        usleep(600000);

        $this->assertNo404();

        if (!empty($module['finalPath'])) {
            $this->assertStringContainsString($module['finalPath'], $this->path());
        }

        if (!empty($module['heading'])) {
            $this->assertHeadingContains($module['heading']);
        }

        if (!empty($module['body'])) {
            $this->assertPageContains($module['body']);
        }

        if (!empty($module['table'])) {
            $this->waitForDataTable($module['table'], 25);
        }
    }
}
