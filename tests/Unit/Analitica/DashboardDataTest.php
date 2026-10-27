<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Analitica;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbDashboardData;

/**
 * DashboardData captura cualquier Throwable dentro de cada consulta y devuelve
 * valores degradados. Sin base de datos todas las consultas fallan, así que la
 * prueba verifica exactamente ese contrato: claves completas en cero y arrays
 * vacíos, nunca excepciones hacia la vista.
 */
final class DashboardDataTest extends TestCase
{
    private const CLAVES_STATS = [
        'total_especies',
        'total_plantas',
        'total_clientes',
        'total_proveedores',
        'total_trabajadores',
        'total_lotes',
        'total_insumos',
        'total_herramientas',
        'total_precios_vigentes',
        'total_ventas',
        'total_ubicaciones',
        'total_compras',
        'total_ornatos',
        'total_tareas_pendientes',
        'total_ventas_bs',
        'total_cobrado',
        'total_compras_bs',
        'total_cuentas_cobrar',
        'total_mermas',
    ];

    #[Test]
    public function getStats_sin_base_de_datos_devuelve_todas_las_claves_en_cero(): void
    {
        $stats = (new NoDbDashboardData())->getStats();

        $this->assertSame(self::CLAVES_STATS, array_keys($stats));
        $this->assertCount(19, $stats);

        foreach (self::CLAVES_STATS as $clave) {
            $this->assertSame(0, (int) $stats[$clave], sprintf('La clave %s debe quedar en cero', $clave));
        }
    }

    #[Test]
    public function getStats_convierte_los_montos_degradados_a_cero_numerico(): void
    {
        $stats = (new NoDbDashboardData())->getStats();

        $this->assertSame(0.0, (float) $stats['total_ventas_bs']);
        $this->assertSame(0.0, (float) $stats['total_cobrado']);
        $this->assertSame(0.0, (float) $stats['total_compras_bs']);
    }

    #[Test]
    public function getPlantsBySpecies_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getPlantsBySpecies());
    }

    #[Test]
    public function getInventorySummary_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getInventorySummary());
    }

    #[Test]
    public function getPendingTasks_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getPendingTasks());
    }

    #[Test]
    public function getRecentActivity_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $dashboard = new NoDbDashboardData();

        $this->assertSame([], $dashboard->getRecentActivity());
        $this->assertSame([], $dashboard->getRecentActivity(3));
    }

    #[Test]
    public function getLowStockLots_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getLowStockLots());
        $this->assertSame([], (new NoDbDashboardData())->getLowStockLots(50));
    }

    #[Test]
    public function getLowStockSupplies_sin_base_de_datos_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getLowStockSupplies());
        $this->assertSame([], (new NoDbDashboardData())->getLowStockSupplies(100));
    }

    #[Test]
    public function getReportData_con_tipo_desconocido_devuelve_array_vacio(): void
    {
        $this->assertSame([], (new NoDbDashboardData())->getReportData('tipo_inexistente'));
    }

    #[Test]
    public function getReportData_de_todos_los_tipos_conocidos_se_degrada_a_array_vacio(): void
    {
        $dashboard = new NoDbDashboardData();

        foreach (['plants_by_species', 'lots_by_status', 'inventory_summary', 'supply_stock', 'recent_sales'] as $tipo) {
            $this->assertSame([], $dashboard->getReportData($tipo), sprintf('El reporte %s debe degradarse', $tipo));
        }
    }
}
