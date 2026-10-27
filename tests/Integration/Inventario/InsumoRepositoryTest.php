<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Insumo;
use SysInescolara\Tests\Support\SqliteInsumo;
use SysInescolara\Tests\Support\SqliteTestCase;

final class InsumoRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
                activo                INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (id_unidad_medida) REFERENCES unidad_medida (id_unidad_medida)
            )
        ');

        $this->insert('unidad_medida', [
            'nombre_unidad_medida' => 'Kilogramo',
            'simbolo'              => 'kg',
            'activo'               => 1,
        ]);

        SqliteInsumo::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteInsumo::boot(null);
        parent::tearDown();
    }

    private function crearInsumo(array $atributos = []): SqliteInsumo
    {
        $insumo = new SqliteInsumo($atributos + [
            'nombre_insumo'         => 'Abono',
            'id_unidad_medida'      => 1,
            'stock_actual'          => 10.0,
            'costo_unitario_actual' => 2.0,
        ]);

        $this->assertTrue($insumo->save());

        return $insumo;
    }

    #[Test]
    public function guardar_un_insumo_nuevo_le_asigna_un_id(): void
    {
        $insumo = new SqliteInsumo([
            'nombre_insumo'         => 'Abono',
            'id_unidad_medida'      => 1,
            'categoria'             => 'Fertilizante',
            'stock_actual'          => 12.5,
            'costo_unitario_actual' => 2.25,
        ]);

        $this->assertTrue($insumo->save());
        $this->assertNotNull($insumo->getId());

        $encontrado = SqliteInsumo::find($insumo->getId());
        $this->assertInstanceOf(Insumo::class, $encontrado);
        $this->assertSame('Abono', $encontrado->getNombreInsumo());
        $this->assertSame(1, $encontrado->getIdUnidadMedida());
        $this->assertSame('Fertilizante', $encontrado->getCategoria());
        $this->assertSame(12.5, $encontrado->getStockActual());
        $this->assertSame(2.25, $encontrado->getCostoUnitarioActual());
        $this->assertTrue($encontrado->isActivo());
    }

    #[Test]
    public function save_actualiza_un_insumo_existente(): void
    {
        $insumo = $this->crearInsumo();

        $insumo->setNombreInsumo('Abono foliar');
        $insumo->setStockActual(20.5);
        $this->assertTrue($insumo->save());

        $fila = $insumo->getById($insumo->getId());
        $this->assertSame('Abono foliar', $fila['nombre_insumo']);
        $this->assertSame(20.5, (float) $fila['stock_actual']);
    }

    #[Test]
    public function all_devuelve_solo_activos_con_los_datos_de_la_unidad(): void
    {
        $this->crearInsumo(['nombre_insumo' => 'Zeta']);
        $this->crearInsumo(['nombre_insumo' => 'Alfa']);
        $borrado = $this->crearInsumo(['nombre_insumo' => 'Mebra']);
        $this->assertTrue($borrado->delete($borrado->getId()));

        $filas = SqliteInsumo::all();

        $this->assertSame(['Alfa', 'Zeta'], array_column($filas, 'nombre_insumo'));
        $this->assertSame('Kilogramo', $filas[0]['nombre_unidad_medida']);
        $this->assertSame('kg', $filas[0]['simbolo']);
        $this->assertSame(1, (int) $filas[0]['activo']);
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $this->crearInsumo(['nombre_insumo' => 'Unico']);

        $this->assertSame(SqliteInsumo::all(), (new SqliteInsumo())->getAll());
    }

    #[Test]
    public function getLastInsertId_devuelve_el_id_del_ultimo_insert(): void
    {
        $insumo = $this->crearInsumo();

        $this->assertSame($insumo->getId(), $insumo->getLastInsertId());
    }

    #[Test]
    public function getById_devuelve_la_fila_con_la_unidad_o_null(): void
    {
        $insumo = $this->crearInsumo(['nombre_insumo' => 'Semilla']);

        $fila = $insumo->getById($insumo->getId());
        $this->assertSame($insumo->getId(), (int) $fila['id_insumo']);
        $this->assertSame('Semilla', $fila['nombre_insumo']);
        $this->assertSame('Kilogramo', $fila['nombre_unidad_medida']);

        $this->assertNull($insumo->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_insumo_existe(): void
    {
        $insumo = $this->crearInsumo();

        $this->assertTrue($insumo->exists($insumo->getId()));
        $this->assertFalse($insumo->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $insumo = $this->crearInsumo();
        $id = $insumo->getId();

        $this->assertTrue($insumo->delete($id));
        $this->assertSame(0, (int) $this->fetchRow('insumo', $id, 'id_insumo')['activo']);
        $this->assertSame([], SqliteInsumo::all());

        $this->assertTrue($insumo->restore($id));
        $this->assertSame(1, (int) $this->fetchRow('insumo', $id, 'id_insumo')['activo']);
        $this->assertCount(1, SqliteInsumo::all());
    }

    #[Test]
    public function where_filtra_solo_activos(): void
    {
        $this->crearInsumo(['nombre_insumo' => 'Abono', 'categoria' => 'Fertilizante']);
        $this->crearInsumo(['nombre_insumo' => 'Abono liquido', 'categoria' => 'Fertilizante']);
        $this->crearInsumo(['nombre_insumo' => 'Tierra', 'categoria' => 'Sustrato']);
        $inactivo = $this->crearInsumo(['nombre_insumo' => 'Abono viejo', 'categoria' => 'Fertilizante']);
        $inactivo->delete($inactivo->getId());

        $resultados = SqliteInsumo::where('categoria', 'Fertilizante');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Insumo::class, $resultado);
            $this->assertSame('Fertilizante', $resultado->getCategoria());
        }
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $insumo = $this->crearInsumo([
            'nombre_insumo' => 'Sustrato',
            'categoria'     => 'Suelo',
            'stock_actual'  => 7.5,
        ]);

        $destino = new SqliteInsumo();
        $this->assertTrue($destino->loadById($insumo->getId()));
        $this->assertSame($insumo->getId(), $destino->getId());
        $this->assertSame('Sustrato', $destino->getNombreInsumo());
        $this->assertSame('Suelo', $destino->getCategoria());
        $this->assertSame(7.5, $destino->getStockActual());
        $this->assertTrue($destino->isActivo());

        $this->assertFalse((new SqliteInsumo())->loadById(99999));
    }

    #[Test]
    public function findByNameAndCategory_solo_encuentra_insumos_activos_de_esa_categoria(): void
    {
        $insumo = $this->crearInsumo([
            'nombre_insumo' => 'Abono',
            'categoria'     => 'Fertilizante',
            'stock_actual'  => 3.5,
        ]);

        $encontrado = $insumo->findByNameAndCategory('Abono', 'Fertilizante');
        $this->assertSame($insumo->getId(), (int) $encontrado['id_insumo']);
        $this->assertSame('Abono', $encontrado['nombre_insumo']);
        $this->assertSame(3.5, (float) $encontrado['stock_actual']);

        $this->assertNull($insumo->findByNameAndCategory('Abono', 'Sustrato'));

        $insumo->delete($insumo->getId());
        $this->assertNull($insumo->findByNameAndCategory('Abono', 'Fertilizante'));
    }

    #[Test]
    public function increaseStock_suma_la_cantidad_al_stock_actual(): void
    {
        $insumo = $this->crearInsumo(['stock_actual' => 10.5]);

        $this->assertTrue($insumo->increaseStock($insumo->getId(), 2.25));

        $fila = $insumo->getById($insumo->getId());
        $this->assertSame(12.75, (float) $fila['stock_actual']);
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE insumo');

        $insumo = new SqliteInsumo([
            'nombre_insumo'    => 'Abono',
            'id_unidad_medida' => 1,
        ]);

        $this->assertFalse($insumo->save());
    }
}
