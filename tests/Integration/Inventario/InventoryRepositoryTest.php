<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteInventory;
use SysInescolara\Tests\Support\SqliteTestCase;

final class InventoryRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT \'\',
                nombre_comun   TEXT DEFAULT NULL,
                cantidad_total INTEGER NOT NULL DEFAULT 0,
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
            CREATE TABLE herramienta (
                id_herramienta     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_herramienta TEXT NOT NULL,
                cantidad           INTEGER NOT NULL DEFAULT 1,
                tipo               TEXT DEFAULT NULL,
                estado             TEXT NOT NULL DEFAULT \'disponible\',
                activo             INTEGER NOT NULL DEFAULT 1
            )
        ');

        /*
         * Inventory::getConsolidated() ejecuta "SET NAMES ..." (MySQL) antes de
         * la consulta. En SQLite esa sentencia no existe, así que se desactiva
         * el modo excepción para que devuelva false sin abortar el método y el
         * UNION real pueda ejecutarse.
         */
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

        SqliteInventory::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteInventory::boot(null);
        parent::tearDown();
    }

    private function sembrarInventario(): void
    {
        $this->insert('plantas', [
            'id_especie'     => 1,
            'nombre_tecnico' => 'Salvia rosmarinus',
            'nombre_comun'   => 'Romero',
            'cantidad_total' => 10,
            'activo'         => 1,
        ]);

        $this->insert('ubicacion', [
            'nombre_ubicacion' => 'Invernadero',
            'zona'             => 'Norte',
            'activo'           => 1,
        ]);

        $this->insert('lote', [
            'id_planta'        => 1,
            'id_ubicacion'     => 1,
            'fecha_siembra'    => '2026-01-10',
            'cantidad_inicial' => 10,
            'cantidad_actual'  => 10,
            'costo_unitario'   => 2.5,
            'activo'           => 1,
        ]);

        $this->insert('unidad_medida', [
            'nombre_unidad_medida' => 'Kilogramo',
            'simbolo'              => 'kg',
            'activo'               => 1,
        ]);

        $this->insert('insumo', [
            'id_unidad_medida'      => 1,
            'nombre_insumo'         => 'Abono',
            'categoria'             => 'Fertilizante',
            'stock_actual'          => 4.5,
            'costo_unitario_actual' => 1.25,
            'activo'                => 1,
        ]);

        $this->insert('herramienta', [
            'nombre_herramienta' => 'Pala',
            'cantidad'           => 2,
            'tipo'               => 'manual',
            'estado'             => 'disponible',
            'activo'             => 1,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function consolidadoPorTipo(): array
    {
        $porTipo = [];
        foreach ((new SqliteInventory())->getConsolidated() as $fila) {
            $porTipo[$fila['tipo']] = $fila;
        }

        return $porTipo;
    }

    #[Test]
    public function getConsolidated_reune_plantas_insumos_herramientas_y_lotes(): void
    {
        $this->sembrarInventario();

        $filas = (new SqliteInventory())->getConsolidated();

        $this->assertCount(4, $filas);
        $this->assertSame(
            ['Herramienta', 'Insumo', 'Lote', 'Planta'],
            array_column($filas, 'tipo')
        );
    }

    #[Test]
    public function getConsolidated_identifica_cada_registro_con_su_prefijo(): void
    {
        $this->sembrarInventario();

        $filas = $this->consolidadoPorTipo();

        $this->assertSame('PLANTA_1', $filas['Planta']['id']);
        $this->assertSame('INSUMO_1', $filas['Insumo']['id']);
        $this->assertSame('HERRAMIENTA_1', $filas['Herramienta']['id']);
        $this->assertSame('LOTE_1', $filas['Lote']['id']);

        $this->assertSame(1, $filas['Planta']['item_id']);
        $this->assertSame(1, $filas['Insumo']['item_id']);
        $this->assertSame(1, $filas['Herramienta']['item_id']);
        $this->assertSame(1, $filas['Lote']['item_id']);
    }

    #[Test]
    public function getConsolidated_calcula_el_stock_y_el_precio_de_la_planta(): void
    {
        $this->sembrarInventario();

        $planta = $this->consolidadoPorTipo()['Planta'];

        $this->assertSame('Romero', $planta['nombre']);
        $this->assertSame(10.0, (float) $planta['stock']);
        $this->assertSame('unidades', $planta['unidad']);
        $this->assertNull($planta['ubicacion']);
        $this->assertSame(2.5, (float) $planta['precio']);
    }

    #[Test]
    public function getConsolidated_usa_el_nombre_tecnico_si_no_hay_nombre_comun(): void
    {
        $this->insert('plantas', [
            'nombre_tecnico' => 'Mentha spicata',
            'nombre_comun'   => '',
            'activo'         => 1,
        ]);

        $planta = $this->consolidadoPorTipo()['Planta'];

        $this->assertSame('Mentha spicata', $planta['nombre']);
        $this->assertSame(0.0, (float) $planta['stock']);
    }

    #[Test]
    public function getConsolidated_expone_los_datos_del_insumo(): void
    {
        $this->sembrarInventario();

        $insumo = $this->consolidadoPorTipo()['Insumo'];

        $this->assertSame('Abono', $insumo['nombre']);
        $this->assertSame(4.5, (float) $insumo['stock']);
        $this->assertSame('Kilogramo', $insumo['unidad']);
        $this->assertNull($insumo['ubicacion']);
        $this->assertSame(1.25, (float) $insumo['precio']);
    }

    #[Test]
    public function getConsolidated_expone_la_herramienta_sin_stock_ni_precio(): void
    {
        $this->sembrarInventario();

        $herramienta = $this->consolidadoPorTipo()['Herramienta'];

        $this->assertSame('Pala', $herramienta['nombre']);
        $this->assertNull($herramienta['stock']);
        $this->assertNull($herramienta['unidad']);
        $this->assertNull($herramienta['ubicacion']);
        $this->assertNull($herramienta['precio']);
    }

    #[Test]
    public function getConsolidated_expone_el_lote_con_su_planta_y_ubicacion(): void
    {
        $this->sembrarInventario();

        $lote = $this->consolidadoPorTipo()['Lote'];

        $this->assertSame('Romero (Lote #1)', $lote['nombre']);
        $this->assertSame(10.0, (float) $lote['stock']);
        $this->assertSame('unidades', $lote['unidad']);
        $this->assertSame('Invernadero', $lote['ubicacion']);
        $this->assertSame(2.5, (float) $lote['precio']);
    }

    #[Test]
    public function getConsolidated_usa_el_placeholder_cuando_el_lote_no_tiene_planta_activa(): void
    {
        $this->insert('plantas', [
            'nombre_tecnico' => 'Ocimum basilicum',
            'nombre_comun'   => 'Albahaca',
            'activo'         => 0,
        ]);
        $this->insert('lote', [
            'id_planta'        => 1,
            'fecha_siembra'    => '2026-02-01',
            'cantidad_inicial' => 5,
            'cantidad_actual'  => 5,
            'costo_unitario'   => 1.0,
            'activo'           => 1,
        ]);

        $lote = $this->consolidadoPorTipo()['Lote'];

        $this->assertSame('Planta #1 (Lote #1)', $lote['nombre']);
        $this->assertNull($lote['ubicacion']);
    }

    #[Test]
    public function getConsolidated_omite_los_registros_inactivos(): void
    {
        $this->sembrarInventario();

        $this->exec('UPDATE plantas SET activo = 0');
        $this->exec('UPDATE insumo SET activo = 0');
        $this->exec('UPDATE herramienta SET activo = 0');
        $this->exec('UPDATE lote SET activo = 0');

        $this->assertSame([], (new SqliteInventory())->getConsolidated());
    }

    #[Test]
    public function getConsolidated_devuelve_lista_vacia_si_falta_una_tabla(): void
    {
        $this->exec('DROP TABLE herramienta');

        $this->assertSame([], (new SqliteInventory())->getConsolidated());
    }
}
