<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\core\Database;
use SysInescolara\models\Merma;
use SysInescolara\Tests\Support\SqliteMerma;
use SysInescolara\Tests\Support\SqliteTestCase;

final class MermaRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->attachSecurityDatabase();

        $this->exec('
            CREATE TABLE "SysInescolara-Seguridad".usuarios (
                id_usuario    INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario TEXT DEFAULT NULL
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
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
                activo               INTEGER NOT NULL DEFAULT 1,
                created_at           TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        SqliteMerma::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteMerma::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function save_inserta_una_merma(): void
    {
        $idLote = $this->crearLote(20, 5.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 10);

        $merma = new SqliteMerma([
            'id_trazabilidad'     => $idTrazabilidad,
            'id_lote'             => $idLote,
            'cantidad'            => 3,
            'motivo'              => 'plaga',
            'descripcion'         => 'Hojas perforadas',
            'fecha_merma'         => '2026-02-01',
            'impacto_economico'   => 15.0,
            'id_usuario_registra' => 2,
        ]);

        $this->assertTrue($merma->save());
        $this->assertSame(1, $merma->getId());
        $this->assertDatabaseHas('mermas_historico', [
            'id_merma'        => 1,
            'id_trazabilidad' => $idTrazabilidad,
            'id_lote'         => $idLote,
            'cantidad'        => 3,
            'motivo'          => 'plaga',
            'fecha_merma'     => '2026-02-01',
        ]);
    }

    #[Test]
    public function register_loss_descuenta_la_cuarentena_y_calcula_el_impacto(): void
    {
        $idLote = $this->crearLote(20, 12.5);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 10);

        $id = $this->mermaConTransacciones()->registerLoss(
            $idTrazabilidad,
            4,
            'plaga',
            'Manchas en las hojas',
            '2026-02-05',
            3
        );

        $this->assertSame(1, $id);
        $this->assertDatabaseHas('mermas_historico', [
            'id_merma'             => 1,
            'id_trazabilidad'      => $idTrazabilidad,
            'id_lote'              => $idLote,
            'cantidad'             => 4,
            'motivo'               => 'plaga',
            'descripcion'          => 'Manchas en las hojas',
            'fecha_merma'          => '2026-02-05',
            'id_usuario_registra'  => 3,
        ]);

        $merma = $this->fetchRow('mermas_historico', 1, 'id_merma');
        $this->assertSame(50.0, (float) $merma['impacto_economico']);

        $trazabilidad = $this->fetchRow('trazabilidad', $idTrazabilidad, 'id_trazabilidad');
        $this->assertSame(6, (int) $trazabilidad['cantidad']);
    }

    #[Test]
    public function register_loss_rechaza_cantidades_mayores_a_la_cuarentena(): void
    {
        $idLote = $this->crearLote(20, 1.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Esta cuarentena solo tiene 5 ejemplares disponibles.');

        $this->mermaConTransacciones()->registerLoss(
            $idTrazabilidad,
            6,
            'plaga',
            null,
            '2026-02-05',
            1
        );
    }

    #[Test]
    public function register_loss_falla_si_la_cuarentena_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El registro de cuarentena seleccionado no existe.');

        $this->mermaConTransacciones()->registerLoss(999, 1, 'plaga', null, '2026-02-05', 1);
    }

    #[Test]
    public function register_loss_puede_agotar_la_cuarentena(): void
    {
        $idLote = $this->crearLote(20, 2.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 4);

        $id = $this->mermaConTransacciones()->registerLoss(
            $idTrazabilidad,
            4,
            'enfermedad',
            'Cuarentena completa',
            '2026-02-06',
            1
        );

        $this->assertSame(1, $id);
        $trazabilidad = $this->fetchRow('trazabilidad', $idTrazabilidad, 'id_trazabilidad');
        $this->assertSame(0, (int) $trazabilidad['cantidad']);
    }

    #[Test]
    public function get_available_quarantine_excluye_estados_sanos_y_cantidades_agotadas(): void
    {
        $idLote = $this->crearLote(30, 8.0);
        $disponible = $this->crearTrazabilidad($idLote, 5, 'Cuarentena');
        $this->crearTrazabilidad($idLote, 5, 'Vivo');
        $this->crearTrazabilidad($idLote, 0, 'Cuarentena');
        $this->crearTrazabilidad($idLote, 5, 'Cuarentena', 0);

        $cuarentenas = (new SqliteMerma())->getAvailableQuarantine();

        $this->assertCount(1, $cuarentenas);
        $this->assertSame($disponible, (int) $cuarentenas[0]['id']);
        $this->assertSame('Cuarentena', $cuarentenas[0]['estado_salud']);
        $this->assertSame('Rosa', $cuarentenas[0]['planta_nombre']);
        $this->assertSame(30, (int) $cuarentenas[0]['lote_stock']);
        $this->assertSame('Vivero Norte', $cuarentenas[0]['nombre_ubicacion']);
        $this->assertSame(8.0, (float) $cuarentenas[0]['precio_unitario']);
    }

    #[Test]
    public function getAll_solo_devuelve_mermas_activas_ordenadas_por_fecha(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');

        $antigua = $this->crearMerma($idTrazabilidad, $idLote, '2026-01-10');
        $reciente = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');
        $borrada = $this->crearMerma($idTrazabilidad, $idLote, '2026-03-10', 0);

        $filas = (new SqliteMerma())->getAll();

        $this->assertSame([$reciente, $antigua], array_map(
            static fn (array $fila): int => (int) $fila['id_merma'],
            $filas
        ));
        $this->assertSame('Cuarentena', $filas[0]['estado_salud']);
        $this->assertSame('Rosa', $filas[0]['planta_nombre']);
        $this->assertNotContains($borrada, array_column($filas, 'id_merma'));
    }

    #[Test]
    public function get_by_id_devuelve_la_fila_o_null(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');
        $id = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');

        $fila = (new SqliteMerma())->getById($id);

        $this->assertNotNull($fila);
        $this->assertSame($id, (int) $fila['id_merma']);
        $this->assertSame('Cuarentena', $fila['estado_salud']);
        $this->assertSame('Rosa', $fila['planta_nombre']);
        $this->assertNull($fila['usuario_registra']);

        $this->assertNull((new SqliteMerma())->getById(999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');
        $id = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');

        $merma = new SqliteMerma();

        $this->assertTrue($merma->delete($id));
        $this->assertSame([], $merma->getAll());

        $this->assertTrue($merma->restore($id));
        $this->assertCount(1, $merma->getAll());
    }

    #[Test]
    public function load_by_id_carga_los_atributos(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');
        $id = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');

        $destino = new SqliteMerma();
        $this->assertTrue($destino->loadById($id));

        $this->assertSame($id, $destino->getId());
        $this->assertSame($idTrazabilidad, $destino->getIdTrazabilidad());
        $this->assertSame($idLote, $destino->getIdLote());
        $this->assertSame(2, $destino->getCantidad());
        $this->assertSame('plaga', $destino->getMotivo());
        $this->assertSame(8.0, $destino->getImpactoEconomico());
        $this->assertFalse((new SqliteMerma())->loadById(999));
    }

    #[Test]
    public function exists_refleja_si_la_merma_existe(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');
        $id = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');

        $merma = new SqliteMerma();

        $this->assertTrue($merma->exists($id));
        $this->assertFalse($merma->exists(999));
    }

    #[Test]
    public function find_devuelve_el_modelo_o_null(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');
        $id = $this->crearMerma($idTrazabilidad, $idLote, '2026-02-10');

        $merma = SqliteMerma::find($id);

        $this->assertInstanceOf(Merma::class, $merma);
        $this->assertSame($id, $merma->getId());
        $this->assertSame('plaga', $merma->getMotivo());
        $this->assertNull(SqliteMerma::find(999));
    }

    #[Test]
    public function get_last_insert_id_devuelve_el_ultimo_id(): void
    {
        $idLote = $this->crearLote(30, 4.0);
        $idTrazabilidad = $this->crearTrazabilidad($idLote, 20, 'Cuarentena');

        $merma = new SqliteMerma([
            'id_trazabilidad' => $idTrazabilidad,
            'id_lote'         => $idLote,
            'cantidad'        => 1,
            'motivo'          => 'otro',
            'fecha_merma'     => '2026-02-10',
        ]);
        $this->assertTrue($merma->save());

        $this->assertSame($merma->getId(), $merma->getLastInsertId());
    }

    /**
     * registerLoss() usa los helpers beginTransaction()/commit() de Database,
     * que leen la propiedad privada $pdo. El doble solo inyecta db(), así que
     * aquí se completa el doble para poder probar la transacción real.
     */
    private function mermaConTransacciones(): SqliteMerma
    {
        $merma = new SqliteMerma();

        $property = new \ReflectionProperty(Database::class, 'pdo');
        $property->setValue($merma, $this->pdo);

        return $merma;
    }

    private function crearLote(int $cantidad, float $costoUnitario): int
    {
        $idPlanta = $this->insert('plantas', [
            'nombre_comun'   => 'Rosa',
            'nombre_tecnico' => 'Rosa spp.',
            'activo'         => 1,
        ]);
        $idUbicacion = $this->insert('ubicacion', [
            'nombre_ubicacion' => 'Vivero Norte',
            'activo'           => 1,
        ]);

        return $this->insert('lote', [
            'id_planta'        => $idPlanta,
            'id_ubicacion'     => $idUbicacion,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => $cantidad,
            'cantidad_actual'  => $cantidad,
            'costo_unitario'   => $costoUnitario,
            'activo'           => 1,
        ]);
    }

    private function crearTrazabilidad(int $idLote, int $cantidad, string $estado = 'Cuarentena', int $activo = 1): int
    {
        return $this->insert('trazabilidad', [
            'id_lote'        => $idLote,
            'cantidad'       => $cantidad,
            'estado_salud'   => $estado,
            'observacion'    => null,
            'activo'         => $activo,
            'fecha_registro' => '2026-01-15',
        ]);
    }

    private function crearMerma(int $idTrazabilidad, int $idLote, string $fecha, int $activo = 1): int
    {
        return $this->insert('mermas_historico', [
            'id_trazabilidad'    => $idTrazabilidad,
            'id_lote'            => $idLote,
            'cantidad'           => 2,
            'motivo'             => 'plaga',
            'descripcion'        => null,
            'fecha_merma'        => $fecha,
            'impacto_economico'  => 8.0,
            'id_usuario_registra' => null,
            'activo'             => $activo,
        ]);
    }
}
