<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Analitica;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbReports;

/**
 * Reports define sus módulos y filtros sin tocar la base de datos: los módulos
 * se construyen en el constructor y las opciones de los select se capturan en
 * try/catch. Sin BD queda el esqueleto de filtros con la opción "Todos".
 */
final class ReportsTest extends TestCase
{
    #[Test]
    public function getModules_devuelve_vacio_cuando_el_constructor_no_se_ejecuta(): void
    {
        $this->assertSame([], (new NoDbReports())->getModules());
    }

    #[Test]
    public function getModuleFilters_de_plantas_conserva_el_esqueleto_de_filtros(): void
    {
        $filtros = (new NoDbReports())->getModuleFilters('plantas');

        $this->assertCount(6, $filtros);
        $this->assertSame(
            ['activo', 'id_especie', 'nombre_comun', 'nombre_tecnico', 'stock_lotes_min', 'stock_lotes_max'],
            array_column($filtros, 'field')
        );
        $this->assertSame('p.activo', $filtros[0]['column']);
        $this->assertSame('select', $filtros[1]['type']);
        $this->assertSame('p.id_especie', $filtros[1]['column']);
        $this->assertSame([['value' => '', 'label' => 'Todos']], $filtros[1]['options']);
        $this->assertTrue($filtros[4]['manual']);
    }

    #[Test]
    public function getModuleFilters_de_lotes_usa_columnas_de_texto_sin_esquema(): void
    {
        $filtros = (new NoDbReports())->getModuleFilters('lotes');

        $porCampo = [];
        foreach ($filtros as $filtro) {
            $porCampo[$filtro['field']] = $filtro;
        }

        $this->assertCount(10, $filtros);
        $this->assertSame('l.estado', $porCampo['estado_lote']['column']);
        $this->assertSame('l.categoria', $porCampo['categoria']['column']);
        $this->assertSame('l.origen', $porCampo['origen']['column']);
        $this->assertSame([['value' => '', 'label' => 'Todos']], $porCampo['estado_lote']['options']);
    }

    #[Test]
    public function getModuleFilters_de_un_modulo_desconocido_solo_devuelve_el_filtro_activo(): void
    {
        $filtros = (new NoDbReports())->getModuleFilters('modulo_inexistente');

        $this->assertCount(1, $filtros);
        $this->assertSame('activo', $filtros[0]['field']);
        $this->assertSame('activo', $filtros[0]['column']);
        $this->assertSame('select', $filtros[0]['type']);
        $this->assertSame('Todos', $filtros[0]['options'][0]['label']);
    }

    #[Test]
    public function getModuleFilters_de_ventas_mantiene_los_filtros_manuales(): void
    {
        $filtros = (new NoDbReports())->getModuleFilters('ventas');
        $porCampo = array_column($filtros, null, 'field');

        $this->assertArrayHasKey('total_min', $porCampo);
        $this->assertArrayHasKey('items_min', $porCampo);
        $this->assertTrue($porCampo['total_min']['manual']);
        $this->assertSame('v.id_cliente', $porCampo['id_cliente']['column']);
        $this->assertSame([['value' => '', 'label' => 'Todos']], $porCampo['id_cliente']['options']);
    }

    #[Test]
    public function getReportData_de_un_modulo_desconocido_devuelve_la_estructura_vacia(): void
    {
        $this->assertSame(
            ['columns' => [], 'rows' => [], 'chart' => null],
            (new NoDbReports())->getReportData('modulo_inexistente', [])
        );
    }

    #[Test]
    public function getReportData_de_los_modulos_conocidos_se_degrada_sin_base_de_datos(): void
    {
        $reports = new NoDbReports();
        $modulos = [
            'plantas', 'lotes', 'insumos', 'proveedores', 'clientes', 'trabajadores',
            'tareas', 'ventas', 'compras', 'herramientas', 'especies', 'inventario',
            'recoleccion', 'ubicaciones', 'unidades_medida', 'cuentas_pagar',
            'precios', 'cuentas_cobrar',
        ];

        foreach ($modulos as $modulo) {
            $this->assertSame(
                ['columns' => [], 'rows' => [], 'chart' => null],
                $reports->getReportData($modulo, []),
                sprintf('El módulo %s debe degradarse sin BD', $modulo)
            );
        }
    }
}
