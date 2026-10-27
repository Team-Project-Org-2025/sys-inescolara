<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteLotePrecio;
use SysInescolara\Tests\Support\SqliteTestCase;

final class LotePrecioRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE especie (
                id_especie     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie TEXT NOT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT \'\',
                nombre_comun   TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
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
                fecha_siembra       DATE NOT NULL,
                cantidad_inicial    INTEGER NOT NULL,
                cantidad_actual     INTEGER NOT NULL,
                costo_unitario      DECIMAL(10,2) DEFAULT 0.00,
                id_estado           INTEGER DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia DECIMAL(5,2) NOT NULL DEFAULT 30.00
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
            CREATE TABLE insumo (
                id_insumo             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida      INTEGER NOT NULL,
                nombre_insumo         TEXT NOT NULL,
                categoria             TEXT DEFAULT NULL,
                stock_actual          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                costo_unitario_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                activo                INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE registro_insumo (
                id_registro_insumo INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lote            INTEGER DEFAULT NULL,
                id_insumo          INTEGER NOT NULL,
                id_asignacion      INTEGER DEFAULT NULL,
                cantidad           DECIMAL(10,2) NOT NULL,
                costo_unitario     DECIMAL(10,2) NOT NULL,
                fecha_registro     DATE NOT NULL
            )
        ');

        $this->insert('especie', ['nombre_especie' => 'Rosmarinus', 'activo' => 1]);
        $this->insert('plantas', [
            'id_especie'     => 1,
            'nombre_tecnico' => 'Salvia rosmarinus',
            'nombre_comun'   => 'Romero',
            'activo'         => 1,
        ]);
        $this->insert('estado', ['nombre' => 'vivo', 'activo' => 1]);
        $this->insert('unidad_medida', [
            'nombre_unidad_medida' => 'Kilogramo',
            'simbolo'              => 'kg',
            'activo'               => 1,
        ]);
        $this->insert('insumo', [
            'id_unidad_medida'      => 1,
            'nombre_insumo'         => 'Abono',
            'stock_actual'          => 10,
            'costo_unitario_actual' => 1.5,
            'activo'                => 1,
        ]);

        SqliteLotePrecio::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteLotePrecio::boot(null);
        parent::tearDown();
    }

    private function crearLote(array $atributos = []): int
    {
        return $this->insert('lote', $atributos + [
            'id_planta'           => 1,
            'fecha_siembra'       => '2026-01-10',
            'cantidad_inicial'    => 10,
            'cantidad_actual'     => 4,
            'costo_unitario'      => 12.5,
            'id_estado'           => 1,
            'activo'              => 1,
            'porcentaje_ganancia' => 40,
        ]);
    }

    #[Test]
    public function calcularPrecioLote_aplica_el_porcentaje_de_ganancia(): void
    {
        $idLote = $this->crearLote();

        $precio = SqliteLotePrecio::calcularPrecioLote($idLote);

        $this->assertSame($idLote, (int) $precio['id_lote']);
        $this->assertSame(12.5, (float) $precio['costo_unitario']);
        $this->assertSame(40.0, (float) $precio['porcentaje_ganancia']);
        $this->assertSame(4, (int) $precio['cantidad_actual']);
        $this->assertSame('Romero', $precio['planta_nombre']);
        $this->assertSame('Salvia rosmarinus', $precio['nombre_tecnico']);
        $this->assertSame(17.5, (float) $precio['precio_final']);
    }

    #[Test]
    public function calcularPrecioLote_redondea_a_dos_decimales(): void
    {
        $idLote = $this->crearLote([
            'costo_unitario'      => 10,
            'porcentaje_ganancia' => 33.33,
        ]);

        $precio = SqliteLotePrecio::calcularPrecioLote($idLote);

        $this->assertSame(13.33, (float) $precio['precio_final']);
    }

    #[Test]
    public function calcularPrecioLote_devuelve_null_si_no_existe_o_esta_inactivo(): void
    {
        $this->assertNull(SqliteLotePrecio::calcularPrecioLote(99999));

        $idLote = $this->crearLote(['activo' => 0]);
        $this->assertNull(SqliteLotePrecio::calcularPrecioLote($idLote));
    }

    #[Test]
    public function getById_es_un_alias_de_calcularPrecioLote(): void
    {
        $idLote = $this->crearLote();

        $this->assertSame(
            SqliteLotePrecio::calcularPrecioLote($idLote),
            (new SqliteLotePrecio())->getById($idLote)
        );
    }

    #[Test]
    public function getDetalleInsumos_calcula_el_subtotal_y_el_origen(): void
    {
        $idLote = $this->crearLote();

        $this->insert('registro_insumo', [
            'id_lote'        => $idLote,
            'id_insumo'      => 1,
            'id_asignacion'  => null,
            'cantidad'       => 2,
            'costo_unitario' => 1.5,
            'fecha_registro' => '2026-02-01',
        ]);
        $this->insert('registro_insumo', [
            'id_lote'        => $idLote,
            'id_insumo'      => 1,
            'id_asignacion'  => 7,
            'cantidad'       => 3,
            'costo_unitario' => 2,
            'fecha_registro' => '2026-03-01',
        ]);

        $detalle = SqliteLotePrecio::getDetalleInsumos($idLote);

        $this->assertCount(2, $detalle);
        $this->assertSame('2026-03-01', $detalle[0]['fecha_registro']);
        $this->assertSame('Tarea', $detalle[0]['origen']);
        $this->assertSame(6.0, (float) $detalle[0]['subtotal']);
        $this->assertSame('Abono', $detalle[0]['nombre_insumo']);
        $this->assertSame('kg', $detalle[0]['simbolo']);

        $this->assertSame('2026-02-01', $detalle[1]['fecha_registro']);
        $this->assertSame('Directo', $detalle[1]['origen']);
        $this->assertSame(3.0, (float) $detalle[1]['subtotal']);
    }

    #[Test]
    public function getDetalleInsumos_devuelve_lista_vacia_si_el_lote_no_tiene_registros(): void
    {
        $idLote = $this->crearLote();

        $this->assertSame([], SqliteLotePrecio::getDetalleInsumos($idLote));
    }

    #[Test]
    public function getPreciosLotes_calcula_precio_y_valor_total_del_inventario(): void
    {
        $idLote = $this->crearLote();

        $filas = SqliteLotePrecio::getPreciosLotes();

        $this->assertCount(1, $filas);
        $this->assertSame($idLote, $filas[0]['id']);
        $this->assertSame($idLote, $filas[0]['id_lote']);
        $this->assertSame('Romero', $filas[0]['planta_nombre']);
        $this->assertSame('Rosmarinus', $filas[0]['especie_nombre']);
        $this->assertSame('vivo', $filas[0]['estado_nombre']);
        $this->assertSame(17.5, (float) $filas[0]['precio_final']);
        $this->assertSame(70.0, (float) $filas[0]['valor_total_inventario']);
    }

    #[Test]
    public function getPreciosLotes_ordena_por_planta_y_solo_incluye_activos(): void
    {
        $this->insert('plantas', [
            'id_especie'     => 1,
            'nombre_tecnico' => 'Mentha spicata',
            'nombre_comun'   => 'Albahaca',
            'activo'         => 1,
        ]);
        $idAlbahaca = $this->crearLote(['id_planta' => 2, 'fecha_siembra' => '2026-02-01']);
        $this->crearLote(['id_planta' => 1, 'fecha_siembra' => '2026-01-01']);
        $this->crearLote(['id_planta' => 1, 'activo' => 0]);

        $filas = SqliteLotePrecio::getPreciosLotes();

        $this->assertSame(['Albahaca', 'Romero'], array_column($filas, 'planta_nombre'));
        $this->assertSame($idAlbahaca, $filas[0]['id_lote']);
    }

    #[Test]
    public function getAll_es_un_alias_de_getPreciosLotes(): void
    {
        $this->crearLote();

        $this->assertSame(
            SqliteLotePrecio::getPreciosLotes(),
            (new SqliteLotePrecio())->getAll()
        );
    }

    #[Test]
    public function actualizarCostoUnitario_actualiza_el_precio_del_lote_activo(): void
    {
        $idLote = $this->crearLote();

        $this->assertTrue(SqliteLotePrecio::actualizarCostoUnitario($idLote, 20.0));

        $this->assertSame(20.0, (float) $this->fetchRow('lote', $idLote, 'id_lote')['costo_unitario']);
        $precio = SqliteLotePrecio::calcularPrecioLote($idLote);
        $this->assertSame(28.0, (float) $precio['precio_final']);
    }

    #[Test]
    public function actualizarCostoUnitario_no_toca_los_lotes_inactivos(): void
    {
        $idLote = $this->crearLote(['activo' => 0]);

        $this->assertTrue(SqliteLotePrecio::actualizarCostoUnitario($idLote, 99.0));

        $this->assertSame(12.5, (float) $this->fetchRow('lote', $idLote, 'id_lote')['costo_unitario']);
    }

    #[Test]
    public function actualizarPorcentajeGanancia_recalcula_el_precio_final(): void
    {
        $idLote = $this->crearLote();

        $this->assertTrue(SqliteLotePrecio::actualizarPorcentajeGanancia($idLote, 20.0));

        $this->assertSame(20.0, (float) $this->fetchRow('lote', $idLote, 'id_lote')['porcentaje_ganancia']);
        $precio = SqliteLotePrecio::calcularPrecioLote($idLote);
        $this->assertSame(15.0, (float) $precio['precio_final']);
    }

    #[Test]
    public function actualizarPorcentajeGanancia_no_toca_los_lotes_inactivos(): void
    {
        $idLote = $this->crearLote(['activo' => 0]);

        $this->assertTrue(SqliteLotePrecio::actualizarPorcentajeGanancia($idLote, 90.0));

        $this->assertSame(40.0, (float) $this->fetchRow('lote', $idLote, 'id_lote')['porcentaje_ganancia']);
    }

    #[Test]
    public function calcularPrecioLote_degrada_a_null_si_falta_la_tabla(): void
    {
        $this->exec('DROP TABLE lote');

        $this->assertNull(SqliteLotePrecio::calcularPrecioLote(1));
    }
}
