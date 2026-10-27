<?php

namespace SysInescolara\Tests\Integration\Especies;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\models\Especie;
use SysInescolara\Tests\Support\SqliteEspecie;

/**
 * Integra el modelo Especie con SQLite en memoria usando el SQL real del
 * modelo. Incluye la tabla plantas con FK fk_planta_especie para verificar
 * el comportamiento referencial del módulo.
 */
final class EspecieRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->createSchema();

        SqliteEspecie::boot($this->pdo);

        // AuditLog::record() inicia sesión si no lo está; sin usuario en la
        // sesión retorna antes de abrir la conexión de auditoría.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_cookies', '0');
            ini_set('session.cache_limiter', '');
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        SqliteEspecie::boot(null);
        $_SESSION = [];
    }

    #[Test]
    public function guardar_especie_nueva_le_asigna_un_id(): void
    {
        $especie = new SqliteEspecie([
            'nombre_especie' => 'Rosa',
            'descripcion'    => 'Arbusto perenne',
        ]);

        $this->assertTrue($especie->save());
        $this->assertNotNull($especie->getId());

        $found = SqliteEspecie::find($especie->getId());
        $this->assertInstanceOf(Especie::class, $found);
        $this->assertSame('Rosa', $found->getNombreEspecie());
        $this->assertSame('Arbusto perenne', $found->getDescripcion());
        $this->assertTrue($found->isActivo());
    }

    #[Test]
    public function save_actualiza_la_especie_existente(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $especie->setNombreEspecie('Clavel')
            ->setDescripcion('Flor ornamental');
        $this->assertTrue($especie->save());

        $found = SqliteEspecie::find($especie->getId());
        $this->assertSame('Clavel', $found->getNombreEspecie());
        $this->assertSame('Flor ornamental', $found->getDescripcion());
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
    public function getAll_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra', 'Borra'] as $nombre) {
            $especie = new SqliteEspecie(['nombre_especie' => $nombre]);
            $this->assertTrue($especie->save());
        }

        // where() no setea el id en las instancias, se obtiene de la BD
        $idBorrada = (int) $this->pdo->query("SELECT id_especie FROM especie WHERE nombre_especie = 'Borra'")->fetchColumn();
        $borrada = SqliteEspecie::where('nombre_especie', 'Borra')[0];
        $this->assertTrue($borrada->delete($idBorrada));

        $nombres = array_column((new SqliteEspecie())->getAll(), 'nombre_especie');
        $this->assertSame(['Alfa', 'Mebra', 'Zeta'], $nombres);
    }

    #[Test]
    public function getAll_devuelve_arrays_con_clave_id_para_datatables(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa', 'descripcion' => 'Desc']);
        $this->assertTrue($especie->save());

        $filas = (new SqliteEspecie())->getAll();
        $this->assertCount(1, $filas);
        $this->assertSame(['id', 'nombre_especie', 'descripcion', 'activo'], array_keys($filas[0]));
        $this->assertSame('Rosa', $filas[0]['nombre_especie']);
    }

    #[Test]
    public function all_devuelve_instancias_del_modelo(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $todas = SqliteEspecie::all();
        $this->assertCount(1, $todas);
        $this->assertInstanceOf(Especie::class, $todas[0]);
        $this->assertSame('Rosa', $todas[0]->getNombreEspecie());
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null_si_no_existe(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa', 'descripcion' => 'Desc']);
        $this->assertTrue($especie->save());

        $fila = $especie->getById($especie->getId());
        $this->assertIsArray($fila);
        $this->assertSame($especie->getId(), $fila['id']);
        $this->assertSame('Rosa', $fila['nombre_especie']);

        $this->assertNull($especie->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_la_especie_esta_registrada(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());

        $this->assertTrue($especie->exists($especie->getId()));
        $this->assertFalse($especie->exists(99999));
    }

    #[Test]
    public function delete_realiza_un_borrado_logico_y_restore_reactiva(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());
        $id = $especie->getId();

        $this->assertTrue($especie->delete($id));

        $borrada = SqliteEspecie::find($id);
        $this->assertInstanceOf(Especie::class, $borrada);
        $this->assertFalse($borrada->isActivo());
        $this->assertSame([], (new SqliteEspecie())->getAll());
        $this->assertTrue($especie->exists($id));

        $this->assertTrue($especie->restore($id));
        $this->assertTrue(SqliteEspecie::find($id)->isActivo());
        $this->assertCount(1, (new SqliteEspecie())->getAll());
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
    public function delete_de_especie_con_plantas_asociadas_no_falla_y_plantas_pierden_el_nombre(): void
    {
        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);
        $this->assertTrue($especie->save());
        $id = $especie->getId();

        $this->pdo->exec("INSERT INTO plantas (nombre_comun, id_especie) VALUES ('Rosal', $id)");

        // Soft delete: la FK no lo impide (no es DELETE físico)
        $this->assertTrue($especie->delete($id));

        // El JOIN de Planta::all() con e.activo = 1 deja de resolverse
        $fila = $this->pdo->query(
            "SELECT p.nombre_comun, e.nombre_especie
             FROM plantas p
             LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1"
        )->fetch();
        $this->assertSame('Rosal', $fila['nombre_comun']);
        $this->assertNull($fila['nombre_especie']);
    }

    #[Test]
    public function getAll_devuelve_vacio_y_registra_error_si_falta_la_tabla(): void
    {
        // Comportamiento degradado: getAll tiene try/catch y no revienta
        $this->expectErrorLog();

        $this->pdo->exec('DROP TABLE especie');

        $this->assertSame([], (new SqliteEspecie())->getAll());
    }

    #[Test]
    public function save_propaga_la_excepcion_si_falta_la_tabla(): void
    {
        // A diferencia de Planta, Especie::save() NO captura errores de BD
        $this->pdo->exec('DROP TABLE especie');

        $especie = new SqliteEspecie(['nombre_especie' => 'Rosa']);

        $this->expectException(\PDOException::class);
        $especie->save();
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE especie (
                id_especie     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie VARCHAR(150) NOT NULL,
                descripcion    TEXT DEFAULT NULL,
                activo         TINYINT(1) NOT NULL DEFAULT 1
            )
        ");
        $this->pdo->exec("
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico VARCHAR(150) DEFAULT '',
                nombre_comun   VARCHAR(150) DEFAULT NULL,
                cantidad_total INTEGER NOT NULL DEFAULT 0,
                imagen         VARCHAR(255) DEFAULT NULL,
                activo         TINYINT(1) NOT NULL DEFAULT 1,
                FOREIGN KEY (id_especie) REFERENCES especie (id_especie)
            )
        ");
    }
}
