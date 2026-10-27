<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Notification;

/**
 * Doble de Notification sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbNotification extends Notification
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
