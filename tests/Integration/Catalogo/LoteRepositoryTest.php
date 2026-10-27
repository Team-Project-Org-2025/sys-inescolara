<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Catalogo;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Lote;
use SysInescolara\Tests\Support\SqliteLote;
use SysInescolara\Tests\Support\SqliteTestCase;

final class LoteRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE especie (
                id_especie     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie TEXT    NOT NULL,
                descripcion    TEXT    DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT    DEFAULT \'\',
                nombre_comun   TEXT    DEFAULT NULL,
                cantidad_total INTEGER NOT NULL DEFAULT 0,
                imagen         TEXT    DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (id_especie) REFERENCES especie (id_especie)
            )
        ');

        $this->exec('
            CREATE TABLE ubicacion (
                id_ubicacion     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_ubicacion TEXT    NOT NULL,
                descripcion      TEXT    DEFAULT NULL,
                zona             TEXT    DEFAULT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE estado (
                id_estado INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre    TEXT    NOT NULL UNIQUE,
                activo    INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE categoria (
                id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre       TEXT    NOT NULL UNIQUE,
                activo       INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE origen (
                id_origen INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre    TEXT    NOT NULL UNIQUE,
                activo    INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE lote (
                id_lote              INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta            INTEGER NOT NULL,
                id_ubicacion         INTEGER DEFAULT NULL,
                fecha_siembra        TEXT    NOT NULL,
                cantidad_inicial     INTEGER NOT NULL,
                cantidad_actual      INTEGER NOT NULL,
                costo_unitario       NUMERIC DEFAULT 0.00,
                id_estado            INTEGER DEFAULT NULL,
                id_categoria         INTEGER DEFAULT NULL,
                id_origen            INTEGER DEFAULT NULL,
                observacion          TEXT    DEFAULT NULL,
                imagen               TEXT    DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia  NUMERIC NOT NULL DEFAULT 30.00,
                FOREIGN KEY (id_planta)    REFERENCES plantas (id_planta),
                FOREIGN KEY (id_ubicacion) REFERENCES ubicacion (id_ubicacion),
                FOREIGN KEY (id_estado)    REFERENCES estado (id_estado),
                FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria),
                FOREIGN KEY (id_origen)    REFERENCES origen (id_origen)
            )
        ');

        $this->insert('estado', ['id_estado' => 5, 'nombre' => 'vivo', 'activo' => 1]);
        $this->insert('estado', ['id_estado' => 6, 'nombre' => 'cuarentena', 'activo' => 1]);
        $this->insert('estado', ['id_estado' => 7, 'nombre' => 'muerto', 'activo' => 0]);

        $this->insert('categoria', ['id_categoria' => 1, 'nombre' => 'germinado', 'activo' => 1]);
        $this->insert('categoria', ['id_categoria' => 2, 'nombre' => 'plántula', 'activo' => 1]);
        $this->insert('categoria', ['id_categoria' => 3, 'nombre' => 'adulto', 'activo' => 0]);

        $this->insert('origen', ['id_origen' => 1, 'nombre' => 'Siembra', 'activo' => 1]);
        $this->insert('origen', ['id_origen' => 2, 'nombre' => 'Ampliación', 'activo' => 1]);
        $this->insert('origen', ['id_origen' => 3, 'nombre' => 'Donación', 'activo' => 0]);
        $this->insert('origen', ['id_origen' => 4, 'nombre' => 'Compra', 'activo' => 1]);

        $this->insert('especie', ['id_especie' => 1, 'nombre_especie' => 'Rosa']);
        $this->insert('plantas', [
            'id_planta'    => 1,
            'id_especie'   => 1,
            'nombre_comun' => 'Rosal',
            'activo'       => 1,
        ]);
        $this->insert('ubicacion', [
            'id_ubicacion'     => 1,
            'nombre_ubicacion' => 'Invernadero',
            'activo'           => 1,
        ]);

        SqliteLote::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteLote::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function getEstados_devuelve_solo_activos_ordenados_por_nombre(): void
    {
        $estados = (new SqliteLote())->getEstados();

        $this->assertSame([
            ['id' => 6, 'nombre' => 'cuarentena'],
            ['id' => 5, 'nombre' => 'vivo'],
        ], $estados);
    }

    #[Test]
    public function getCategorias_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        $categorias = (new SqliteLote())->getCategorias();

        $this->assertSame([
            ['id' => 1, 'nombre' => 'germinado'],
            ['id' => 2, 'nombre' => 'plántula'],
        ], $categorias);
    }

    #[Test]
    public function getOrigenes_devuelve_solo_activos_ordenados_por_nombre(): void
    {
        $origenes = (new SqliteLote())->getOrigenes();

        $this->assertSame([
            ['id' => 2, 'nombre' => 'Ampliación'],
            ['id' => 4, 'nombre' => 'Compra'],
            ['id' => 1, 'nombre' => 'Siembra'],
        ], $origenes);
    }

    #[Test]
    public function getIdEstadoVivo_devuelve_el_id_del_estado_vivo(): void
    {
        $this->assertSame(5, (new SqliteLote())->getIdEstadoVivo());
    }

    #[Test]
    public function getIdEstadoVivo_devuelve_cero_si_no_hay_estado_vivo(): void
    {
        $this->exec("DELETE FROM estado WHERE nombre = 'vivo'");

        // Sin filas, fetchColumn() devuelve false y el modelo lo castea a 0.
        $this->assertSame(0, (new SqliteLote())->getIdEstadoVivo());
    }

    #[Test]
    public function getIdOrigenPorNombre_devuelve_el_id_o_uno_por_defecto(): void
    {
        $lote = new SqliteLote();

        $this->assertSame(4, $lote->getIdOrigenPorNombre('Compra'));
        $this->assertSame(1, $lote->getIdOrigenPorNombre('Origen inexistente'));
    }

    #[Test]
    public function add_inserta_un_lote_con_estado_y_origen_por_defecto(): void
    {
        $lote = new SqliteLote();

        $this->assertTrue($lote->add(
            1,
            1,
            '2026-01-10',
            10,
            8,
            2.5,
            null,
            1,
            null,
            'Lote A',
            'lote.jpg',
            25.0
        ));

        $id = $lote->getId();
        $this->assertNotNull($id);
        $this->assertSame($id, $lote->getLastInsertId());

        $encontrado = SqliteLote::find($id);
        $this->assertInstanceOf(Lote::class, $encontrado);
        $this->assertSame(1, $encontrado->getIdPlanta());
        $this->assertSame(1, $encontrado->getIdUbicacion());
        $this->assertSame('2026-01-10', $encontrado->getFechaSiembra());
        $this->assertSame(10, $encontrado->getCantidadInicial());
        $this->assertSame(8, $encontrado->getCantidadActual());
        $this->assertSame(2.5, $encontrado->getCostoUnitario());
        $this->assertSame(5, $encontrado->getIdEstado());
        $this->assertSame(1, $encontrado->getIdCategoria());
        $this->assertSame(1, $encontrado->getIdOrigen());
        $this->assertSame('Lote A', $encontrado->getObservacion());
        $this->assertSame('lote.jpg', $encontrado->getImagen());
        $this->assertSame(25.0, $encontrado->getPorcentajeGanancia());
        $this->assertTrue($encontrado->isActivo());

        $this->assertDatabaseHas('lote', [
            'id_lote'   => $id,
            'id_estado' => 5,
            'id_origen' => 1,
            'activo'    => 1,
        ]);
    }

    #[Test]
    public function add_respeta_estado_categoria_y_origen_explicitos(): void
    {
        $lote = new SqliteLote();

        $this->assertTrue($lote->add(1, 1, '2026-02-01', 5, 5, 1.0, 6, 2, 4));

        $encontrado = SqliteLote::find($lote->getId());
        $this->assertSame(6, $encontrado->getIdEstado());
        $this->assertSame(2, $encontrado->getIdCategoria());
        $this->assertSame(4, $encontrado->getIdOrigen());
    }

    #[Test]
    public function add_sin_planta_lanza_excepcion_de_validacion(): void
    {
        $lote = new SqliteLote();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_planta');

        $lote->add(null, 1, '2026-01-01', 1, 1);
    }

    #[Test]
    public function save_sin_planta_lanza_excepcion_de_validacion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_planta');

        (new SqliteLote())->save();
    }

    #[Test]
    public function update_actualiza_un_lote_existente(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 10, 2.0, 5, 1, 1));
        $id = $lote->getId();

        $this->assertTrue($lote->update(
            $id,
            1,
            1,
            '2026-01-20',
            12,
            9,
            3.0,
            6,
            2,
            4,
            'Actualizado',
            'nuevo.jpg',
            40.0
        ));

        $encontrado = SqliteLote::find($id);
        $this->assertSame('2026-01-20', $encontrado->getFechaSiembra());
        $this->assertSame(12, $encontrado->getCantidadInicial());
        $this->assertSame(9, $encontrado->getCantidadActual());
        $this->assertSame(3.0, $encontrado->getCostoUnitario());
        $this->assertSame(6, $encontrado->getIdEstado());
        $this->assertSame(2, $encontrado->getIdCategoria());
        $this->assertSame(4, $encontrado->getIdOrigen());
        $this->assertSame('Actualizado', $encontrado->getObservacion());
        $this->assertSame('nuevo.jpg', $encontrado->getImagen());
        $this->assertSame(40.0, $encontrado->getPorcentajeGanancia());
    }

    #[Test]
    public function update_de_un_lote_inexistente_lanza_excepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe el lote con ID: 999');

        (new SqliteLote())->update(999, 1, 1, '2026-01-01', 1, 1);
    }

    #[Test]
    public function all_devuelve_solo_activos_con_relaciones_ordenados_por_fecha_desc(): void
    {
        $loteEnero = new SqliteLote();
        $this->assertTrue($loteEnero->add(1, 1, '2026-01-10', 10, 10, 2.0, 5, 1, 1));
        $idEnero = $loteEnero->getId();

        $loteFebrero = new SqliteLote();
        $this->assertTrue($loteFebrero->add(1, 1, '2026-02-10', 5, 5, 3.0, 6, 2, 4));
        $idFebrero = $loteFebrero->getId();

        $loteMarzo = new SqliteLote();
        $this->assertTrue($loteMarzo->add(1, 1, '2026-03-10', 2, 2, 1.0, 5, 1, 1));
        $idMarzo = $loteMarzo->getId();

        $this->assertTrue($loteMarzo->delete($idMarzo));

        $filas = SqliteLote::all();

        $this->assertSame([$idFebrero, $idEnero], array_column($filas, 'id'));
        $this->assertSame([
            'id', 'id_planta', 'id_ubicacion', 'fecha_siembra',
            'cantidad_inicial', 'cantidad_actual', 'costo_unitario',
            'id_estado', 'id_categoria', 'id_origen',
            'observacion', 'imagen', 'activo', 'porcentaje_ganancia',
            'planta_nombre', 'especie_nombre', 'ubicacion_nombre',
            'estado_nombre', 'categoria_nombre', 'origen_nombre',
        ], array_keys($filas[0]));
        $this->assertSame('Rosal', $filas[0]['planta_nombre']);
        $this->assertSame('Rosa', $filas[0]['especie_nombre']);
        $this->assertSame('Invernadero', $filas[0]['ubicacion_nombre']);
        $this->assertSame('cuarentena', $filas[0]['estado_nombre']);
        $this->assertSame('plántula', $filas[0]['categoria_nombre']);
        $this->assertSame('Compra', $filas[0]['origen_nombre']);
    }

    #[Test]
    public function getById_devuelve_la_fila_con_relaciones_o_null(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 8, 2.5, 5, 1, 1, 'Lote A'));
        $id = $lote->getId();

        $fila = $lote->getById($id);

        $this->assertSame($id, $fila['id_lote']);
        $this->assertSame(1, $fila['id_planta']);
        $this->assertSame(1, $fila['id_ubicacion']);
        $this->assertSame('2026-01-10', $fila['fecha_siembra']);
        $this->assertSame(10, $fila['cantidad_inicial']);
        $this->assertSame(8, $fila['cantidad_actual']);
        $this->assertSame(2.5, (float) $fila['costo_unitario']);
        $this->assertSame(5, $fila['id_estado']);
        $this->assertSame(1, $fila['id_categoria']);
        $this->assertSame(1, $fila['id_origen']);
        $this->assertSame('Lote A', $fila['observacion']);
        $this->assertSame(1, $fila['activo']);
        $this->assertSame(30.0, (float) $fila['porcentaje_ganancia']);
        $this->assertSame('Rosal', $fila['planta_nombre']);
        $this->assertSame('Rosa', $fila['especie_nombre']);
        $this->assertSame('vivo', $fila['estado_nombre']);
        $this->assertSame('germinado', $fila['categoria_nombre']);
        $this->assertSame('Siembra', $fila['origen_nombre']);

        $this->assertNull($lote->getById(99999));
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 10));

        $this->assertSame(SqliteLote::all(), (new SqliteLote())->getAll());
    }

    #[Test]
    public function exists_refleja_si_el_lote_existe(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 10));

        $this->assertTrue($lote->exists($lote->getId()));
        $this->assertFalse($lote->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 10));
        $id = $lote->getId();

        $this->assertTrue($lote->delete($id));
        $this->assertFalse(SqliteLote::find($id)->isActivo());
        $this->assertSame([], SqliteLote::all());
        $this->assertTrue($lote->exists($id));

        $this->assertTrue($lote->restore($id));
        $this->assertTrue(SqliteLote::find($id)->isActivo());
        $this->assertCount(1, SqliteLote::all());
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $lote = new SqliteLote();
        $this->assertTrue($lote->add(1, 1, '2026-01-10', 10, 8, 2.5, 5, 1, 1, 'Lote A', 'lote.jpg', 25.0));
        $id = $lote->getId();

        $destino = new SqliteLote();
        $this->assertTrue($destino->loadById($id));
        $this->assertSame($id, $destino->getId());
        $this->assertSame(1, $destino->getIdPlanta());
        $this->assertSame(1, $destino->getIdUbicacion());
        $this->assertSame('2026-01-10', $destino->getFechaSiembra());
        $this->assertSame(10, $destino->getCantidadInicial());
        $this->assertSame(8, $destino->getCantidadActual());
        $this->assertSame(2.5, $destino->getCostoUnitario());
        $this->assertSame(5, $destino->getIdEstado());
        $this->assertSame(1, $destino->getIdCategoria());
        $this->assertSame(1, $destino->getIdOrigen());
        $this->assertSame('Lote A', $destino->getObservacion());
        $this->assertSame('lote.jpg', $destino->getImagen());
        $this->assertSame(25.0, $destino->getPorcentajeGanancia());

        $this->assertFalse((new SqliteLote())->loadById(99999));
    }

    #[Test]
    public function find_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull(SqliteLote::find(99999));
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE lote');

        $lote = new SqliteLote([
            'id_planta'        => 1,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => 1,
            'cantidad_actual'  => 1,
        ]);

        $this->assertFalse($lote->save());
    }
}
