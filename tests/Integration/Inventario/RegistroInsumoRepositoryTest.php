<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\RegistroInsumo;
use SysInescolara\Tests\Support\SqliteRegistroInsumo;
use SysInescolara\Tests\Support\SqliteTestCase;

final class RegistroInsumoRepositoryTest extends SqliteTestCase
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

        SqliteRegistroInsumo::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteRegistroInsumo::boot(null);
        parent::tearDown();
    }

    private function crearRegistro(array $atributos = []): SqliteRegistroInsumo
    {
        $registro = new SqliteRegistroInsumo($atributos + [
            'id_lote'        => 1,
            'id_insumo'      => 1,
            'cantidad'       => 2,
            'costo_unitario' => 1.5,
            'fecha_registro' => '2026-02-01',
        ]);

        $this->assertTrue($registro->save());

        return $registro;
    }

    #[Test]
    public function guardar_un_registro_nuevo_le_asigna_un_id(): void
    {
        $registro = new SqliteRegistroInsumo([
            'id_lote'        => 3,
            'id_insumo'      => 1,
            'id_asignacion'  => null,
            'cantidad'       => 4,
            'costo_unitario' => 2.25,
            'fecha_registro' => '2026-02-10',
        ]);

        $this->assertTrue($registro->save());
        $this->assertNotNull($registro->getId());

        $encontrado = SqliteRegistroInsumo::find($registro->getId());
        $this->assertInstanceOf(RegistroInsumo::class, $encontrado);
        $this->assertSame(3, $encontrado->getIdLote());
        $this->assertSame(1, $encontrado->getIdInsumo());
        $this->assertNull($encontrado->getIdAsignacion());
        $this->assertSame(4.0, $encontrado->getCantidad());
        $this->assertSame(2.25, $encontrado->getCostoUnitario());
        $this->assertSame('2026-02-10', $encontrado->getFechaRegistro());
    }

    #[Test]
    public function save_actualiza_un_registro_existente(): void
    {
        $registro = $this->crearRegistro();

        $registro->fill(['cantidad' => 8, 'costo_unitario' => 3.0]);
        $this->assertTrue($registro->save());

        $fila = $registro->getById($registro->getId());
        $this->assertSame(8.0, (float) $fila['cantidad']);
        $this->assertSame(3.0, (float) $fila['costo_unitario']);
    }

    #[Test]
    public function all_devuelve_los_registros_con_insumo_y_unidad_ordenados_por_fecha(): void
    {
        $this->crearRegistro(['fecha_registro' => '2026-01-15']);
        $this->crearRegistro(['fecha_registro' => '2026-03-15']);

        $filas = SqliteRegistroInsumo::all();

        $this->assertCount(2, $filas);
        $this->assertSame('2026-03-15', $filas[0]['fecha_registro']);
        $this->assertSame('Abono', $filas[0]['nombre_insumo']);
        $this->assertSame('kg', $filas[0]['simbolo']);
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $this->crearRegistro();

        $this->assertSame(SqliteRegistroInsumo::all(), (new SqliteRegistroInsumo())->getAll());
    }

    #[Test]
    public function getById_devuelve_la_fila_con_el_insumo_o_null(): void
    {
        $registro = $this->crearRegistro();

        $fila = $registro->getById($registro->getId());
        $this->assertSame($registro->getId(), (int) $fila['id_registro_insumo']);
        $this->assertSame('Abono', $fila['nombre_insumo']);
        $this->assertSame('kg', $fila['simbolo']);

        $this->assertNull($registro->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_registro_existe(): void
    {
        $registro = $this->crearRegistro();

        $this->assertTrue($registro->exists($registro->getId()));
        $this->assertFalse($registro->exists(99999));
    }

    #[Test]
    public function delete_elimina_fisicamente_el_registro(): void
    {
        $registro = $this->crearRegistro();
        $id = $registro->getId();

        $this->assertTrue($registro->delete($id));

        $this->assertNull($this->fetchRow('registro_insumo', $id, 'id_registro_insumo'));
        $this->assertSame([], SqliteRegistroInsumo::all());
    }

    #[Test]
    public function getByLote_filtra_por_lote_y_ordena_por_fecha_descendente(): void
    {
        $this->crearRegistro(['id_lote' => 1, 'fecha_registro' => '2026-01-15']);
        $this->crearRegistro(['id_lote' => 1, 'fecha_registro' => '2026-03-15']);
        $this->crearRegistro(['id_lote' => 2, 'fecha_registro' => '2026-02-15']);

        $filas = (new SqliteRegistroInsumo())->getByLote(1);

        $this->assertCount(2, $filas);
        $this->assertSame('2026-03-15', $filas[0]['fecha_registro']);
        $this->assertSame('2026-01-15', $filas[1]['fecha_registro']);
        $this->assertSame('Abono', $filas[0]['nombre_insumo']);
    }

    #[Test]
    public function getByAsignacion_filtra_por_asignacion(): void
    {
        $this->crearRegistro(['id_asignacion' => 5, 'fecha_registro' => '2026-01-15']);
        $this->crearRegistro(['id_asignacion' => 5, 'fecha_registro' => '2026-02-15']);
        $this->crearRegistro(['id_asignacion' => 9, 'fecha_registro' => '2026-03-15']);

        $filas = (new SqliteRegistroInsumo())->getByAsignacion(5);

        $this->assertCount(2, $filas);
        foreach ($filas as $fila) {
            $this->assertSame(5, (int) $fila['id_asignacion']);
        }
        $this->assertSame('2026-02-15', $filas[0]['fecha_registro']);
    }

    #[Test]
    public function eliminarPorLote_borra_solo_los_registros_del_lote(): void
    {
        $this->crearRegistro(['id_lote' => 1]);
        $this->crearRegistro(['id_lote' => 1]);
        $this->crearRegistro(['id_lote' => 2]);

        $this->assertTrue((new SqliteRegistroInsumo())->eliminarPorLote(1));

        $this->assertDatabaseCount('registro_insumo', 1);
        $this->assertDatabaseHas('registro_insumo', ['id_lote' => 2]);
        $this->assertDatabaseMissing('registro_insumo', ['id_lote' => 1]);
    }

    #[Test]
    public function eliminarPorAsignacion_borra_solo_los_registros_de_la_asignacion(): void
    {
        $this->crearRegistro(['id_asignacion' => 5]);
        $this->crearRegistro(['id_asignacion' => 5]);
        $this->crearRegistro(['id_asignacion' => 9]);
        $this->crearRegistro(['id_asignacion' => null]);

        $this->assertTrue((new SqliteRegistroInsumo())->eliminarPorAsignacion(5));

        $this->assertDatabaseCount('registro_insumo', 2);
        $this->assertDatabaseHas('registro_insumo', ['id_asignacion' => 9]);
        $this->assertDatabaseMissing('registro_insumo', ['id_asignacion' => 5]);
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE registro_insumo');

        $registro = new SqliteRegistroInsumo([
            'id_lote'        => 1,
            'id_insumo'      => 1,
            'cantidad'       => 2,
            'costo_unitario' => 1.5,
            'fecha_registro' => '2026-02-01',
        ]);

        $this->assertFalse($registro->save());
    }
}
