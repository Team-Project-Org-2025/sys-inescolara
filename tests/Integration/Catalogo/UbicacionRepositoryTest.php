<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Catalogo;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Ubicacion;
use SysInescolara\Tests\Support\SqliteTestCase;
use SysInescolara\Tests\Support\SqliteUbicacion;

final class UbicacionRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El esquema real usa `zona`; Ubicacion::tipoColumn() cae en ese
        // fallback cuando information_schema no está disponible (SQLite).
        $this->exec('
            CREATE TABLE ubicacion (
                id_ubicacion     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_ubicacion TEXT    NOT NULL,
                descripcion      TEXT    DEFAULT NULL,
                zona             TEXT    DEFAULT NULL,
                activo           INTEGER NOT NULL DEFAULT 1
            )
        ');

        // Necesaria para que Ubicacion::delete() verifique lotes vinculados.
        $this->exec('
            CREATE TABLE lote (
                id_lote      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ubicacion INTEGER DEFAULT NULL,
                activo       INTEGER NOT NULL DEFAULT 1
            )
        ');

        SqliteUbicacion::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteUbicacion::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function guardar_una_ubicacion_nueva_le_asigna_un_id(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'descripcion'      => 'Área techada',
            'tipo'             => 'zona norte',
        ]);

        $this->assertTrue($ubicacion->save());
        $this->assertNotNull($ubicacion->getId());

        $encontrada = SqliteUbicacion::find($ubicacion->getId());
        $this->assertInstanceOf(Ubicacion::class, $encontrada);
        $this->assertSame($ubicacion->getId(), $encontrada->getId());
        $this->assertSame('Invernadero', $encontrada->getNombreUbicacion());
        $this->assertSame('Área techada', $encontrada->getDescripcion());
        $this->assertTrue($encontrada->isActivo());

        // El valor de `tipo` se persiste en la columna `zona` del esquema real.
        $this->assertDatabaseHas('ubicacion', [
            'id_ubicacion' => $ubicacion->getId(),
            'zona'         => 'zona norte',
        ]);
    }

    #[Test]
    public function save_actualiza_una_ubicacion_existente(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'tipo'             => 'zona norte',
        ]);
        $this->assertTrue($ubicacion->save());

        $ubicacion->setNombreUbicacion('Galpón')
            ->setDescripcion('Depósito')
            ->setTipo('zona sur');

        $this->assertTrue($ubicacion->save());

        $fila = $ubicacion->getById($ubicacion->getId());
        $this->assertSame('Galpón', $fila['nombre_ubicacion']);
        $this->assertSame('Depósito', $fila['descripcion']);
        $this->assertSame('zona sur', $fila['tipo']);
    }

    #[Test]
    public function all_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra'] as $nombre) {
            $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => $nombre, 'tipo' => 'patio']);
            $this->assertTrue($ubicacion->save());
        }

        $idBorrada = (int) $this->pdo
            ->query("SELECT id_ubicacion FROM ubicacion WHERE nombre_ubicacion = 'Mebra'")
            ->fetchColumn();
        $this->assertTrue((new SqliteUbicacion())->delete($idBorrada));

        $filas = SqliteUbicacion::all();

        $this->assertSame(['Alfa', 'Zeta'], array_column($filas, 'nombre_ubicacion'));
        $this->assertSame(['id', 'nombre_ubicacion', 'descripcion', 'tipo', 'activo'], array_keys($filas[0]));
        $this->assertSame('patio', $filas[0]['tipo']);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'descripcion'      => 'Área techada',
            'tipo'             => 'zona norte',
        ]);
        $this->assertTrue($ubicacion->save());

        $fila = $ubicacion->getById($ubicacion->getId());
        $this->assertSame(
            ['id', 'nombre_ubicacion', 'descripcion', 'tipo'],
            array_keys($fila)
        );
        $this->assertSame($ubicacion->getId(), $fila['id']);
        $this->assertSame('Invernadero', $fila['nombre_ubicacion']);
        $this->assertSame('Área techada', $fila['descripcion']);
        $this->assertSame('zona norte', $fila['tipo']);

        $this->assertNull($ubicacion->getById(99999));
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);
        $this->assertTrue($ubicacion->save());

        $this->assertSame(SqliteUbicacion::all(), (new SqliteUbicacion())->getAll());
    }

    #[Test]
    public function getByTipo_filtra_solo_activas_del_tipo(): void
    {
        foreach ([['Invernadero', 'zona norte'], ['Patio', 'patio'], ['Galpón', 'zona norte']] as [$nombre, $tipo]) {
            $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => $nombre, 'tipo' => $tipo]);
            $this->assertTrue($ubicacion->save());
        }

        $filas = (new SqliteUbicacion())->getByTipo('zona norte');

        $this->assertSame(['Galpón', 'Invernadero'], array_column($filas, 'nombre_ubicacion'));
        $this->assertSame(['id', 'nombre_ubicacion', 'descripcion', 'tipo'], array_keys($filas[0]));
        $this->assertSame('zona norte', $filas[0]['tipo']);
    }

    #[Test]
    public function exists_refleja_si_la_ubicacion_existe(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);
        $this->assertTrue($ubicacion->save());

        $this->assertTrue($ubicacion->exists($ubicacion->getId()));
        $this->assertFalse($ubicacion->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->assertTrue($ubicacion->delete($id));
        $this->assertFalse(SqliteUbicacion::find($id)->isActivo());
        $this->assertSame([], SqliteUbicacion::all());

        $this->assertTrue($ubicacion->restore($id));
        $this->assertTrue(SqliteUbicacion::find($id)->isActivo());
        $this->assertCount(1, SqliteUbicacion::all());
    }

    #[Test]
    public function delete_lanza_excepcion_si_hay_lotes_activos_vinculados(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->insert('lote', ['id_ubicacion' => $id, 'activo' => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se puede desactivar la ubicación: Existen lotes vinculados en el inventario activo.');

        $ubicacion->delete($id);
    }

    #[Test]
    public function delete_permite_desactivar_si_los_lotes_estan_inactivos(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->insert('lote', ['id_ubicacion' => $id, 'activo' => 0]);

        $this->assertTrue($ubicacion->delete($id));
        $this->assertFalse(SqliteUbicacion::find($id)->isActivo());
    }

    #[Test]
    public function where_filtra_por_columna_solo_entre_activas(): void
    {
        foreach (['Invernadero', 'Patio', 'Invernadero'] as $nombre) {
            $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => $nombre]);
            $this->assertTrue($ubicacion->save());
        }

        $resultados = SqliteUbicacion::where('nombre_ubicacion', 'Invernadero');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Ubicacion::class, $resultado);
            $this->assertSame('Invernadero', $resultado->getNombreUbicacion());
        }
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'descripcion'      => 'Área techada',
        ]);
        $this->assertTrue($ubicacion->save());

        $destino = new SqliteUbicacion();
        $this->assertTrue($destino->loadById($ubicacion->getId()));
        $this->assertSame($ubicacion->getId(), $destino->getId());
        $this->assertSame('Invernadero', $destino->getNombreUbicacion());
        $this->assertSame('Área techada', $destino->getDescripcion());
        $this->assertTrue($destino->isActivo());

        $this->assertFalse((new SqliteUbicacion())->loadById(99999));
    }

    #[Test]
    public function find_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull(SqliteUbicacion::find(99999));
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE ubicacion');

        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero']);

        $this->assertFalse($ubicacion->save());
    }
}
