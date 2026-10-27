<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Analitica;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Reports;
use SysInescolara\Tests\Support\SqliteReports;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Ejecuta el SQL real de Reports sobre SQLite en memoria.
 *
 * Omisión documentada: reportLotes(), reportInventario(), filtersLotes() y
 * filtersInventario() consultan information_schema para decidir si existen las
 * columnas FK (id_estado, id_categoria, id_origen). En SQLite esa consulta
 * falla y el modelo cae al esquema legado de columnas de texto; por eso las
 * tablas de prueba incluyen `estado`, `categoria` y `origen` como TEXT y no se
 * prueba el camino con FK.
 */
final class ReportsRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->attachSecurityDatabase();
        $this->crearTablas();

        SqliteReports::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteReports::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function getModules_devuelve_los_dieciocho_modulos_en_orden(): void
    {
        $reports = $this->reports();

        // El doble no ejecuta el constructor (evita MySQL), así que el catálogo
        // se hidrata invocando el mismo buildModules() de producción.
        $this->assertSame([], $reports->getModules());
        $this->hidratarModulos($reports);

        $modulos = $reports->getModules();

        $this->assertCount(18, $modulos);
        $this->assertSame('plantas', $modulos[0]['id']);
        $this->assertSame('Plantas', $modulos[0]['nombre']);
        $this->assertSame('fa-seedling', $modulos[0]['icono']);
        $this->assertTrue($modulos[0]['has_chart']);
        $this->assertSame('cuentas_cobrar', $modulos[17]['id']);
        $this->assertSame('Cuentas x Cobrar', $modulos[17]['nombre']);
        $this->assertFalse($modulos[4]['has_chart']);
        $this->assertSame(
            ['id', 'nombre', 'icono', 'has_chart'],
            array_keys($modulos[0])
        );
    }

    #[Test]
    public function getModuleFilters_de_plantas_carga_las_especies_activas(): void
    {
        $rosa = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $orquidea = $this->insert('especie', ['nombre_especie' => 'Orquídea', 'activo' => 1]);
        $this->insert('especie', ['nombre_especie' => 'Inactiva', 'activo' => 0]);

        $filtros = $this->reports()->getModuleFilters('plantas');
        $porCampo = array_column($filtros, null, 'field');

        $this->assertCount(6, $filtros);
        $this->assertSame('p.id_especie', $porCampo['id_especie']['column']);
        $this->assertSame(
            [
                ['value' => '', 'label' => 'Todos'],
                ['value' => $orquidea, 'label' => 'Orquídea'],
                ['value' => $rosa, 'label' => 'Rosa'],
            ],
            $porCampo['id_especie']['options']
        );
    }

    #[Test]
    public function getModuleFilters_de_inventario_usa_las_columnas_de_texto_del_lote(): void
    {
        $this->insert('lote', ['id_planta' => 1, 'estado' => 'Disponible', 'categoria' => 'Perenne']);
        $this->insert('lote', ['id_planta' => 1, 'estado' => 'Agotado', 'categoria' => 'Anual']);

        $filtros = $this->reports()->getModuleFilters('inventario');
        $porCampo = array_column($filtros, null, 'field');

        $this->assertSame(
            ['', 'Agotado', 'Disponible'],
            array_column($porCampo['estado']['options'], 'value')
        );
        $this->assertSame(
            ['', 'Anual', 'Perenne'],
            array_column($porCampo['categoria']['options'], 'value')
        );
        $this->assertSame('l.estado', $porCampo['estado']['column']);
        $this->assertCount(5, $porCampo['nivel_stock']['options']);
    }

    #[Test]
    public function getModuleFilters_de_lotes_carga_estado_planta_y_ubicacion(): void
    {
        $planta = $this->insert('plantas', ['nombre_comun' => 'Rosa', 'activo' => 1]);
        $ubicacion = $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero 1', 'activo' => 1]);
        $this->insert('lote', [
            'id_planta' => $planta,
            'id_ubicacion' => $ubicacion,
            'estado' => 'Disponible',
            'categoria' => 'Perenne',
            'origen' => 'Semilla',
        ]);

        $filtros = $this->reports()->getModuleFilters('lotes');
        $porCampo = array_column($filtros, null, 'field');

        $this->assertCount(10, $filtros);
        $this->assertSame('l.estado', $porCampo['estado_lote']['column']);
        $this->assertSame(['', 'Disponible'], array_column($porCampo['estado_lote']['options'], 'value'));
        $this->assertSame(['', 'Semilla'], array_column($porCampo['origen']['options'], 'value'));
        $this->assertSame(
            [['value' => '', 'label' => 'Todos'], ['value' => $planta, 'label' => 'Rosa']],
            $porCampo['id_planta']['options']
        );
        $this->assertSame(
            [['value' => '', 'label' => 'Todos'], ['value' => $ubicacion, 'label' => 'Vivero 1']],
            $porCampo['id_ubicacion']['options']
        );
    }

    #[Test]
    public function getModuleFilters_de_ventas_carga_cliente_y_vendedor_con_concat(): void
    {
        $this->insert('cliente', ['nombre_cliente' => 'Ana', 'apellido_cliente' => 'Pérez', 'activo' => 1]);
        $this->insertarUsuarioSeguridad(7, 'lgomez', 'Luis', 'Gómez');

        $filtros = $this->reports()->getModuleFilters('ventas');
        $porCampo = array_column($filtros, null, 'field');

        $this->assertSame(
            [['value' => '', 'label' => 'Todos'], ['value' => 1, 'label' => 'Ana Pérez']],
            $porCampo['id_cliente']['options']
        );
        $this->assertSame(
            [['value' => '', 'label' => 'Todos'], ['value' => 7, 'label' => 'Luis Gómez']],
            $porCampo['id_usuario']['options']
        );
        $this->assertSame('v.id_cliente', $porCampo['id_cliente']['column']);
        $this->assertTrue($porCampo['total_min']['manual']);
    }

    #[Test]
    public function getModuleFilters_de_un_modulo_desconocido_solo_devuelve_el_filtro_activo(): void
    {
        $filtros = $this->reports()->getModuleFilters('desconocido');

        $this->assertCount(1, $filtros);
        $this->assertSame('activo', $filtros[0]['field']);
        $this->assertSame('activo', $filtros[0]['column']);
        $this->assertSame(
            [
                ['value' => '', 'label' => 'Todos'],
                ['value' => '1', 'label' => 'Activo'],
                ['value' => '0', 'label' => 'Inactivo'],
            ],
            $filtros[0]['options']
        );
    }

    #[Test]
    public function getReportData_de_plantas_calcula_el_stock_por_lotes(): void
    {
        $rosa = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $roja = $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa roja', 'nombre_tecnico' => 'Rosa sp', 'activo' => 1]);
        $blanca = $this->insert('plantas', ['id_especie' => $rosa, 'nombre_comun' => 'Rosa blanca', 'activo' => 1]);
        $this->insert('plantas', ['id_especie' => null, 'nombre_comun' => 'Margarita', 'activo' => 1]);
        $this->insert('lote', ['id_planta' => $roja, 'cantidad_inicial' => 20, 'cantidad_actual' => 5]);
        $this->insert('lote', ['id_planta' => $roja, 'cantidad_inicial' => 20, 'cantidad_actual' => 10]);
        $this->insert('lote', ['id_planta' => $blanca, 'cantidad_inicial' => 20, 'cantidad_actual' => 1]);

        $reporte = $this->reports()->getReportData('plantas', []);

        $this->assertSame(
            ['ID', 'Nombre Común', 'Nombre Técnico', 'Especie', 'Stock en Lotes'],
            $reporte['columns']
        );
        $this->assertCount(3, $reporte['rows']);
        $this->assertSame(
            ['Margarita', 'Rosa blanca', 'Rosa roja'],
            array_column($reporte['rows'], 'nombre_comun')
        );
        $this->assertSame(15, (int) $reporte['rows'][2]['stock_lotes']);
        $this->assertSame('Rosa', $reporte['rows'][2]['especie']);
        $this->assertSame('bar', $reporte['chart']['type']);
        $this->assertSame(['Sin especie', 'Rosa'], $reporte['chart']['labels']);
        $this->assertSame([0, 16], array_map('intval', $reporte['chart']['values']));
    }

    #[Test]
    public function getReportData_de_plantas_aplica_el_filtro_por_nombre(): void
    {
        $this->insert('plantas', ['nombre_comun' => 'Rosa roja', 'activo' => 1]);
        $this->insert('plantas', ['nombre_comun' => 'Margarita', 'activo' => 1]);

        $reporte = $this->reports()->getReportData('plantas', ['nombre_comun' => 'Margarita']);

        $this->assertCount(1, $reporte['rows']);
        $this->assertSame('Margarita', $reporte['rows'][0]['nombre_comun']);
    }

    #[Test]
    public function getReportData_de_ventas_calcula_totales_e_ignora_canceladas(): void
    {
        $this->insert('cliente', ['nombre_cliente' => 'Ana', 'apellido_cliente' => 'Pérez', 'activo' => 1]);
        $this->insertarUsuarioSeguridad(7, 'lgomez', 'Luis', 'Gómez');

        $completada = $this->insert('venta', [
            'referencia' => 'V-1',
            'id_cliente' => 1,
            'id_usuario' => 7,
            'estado' => 'completada',
            'tipo_venta' => 'contado',
            'fecha_venta' => '2026-02-01 10:00:00',
        ]);
        $this->insert('venta', [
            'referencia' => 'V-2',
            'id_cliente' => 1,
            'id_usuario' => 7,
            'estado' => 'cancelada',
            'fecha_venta' => '2026-03-01 10:00:00',
        ]);
        $this->insert('venta', [
            'referencia' => 'V-3',
            'id_cliente' => 1,
            'id_usuario' => 7,
            'estado' => 'completada',
            'fecha_venta' => '2026-01-01 10:00:00',
        ]);

        $this->insert('detalle_venta', ['id_venta' => $completada, 'cantidad' => 2, 'precio_unitario' => 10]);
        $this->insert('detalle_venta', ['id_venta' => $completada, 'cantidad' => 3, 'precio_unitario' => 5]);

        $reporte = $this->reports()->getReportData('ventas', []);

        $this->assertSame(
            ['ID', 'Referencia', 'Cliente', 'Vendedor', 'Tipo', 'Estado', 'Total', 'Items', 'Fecha'],
            $reporte['columns']
        );
        $this->assertSame(['V-1', 'V-3'], array_column($reporte['rows'], 'referencia'));
        $this->assertSame('Ana Pérez', $reporte['rows'][0]['cliente']);
        $this->assertSame('Luis Gómez', $reporte['rows'][0]['vendedor']);
        $this->assertSame(35.0, (float) $reporte['rows'][0]['total']);
        $this->assertSame(2, (int) $reporte['rows'][0]['items']);
        $this->assertSame(0.0, (float) $reporte['rows'][1]['total']);
        $this->assertSame('line', $reporte['chart']['type']);
        $this->assertSame(['2026-02', '2026-01'], $reporte['chart']['labels']);
        $this->assertSame([35.0, 0.0], array_map('floatval', $reporte['chart']['values']));
    }

    #[Test]
    public function getReportData_de_ventas_con_filtro_de_estado_incluye_canceladas(): void
    {
        $this->insert('cliente', ['nombre_cliente' => 'Ana', 'apellido_cliente' => 'Pérez', 'activo' => 1]);
        $this->insert('venta', [
            'referencia' => 'V-1',
            'id_cliente' => 1,
            'id_usuario' => 1,
            'estado' => 'completada',
            'fecha_venta' => '2026-02-01 10:00:00',
        ]);
        $this->insert('venta', [
            'referencia' => 'V-2',
            'id_cliente' => 1,
            'id_usuario' => 1,
            'estado' => 'cancelada',
            'fecha_venta' => '2026-03-01 10:00:00',
        ]);

        $reporte = $this->reports()->getReportData('ventas', ['estado' => 'cancelada']);

        $this->assertCount(1, $reporte['rows']);
        $this->assertSame('V-2', $reporte['rows'][0]['referencia']);
        $this->assertSame('cancelada', $reporte['rows'][0]['estado']);
    }

    #[Test]
    public function getReportData_de_insumos_ordena_por_nombre_y_arma_el_grafico(): void
    {
        $kilo = $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo']);
        $litro = $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Litro']);
        $this->insert('insumo', [
            'id_unidad_medida' => $kilo,
            'nombre_insumo' => 'Fertilizante',
            'categoria' => 'Químico',
            'stock_actual' => 5,
            'costo_unitario_actual' => 2.5,
            'activo' => 1,
        ]);
        $this->insert('insumo', [
            'id_unidad_medida' => $litro,
            'nombre_insumo' => 'Sustrato',
            'categoria' => 'Orgánico',
            'stock_actual' => 20,
            'costo_unitario_actual' => 1.5,
            'activo' => 1,
        ]);

        $reporte = $this->reports()->getReportData('insumos', []);

        $this->assertSame(
            ['ID', 'Nombre', 'Categoría', 'Stock Actual', 'Unidad', 'Costo Unit.'],
            $reporte['columns']
        );
        $this->assertSame(['Fertilizante', 'Sustrato'], array_column($reporte['rows'], 'nombre_insumo'));
        $this->assertSame('Kilogramo', $reporte['rows'][0]['unidad']);
        $this->assertSame('bar', $reporte['chart']['type']);
        $this->assertSame(['Fertilizante', 'Sustrato'], $reporte['chart']['labels']);
        $this->assertSame([5.0, 20.0], array_map('floatval', $reporte['chart']['values']));
    }

    #[Test]
    public function getReportData_de_un_modulo_desconocido_devuelve_la_estructura_vacia(): void
    {
        $this->assertSame(
            ['columns' => [], 'rows' => [], 'chart' => null],
            $this->reports()->getReportData('desconocido', [])
        );
    }

    private function reports(): SqliteReports
    {
        return new SqliteReports();
    }

    private function hidratarModulos(SqliteReports $reports): void
    {
        $buildModules = new \ReflectionMethod(Reports::class, 'buildModules');
        $propiedad = new \ReflectionProperty(Reports::class, 'modules');
        $propiedad->setValue($reports, $buildModules->invoke($reports));
    }

    private function insertarUsuarioSeguridad(int $id, string $usuario, string $nombre, string $apellido): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO "SysInescolara-Seguridad".usuarios
             (id_usuario, nombre_usuario, nombre_trabajador, apellido_trabajador, estatus)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $usuario, $nombre, $apellido, 'Activo']);
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
            CREATE TABLE lote (
                id_lote             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta           INTEGER NOT NULL DEFAULT 1,
                id_ubicacion        INTEGER DEFAULT NULL,
                fecha_siembra       TEXT NOT NULL DEFAULT "2026-01-01",
                cantidad_inicial    INTEGER NOT NULL DEFAULT 0,
                cantidad_actual     INTEGER NOT NULL DEFAULT 0,
                costo_unitario      REAL NOT NULL DEFAULT 0,
                estado              TEXT DEFAULT NULL,
                categoria           TEXT DEFAULT NULL,
                origen              TEXT DEFAULT NULL,
                observacion         TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia REAL NOT NULL DEFAULT 30
            )
        ');

        $this->exec('
            CREATE TABLE insumo (
                id_insumo             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida      INTEGER NOT NULL DEFAULT 1,
                nombre_insumo         TEXT NOT NULL,
                categoria             TEXT DEFAULT NULL,
                stock_actual          REAL NOT NULL DEFAULT 0,
                costo_unitario_actual REAL NOT NULL DEFAULT 0,
                activo                INTEGER NOT NULL DEFAULT 1
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
                rif_proveedor      TEXT NOT NULL DEFAULT "",
                contacto_vendedor  TEXT DEFAULT NULL,
                telefono_proveedor TEXT DEFAULT NULL,
                activo             INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE compra (
                id_compra          INTEGER PRIMARY KEY AUTOINCREMENT,
                id_proveedor       INTEGER NOT NULL DEFAULT 1,
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
                activo            INTEGER NOT NULL DEFAULT 1
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
