<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Trazabilidad;
use SysInescolara\Tests\Support\SqliteTrazabilidad;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Trazabilidad::save() no se prueba: cuando `id_estado` es válido el modelo
 * resuelve el nombre del estado con `new Lote()`, que abre MySQL y llama a
 * exit() si el servidor no está disponible. Las filas se insertan
 * directamente para cubrir lectura, borrado lógico y ajustes de stock.
 */
final class TrazabilidadRepositoryTest extends SqliteTestCase
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
                nombre_comun   TEXT DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT \'\',
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE ubicacion (
                id_ubicacion     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_ubicacion TEXT NOT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
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
                id_lote          INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta        INTEGER NOT NULL,
                id_ubicacion     INTEGER DEFAULT NULL,
                fecha_siembra    TEXT NOT NULL,
                cantidad_inicial INTEGER NOT NULL,
                cantidad_actual  INTEGER NOT NULL,
                costo_unitario   REAL NOT NULL DEFAULT 0,
                estado           TEXT DEFAULT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE trazabilidad (
                id_trazabilidad INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lote         INTEGER NOT NULL,
                cantidad        INTEGER NOT NULL DEFAULT 1,
                estado_salud    TEXT NOT NULL,
                observacion     TEXT DEFAULT NULL,
                activo          INTEGER NOT NULL DEFAULT 1,
                fecha_registro  TEXT NOT NULL
            )
        ');

        SqliteTrazabilidad::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteTrazabilidad::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function find_devuelve_el_modelo_con_sus_atributos(): void
    {
        $this->insert('especie', ['nombre_especie' => 'Rosa']);
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $id = $this->crearTrazabilidad([
            'id_lote'        => $idLote,
            'cantidad'       => 4,
            'fecha_registro' => '2026-01-05',
            'observacion'    => 'Hojas con manchas',
        ]);

        $trazabilidad = SqliteTrazabilidad::find($id);

        $this->assertInstanceOf(Trazabilidad::class, $trazabilidad);
        $this->assertSame($id, $trazabilidad->getId());
        $this->assertSame($idLote, $trazabilidad->getIdLote());
        $this->assertSame(4, $trazabilidad->getCantidad());
        $this->assertSame('2026-01-05', $trazabilidad->getFechaRegistro());
        $this->assertSame('Hojas con manchas', $trazabilidad->getObservacion());
        $this->assertTrue($trazabilidad->isActivo());
    }

    #[Test]
    public function find_devuelve_null_si_no_existe(): void
    {
        $this->assertNull(SqliteTrazabilidad::find(999));
    }

    #[Test]
    public function all_solo_devuelve_activos_con_datos_del_lote_y_la_planta(): void
    {
        $this->insert('especie', ['nombre_especie' => 'Rosa']);
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 15);

        $antigua = $this->crearTrazabilidad([
            'id_lote'        => $idLote,
            'cantidad'       => 2,
            'fecha_registro' => '2026-01-01',
        ]);
        $reciente = $this->crearTrazabilidad([
            'id_lote'        => $idLote,
            'cantidad'       => 5,
            'fecha_registro' => '2026-02-01',
        ]);
        $borrada = $this->crearTrazabilidad([
            'id_lote'        => $idLote,
            'cantidad'       => 9,
            'fecha_registro' => '2026-03-01',
            'activo'         => 0,
        ]);

        $filas = SqliteTrazabilidad::all();

        $this->assertCount(2, $filas);
        $this->assertSame([$reciente, $antigua], array_map(
            static fn (array $fila): int => (int) $fila['id'],
            $filas
        ));
        $this->assertSame('Rosa', $filas[0]['planta_nombre']);
        $this->assertSame(15, (int) $filas[0]['lote_cantidad_actual']);
        $this->assertNotContains($borrada, array_column($filas, 'id'));
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $this->crearTrazabilidad(['id_lote' => $idLote]);

        $this->assertSame(SqliteTrazabilidad::all(), (new SqliteTrazabilidad())->getAll());
    }

    #[Test]
    public function get_by_id_devuelve_la_fila_o_null(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 12);
        $id = $this->crearTrazabilidad([
            'id_lote'     => $idLote,
            'cantidad'    => 3,
            'estado_salud' => 'Cuarentena',
        ]);

        $fila = (new SqliteTrazabilidad())->getById($id);

        $this->assertNotNull($fila);
        $this->assertSame($id, (int) $fila['id_trazabilidad']);
        $this->assertSame('Cuarentena', $fila['estado_salud']);
        $this->assertSame('Rosa', $fila['planta_nombre']);
        $this->assertSame(12, (int) $fila['lote_cantidad_actual']);

        $this->assertNull((new SqliteTrazabilidad())->getById(999));
    }

    #[Test]
    public function where_filtra_solo_registros_activos(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $this->crearTrazabilidad(['id_lote' => $idLote, 'cantidad' => 3]);
        $this->crearTrazabilidad(['id_lote' => $idLote, 'cantidad' => 4, 'activo' => 0]);

        $resultados = SqliteTrazabilidad::where('id_lote', $idLote);

        $this->assertCount(1, $resultados);
        $this->assertSame(3, $resultados[0]->getCantidad());
    }

    #[Test]
    public function exists_refleja_si_el_registro_existe(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $id = $this->crearTrazabilidad(['id_lote' => $idLote]);
        $trazabilidad = new SqliteTrazabilidad();

        $this->assertTrue($trazabilidad->exists($id));
        $this->assertFalse($trazabilidad->exists(999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $id = $this->crearTrazabilidad(['id_lote' => $idLote]);
        $trazabilidad = new SqliteTrazabilidad();

        $this->assertTrue($trazabilidad->delete($id));
        $this->assertSame([], SqliteTrazabilidad::all());

        $this->assertTrue($trazabilidad->restore($id));
        $this->assertCount(1, SqliteTrazabilidad::all());
    }

    #[Test]
    public function load_by_id_carga_los_atributos(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $id = $this->crearTrazabilidad([
            'id_lote'     => $idLote,
            'cantidad'    => 7,
            'observacion' => 'Revisado',
        ]);

        $destino = new SqliteTrazabilidad();
        $this->assertTrue($destino->loadById($id));

        $this->assertSame($id, $destino->getId());
        $this->assertSame($idLote, $destino->getIdLote());
        $this->assertSame(7, $destino->getCantidad());
        $this->assertSame('Revisado', $destino->getObservacion());
        $this->assertFalse((new SqliteTrazabilidad())->loadById(999));
    }

    #[Test]
    public function get_available_batches_excluye_lotes_sin_stock_o_inactivos(): void
    {
        $idPlantaRosa = $this->crearPlanta('Rosa');
        $idPlantaAloe = $this->crearPlanta('Aloe');

        $conStock = $this->crearLote($idPlantaRosa, 10);
        $sinStock = $this->crearLote($idPlantaRosa, 0);
        $inactivo = $this->crearLote($idPlantaRosa, 8, 0);
        $aloe = $this->crearLote($idPlantaAloe, 5);

        $lotes = (new SqliteTrazabilidad())->getAvailableBatches();

        $this->assertSame([$aloe, $conStock], array_map(
            static fn (array $fila): int => (int) $fila['id'],
            $lotes
        ));
        $this->assertSame('Aloe', $lotes[0]['planta_nombre']);
        $this->assertSame('Vivero Norte', $lotes[0]['nombre_ubicacion']);

        $incluyendoSinStock = (new SqliteTrazabilidad())->getAvailableBatches($sinStock);
        $this->assertContains($sinStock, array_map(
            static fn (array $fila): int => (int) $fila['id'],
            $incluyendoSinStock
        ));
        $this->assertNotContains($inactivo, array_column($incluyendoSinStock, 'id'));
    }

    #[Test]
    public function deduct_batch_stock_descuenta_sin_bajar_de_cero(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);
        $trazabilidad = new SqliteTrazabilidad();

        $this->assertTrue($trazabilidad->deductBatchStock($idLote, 4));
        $this->assertSame(6, (int) $this->fetchRow('lote', $idLote, 'id_lote')['cantidad_actual']);

        $this->assertTrue($trazabilidad->deductBatchStock($idLote, 100));
        $this->assertSame(6, (int) $this->fetchRow('lote', $idLote, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function restore_batch_stock_suma_al_stock_actual(): void
    {
        $idPlanta = $this->crearPlanta('Rosa');
        $idLote = $this->crearLote($idPlanta, 10);

        $this->assertTrue((new SqliteTrazabilidad())->restoreBatchStock($idLote, 3));
        $this->assertSame(13, (int) $this->fetchRow('lote', $idLote, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function save_no_es_portable_a_sqlite(): void
    {
        $this->markTestSkipped(
            'Trazabilidad::save() resuelve el estado con new Lote(), que abre MySQL y llama a exit() '
            . 'si el servidor no está disponible; no se puede inyectar el PDO de SQLite.'
        );
    }

    private function crearPlanta(string $nombreComun, string $nombreTecnico = '', int $activo = 1): int
    {
        return $this->insert('plantas', [
            'nombre_comun'   => $nombreComun,
            'nombre_tecnico' => $nombreTecnico,
            'activo'         => $activo,
        ]);
    }

    private function crearLote(int $idPlanta, int $cantidadActual, int $activo = 1): int
    {
        $idUbicacion = $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        return $this->insert('lote', [
            'id_planta'        => $idPlanta,
            'id_ubicacion'     => $idUbicacion,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => $cantidadActual,
            'cantidad_actual'  => $cantidadActual,
            'activo'           => $activo,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function crearTrazabilidad(array $overrides = []): int
    {
        return $this->insert('trazabilidad', array_merge([
            'id_lote'        => 1,
            'cantidad'       => 1,
            'estado_salud'   => 'Cuarentena',
            'observacion'    => null,
            'activo'         => 1,
            'fecha_registro' => '2026-01-01',
        ], $overrides));
    }
}
