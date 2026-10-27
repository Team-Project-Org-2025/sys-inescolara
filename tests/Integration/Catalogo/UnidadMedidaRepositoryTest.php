<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Catalogo;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\UnidadMedida;
use SysInescolara\Tests\Support\SqliteTestCase;
use SysInescolara\Tests\Support\SqliteUnidadMedida;

final class UnidadMedidaRepositoryTest extends SqliteTestCase
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

        SqliteUnidadMedida::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteUnidadMedida::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function guardar_una_unidad_nueva_le_asigna_un_id(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Kilogramo']);

        $this->assertTrue($unidad->save());
        $this->assertNotNull($unidad->getId());

        $encontrada = SqliteUnidadMedida::find($unidad->getId());
        $this->assertInstanceOf(UnidadMedida::class, $encontrada);
        $this->assertSame('Kilogramo', $encontrada->getNombreUnidadMedida());
        $this->assertTrue($encontrada->isActivo());
    }

    #[Test]
    public function save_actualiza_una_unidad_existente(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Litro']);
        $this->assertTrue($unidad->save());

        $unidad->setNombreUnidadMedida('Mililitro');
        $this->assertTrue($unidad->save());

        $fila = $unidad->getById($unidad->getId());
        $this->assertSame('Mililitro', $fila['nombre_unidad_medida']);
    }

    #[Test]
    public function all_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra'] as $nombre) {
            $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => $nombre]);
            $this->assertTrue($unidad->save());
        }

        $idBorrada = (int) $this->pdo->query("SELECT id_unidad_medida FROM unidad_medida WHERE nombre_unidad_medida = 'Mebra'")->fetchColumn();
        (new SqliteUnidadMedida())->delete($idBorrada);

        $nombres = array_column(SqliteUnidadMedida::all(), 'nombre_unidad_medida');
        $this->assertSame(['Alfa', 'Zeta'], $nombres);
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Unidad']);
        $this->assertTrue($unidad->save());

        $this->assertSame(SqliteUnidadMedida::all(), (new SqliteUnidadMedida())->getAll());
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Bulto']);
        $this->assertTrue($unidad->save());

        $fila = $unidad->getById($unidad->getId());
        $this->assertSame($unidad->getId(), $fila['id']);
        $this->assertSame('Bulto', $fila['nombre_unidad_medida']);

        $this->assertNull($unidad->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_la_unidad_existe(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Guacal']);
        $this->assertTrue($unidad->save());

        $this->assertTrue($unidad->exists($unidad->getId()));
        $this->assertFalse($unidad->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Metro']);
        $this->assertTrue($unidad->save());
        $id = $unidad->getId();

        $this->assertTrue($unidad->delete($id));
        $this->assertFalse(SqliteUnidadMedida::find($id)->isActivo());
        $this->assertSame([], SqliteUnidadMedida::all());

        $this->assertTrue($unidad->restore($id));
        $this->assertTrue(SqliteUnidadMedida::find($id)->isActivo());
        $this->assertCount(1, SqliteUnidadMedida::all());
    }

    #[Test]
    public function where_filtra_solo_activas(): void
    {
        foreach (['Rosa', 'Rosa', 'Otra'] as $nombre) {
            $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => $nombre]);
            $this->assertTrue($unidad->save());
        }

        $resultados = SqliteUnidadMedida::where('nombre_unidad_medida', 'Rosa');
        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(UnidadMedida::class, $resultado);
            $this->assertSame('Rosa', $resultado->getNombreUnidadMedida());
        }
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Gramo']);
        $this->assertTrue($unidad->save());

        $destino = new SqliteUnidadMedida();
        $this->assertTrue($destino->loadById($unidad->getId()));
        $this->assertSame($unidad->getId(), $destino->getId());
        $this->assertSame('Gramo', $destino->getNombreUnidadMedida());

        $this->assertFalse((new SqliteUnidadMedida())->loadById(99999));
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE unidad_medida');

        $unidad = new SqliteUnidadMedida(['nombre_unidad_medida' => 'Hectarea']);

        $this->assertFalse($unidad->save());
    }
}
