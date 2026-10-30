<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\CrudConfigs;
use SysInescolara\Tests\Interfaz\Support\CrudTestCase;
use SysInescolara\Tests\Interfaz\Support\Fixtures;

final class InventarioCrudTest extends CrudTestCase
{
    #[Test]
    public function crud_de_unidades_de_medida(): void
    {
        $name = $this->unique('Unidad', 50);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::unidades(),
            ['nombre' => $name],
            ['nombre' => $edited],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_herramientas(): void
    {
        $name = $this->unique('Herramienta', 80);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::herramientas(),
            [
                'nombre_herramienta' => $name,
                'cantidad' => '2',
                'estado' => 'Disponible',
                'fecha_ultimo_mantenimiento' => date('Y-m-d'),
                'observacion' => 'Herramienta E2E',
            ],
            [
                'nombre_herramienta' => $edited,
                'cantidad' => '3',
                'estado' => 'En Uso',
                'observacion' => 'Herramienta E2E actualizada',
            ],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_insumos(): void
    {
        $unidad = Fixtures::unidad();
        $name = $this->unique('Insumo', 80);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::insumos(),
            [
                'nombre_insumo' => $name,
                'id_unidad_medida' => $unidad['id'],
                'categoria' => 'E2E',
                'stock_actual' => '12',
                'costo_unitario_actual' => '1.50',
            ],
            [
                'nombre_insumo' => $edited,
                'categoria' => 'E2E editada',
                'stock_actual' => '9',
                'costo_unitario_actual' => '2.25',
            ],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_lotes(): void
    {
        $planta = Fixtures::planta();
        $ubicacion = Fixtures::ubicacion();
        $name = $this->unique('Lote', 40);

        $this->runCrud(
            CrudConfigs::lotes(),
            [
                'id_planta' => $planta['id'],
                'id_ubicacion' => $ubicacion['id'],
                'fecha_siembra' => date('Y-m-d', strtotime('-10 days')),
                'cantidad_inicial' => '25',
                'costo_unitario' => '2.50',
                'porcentaje_ganancia' => '30',
                'id_categoria' => null,
                'id_origen' => null,
                'observacion' => $name,
            ],
            [
                'cantidad_actual' => '20',
                'observacion' => $name . ' edit',
            ],
            $planta['name'],
            $planta['name']
        );
    }
}
