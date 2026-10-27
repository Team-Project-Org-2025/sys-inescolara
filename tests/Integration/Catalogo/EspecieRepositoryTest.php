<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Catalogo;

use PDOException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Especie;
use SysInescolara\Tests\Support\SqliteEspecie;
use SysInescolara\Tests\Support\SqliteTestCase;

final class EspecieRepositoryTest extends SqliteTestCase
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

        SqliteEspecie::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteEspecie::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function guardar_una_especie_nueva_le_asigna_un_id(): void
    {
        $especie = new SqliteEspecie([
            'nombre_especie' => 'Rosa',
            'descripcion'    => 'Arbusto perenne',
        ]);

        $this->assertTrue($especie->save());
        $this->assertNotNull($especie->getId());

        $encontrada = SqliteEspecie::find($especie->getId());
        $this->assertInstanceOf(Especie::class, $encontrada);
        $this->assertSame($especie->getId(), $encontrada->getId());
        $this->assertSame('Rosa', $encontrada->getNombreEspecie());
        $this->assertSame('Arbusto perenne', $encontrada->getDescripcion());
        $this->assertTrue($encontrada->isActivo());
    }

    #[Test]
    public function save_actualiza_una_especie_existente(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $especie->setNombreEspecie('Clavel')
            ->setDescripcion('Flor ornamental');

        $this->assertTrue($especie->save());

        $fila = $especie->getById($especie->getId());
        $this->assertSame('Clavel', $fila['nombre_especie']);
        $this->assertSame('Flor ornamental', $fila['descripcion']);
    }

    #[Test]
    public function save_con_descripcion_null_limpia_el_campo(): void
    {
        $especie = new SqliteEspecie([
            'nombre_especie' => 'Rosa',
            'descripcion'    => 'Texto temporal',
        ]);
        $this->assertTrue($especie->save());

        $especie->setDescripcion(null);
        $this->assertTrue($especie->save());

        $fila = $especie->getById($especie->getId());
        $this->assertNull($fila['descripcion']);
    }

    #[Test]
    public function all_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra', 'Borra'] as $nombre) {
            $especie = new SqliteEspecie(['nombre_especie' => $nombre]);
            $this->assertTrue($especie->save());
        }

        $idBorrada = (int) $this->pdo
            ->query("SELECT id_especie FROM especie WHERE nombre_especie = 'Borra'")
            ->fetchColumn();
        $this->assertTrue((new SqliteEspecie())->delete($idBorrada));

        $nombres = array_map(
            static fn (Especie $especie): string => $especie->getNombreEspecie(),
            SqliteEspecie::all()
        );

        $this->assertSame(['Alfa', 'Mebra', 'Zeta'], $nombres);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa', 'descripcion' => 'Desc']);
        $this->assertTrue($especie->save());

        $fila = $especie->getById($especie->getId());
        $this->assertSame(
            ['id', 'nombre_especie', 'descripcion', 'activo'],
            array_keys($fila)
        );
        $this->assertSame($especie->getId(), $fila['id']);
        $this->assertSame('Rosa', $fila['nombre_especie']);
        $this->assertSame('Desc', $fila['descripcion']);
        $this->assertSame(1, $fila['activo']);

        $this->assertNull($especie->getById(99999));
    }

    #[Test]
    public function getAll_devuelve_arreglos_activos_ordenados(): void
    {
        foreach (['Zeta', 'Alfa'] as $nombre) {
            $especie = new SqliteEspecie(['nombre_especie' => $nombre]);
            $this->assertTrue($especie->save());
        }

        $filas = (new SqliteEspecie())->getAll();

        $this->assertCount(2, $filas);
        $this->assertSame(['id', 'nombre_especie', 'descripcion', 'activo'], array_keys($filas[0]));
        $this->assertSame(['Alfa', 'Zeta'], array_column($filas, 'nombre_especie'));
    }

    #[Test]
    public function exists_refleja_si_la_especie_existe(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $this->assertTrue($especie->exists($especie->getId()));
        $this->assertFalse($especie->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());
        $id = $especie->getId();

        $this->assertTrue($especie->delete($id));
        $this->assertFalse(SqliteEspecie::find($id)->isActivo());
        $this->assertSame([], SqliteEspecie::all());
        $this->assertTrue($especie->exists($id));

        $this->assertTrue($especie->restore($id));
        $this->assertTrue(SqliteEspecie::find($id)->isActivo());
        $this->assertCount(1, SqliteEspecie::all());
    }

    #[Test]
    public function where_filtra_por_columna_solo_entre_activas(): void
    {
        foreach (['Rosa', 'Mango', 'Rosa'] as $nombre) {
            $especie = new SqliteEspecie(['nombre_especie' => $nombre]);
            $this->assertTrue($especie->save());
        }

        $resultados = SqliteEspecie::where('nombre_especie', 'Rosa');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Especie::class, $resultado);
            $this->assertSame('Rosa', $resultado->getNombreEspecie());
        }
    }

    #[Test]
    public function find_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull(SqliteEspecie::find(99999));
    }

    #[Test]
    public function getLastInsertId_devuelve_el_ultimo_id_insertado(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $this->assertSame($especie->getId(), $especie->getLastInsertId());
    }

    #[Test]
    public function save_propaga_pdoexception_si_la_tabla_no_existe(): void
    {
        // Especie::save() no captura errores de BD.
        $this->exec('DROP TABLE especie');

        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);

        $this->expectException(PDOException::class);
        $especie->save();
    }
}
