<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\SeedCollection;
use SysInescolara\Tests\Support\SqliteSeedCollection;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Omitidos por depender de modelos que abren MySQL (llaman a exit() sin
 * servidor): getAll() y getById() con un registro existente instancian
 * Usuario; update(), complete() y delete() solo se prueban en sus rutas
 * portables (registro inexistente) por la misma razón;
 * registerSeedsWithTransaction() instancia Insumo. save() solo se prueba en la
 * rama de inserción.
 */
final class SeedCollectionRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->attachSecurityDatabase();

        $this->exec('
            CREATE TABLE "SysInescolara-Seguridad".usuarios (
                id_usuario          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario      TEXT DEFAULT NULL,
                nombre_trabajador   TEXT DEFAULT NULL,
                apellido_trabajador TEXT DEFAULT NULL
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
            CREATE TABLE recoleccion_semillas (
                id_recoleccion    INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario        INTEGER NOT NULL,
                id_ubicacion      INTEGER NOT NULL,
                fecha_asignacion  TEXT NOT NULL,
                fecha_recoleccion TEXT DEFAULT NULL,
                estatus           TEXT NOT NULL DEFAULT \'Pendiente\',
                observacion       TEXT DEFAULT NULL,
                activo            INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE recoleccion_semillas_detalle (
                id_recoleccion_detalle INTEGER PRIMARY KEY AUTOINCREMENT,
                id_recoleccion         INTEGER NOT NULL,
                planta_origen          TEXT DEFAULT NULL,
                nombre_semilla         TEXT NOT NULL,
                id_unidad_medida       INTEGER NOT NULL,
                id_insumo              INTEGER DEFAULT NULL,
                cantidad               REAL NOT NULL DEFAULT 0
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
                stock_actual          REAL NOT NULL DEFAULT 0,
                costo_unitario_actual REAL NOT NULL DEFAULT 0,
                activo                INTEGER NOT NULL DEFAULT 1
            )
        ');

        SqliteSeedCollection::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteSeedCollection::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function add_crea_una_recoleccion_pendiente(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $recoleccion = new SqliteSeedCollection();

        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01', 'Recolectar en la mañana'));
        $this->assertSame(1, $recoleccion->getId());
        $this->assertDatabaseHas('recoleccion_semillas', [
            'id_recoleccion'   => 1,
            'id_usuario'       => 1,
            'id_ubicacion'     => 1,
            'fecha_asignacion' => '2026-02-01',
            'estatus'          => 'Pendiente',
            'observacion'      => 'Recolectar en la mañana',
            'activo'           => 1,
        ]);
        $this->assertNull($this->fetchRow('recoleccion_semillas', 1, 'id_recoleccion')['fecha_recoleccion']);
    }

    #[Test]
    public function save_inserta_una_recoleccion_nueva(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Sur']);

        $recoleccion = new SqliteSeedCollection([
            'id_usuario'       => 2,
            'id_ubicacion'     => 1,
            'fecha_asignacion' => '2026-02-03',
            'estatus'          => 'Pendiente',
            'observacion'      => null,
        ]);

        $this->assertTrue($recoleccion->save());
        $this->assertSame(1, $recoleccion->getId());
        $this->assertDatabaseHas('recoleccion_semillas', [
            'id_recoleccion'   => 1,
            'id_usuario'       => 2,
            'id_ubicacion'     => 1,
            'fecha_asignacion' => '2026-02-03',
        ]);
    }

    #[Test]
    public function add_detail_registra_la_semilla_recolectada(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);
        $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo', 'simbolo' => 'kg']);
        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida' => 1,
            'nombre_insumo'    => 'Semilla de rosa',
        ]);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));

        $this->assertTrue($recoleccion->addDetail(1, 'Rosa', 'Semilla de rosa', 1, 2.5, $idInsumo));
        $this->assertDatabaseHas('recoleccion_semillas_detalle', [
            'id_recoleccion'   => 1,
            'planta_origen'    => 'Rosa',
            'nombre_semilla'   => 'Semilla de rosa',
            'id_unidad_medida' => 1,
            'id_insumo'        => $idInsumo,
            'cantidad'         => 2.5,
        ]);
    }

    #[Test]
    public function get_details_une_unidad_de_medida_e_insumo(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);
        $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo', 'simbolo' => 'kg']);
        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida' => 1,
            'nombre_insumo'    => 'Semilla de rosa',
        ]);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));
        $this->assertTrue($recoleccion->addDetail(1, 'Rosa', 'Semilla de rosa', 1, 2.5, $idInsumo));
        $this->assertTrue($recoleccion->addDetail(1, null, 'Semilla de aloe', 1, 1.0, null));

        $detalles = $recoleccion->getDetails(1);

        $this->assertCount(2, $detalles);
        $this->assertSame('Semilla de rosa', $detalles[0]['nombre_semilla']);
        $this->assertSame('Kilogramo', $detalles[0]['nombre_unidad_medida']);
        $this->assertSame('kg', $detalles[0]['simbolo']);
        $this->assertSame('Semilla de rosa', $detalles[0]['insumo_nombre']);
        $this->assertSame(2.5, (float) $detalles[0]['cantidad']);
        $this->assertNull($detalles[1]['insumo_nombre']);
    }

    #[Test]
    public function get_details_count_cuenta_los_detalles(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);
        $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Unidad', 'simbolo' => 'ud']);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));

        $this->assertSame(0, $recoleccion->getDetailsCount(1));
        $this->assertTrue($recoleccion->addDetail(1, 'Rosa', 'Semilla A', 1, 1.0));
        $this->assertTrue($recoleccion->addDetail(1, 'Rosa', 'Semilla B', 1, 1.0));

        $this->assertSame(2, $recoleccion->getDetailsCount(1));
        $this->assertSame(0, $recoleccion->getDetailsCount(999));
    }

    #[Test]
    public function get_details_devuelve_array_vacio_si_no_hay_detalles(): void
    {
        $this->assertSame([], (new SqliteSeedCollection())->getDetails(999));
    }

    #[Test]
    public function restore_reactiva_una_recoleccion(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));

        $this->pdo->exec('UPDATE recoleccion_semillas SET activo = 0 WHERE id_recoleccion = 1');
        $this->assertSame([], SqliteSeedCollection::all());

        $this->assertTrue($recoleccion->restore(1));
        $this->assertCount(1, SqliteSeedCollection::all());
    }

    #[Test]
    public function find_y_load_by_id_cargan_los_atributos(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01', 'Nota'));

        $encontrada = SqliteSeedCollection::find(1);

        $this->assertInstanceOf(SeedCollection::class, $encontrada);
        $this->assertSame(1, $encontrada->getId());
        $this->assertSame(1, $encontrada->getIdUsuario());
        $this->assertSame(1, $encontrada->getIdUbicacion());
        $this->assertSame('2026-02-01', $encontrada->getFechaAsignacion());
        $this->assertSame('Pendiente', $encontrada->getEstatus());
        $this->assertSame('Nota', $encontrada->getObservacion());

        $destino = new SqliteSeedCollection();
        $this->assertTrue($destino->loadById(1));
        $this->assertSame(1, $destino->getId());
        $this->assertFalse((new SqliteSeedCollection())->loadById(999));
        $this->assertNull(SqliteSeedCollection::find(999));
    }

    #[Test]
    public function all_ordena_por_fecha_descendente_y_where_filtra_activos(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $this->assertTrue((new SqliteSeedCollection())->add(1, 1, '2026-01-01'));
        $this->assertTrue((new SqliteSeedCollection())->add(1, 1, '2026-03-01'));

        $filas = SqliteSeedCollection::all();

        $this->assertSame([2, 1], array_map(
            static fn (array $fila): int => (int) $fila['id_recoleccion'],
            $filas
        ));

        $pendientes = SqliteSeedCollection::where('estatus', 'Pendiente');
        $this->assertCount(2, $pendientes);
        $this->assertSame('Pendiente', $pendientes[0]->getEstatus());

        $this->pdo->exec('UPDATE recoleccion_semillas SET activo = 0 WHERE id_recoleccion = 1');
        $this->assertCount(1, SqliteSeedCollection::where('estatus', 'Pendiente'));
    }

    #[Test]
    public function exists_refleja_si_la_recoleccion_existe(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));

        $this->assertTrue($recoleccion->exists(1));
        $this->assertFalse($recoleccion->exists(999));
    }

    #[Test]
    public function get_last_insert_id_devuelve_el_ultimo_id(): void
    {
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte']);

        $recoleccion = new SqliteSeedCollection();
        $this->assertTrue($recoleccion->add(1, 1, '2026-02-01'));

        $this->assertSame($recoleccion->getId(), $recoleccion->getLastInsertId());
    }

    #[Test]
    public function get_by_id_devuelve_null_si_la_recoleccion_no_existe(): void
    {
        $this->assertNull((new SqliteSeedCollection())->getById(999));
    }

    #[Test]
    public function update_lanza_excepcion_si_la_recoleccion_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la recolección solicitada para modificar.');

        (new SqliteSeedCollection())->update(999, 1, 1, '2026-02-01');
    }

    #[Test]
    public function complete_lanza_excepcion_si_la_recoleccion_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la recolección solicitada.');

        (new SqliteSeedCollection())->complete(999, '2026-02-05');
    }

    #[Test]
    public function delete_devuelve_true_aunque_la_recoleccion_no_exista(): void
    {
        $this->assertTrue((new SqliteSeedCollection())->delete(999));
        $this->assertDatabaseCount('recoleccion_semillas', 0);
    }

    #[Test]
    public function los_metodos_dependientes_de_mysql_no_son_portables(): void
    {
        $this->markTestSkipped(
            'getAll() y getById() con un registro existente instancian Usuario; update(), '
            . 'complete() y delete() con un registro existente pasan por getById(); '
            . 'registerSeedsWithTransaction() instancia Insumo. Esos constructores abren MySQL '
            . 'y llaman a exit() sin servidor disponible.'
        );
    }
}
