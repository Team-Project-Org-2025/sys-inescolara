<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\helpers\Auth;
use SysInescolara\Tests\Support\NoDbAuditLog;

/**
 * AuditLog no tiene getters ni setters: solo escritura y lectura de la
 * bitácora. Sin base de datos todas las consultas degradan a false/null/[].
 */
final class AuditLogTest extends TestCase
{
    #[Test]
    public function log_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbAuditLog())->log(1, 'CREATE', 'roles', 1));
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio_degradado(): void
    {
        $this->assertSame([], (new NoDbAuditLog())->getAll());
    }

    #[Test]
    public function getById_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbAuditLog())->getById(1));
    }

    #[Test]
    public function record_sin_sesion_iniciada_no_intenta_escribir_y_no_devuelve_nada(): void
    {
        $_SESSION = [];

        $resultado = NoDbAuditLog::record('CREATE', 'roles', 1, null, ['nombre_rol' => 'Auditor']);

        $this->assertNull($resultado);
        $this->assertFalse(Auth::check());
        $this->assertSame([], $_SESSION);
    }
}
