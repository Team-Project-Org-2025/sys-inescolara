<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\models\Inventory;
use SysInescolara\Tests\Support\NoDbInventory;

final class InventoryTest extends TestCase
{
    #[Test]
    public function el_doble_conserva_el_tipo_del_modelo_real(): void
    {
        $this->assertInstanceOf(Inventory::class, new NoDbInventory());
    }

    #[Test]
    public function getConsolidated_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $inventario = new NoDbInventory();

        $this->assertSame([], $inventario->getConsolidated());
    }
}
