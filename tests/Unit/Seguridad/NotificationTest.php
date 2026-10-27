<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbNotification;

/**
 * Notification no tiene getters, setters ni fill(): sus datos se persisten
 * directamente en `notificaciones`. Sin base de datos cada método degrada al
 * valor neutro que declara el propio modelo (0, [], false).
 */
final class NotificationTest extends TestCase
{
    #[Test]
    public function getUnreadCount_sin_base_de_datos_devuelve_cero(): void
    {
        $this->assertSame(0, (new NoDbNotification())->getUnreadCount(1));
    }

    #[Test]
    public function getRecent_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbNotification())->getRecent(1));
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_la_pagina_solicitada_sin_datos(): void
    {
        $this->assertSame(
            ['data' => [], 'total' => 0, 'page' => 3, 'perPage' => 5],
            (new NoDbNotification())->getAll(1, 3, 5)
        );
    }

    #[Test]
    public function markAsRead_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->markAsRead(1, 1));
    }

    #[Test]
    public function markAllAsRead_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->markAllAsRead(1));
    }

    #[Test]
    public function markAllWarningsAsRead_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->markAllWarningsAsRead(1));
    }

    #[Test]
    public function markTaskAssignedAsRead_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->markTaskAssignedAsRead(1, 'Podar plantas'));
    }

    #[Test]
    public function create_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->create(1, 'Título', 'Mensaje'));
    }

    #[Test]
    public function existsByTitle_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->existsByTitle(1, 'Título'));
    }

    #[Test]
    public function delete_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbNotification())->delete(1, 1));
    }
}
