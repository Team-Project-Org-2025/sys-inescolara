<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Ornato;
use SysInescolara\Tests\Support\SqliteOrnato;
use SysInescolara\Tests\Support\SqliteTestCase;

final class OrnatoRepositoryTest extends SqliteTestCase
{
    private int $clienteId;
    private int $loteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE cliente (
                id_cliente          INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_cedula_cliente TEXT DEFAULT NULL,
                cedula_cliente      TEXT DEFAULT NULL,
                nombre_cliente      TEXT NOT NULL,
                apellido_cliente    TEXT DEFAULT \'\',
                contacto_cliente    TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1
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
                nombre_tecnico TEXT DEFAULT \'\',
                nombre_comun   TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE lote (
                id_lote         INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta       INTEGER NOT NULL,
                cantidad_actual INTEGER NOT NULL DEFAULT 0,
                activo          INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (id_planta) REFERENCES plantas (id_planta)
            )
        ');

        $this->exec('
            CREATE TABLE ornatos (
                id_ornato     INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cliente    INTEGER NOT NULL,
                tipo_ornato   TEXT NOT NULL DEFAULT \'Venta\',
                descripcion   TEXT DEFAULT NULL,
                ubicacion     TEXT DEFAULT NULL,
                monto_total   REAL DEFAULT 0.00,
                fecha         TEXT NOT NULL,
                activo        INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
            )
        ');

        $this->exec('
            CREATE TABLE detalle_ornatos (
                id_detalle_ornato INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ornato         INTEGER NOT NULL,
                id_lote           INTEGER DEFAULT NULL,
                cantidad          INTEGER NOT NULL,
                precio_unitario   REAL DEFAULT NULL,
                sub_total         REAL DEFAULT NULL,
                FOREIGN KEY (id_ornato) REFERENCES ornatos (id_ornato) ON DELETE CASCADE,
                FOREIGN KEY (id_lote) REFERENCES lote (id_lote)
            )
        ');

        $this->clienteId = $this->insert('cliente', [
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
            'nombre_cliente'      => 'Ana',
            'apellido_cliente'    => 'Pérez',
            'contacto_cliente'    => '04141234567',
            'activo'              => 1,
        ]);

        $especieId = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $plantaId = $this->insert('plantas', [
            'id_especie'     => $especieId,
            'nombre_tecnico' => 'Rosa spp',
            'nombre_comun'   => 'Rosa roja',
            'activo'         => 1,
        ]);
        $this->loteId = $this->insert('lote', [
            'id_planta'       => $plantaId,
            'cantidad_actual' => 10,
            'activo'          => 1,
        ]);

        SqliteOrnato::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteOrnato::boot(null);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function crearOrnato(array $datos = []): SqliteOrnato
    {
        $ornato = new SqliteOrnato();
        $this->assertTrue($ornato->agregar($datos + [
            'id_cliente'  => $this->clienteId,
            'tipo_ornato' => 'Venta',
            'descripcion' => 'Arreglo floral',
            'ubicacion'   => 'Entrada principal',
            'monto_total' => 150.0,
            'fecha'       => '2026-10-27',
        ]));

        return $ornato;
    }

    #[Test]
    public function agregar_crea_el_ornato_y_asigna_id(): void
    {
        $ornato = $this->crearOrnato();

        $this->assertNotNull($ornato->getId());

        $fila = $this->fetchRow('ornatos', $ornato->getId(), 'id_ornato');
        $this->assertNotNull($fila);
        $this->assertSame($this->clienteId, (int) $fila['id_cliente']);
        $this->assertSame('Venta', $fila['tipo_ornato']);
        $this->assertSame('Arreglo floral', $fila['descripcion']);
        $this->assertSame('Entrada principal', $fila['ubicacion']);
        $this->assertSame(150.0, (float) $fila['monto_total']);
        $this->assertSame('2026-10-27', $fila['fecha']);
        $this->assertSame(1, (int) $fila['activo']);
    }

    #[Test]
    public function agregar_sin_cliente_lanza_excepcion_de_validacion(): void
    {
        $ornato = new SqliteOrnato();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_cliente');

        $ornato->agregar(['fecha' => '2026-10-27', 'monto_total' => 10.0]);
    }

    #[Test]
    public function save_actualiza_un_ornato_existente(): void
    {
        $ornato = $this->crearOrnato();

        $ornato->setDescripcion('Arreglo actualizado')->setMontoTotal(200.0);
        $this->assertTrue($ornato->save());

        $fila = $ornato->getById($ornato->getId());
        $this->assertNotNull($fila);
        $this->assertSame('Arreglo actualizado', $fila['descripcion']);
        $this->assertSame(200.0, (float) $fila['monto_total']);
        $this->assertSame(1, $this->countRows('ornatos'));
    }

    #[Test]
    public function agregarDetalles_inserta_los_detalles(): void
    {
        $ornato = $this->crearOrnato();
        $id = $ornato->getId();

        $this->assertTrue($ornato->agregarDetalles($id, [
            ['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 50.0, 'sub_total' => 100.0],
            ['id_lote' => $this->loteId, 'cantidad' => 1, 'precio_unitario' => 30.0, 'sub_total' => 30.0],
        ]));

        $this->assertSame(2, $this->countRows('detalle_ornatos'));

        $detalles = $ornato->obtenerDetalles($id);
        $this->assertCount(2, $detalles);
        $this->assertSame('Rosa roja', $detalles[0]['planta_nombre']);
        $this->assertSame('Rosa', $detalles[0]['especie_nombre']);
        $this->assertSame(2, (int) $detalles[0]['cantidad']);
        $this->assertSame(100.0, (float) $detalles[0]['sub_total']);
        $this->assertSame(10, (int) $detalles[0]['cantidad_actual']);
    }

    #[Test]
    public function agregarDetalles_devuelve_false_si_el_lote_no_existe(): void
    {
        $ornato = $this->crearOrnato();

        $this->assertFalse($ornato->agregarDetalles($ornato->getId(), [
            ['id_lote' => 9999, 'cantidad' => 1, 'precio_unitario' => 10.0, 'sub_total' => 10.0],
        ]));

        $this->assertSame(0, $this->countRows('detalle_ornatos'));
    }

    #[Test]
    public function actualizar_modifica_los_datos_del_ornato(): void
    {
        $ornato = $this->crearOrnato();
        $id = $ornato->getId();

        $this->assertTrue($ornato->actualizar($id, [
            'descripcion' => 'Centro de mesa',
            'ubicacion'   => 'Salón',
            'monto_total' => 99.5,
        ]));

        $fila = $ornato->getById($id);
        $this->assertNotNull($fila);
        $this->assertSame('Centro de mesa', $fila['descripcion']);
        $this->assertSame('Salón', $fila['ubicacion']);
        $this->assertSame(99.5, (float) $fila['monto_total']);
    }

    #[Test]
    public function actualizar_un_ornato_inexistente_lanza_excepcion(): void
    {
        $ornato = new SqliteOrnato();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe el ornato con ID: 999');

        $ornato->actualizar(999, ['descripcion' => 'No existe']);
    }

    #[Test]
    public function actualizarDetalles_reemplaza_los_items(): void
    {
        $ornato = $this->crearOrnato();
        $id = $ornato->getId();

        $this->assertTrue($ornato->agregarDetalles($id, [
            ['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 50.0, 'sub_total' => 100.0],
        ]));

        $this->assertTrue($ornato->actualizarDetalles($id, [
            ['id_lote' => $this->loteId, 'cantidad' => 5, 'precio_unitario' => 20.0, 'sub_total' => 100.0],
        ]));

        $detalles = $ornato->obtenerDetalles($id);
        $this->assertCount(1, $detalles);
        $this->assertSame(5, (int) $detalles[0]['cantidad']);
        $this->assertSame(20.0, (float) $detalles[0]['precio_unitario']);
    }

    #[Test]
    public function getAll_devuelve_solo_activos_con_el_nombre_del_cliente(): void
    {
        $primero = $this->crearOrnato(['fecha' => '2026-10-01']);
        $segundo = $this->crearOrnato(['fecha' => '2026-10-02', 'descripcion' => 'Segundo']);

        $this->assertTrue($segundo->delete($segundo->getId()));

        $filas = (new SqliteOrnato())->getAll();

        $this->assertCount(1, $filas);
        $this->assertSame($primero->getId(), (int) $filas[0]['id']);
        $this->assertSame('Ana Pérez', $filas[0]['nombre_cliente']);
        $this->assertSame('12345678', $filas[0]['cedula_cliente']);
        $this->assertCount(1, SqliteOrnato::all());
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $ornato = $this->crearOrnato();

        $fila = (new SqliteOrnato())->getById($ornato->getId());

        $this->assertNotNull($fila);
        $this->assertSame($ornato->getId(), (int) $fila['id_ornato']);
        $this->assertSame('Ana Pérez', $fila['nombre_cliente']);
        $this->assertSame('Arreglo floral', $fila['descripcion']);

        $this->assertNull((new SqliteOrnato())->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_ornato_existe(): void
    {
        $ornato = $this->crearOrnato();

        $this->assertTrue($ornato->exists($ornato->getId()));
        $this->assertFalse($ornato->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $ornato = $this->crearOrnato();
        $id = $ornato->getId();

        $this->assertTrue($ornato->delete($id));
        $this->assertSame(0, (int) $this->fetchRow('ornatos', $id, 'id_ornato')['activo']);
        $this->assertSame([], (new SqliteOrnato())->getAll());

        $this->assertTrue($ornato->restore($id));
        $this->assertSame(1, (int) $this->fetchRow('ornatos', $id, 'id_ornato')['activo']);
        $this->assertCount(1, (new SqliteOrnato())->getAll());
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $ornato = $this->crearOrnato();

        $destino = new SqliteOrnato();
        $this->assertTrue($destino->loadById($ornato->getId()));

        $this->assertSame($ornato->getId(), $destino->getId());
        $this->assertSame($this->clienteId, $destino->getIdCliente());
        $this->assertSame('Venta', $destino->getTipoOrnato());
        $this->assertSame('Arreglo floral', $destino->getDescripcion());
        $this->assertSame('Entrada principal', $destino->getUbicacion());
        $this->assertSame(150.0, $destino->getMontoTotal());
        $this->assertSame('2026-10-27', $destino->getFecha());
        $this->assertTrue($destino->isActivo());

        $this->assertFalse((new SqliteOrnato())->loadById(99999));
    }

    #[Test]
    public function find_devuelve_el_ornato_o_null(): void
    {
        $ornato = $this->crearOrnato();

        $encontrado = SqliteOrnato::find($ornato->getId());

        $this->assertInstanceOf(Ornato::class, $encontrado);
        $this->assertSame($ornato->getId(), $encontrado->getId());
        $this->assertSame('Arreglo floral', $encontrado->getDescripcion());

        $this->assertNull(SqliteOrnato::find(99999));
    }

    #[Test]
    public function where_filtra_por_columna_y_solo_activos(): void
    {
        $this->crearOrnato(['tipo_ornato' => 'Donacion', 'descripcion' => 'Donación uno']);
        $segundo = $this->crearOrnato(['tipo_ornato' => 'Donacion', 'descripcion' => 'Donación dos']);
        $this->crearOrnato(['tipo_ornato' => 'Venta']);

        $this->assertTrue($segundo->delete($segundo->getId()));

        $resultados = SqliteOrnato::where('tipo_ornato', 'Donacion');

        $this->assertCount(1, $resultados);
        $this->assertInstanceOf(Ornato::class, $resultados[0]);
        $this->assertSame('Donacion', $resultados[0]->getTipoOrnato());
        $this->assertSame('Donación uno', $resultados[0]->getDescripcion());
    }

    #[Test]
    public function buscarClientes_filtra_por_nombre(): void
    {
        $ornato = new SqliteOrnato();

        $resultados = $ornato->buscarClientes('Ana');

        $this->assertCount(1, $resultados);
        $this->assertSame('Ana Pérez', $resultados[0]['nombre_cliente']);
        $this->assertSame('04141234567', $resultados[0]['contacto_cliente']);

        $this->assertSame([], $ornato->buscarClientes('NoExiste'));
    }

    #[Test]
    public function obtenerUltimoId_devuelve_el_ultimo_id_insertado(): void
    {
        $ornato = $this->crearOrnato();

        $this->assertSame($ornato->getId(), (new SqliteOrnato())->obtenerUltimoId());
    }
}
