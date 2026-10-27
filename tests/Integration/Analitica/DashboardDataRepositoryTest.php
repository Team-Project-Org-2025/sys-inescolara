<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Analitica;

use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use SysInescolara\models\DashboardData;
use SysInescolara\Tests\Support\SqliteDashboardData;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Ejecuta el SQL real de DashboardData sobre SQLite en memoria.
 *
 * Dos adaptaciones necesarias:
 * - `FIELD()` no está emulado por SqliteTestCase, así que la prueba registra
 *   una equivalencia 1-based para poder validar el orden de los niveles.
 * - `$secDb` es una conexión MySQL privada; se inyecta el PDO de la prueba por
 *   reflexión para probar getRecentActivity() contra las mismas tablas.
 */
final class DashboardDataRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->sqliteCreateFunction(
            'field',
            static function (mixed $value, mixed ...$list): int {
                foreach ($list as $index => $candidate) {
                    if ((string) $value === (string) $candidate) {
                        return $index + 1;
                    }
                }

                return 0;
            },
            -1
        );

        $this->attachSecurityDatabase();
        $this->crearTablas();

        SqliteDashboardData::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteDashboardData::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function getStats_cuenta_registros_activos_y_suma_los_montos(): void
    {
        $this->sembrarDatosDeStats();

        $stats = $this->dashboard()->getStats();

        $this->assertSame(2, $stats['total_especies']);
        $this->assertSame(3, $stats['total_plantas']);
        $this->assertSame(2, $stats['total_clientes']);
        $this->assertSame(1, $stats['total_proveedores']);
        $this->assertSame(2, $stats['total_trabajadores']);
        $this->assertSame(3, $stats['total_lotes']);
        $this->assertSame(2, $stats['total_insumos']);
        $this->assertSame(1, $stats['total_herramientas']);
        $this->assertSame(2, $stats['total_precios_vigentes']);
        $this->assertSame(2, $stats['total_ventas']);
        $this->assertSame(1, $stats['total_ubicaciones']);
        $this->assertSame(1, $stats['total_compras']);
        $this->assertSame(1, $stats['total_ornatos']);
        $this->assertSame(2, $stats['total_tareas_pendientes']);
        $this->assertSame(1300.0, (float) $stats['total_ventas_bs']);
        $this->assertSame(500.0, (float) $stats['total_cobrado']);
        $this->assertSame(500.0, (float) $stats['total_compras_bs']);
        $this->assertSame(1, $stats['total_cuentas_cobrar']);
        $this->assertSame(1, $stats['total_mermas']);
    }

    #[Test]
    public function getStats_devuelve_cero_cuando_falta_una_tabla(): void
    {
        $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $this->exec('DROP TABLE herramienta');

        $stats = $this->dashboard()->getStats();

        $this->assertSame(0, $stats['total_herramientas']);
        $this->assertSame(1, $stats['total_especies']);
    }

    #[Test]
    public function getPlantsBySpecies_agrupa_solo_plantas_activas_y_ordena_por_cantidad(): void
    {
        $rosa = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $orquidea = $this->insert('especie', ['nombre_especie' => 'Orquídea', 'activo' => 1]);
        $inactiva = $this->insert('especie', ['nombre_especie' => 'Inactiva', 'activo' => 0]);

        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa roja', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa blanca', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa muerta', 'activo' => 0]);
        $this->insert('plantas', ['id_especie' => $orquidea, 'nombre_comun' => 'Orquídea', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $inactiva, 'nombre_comun' => 'Fantasma', 'activo' => 1]);

        $filas = $this->dashboard()->getPlantsBySpecies();

        $this->assertSame(['Rosa', 'Orquídea'], array_column($filas, 'label'));
        $this->assertSame([2, 1], array_map('intval', array_column($filas, 'value')));
    }

    #[Test]
    public function getInventorySummary_clasifica_los_lotes_y_respeta_el_orden_de_niveles(): void
    {
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 0, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 30, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 60, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 5, 'activo' => 0]);

        $filas = $this->dashboard()->getInventorySummary();

        $this->assertSame(['Alto', 'Medio', 'Bajo', 'Sin stock'], array_column($filas, 'label'));
        $this->assertSame([1, 1, 1, 1], array_map('intval', array_column($filas, 'value')));
    }

    #[Test]
    public function getPendingTasks_excluye_cerradas_ordena_por_fecha_y_limita_a_seis(): void
    {
        $usuario = 1;
        $this->insertarUsuarioSeguridad(1, 'juan', 'Activo');

        for ($dia = 1; $dia <= 7; $dia++) {
            $this->insert('asignar_tarea', [
                'id_usuario' => $usuario,
                'nombre_tarea' => 'Tarea ' . $dia,
                'descripcion' => 'Detalle ' . $dia,
                'fecha_asignacion' => '2026-02-01',
                'fecha_cumplimiento' => sprintf('2026-03-%02d', $dia),
                'estatus_tarea' => 'pendiente',
            ]);
        }
        $this->insert('asignar_tarea', [
            'id_usuario' => $usuario,
            'nombre_tarea' => 'Terminada',
            'fecha_asignacion' => '2026-02-01',
            'fecha_cumplimiento' => '2026-02-15',
            'estatus_tarea' => 'completada',
        ]);
        $this->insert('asignar_tarea', [
            'id_usuario' => $usuario,
            'nombre_tarea' => 'Cancelada',
            'fecha_asignacion' => '2026-02-01',
            'fecha_cumplimiento' => '2026-02-16',
            'estatus_tarea' => 'cancelada',
        ]);

        $tareas = $this->dashboard()->getPendingTasks();

        $this->assertCount(6, $tareas);
        $this->assertSame(
            ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06'],
            array_column($tareas, 'fecha_cumplimiento')
        );
        $this->assertSame('juan', $tareas[0]['nombre_trabajador']);
        $this->assertSame('Tarea 1', $tareas[0]['nombre_tarea']);
        $this->assertArrayHasKey('id_asignacion', $tareas[0]);
        $this->assertArrayHasKey('estatus_tarea', $tareas[0]);
    }

    #[Test]
    public function getRecentActivity_usa_la_conexion_de_seguridad_y_respeta_el_limite(): void
    {
        $this->insert('usuarios', ['id_usuario' => 1, 'nombre_usuario' => 'admin']);
        $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'UPDATE',
            'tabla_afectada' => 'lote',
            'fecha_accion' => '2026-01-10 09:00:00',
            'valor_nuevo' => '{"a":1}',
        ]);
        $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'DELETE',
            'tabla_afectada' => 'planta',
            'fecha_accion' => '2026-03-10 09:00:00',
            'valor_nuevo' => '{"b":2}',
        ]);
        $this->insert('auditoria_logs', [
            'id_usuario' => 1,
            'accion' => 'INSERT',
            'tabla_afectada' => 'venta',
            'fecha_accion' => '2026-02-10 09:00:00',
            'valor_nuevo' => '{"c":3}',
        ]);

        $actividad = $this->dashboard()->getRecentActivity(2);

        $this->assertCount(2, $actividad);
        $this->assertSame(['DELETE', 'INSERT'], array_column($actividad, 'accion'));
        $this->assertSame('admin', $actividad[0]['nombre_usuario']);
    }

    #[Test]
    public function getLowStockLots_devuelve_solo_lotes_activos_bajo_el_umbral(): void
    {
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $estado = $this->insert('estado', ['nombre' => 'Bueno']);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $estado, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $estado, 'cantidad_inicial' => 10, 'cantidad_actual' => 5, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $estado, 'cantidad_inicial' => 10, 'cantidad_actual' => 25, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $estado, 'cantidad_inicial' => 10, 'cantidad_actual' => 1, 'activo' => 0]);

        $lotes = $this->dashboard()->getLowStockLots(20);

        $this->assertCount(2, $lotes);
        $this->assertSame([5, 10], array_map('intval', array_column($lotes, 'cantidad_actual')));
        $this->assertSame('Bueno', $lotes[0]['estado_nombre']);
        $this->assertSame('Rosa', $lotes[0]['planta_nombre']);
    }

    #[Test]
    public function getLowStockSupplies_devuelve_insumos_activos_bajo_el_umbral_con_su_unidad(): void
    {
        $unidad = $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo']);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Fertilizante', 'stock_actual' => 3, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Sustrato', 'stock_actual' => 8, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Abono', 'stock_actual' => 15, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Inactivo', 'stock_actual' => 1, 'activo' => 0]);

        $insumos = $this->dashboard()->getLowStockSupplies(10);

        $this->assertCount(2, $insumos);
        $this->assertSame(['Fertilizante', 'Sustrato'], array_column($insumos, 'nombre_insumo'));
        $this->assertSame('Kilogramo', $insumos[0]['unidad_medida']);
    }

    #[Test]
    public function getReportData_de_plantas_por_especie_devuelve_el_conteo_por_especie(): void
    {
        $rosa = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $orquidea = $this->insert('especie', ['nombre_especie' => 'Orquídea', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa 1', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa 2', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $orquidea, 'nombre_comun' => 'Orquídea 1', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa inactiva', 'activo' => 0]);

        $filas = $this->dashboard()->getReportData('plants_by_species');

        $this->assertSame(['Rosa', 'Orquídea'], array_column($filas, 'especie'));
        $this->assertSame([2, 1], array_map('intval', array_column($filas, 'total_plantas')));
    }

    #[Test]
    public function getReportData_de_lotes_por_estado_agrupa_por_estado(): void
    {
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $bueno = $this->insert('estado', ['nombre' => 'Bueno']);
        $regular = $this->insert('estado', ['nombre' => 'Regular']);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $bueno, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $bueno, 'cantidad_inicial' => 10, 'cantidad_actual' => 20, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $regular, 'cantidad_inicial' => 10, 'cantidad_actual' => 5, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'id_estado' => $regular, 'cantidad_inicial' => 10, 'cantidad_actual' => 5, 'activo' => 0]);

        $filas = $this->dashboard()->getReportData('lots_by_status');

        $this->assertSame(['Bueno', 'Regular'], array_column($filas, 'estado'));
        $this->assertSame([2, 1], array_map('intval', array_column($filas, 'total_lotes')));
        $this->assertSame([30, 5], array_map('intval', array_column($filas, 'total_plantas')));
    }

    #[Test]
    public function getReportData_de_inventario_clasifica_por_nivel_de_stock(): void
    {
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 0, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 60, 'activo' => 1]);

        $filas = $this->dashboard()->getReportData('inventory_summary');

        $this->assertSame(['Alto', 'Bajo', 'Sin stock'], array_column($filas, 'nivel_stock'));
        $this->assertSame([1, 1, 1], array_map('intval', array_column($filas, 'total_lotes')));
        $this->assertSame([60, 10, 0], array_map('intval', array_column($filas, 'total_plantas')));
    }

    #[Test]
    public function getReportData_de_stock_de_insumos_ordena_por_stock_ascendente(): void
    {
        $unidad = $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Litro']);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'B', 'stock_actual' => 2, 'costo_unitario_actual' => 1.5, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'A', 'stock_actual' => 9, 'costo_unitario_actual' => 2.5, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'C', 'stock_actual' => 1, 'costo_unitario_actual' => 3.0, 'activo' => 0]);

        $filas = $this->dashboard()->getReportData('supply_stock');

        $this->assertSame(['B', 'A'], array_column($filas, 'nombre_insumo'));
        $this->assertSame('Litro', $filas[0]['unidad_medida']);
        $this->assertSame(1.5, (float) $filas[0]['costo_unitario_actual']);
    }

    #[Test]
    public function getReportData_de_ventas_recientes_une_cliente_y_detalle(): void
    {
        $cliente = $this->insert('cliente', [
            'nombre_cliente' => 'Ana',
            'apellido_cliente' => 'Pérez',
            'activo' => 1,
        ]);
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $lote = $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'activo' => 1]);

        $enero = $this->insert('movimiento_planta', [
            'tipo_movimiento' => 'Venta',
            'id_cliente' => $cliente,
            'id_usuario_gestor' => 1,
            'fecha_movimiento' => '2026-01-10',
            'activo' => 1,
        ]);
        $febrero = $this->insert('movimiento_planta', [
            'tipo_movimiento' => 'Venta',
            'id_cliente' => $cliente,
            'id_usuario_gestor' => 1,
            'fecha_movimiento' => '2026-02-10',
            'activo' => 1,
        ]);
        $this->insert('movimiento_planta', [
            'tipo_movimiento' => 'Donacion',
            'id_cliente' => $cliente,
            'id_usuario_gestor' => 1,
            'fecha_movimiento' => '2026-03-10',
            'activo' => 1,
        ]);

        $this->insert('movimiento_planta_detalle', [
            'id_movimiento_planta' => $enero,
            'id_lote' => $lote,
            'cantidad' => 1,
            'sub_total' => 100.5,
            'activo' => 1,
        ]);
        $this->insert('movimiento_planta_detalle', [
            'id_movimiento_planta' => $febrero,
            'id_lote' => $lote,
            'cantidad' => 2,
            'sub_total' => 250.0,
            'activo' => 1,
        ]);

        $filas = $this->dashboard()->getReportData('recent_sales');

        $this->assertCount(2, $filas);
        $this->assertSame('Ana Pérez', $filas[0]['nombre_cliente']);
        $this->assertSame(250.0, (float) $filas[0]['monto_total']);
        $this->assertSame('2026-02-10', $filas[0]['fecha_venta']);
        $this->assertSame('2026-01-10', $filas[1]['fecha_venta']);
    }

    #[Test]
    public function getReportData_de_un_tipo_desconocido_devuelve_array_vacio(): void
    {
        $this->assertSame([], $this->dashboard()->getReportData('inexistente'));
    }

    private function dashboard(): SqliteDashboardData
    {
        $dashboard = new SqliteDashboardData();

        $propiedad = new ReflectionProperty(DashboardData::class, 'secDb');
        $propiedad->setValue($dashboard, $this->pdo);

        return $dashboard;
    }

    /**
     * El helper insert() de SqliteTestCase valida identificadores simples, así
     * que las filas de la base adjunta se insertan con SQL propio.
     */
    private function insertarUsuarioSeguridad(int $id, string $nombre, string $estatus): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO "SysInescolara-Seguridad".usuarios (id_usuario, nombre_usuario, estatus) VALUES (?, ?, ?)'
        );
        $stmt->execute([$id, $nombre, $estatus]);
    }

    private function sembrarDatosDeStats(): void
    {
        $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $this->insert('especie', ['nombre_especie' => 'Orquídea', 'activo' => 1]);
        $this->insert('especie', ['nombre_especie' => 'Inactiva', 'activo' => 0]);

        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa 1', 'activo' => 1]);
        $this->insert('plantas', ['nombre_comun' => 'Rosa 2', 'activo' => 1]);
        $this->insert('plantas', ['nombre_comun' => 'Rosa 3', 'activo' => 1]);
        $this->insert('plantas', ['nombre_comun' => 'Rosa inactiva', 'activo' => 0]);

        $this->insert('cliente', ['nombre_cliente' => 'Ana', 'apellido_cliente' => 'Pérez', 'activo' => 1]);
        $this->insert('cliente', ['nombre_cliente' => 'Luis', 'apellido_cliente' => 'Gómez', 'activo' => 1]);
        $this->insert('cliente', ['nombre_cliente' => 'Inactivo', 'apellido_cliente' => 'X', 'activo' => 0]);

        $this->insert('proveedores', ['nombre_proveedor' => 'Vivero', 'rif_proveedor' => 'J-1', 'activo' => 1]);
        $this->insert('proveedores', ['nombre_proveedor' => 'Viejo', 'rif_proveedor' => 'J-2', 'activo' => 0]);

        $this->insertarUsuarioSeguridad(1, 'juan', 'Activo');
        $this->insertarUsuarioSeguridad(2, 'maria', 'Activo');
        $this->insertarUsuarioSeguridad(3, 'pedro', 'Inactivo');

        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 10, 'costo_unitario' => 10, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 5, 'costo_unitario' => 5, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 1, 'costo_unitario' => 0, 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $planta, 'cantidad_inicial' => 10, 'cantidad_actual' => 1, 'costo_unitario' => 7, 'activo' => 0]);

        $unidad = $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo']);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Fertilizante', 'stock_actual' => 5, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Sustrato', 'stock_actual' => 15, 'activo' => 1]);
        $this->insert('insumo', ['id_unidad_medida' => $unidad, 'nombre_insumo' => 'Viejo', 'stock_actual' => 1, 'activo' => 0]);

        $this->insert('herramienta', ['nombre_herramienta' => 'Pala', 'activo' => 1]);
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero 1', 'activo' => 1]);

        $this->insert('compra', ['id_proveedor' => 1, 'fecha_compra' => '2026-01-05', 'total' => 500, 'activo' => 1]);
        $this->insert('compra', ['id_proveedor' => 1, 'fecha_compra' => '2026-01-06', 'total' => 900, 'activo' => 0]);

        $this->insert('ornatos', ['id_cliente' => 1, 'fecha' => '2026-01-07', 'activo' => 1]);

        $this->insert('asignar_tarea', ['id_usuario' => 1, 'nombre_tarea' => 'Regar', 'fecha_asignacion' => '2026-01-01', 'estatus_tarea' => 'pendiente']);
        $this->insert('asignar_tarea', ['id_usuario' => 2, 'nombre_tarea' => 'Podar', 'fecha_asignacion' => '2026-01-01', 'estatus_tarea' => 'en_proceso']);
        $this->insert('asignar_tarea', ['id_usuario' => 1, 'nombre_tarea' => 'Listo', 'fecha_asignacion' => '2026-01-01', 'estatus_tarea' => 'completada']);
        $this->insert('asignar_tarea', ['id_usuario' => 1, 'nombre_tarea' => 'Cancelada', 'fecha_asignacion' => '2026-01-01', 'estatus_tarea' => 'cancelada']);

        $this->insert('venta', ['referencia' => 'V-1', 'id_cliente' => 1, 'id_usuario' => 1, 'total' => 1000, 'activo' => 1]);
        $this->insert('venta', ['referencia' => 'V-2', 'id_cliente' => 1, 'id_usuario' => 1, 'total' => 250, 'activo' => 0]);
        $this->insert('venta', [
            'referencia' => 'V-3',
            'id_cliente' => 1,
            'id_usuario' => 1,
            'tipo_venta' => 'credito',
            'estado' => 'pendiente',
            'total' => 300,
            'activo' => 1,
        ]);

        $this->insert('pago_venta', ['id_venta' => 1, 'monto' => 300, 'estado_pago' => 'registrado']);
        $this->insert('pago_venta', ['id_venta' => 1, 'monto' => 200, 'estado_pago' => 'confirmado']);
        $this->insert('pago_venta', ['id_venta' => 1, 'monto' => 100, 'estado_pago' => 'rechazado']);

        $this->insert('mermas_historico', ['id_trazabilidad' => 1, 'id_lote' => 1, 'cantidad' => 1, 'motivo' => 'plaga', 'fecha_merma' => '2026-01-01', 'activo' => 1]);
        $this->insert('mermas_historico', ['id_trazabilidad' => 1, 'id_lote' => 1, 'cantidad' => 1, 'motivo' => 'otro', 'fecha_merma' => '2026-01-02', 'activo' => 0]);

        $this->insert('movimiento_planta', ['tipo_movimiento' => 'Venta', 'id_cliente' => 1, 'id_usuario_gestor' => 1, 'fecha_movimiento' => '2026-01-01', 'activo' => 1]);
        $this->insert('movimiento_planta', ['tipo_movimiento' => 'Venta', 'id_cliente' => 1, 'id_usuario_gestor' => 1, 'fecha_movimiento' => '2026-01-02', 'activo' => 1]);
        $this->insert('movimiento_planta', ['tipo_movimiento' => 'Donacion', 'id_cliente' => 1, 'id_usuario_gestor' => 1, 'fecha_movimiento' => '2026-01-03', 'activo' => 1]);
    }

    private function crearTablas(): void
    {
        $this->exec('
            CREATE TABLE especie (
                id_especie     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie TEXT NOT NULL,
                descripcion    TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT "",
                nombre_comun   TEXT DEFAULT NULL,
                cantidad_total INTEGER NOT NULL DEFAULT 0,
                imagen         TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE ubicacion (
                id_ubicacion     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_ubicacion TEXT NOT NULL,
                descripcion      TEXT DEFAULT NULL,
                zona             TEXT DEFAULT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE unidad_medida (
                id_unidad_medida     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_unidad_medida TEXT NOT NULL,
                simbolo              TEXT DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE estado (
                id_estado INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre    TEXT NOT NULL,
                activo    INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE lote (
                id_lote             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta           INTEGER NOT NULL,
                id_ubicacion        INTEGER DEFAULT NULL,
                fecha_siembra       TEXT NOT NULL DEFAULT "2026-01-01",
                cantidad_inicial    INTEGER NOT NULL DEFAULT 0,
                cantidad_actual     INTEGER NOT NULL DEFAULT 0,
                costo_unitario      REAL NOT NULL DEFAULT 0,
                id_estado           INTEGER DEFAULT NULL,
                id_categoria        INTEGER DEFAULT NULL,
                id_origen           INTEGER DEFAULT NULL,
                observacion         TEXT DEFAULT NULL,
                imagen              TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia REAL NOT NULL DEFAULT 30
            )
        ');

        $this->exec('
            CREATE TABLE insumo (
                id_insumo              INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida       INTEGER NOT NULL,
                nombre_insumo          TEXT NOT NULL,
                categoria              TEXT DEFAULT NULL,
                stock_actual           REAL NOT NULL DEFAULT 0,
                costo_unitario_actual  REAL NOT NULL DEFAULT 0,
                activo                 INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE herramienta (
                id_herramienta             INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_herramienta         TEXT NOT NULL,
                cantidad                   INTEGER NOT NULL DEFAULT 1,
                tipo                       TEXT DEFAULT NULL,
                estado                     TEXT NOT NULL DEFAULT "disponible",
                fecha_adquisicion          TEXT DEFAULT NULL,
                fecha_ultimo_mantenimiento TEXT DEFAULT NULL,
                observacion                TEXT DEFAULT NULL,
                activo                     INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE cliente (
                id_cliente          INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_cedula_cliente TEXT DEFAULT NULL,
                cedula_cliente      TEXT DEFAULT NULL,
                nombre_cliente      TEXT NOT NULL,
                apellido_cliente    TEXT NOT NULL DEFAULT "",
                contacto_cliente    TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE proveedores (
                id_proveedor       INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_proveedor   TEXT NOT NULL,
                rif_proveedor      TEXT NOT NULL,
                contacto_vendedor  TEXT DEFAULT NULL,
                telefono_proveedor TEXT DEFAULT NULL,
                activo             INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE asignar_tarea (
                id_asignacion      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario         INTEGER NOT NULL,
                nombre_tarea       TEXT NOT NULL,
                descripcion        TEXT DEFAULT NULL,
                fecha_asignacion   TEXT NOT NULL DEFAULT "2026-01-01",
                fecha_cumplimiento TEXT DEFAULT NULL,
                estatus_tarea      TEXT NOT NULL DEFAULT "pendiente",
                horas_dedicadas    REAL DEFAULT NULL
            )
        ');

        $this->exec('
            CREATE TABLE compra (
                id_compra          INTEGER PRIMARY KEY AUTOINCREMENT,
                id_proveedor       INTEGER NOT NULL,
                fecha_compra       TEXT NOT NULL DEFAULT "2026-01-01",
                tipo_comprobante   TEXT DEFAULT "Factura",
                numero_comprobante TEXT DEFAULT NULL,
                subtotal           REAL NOT NULL DEFAULT 0,
                iva                REAL NOT NULL DEFAULT 0,
                total              REAL NOT NULL DEFAULT 0,
                estado             TEXT NOT NULL DEFAULT "pendiente",
                activo             INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE ornatos (
                id_ornato   INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cliente  INTEGER NOT NULL,
                tipo_ornato TEXT NOT NULL DEFAULT "Venta",
                descripcion TEXT DEFAULT NULL,
                ubicacion   TEXT DEFAULT NULL,
                monto_total REAL DEFAULT 0,
                fecha       TEXT NOT NULL DEFAULT "2026-01-01",
                activo      INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE venta (
                id_venta          INTEGER PRIMARY KEY AUTOINCREMENT,
                referencia        TEXT NOT NULL,
                id_cliente        INTEGER NOT NULL DEFAULT 1,
                id_usuario        INTEGER NOT NULL DEFAULT 1,
                tipo_venta        TEXT NOT NULL DEFAULT "contado",
                estado            TEXT NOT NULL DEFAULT "completada",
                iva_porcentaje    REAL NOT NULL DEFAULT 16,
                fecha_venta       TEXT NOT NULL DEFAULT "2026-01-01 00:00:00",
                fecha_vencimiento TEXT DEFAULT NULL,
                observaciones     TEXT DEFAULT NULL,
                activo            INTEGER NOT NULL DEFAULT 1,
                total             REAL NOT NULL DEFAULT 0
            )
        ');

        $this->exec('
            CREATE TABLE detalle_venta (
                id_detalle_venta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_venta         INTEGER NOT NULL,
                id_lote          INTEGER DEFAULT NULL,
                cantidad         INTEGER NOT NULL,
                precio_unitario  REAL NOT NULL
            )
        ');

        $this->exec('
            CREATE TABLE pago_venta (
                id_pago       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_venta      INTEGER NOT NULL,
                metodo        TEXT NOT NULL DEFAULT "efectivo",
                monto         REAL NOT NULL,
                referencia    TEXT DEFAULT NULL,
                fecha_pago    TEXT DEFAULT NULL,
                estado_pago   TEXT NOT NULL DEFAULT "registrado",
                banco         TEXT DEFAULT NULL,
                id_usuario    INTEGER DEFAULT NULL,
                observaciones TEXT DEFAULT NULL
            )
        ');

        $this->exec('
            CREATE TABLE mermas_historico (
                id_merma             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_trazabilidad      INTEGER NOT NULL,
                id_lote              INTEGER NOT NULL,
                cantidad             INTEGER NOT NULL,
                motivo               TEXT NOT NULL,
                descripcion          TEXT DEFAULT NULL,
                fecha_merma          TEXT NOT NULL,
                impacto_economico    REAL NOT NULL DEFAULT 0,
                id_usuario_registra  INTEGER DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE movimiento_planta (
                id_movimiento_planta INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_movimiento      TEXT NOT NULL,
                id_cliente           INTEGER DEFAULT NULL,
                id_usuario_gestor    INTEGER NOT NULL DEFAULT 1,
                fecha_movimiento     TEXT NOT NULL DEFAULT "2026-01-01",
                observacion          TEXT DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE movimiento_planta_detalle (
                id_detalle_mov_planta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_movimiento_planta  INTEGER NOT NULL,
                id_lote               INTEGER NOT NULL,
                tipo                  TEXT NOT NULL DEFAULT "salida",
                cantidad              INTEGER NOT NULL,
                precio_unitario       REAL DEFAULT NULL,
                sub_total             REAL DEFAULT NULL,
                activo                INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE auditoria_logs (
                id_log               INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario           INTEGER DEFAULT NULL,
                accion               TEXT DEFAULT NULL,
                tabla_afectada       TEXT DEFAULT NULL,
                id_registro_afectado INTEGER DEFAULT NULL,
                valor_anterior       TEXT DEFAULT NULL,
                valor_nuevo          TEXT DEFAULT NULL,
                endpoint_solicitado  TEXT DEFAULT NULL,
                fecha_accion         TEXT NOT NULL DEFAULT "2026-01-01 00:00:00"
            )
        ');

        $this->exec('
            CREATE TABLE usuarios (
                id_usuario     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario TEXT NOT NULL
            )
        ');

        $this->exec('
            CREATE TABLE "SysInescolara-Seguridad".usuarios (
                id_usuario          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario      TEXT NOT NULL,
                nombre_trabajador   TEXT DEFAULT NULL,
                apellido_trabajador TEXT DEFAULT NULL,
                cedula_trabajador   TEXT DEFAULT NULL,
                telefono_trabajador TEXT DEFAULT NULL,
                cargo               TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                estatus             TEXT DEFAULT "Activo"
            )
        ');
    }
}
