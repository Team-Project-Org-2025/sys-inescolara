<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\core\Database;
use SysInescolara\models\Especie;
use SysInescolara\Tests\Support\NoDbTrait;
use SysInescolara\Tests\Support\SqliteTrait;

final class AutoloadTest extends TestCase
{
    #[Test]
    public function el_autoload_resuelve_clases_de_la_aplicacion(): void
    {
        $this->assertTrue(class_exists(Database::class));
        $this->assertTrue(class_exists(Especie::class));
    }

    #[Test]
    public function el_bootstrap_registra_los_traits_de_soporte(): void
    {
        $this->assertTrue(trait_exists(NoDbTrait::class));
        $this->assertTrue(trait_exists(SqliteTrait::class));
    }

    #[Test]
    public function la_zona_horaria_coincide_con_la_aplicacion(): void
    {
        $this->assertSame('America/Caracas', date_default_timezone_get());
    }
}
