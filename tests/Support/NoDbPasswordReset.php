<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\PasswordReset;

/**
 * Doble de PasswordReset sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbPasswordReset extends PasswordReset
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
