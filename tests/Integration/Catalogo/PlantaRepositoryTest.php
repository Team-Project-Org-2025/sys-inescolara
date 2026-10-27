<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Catalogo;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Planta;
use SysInescolara\Tests\Support\SqlitePlanta;
use SysInescolara\Tests\Support\SqliteTestCase;

final class PlantaRepositoryTest extends SqliteTestCase
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

        // Columnas reales de `lote` que consumen las subconsultas de Planta::all().
        $this->exec('
            CREATE TABLE lote (
                id_lote         INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta       INTEGER NOT NULL,
                cantidad_actual INTEGER NOT NULL DEFAULT 0,
                costo_unitario  NUMERIC DEFAULT 0.00,
                fecha_siembra   TEXT    NOT NULL,
                activo          INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->insert('especie', [
            'id_especie'     => 1,
            'nombre_especie' => 'Rosa',
        ]);

        SqlitePlanta::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqlitePlanta::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function guardar_una_planta_nueva_le_asigna_un_id(): void
    {
        $planta = new SqlitePlanta([
            'nombre_comun'   => 'Rosal',
            'nombre_tecnico' => 'Rosa gallica',
            'id_especie'     => 1,
            'imagen'         => 'rosal.jpg',
            'cantidad_total' => 5,
        ]);

        $this->assertTrue($planta->save());
        $this->assertNotNull($planta->getId());

        $encontrada = SqlitePlanta::find($planta->getId());
        $this->assertInstanceOf(Planta::class, $encontrada);
        $this->assertSame($planta->getId(), $encontrada->getId());
        $this->assertSame('Rosal', $encontrada->getNombreComun());
        $this->assertSame('Rosa gallica', $encontrada->getNombreTecnico());
        $this->assertSame(1, $encontrada->getIdEspecie());
        $this->assertSame('rosal.jpg', $encontrada->getImagen());
        $this->assertSame(5, $encontrada->getCantidadTotal());
        $this->assertTrue($encontrada->isActivo());
    }

    #[Test]
    public function save_actualiza_una_planta_existente(): void
    {
        $planta = new SqlitePlanta([
            'nombre_comun' => 'Rosal',
            'id_especie'   => 1,
        ]);
        $this->assertTrue($planta->save());

        $planta->setNombreComun('Clavel')
            ->setCantidadTotal(12)
            ->setImagen('clavel.jpg');

        $this->assertTrue($planta->save());

        $fila = $planta->getById($planta->getId());
        $this->assertSame('Clavel', $fila['nombre_comun']);
        $this->assertSame(12, $fila['cantidad_total']);
        $this->assertSame('clavel.jpg', $fila['imagen']);
    }

    #[Test]
    public function all_devuelve_solo_activas_con_join_y_stock_de_lotes(): void
    {
        $rosal = new SqlitePlanta(['nombre_comun' => 'Rosal', 'id_especie' => 1]);
        $this->assertTrue($rosal->save());

        $ficus = new SqlitePlanta(['nombre_comun' => 'Ficus', 'id_especie' => 1]);
        $this->assertTrue($ficus->save());

        $this->insert('lote', [
            'id_planta'       => $rosal->getId(),
            'cantidad_actual' => 7,
            'costo_unitario'  => 2.5,
            'fecha_siembra'   => '2026-01-10',
        ]);
        $this->insert('lote', [
            'id_planta'       => $rosal->getId(),
            'cantidad_actual' => 3,
            'costo_unitario'  => 4.75,
            'fecha_siembra'   => '2026-02-10',
        ]);

        $filas = SqlitePlanta::all();

        $this->assertCount(2, $filas);
        $this->assertSame(['Ficus', 'Rosal'], array_column($filas, 'nombre_comun'));
        $this->assertSame('Rosa', $filas[1]['especie_nombre']);
        $this->assertSame(10, (int) $filas[1]['stock_lotes']);
        $this->assertSame(4.75, (float) $filas[1]['precio_vigente']);
        $this->assertSame(0, (int) $filas[0]['stock_lotes']);
        $this->assertNull($filas[0]['precio_vigente']);
    }

    #[Test]
    public function getById_devuelve_la_fila_con_el_nombre_de_la_especie(): void
    {
        $planta = new SqlitePlanta([
            'nombre_comun'   => 'Rosal',
            'nombre_tecnico' => 'Rosa gallica',
            'id_especie'     => 1,
            'cantidad_total' => 4,
        ]);
        $this->assertTrue($planta->save());

        $fila = $planta->getById($planta->getId());

        $this->assertSame(
            ['id', 'nombre_comun', 'nombre_tecnico', 'especie_id', 'imagen', 'activo', 'cantidad_total', 'especie_nombre'],
            array_keys($fila)
        );
        $this->assertSame($planta->getId(), $fila['id']);
        $this->assertSame('Rosal', $fila['nombre_comun']);
        $this->assertSame(1, $fila['especie_id']);
        $this->assertSame('Rosa', $fila['especie_nombre']);

        $this->assertNull($planta->getById(99999));
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosal', 'id_especie' => 1]);
        $this->assertTrue($planta->save());

        $this->assertSame(SqlitePlanta::all(), (new SqlitePlanta())->getAll());
    }

    #[Test]
    public function exists_refleja_si_la_planta_existe(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosal', 'id_especie' => 1]);
        $this->assertTrue($planta->save());

        $this->assertTrue($planta->exists($planta->getId()));
        $this->assertFalse($planta->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosal', 'id_especie' => 1]);
        $this->assertTrue($planta->save());
        $id = $planta->getId();

        $this->assertTrue($planta->delete($id));
        $this->assertFalse(SqlitePlanta::find($id)->isActivo());
        $this->assertSame([], SqlitePlanta::all());

        $this->assertTrue($planta->restore($id));
        $this->assertTrue(SqlitePlanta::find($id)->isActivo());
        $this->assertCount(1, SqlitePlanta::all());
    }

    #[Test]
    public function where_filtra_por_columna_solo_entre_activas(): void
    {
        foreach (['Rosal', 'Ficus', 'Rosal'] as $nombre) {
            $planta = new SqlitePlanta(['nombre_comun' => $nombre, 'id_especie' => 1]);
            $this->assertTrue($planta->save());
        }

        $resultados = SqlitePlanta::where('nombre_comun', 'Rosal');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Planta::class, $resultado);
            $this->assertSame('Rosal', $resultado->getNombreComun());
        }
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $planta = new SqlitePlanta([
            'nombre_comun'   => 'Rosal',
            'nombre_tecnico' => 'Rosa gallica',
            'id_especie'     => 1,
            'imagen'         => 'rosal.jpg',
            'cantidad_total' => 6,
        ]);
        $this->assertTrue($planta->save());

        $destino = new SqlitePlanta();
        $this->assertTrue($destino->loadById($planta->getId()));
        $this->assertSame($planta->getId(), $destino->getId());
        $this->assertSame('Rosal', $destino->getNombreComun());
        $this->assertSame('Rosa gallica', $destino->getNombreTecnico());
        $this->assertSame(1, $destino->getIdEspecie());
        $this->assertSame('rosal.jpg', $destino->getImagen());
        $this->assertSame(6, $destino->getCantidadTotal());

        $this->assertFalse((new SqlitePlanta())->loadById(99999));
    }

    #[Test]
    public function find_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull(SqlitePlanta::find(99999));
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE plantas');

        $planta = new SqlitePlanta(['nombre_comun' => 'Rosal', 'id_especie' => 1]);

        $this->assertFalse($planta->save());
    }
}
