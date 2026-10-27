<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Ampliacion;
use SysInescolara\Tests\Support\SqliteAmpliacion;
use SysInescolara\Tests\Support\SqliteTestCase;

/**
 * Omitidos por depender de modelos que abren MySQL (llaman a exit() si no hay
 * servidor): getAll() y getGestores() (Usuario), getLocations() (Ubicacion) y
 * getSpecies() (Especie). getById(), update() y delete() solo se prueban en
 * sus rutas portables (registro inexistente), porque con un registro existente
 * getById() instancia Usuario. save() solo se prueba en la rama de inserción.
 */
final class AmpliacionRepositoryTest extends SqliteTestCase
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
                apellido_trabajador TEXT DEFAULT NULL,
                estatus             TEXT DEFAULT NULL
            )
        ');

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
            CREATE TABLE cliente (
                id_cliente          INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_cedula_cliente TEXT DEFAULT NULL,
                cedula_cliente      TEXT DEFAULT NULL,
                nombre_cliente      TEXT NOT NULL,
                apellido_cliente    TEXT NOT NULL DEFAULT \'\',
                contacto_cliente    TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1
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
                origen           TEXT DEFAULT NULL,
                observacion      TEXT DEFAULT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE movimiento_planta (
                id_movimiento_planta INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_movimiento      TEXT NOT NULL,
                id_cliente           INTEGER DEFAULT NULL,
                id_usuario_gestor    INTEGER NOT NULL,
                fecha_movimiento     TEXT NOT NULL,
                observacion          TEXT DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE movimiento_planta_detalle (
                id_detalle_mov_planta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_movimiento_planta  INTEGER NOT NULL,
                id_lote               INTEGER NOT NULL,
                tipo                  TEXT NOT NULL DEFAULT \'salida\',
                cantidad              INTEGER NOT NULL,
                precio_unitario       REAL DEFAULT NULL,
                sub_total             REAL DEFAULT NULL,
                activo                INTEGER NOT NULL DEFAULT 1
            )
        ');

        SqliteAmpliacion::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteAmpliacion::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function register_exchange_registra_salida_y_entrada_en_lotes_existentes(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);
        $loteTulipa = $this->crearLote(2, 2, 5);

        $id = (new SqliteAmpliacion())->registerExchange([
            'id_cliente'        => 1,
            'id_usuario_gestor' => 5,
            'fecha_movimiento'  => '2026-02-01',
            'observacion'       => 'Intercambio de plantas',
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 3]],
            'entrada_items'     => [['id_planta' => 2, 'id_ubicacion' => 2, 'cantidad' => 4]],
        ]);

        $this->assertSame(1, $id);
        $this->assertDatabaseHas('movimiento_planta', [
            'id_movimiento_planta' => 1,
            'tipo_movimiento'      => 'intercambio',
            'id_cliente'           => 1,
            'id_usuario_gestor'    => 5,
            'fecha_movimiento'     => '2026-02-01',
            'observacion'          => 'Intercambio de plantas',
        ]);

        $this->assertDatabaseCount('movimiento_planta_detalle', 2);
        $this->assertDatabaseHas('movimiento_planta_detalle', [
            'id_movimiento_planta' => 1,
            'id_lote'              => $loteRosa,
            'tipo'                 => 'salida',
            'cantidad'             => 3,
        ]);
        $this->assertDatabaseHas('movimiento_planta_detalle', [
            'id_movimiento_planta' => 1,
            'id_lote'              => $loteTulipa,
            'tipo'                 => 'entrada',
            'cantidad'             => 4,
        ]);

        $this->assertSame(7, (int) $this->fetchRow('lote', $loteRosa, 'id_lote')['cantidad_actual']);
        $this->assertSame(9, (int) $this->fetchRow('lote', $loteTulipa, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function register_exchange_crea_planta_y_lote_para_una_entrada_nueva(): void
    {
        $this->sembrarCatalogo();

        $id = (new SqliteAmpliacion())->registerExchange([
            'id_usuario_gestor' => 1,
            'fecha_movimiento'  => '2026-02-02',
            'entrada_items'     => [[
                'id_planta'              => 0,
                'id_ubicacion'           => 1,
                'cantidad'               => 6,
                'nueva_planta_nombre'    => 'Girasol',
                'nueva_planta_tecnico'   => 'Helianthus annuus',
                'nueva_planta_id_especie' => 1,
            ]],
        ]);

        $this->assertSame(1, $id);

        $planta = $this->fetchRowWhere('plantas', ['nombre_comun' => 'Girasol']);
        $this->assertNotNull($planta);
        $this->assertSame('Helianthus annuus', $planta['nombre_tecnico']);
        $this->assertSame(1, (int) $planta['id_especie']);
        $this->assertSame(1, (int) $planta['activo']);

        $idPlanta = (int) $planta['id_planta'];
        $lote = $this->fetchRowWhere('lote', ['id_planta' => $idPlanta]);
        $this->assertNotNull($lote);
        $this->assertSame('Vivo', $lote['estado']);
        $this->assertSame('Ampliación', $lote['origen']);
        $this->assertSame(6, (int) $lote['cantidad_inicial']);
        $this->assertSame(6, (int) $lote['cantidad_actual']);

        $this->assertDatabaseHas('movimiento_planta_detalle', [
            'id_movimiento_planta' => 1,
            'id_lote'              => (int) $lote['id_lote'],
            'tipo'                 => 'entrada',
            'cantidad'             => 6,
        ]);
    }

    #[Test]
    public function register_exchange_sin_items_lanza_excepcion(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debe agregar al menos un item de salida o entrada.');

        (new SqliteAmpliacion())->registerExchange(['id_usuario_gestor' => 1]);
    }

    #[Test]
    public function register_exchange_revierte_si_el_stock_es_insuficiente(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente en el lote seleccionado para salida.');

        try {
            (new SqliteAmpliacion())->registerExchange([
                'id_usuario_gestor' => 1,
                'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 5]],
            ]);
        } finally {
            $this->assertDatabaseCount('movimiento_planta', 0);
            $this->assertDatabaseCount('movimiento_planta_detalle', 0);
            $this->assertSame(2, (int) $this->fetchRow('lote', $loteRosa, 'id_lote')['cantidad_actual']);
        }
    }

    #[Test]
    public function register_exchange_exige_especie_para_una_planta_nueva(): void
    {
        $this->sembrarCatalogo();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Debe seleccionar una especie para la nueva planta 'Girasol'.");

        try {
            (new SqliteAmpliacion())->registerExchange([
                'id_usuario_gestor' => 1,
                'entrada_items'     => [[
                    'id_planta'           => 0,
                    'id_ubicacion'        => 1,
                    'cantidad'            => 2,
                    'nueva_planta_nombre' => 'Girasol',
                ]],
            ]);
        } finally {
            $this->assertDatabaseCount('movimiento_planta', 0);
        }
    }

    #[Test]
    public function get_details_devuelve_planta_ubicacion_y_stock(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);

        $id = (new SqliteAmpliacion())->registerExchange([
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 3]],
            'entrada_items'     => [['id_planta' => 1, 'id_ubicacion' => 2, 'cantidad' => 4]],
        ]);

        $detalles = (new SqliteAmpliacion())->getDetails($id);

        $this->assertCount(2, $detalles);
        $this->assertSame(['entrada', 'salida'], array_column($detalles, 'tipo'));
        $this->assertSame('Rosa', $detalles[0]['planta_nombre']);
        $this->assertSame('Vivero Sur', $detalles[0]['ubicacion_nombre']);
        $this->assertSame(4, (int) $detalles[0]['lote_stock_actual']);
        $this->assertSame('Vivero Norte', $detalles[1]['ubicacion_nombre']);
        $this->assertSame(7, (int) $detalles[1]['lote_stock_actual']);
    }

    #[Test]
    public function get_available_lots_excluye_lotes_agotados_o_inactivos(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);
        $loteTulipa = $this->crearLote(2, 2, 5);
        $this->crearLote(1, 1, 0);
        $this->crearLote(1, 1, 7, 0);

        $lotes = (new SqliteAmpliacion())->getAvailableLots();

        $this->assertSame([$loteTulipa, $loteRosa], array_map(
            static fn (array $fila): int => (int) $fila['id'],
            $lotes
        ));
        $this->assertSame('Tulipa spp.', $lotes[0]['planta_nombre']);
        $this->assertSame('Rosa', $lotes[1]['planta_nombre']);
        $this->assertSame('Vivero Norte', $lotes[1]['ubicacion_nombre']);
    }

    #[Test]
    public function get_plants_solo_devuelve_activas_ordenadas_por_nombre_comun(): void
    {
        $this->sembrarCatalogo();
        $this->insert('plantas', [
            'nombre_comun'   => 'Aloe',
            'nombre_tecnico' => 'Aloe vera',
            'activo'         => 0,
        ]);

        $plantas = (new SqliteAmpliacion())->getPlants();

        $this->assertSame(['', 'Rosa'], array_column($plantas, 'nombre_comun'));
        $this->assertSame(['Tulipa spp.', 'Rosa spp.'], array_column($plantas, 'nombre_tecnico'));
    }

    #[Test]
    public function buscar_clientes_encuentra_por_nombre_contacto_y_cedula(): void
    {
        $this->sembrarCatalogo();
        $ampliacion = new SqliteAmpliacion();

        $porNombre = $ampliacion->buscarClientes('Mar');
        $this->assertCount(1, $porNombre);
        $this->assertSame('María Gómez', $porNombre[0]['nombre_cliente']);
        $this->assertSame('Gómez', $porNombre[0]['apellido_cliente']);

        $porContacto = $ampliacion->buscarClientes('0414');
        $this->assertCount(1, $porContacto);
        $this->assertSame('María Gómez', $porContacto[0]['nombre_cliente']);

        $porCedula = $ampliacion->buscarClientes('12345678');
        $this->assertCount(1, $porCedula);

        $this->assertSame([], $ampliacion->buscarClientes('zzz'));
    }

    #[Test]
    public function restore_reactiva_el_movimiento_y_sus_detalles(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);

        $ampliacion = new SqliteAmpliacion();
        $id = $ampliacion->registerExchange([
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 2]],
        ]);

        $this->pdo->exec('UPDATE movimiento_planta SET activo = 0 WHERE id_movimiento_planta = ' . $id);
        $this->pdo->exec('UPDATE movimiento_planta_detalle SET activo = 0 WHERE id_movimiento_planta = ' . $id);

        $this->assertSame([], $ampliacion->getDetails($id));
        $this->assertTrue($ampliacion->restore($id));
        $this->assertCount(1, $ampliacion->getDetails($id));
        $this->assertDatabaseHas('movimiento_planta', ['id_movimiento_planta' => $id, 'activo' => 1]);
    }

    #[Test]
    public function save_inserta_un_movimiento_nuevo(): void
    {
        $this->sembrarCatalogo();

        $ampliacion = new SqliteAmpliacion([
            'tipo_movimiento'   => 'donacion',
            'id_cliente'        => 1,
            'id_usuario_gestor' => 2,
            'fecha_movimiento'  => '2026-03-01',
            'observacion'       => 'Donación institucional',
        ]);

        $this->assertTrue($ampliacion->save());
        $this->assertSame(1, $ampliacion->getId());
        $this->assertDatabaseHas('movimiento_planta', [
            'id_movimiento_planta' => 1,
            'tipo_movimiento'      => 'donacion',
            'id_cliente'           => 1,
            'id_usuario_gestor'    => 2,
            'fecha_movimiento'     => '2026-03-01',
            'activo'               => 1,
        ]);
    }

    #[Test]
    public function exists_refleja_si_el_movimiento_existe(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);
        $ampliacion = new SqliteAmpliacion();
        $id = $ampliacion->registerExchange([
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 1]],
        ]);

        $this->assertTrue($ampliacion->exists($id));
        $this->assertFalse($ampliacion->exists(999));
    }

    #[Test]
    public function find_y_load_by_id_cargan_los_atributos(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);
        $id = (new SqliteAmpliacion())->registerExchange([
            'id_cliente'        => 1,
            'id_usuario_gestor' => 3,
            'fecha_movimiento'  => '2026-04-01',
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 1]],
        ]);

        $encontrada = SqliteAmpliacion::find($id);

        $this->assertInstanceOf(Ampliacion::class, $encontrada);
        $this->assertSame($id, $encontrada->getId());
        $this->assertSame('intercambio', $encontrada->getTipoMovimiento());
        $this->assertSame(1, $encontrada->getIdCliente());
        $this->assertSame(3, $encontrada->getIdUsuarioGestor());
        $this->assertSame('2026-04-01', $encontrada->getFechaMovimiento());

        $destino = new SqliteAmpliacion();
        $this->assertTrue($destino->loadById($id));
        $this->assertSame($id, $destino->getId());
        $this->assertFalse((new SqliteAmpliacion())->loadById(999));
        $this->assertNull(SqliteAmpliacion::find(999));
    }

    #[Test]
    public function all_y_where_filtran_por_activo(): void
    {
        $this->sembrarCatalogo();
        $loteRosa = $this->crearLote(1, 1, 10);
        $loteRosa2 = $this->crearLote(1, 1, 10);

        $ampliacion = new SqliteAmpliacion();
        $intercambio = $ampliacion->registerExchange([
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => $loteRosa, 'cantidad' => 1]],
        ]);
        $donacion = (new SqliteAmpliacion([
            'tipo_movimiento'   => 'donacion',
            'id_usuario_gestor' => 1,
            'fecha_movimiento'  => '2026-05-01',
        ]))->save();

        $this->assertTrue($donacion);
        $this->assertCount(2, SqliteAmpliacion::all());

        $this->pdo->exec('UPDATE movimiento_planta SET activo = 0 WHERE id_movimiento_planta = ' . $intercambio);
        $this->assertCount(1, SqliteAmpliacion::all());

        $donaciones = SqliteAmpliacion::where('tipo_movimiento', 'donacion');
        $this->assertCount(1, $donaciones);
        $this->assertSame('donacion', $donaciones[0]->getTipoMovimiento());

        $this->assertCount(0, SqliteAmpliacion::where('tipo_movimiento', 'intercambio'));
    }

    #[Test]
    public function get_last_insert_id_devuelve_el_ultimo_id(): void
    {
        $this->sembrarCatalogo();

        $ampliacion = new SqliteAmpliacion([
            'tipo_movimiento'   => 'ornato',
            'id_usuario_gestor' => 1,
            'fecha_movimiento'  => '2026-06-01',
        ]);
        $this->assertTrue($ampliacion->save());

        $this->assertSame($ampliacion->getId(), $ampliacion->getLastInsertId());
    }

    #[Test]
    public function get_by_id_devuelve_null_si_el_movimiento_no_existe(): void
    {
        $this->assertNull((new SqliteAmpliacion())->getById(999));
    }

    #[Test]
    public function delete_devuelve_true_aunque_el_movimiento_no_exista(): void
    {
        $this->assertTrue((new SqliteAmpliacion())->delete(999));
        $this->assertDatabaseCount('movimiento_planta', 0);
    }

    #[Test]
    public function update_lanza_excepcion_si_el_movimiento_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la ampliación');

        (new SqliteAmpliacion())->update(999, [
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => 1, 'cantidad' => 1]],
        ]);
    }

    #[Test]
    public function los_metodos_dependientes_de_mysql_no_son_portables(): void
    {
        $this->markTestSkipped(
            'getAll() y getGestores() instancian Usuario; getLocations() instancia Ubicacion; '
            . 'getSpecies() instancia Especie; getById(), update() y delete() con un registro '
            . 'existente pasan por Usuario. Esos constructores abren MySQL y llaman a exit() '
            . 'sin servidor disponible.'
        );
    }

    private function sembrarCatalogo(): void
    {
        $this->insert('especie', ['nombre_especie' => 'Rosáceas']);

        $this->insert('plantas', [
            'id_especie'     => 1,
            'nombre_comun'   => 'Rosa',
            'nombre_tecnico' => 'Rosa spp.',
            'activo'         => 1,
        ]);
        $this->insert('plantas', [
            'id_especie'     => 1,
            'nombre_comun'   => '',
            'nombre_tecnico' => 'Tulipa spp.',
            'activo'         => 1,
        ]);

        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Norte', 'activo' => 1]);
        $this->insert('ubicacion', ['nombre_ubicacion' => 'Vivero Sur', 'activo' => 1]);

        $this->insert('cliente', [
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
            'nombre_cliente'      => 'María',
            'apellido_cliente'    => 'Gómez',
            'contacto_cliente'    => '04141234567',
            'activo'              => 1,
        ]);
        $this->insert('cliente', [
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '87654321',
            'nombre_cliente'      => 'Pedro',
            'apellido_cliente'    => 'Pérez',
            'contacto_cliente'    => '04249876543',
            'activo'              => 1,
        ]);
    }

    private function crearLote(int $idPlanta, int $idUbicacion, int $cantidad, int $activo = 1): int
    {
        return $this->insert('lote', [
            'id_planta'        => $idPlanta,
            'id_ubicacion'     => $idUbicacion,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => $cantidad,
            'cantidad_actual'  => $cantidad,
            'costo_unitario'   => 0,
            'estado'           => 'Vivo',
            'origen'           => 'Siembra',
            'activo'           => $activo,
        ]);
    }
}
