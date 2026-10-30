<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Publico;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;
use SysInescolara\Tests\Interfaz\Support\Session;

final class PublicoTest extends InterfazTestCase
{
    protected bool $requiresAuth = false;

    public static function paginas(): array
    {
        return [
            'inicio' => ['', 'Inicio'],
            'catalogo' => ['catalogo', 'Catálogo'],
            'servicios' => ['servicios', 'Servicios'],
            'nosotros' => ['nosotros', 'Nosotros'],
            'contacto' => ['contacto', 'Contacto'],
        ];
    }

    #[Test]
    #[DataProvider('paginas')]
    public function las_paginas_publicas_cargan_sin_error(string $path, string $texto): void
    {
        Session::logout();
        $this->visit($path);
        $this->waitForCss('body');
        usleep(400000);

        $this->assertNo404();
        $this->assertPageContains($texto);
        $this->assertNotEmpty($this->driver()->findElements($this->byCss('a[href*="login"]')));
    }
}
