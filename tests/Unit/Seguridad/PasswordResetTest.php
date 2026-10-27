<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbPasswordReset;

/**
 * PasswordReset no expone getters, setters ni fill(): todo su estado vive en
 * la tabla `password_resets`. Sin base de datos cada operación propaga la
 * RuntimeException del doble (el modelo no captura errores en estos métodos).
 */
final class PasswordResetTest extends TestCase
{
    #[Test]
    public function createToken_sin_base_de_datos_lanza_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbPasswordReset())->createToken(1, 'admin@test.com');
    }

    #[Test]
    public function validateToken_sin_base_de_datos_lanza_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbPasswordReset())->validateToken('token-de-prueba');
    }

    #[Test]
    public function markAsUsed_sin_base_de_datos_lanza_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbPasswordReset())->markAsUsed('token-de-prueba');
    }

    #[Test]
    public function deleteExpiredTokens_sin_base_de_datos_lanza_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbPasswordReset())->deleteExpiredTokens();
    }
}
