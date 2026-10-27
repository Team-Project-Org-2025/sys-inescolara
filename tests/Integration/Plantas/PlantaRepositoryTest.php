<?php

namespace SysInescolara\Tests\Integration\Plantas;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\models\Planta;
use SysInescolara\Tests\Support\SqlitePlanta;

/**
 * Integra el modelo Planta con una base SQLite en memoria usando el mismo
 * SQL real del modelo (INSERT/UPDATE/SELECT con JOIN y subconsultas).
 * No requiere MySQL: SqlitePlanta inyecta el PDO y omite la conexión inicial.
 */
final class PlantaRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->createSchema();
        $this->seed();

        SqlitePlanta::boot($this->pdo);

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
        SqlitePlanta::boot(null);
        $_SESSION = [];
    }

    #[Test]
    public function guardar_planta_nueva_le_asigna_un_id(): void
    {
        $planta = new SqlitePlanta([
            'nombre_comun'   => 'Rosa',
            'nombre_tecnico' => 'Rosa damascena',
            'id_especie'     => 1,
        ]);

        $this->assertTrue($planta->save());
        $this->assertNotNull($planta->getId());

        $found = SqlitePlanta::find($planta->getId());
        $this->assertInstanceOf(Planta::class, $found);
        $this->assertSame('Rosa', $found->getNombreComun());
        $this->assertSame('Rosa damascena', $found->getNombreTecnico());
        $this->assertSame(1, $found->getIdEspecie());
        $this->assertTrue($found->isActivo());
    }

    #[Test]
    public function save_actualiza_la_planta_existente(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1]);
        $this->assertTrue($planta->save());

        $planta->setNombreComun('Clavel')
            ->setNombreTecnico('Clavel officinalis')
            ->setCantidadTotal(5);
        $this->assertTrue($planta->save());

        $found = SqlitePlanta::find($planta->getId());
        $this->assertSame('Clavel', $found->getNombreComun());
        $this->assertSame('Clavel officinalis', $found->getNombreTecnico());
        $this->assertSame(5, $found->getCantidadTotal());
    }

    #[Test]
    public function getAll_devuelve_solo_plantas_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra', 'Borra'] as $nombre) {
            $planta = new SqlitePlanta(['nombre_comun' => $nombre, 'id_especie' => 1]);
            $this->assertTrue($planta->save());
        }

        // where() no setea el id en las instancias, se obtiene de la BD
        $idBorrada = (int) $this->pdo->query("SELECT id_planta FROM plantas WHERE nombre_comun = 'Borra'")->fetchColumn();
        $borrada = SqlitePlanta::where('nombre_comun', 'Borra')[0];
        $this->assertTrue($borrada->delete($idBorrada));

        $nombres = array_column((new SqlitePlanta())->getAll(), 'nombre_comun');
        $this->assertSame(['Alfa', 'Mebra', 'Zeta'], $nombres);
    }

    #[Test]
    public function getAll_incluye_especie_stock_y_precio_vigente(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1]);
        $this->assertTrue($planta->save());
        $id = $planta->getId();

        $this->pdo->exec("INSERT INTO lote (id_planta, cantidad_actual, costo_unitario, fecha_siembra, activo)
                          VALUES ($id, 3, 10.50, '2026-01-15', 1)");
        $this->pdo->exec("INSERT INTO lote (id_planta, cantidad_actual, costo_unitario, fecha_siembra, activo)
                          VALUES ($id, 4, 12.75, '2026-02-20', 1)");
        $this->pdo->exec("INSERT INTO lote (id_planta, cantidad_actual, costo_unitario, fecha_siembra, activo)
                          VALUES ($id, 99, 99.99, '2026-03-01', 0)");

        $rows = SqlitePlanta::all();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('Rosa', $row['nombre_comun']);
        $this->assertSame('Rosa de Castilla', $row['especie_nombre']);
        $this->assertEquals(7, $row['stock_lotes']);
        $this->assertEquals(12.75, $row['precio_vigente']);
    }

    #[Test]
    public function getAll_con_especie_inactiva_deja_especie_nombre_en_null(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Misterio', 'id_especie' => 3]);
        $this->assertTrue($planta->save());

        $rows = SqlitePlanta::all();
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['especie_nombre']);
        $this->assertSame(3, $rows[0]['especie_id']);
    }

    #[Test]
    public function getById_devuelve_la_fila_con_el_join_o_null_si_no_existe(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1]);
        $this->assertTrue($planta->save());

        $fila = $planta->getById($planta->getId());
        $this->assertIsArray($fila);
        $this->assertSame('Rosa', $fila['nombre_comun']);
        $this->assertSame('Rosa de Castilla', $fila['especie_nombre']);

        $this->assertNull($planta->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_la_planta_esta_registrada(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1]);
        $this->assertTrue($planta->save());

        $this->assertTrue($planta->exists($planta->getId()));
        $this->assertFalse($planta->exists(99999));
    }

    #[Test]
    public function delete_realiza_un_borrado_logico_y_restore_reactiva(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1]);
        $this->assertTrue($planta->save());
        $id = $planta->getId();

        $this->assertTrue($planta->delete($id));

        $borrada = SqlitePlanta::find($id);
        $this->assertInstanceOf(Planta::class, $borrada);
        $this->assertFalse($borrada->isActivo());
        $this->assertNotContains('Rosa', array_column(SqlitePlanta::all(), 'nombre_comun'));
        $this->assertTrue($planta->exists($id));

        $this->assertTrue($planta->restore($id));
        $this->assertTrue(SqlitePlanta::find($id)->isActivo());
        $this->assertContains('Rosa', array_column(SqlitePlanta::all(), 'nombre_comun'));
    }

    #[Test]
    public function where_filtra_por_columna_solo_entre_activas(): void
    {
        foreach (['Rosa', 'Mango', 'Rosa'] as $nombre) {
            $planta = new SqlitePlanta(['nombre_comun' => $nombre, 'id_especie' => 1]);
            $this->assertTrue($planta->save());
        }

        $resultados = SqlitePlanta::where('nombre_comun', 'Rosa');
        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Planta::class, $resultado);
            $this->assertSame('Rosa', $resultado->getNombreComun());
        }
    }

    #[Test]
    public function save_devuelve_false_si_se_viola_la_fk_de_especie(): void
    {
        // save() debe registrar la violación con error_log() sin propagarla
        $this->expectErrorLog();

        $planta = new SqlitePlanta([
            'nombre_comun' => 'Huerfana',
            'id_especie'   => 999,
        ]);

        $this->assertFalse($planta->save());
        $this->assertNull(SqlitePlanta::find(1));
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $planta = new SqlitePlanta(['nombre_comun' => 'Rosa', 'id_especie' => 1, 'cantidad_total' => 4]);
        $this->assertTrue($planta->save());

        $destino = new SqlitePlanta();
        $this->assertTrue($destino->loadById($planta->getId()));
        $this->assertSame($planta->getId(), $destino->getId());
        $this->assertSame('Rosa', $destino->getNombreComun());
        $this->assertSame(4, $destino->getCantidadTotal());

        $this->assertFalse((new SqlitePlanta())->loadById(99999));
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE especie (
                id_especie    INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie VARCHAR(150),
                activo        TINYINT(1) NOT NULL DEFAULT 1
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
        $this->pdo->exec("
            CREATE TABLE lote (
                id_lote         INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta       INTEGER NOT NULL,
                cantidad_actual INTEGER NOT NULL DEFAULT 0,
                costo_unitario  REAL DEFAULT NULL,
                fecha_siembra   DATE DEFAULT NULL,
                activo          TINYINT(1) NOT NULL DEFAULT 1,
                FOREIGN KEY (id_planta) REFERENCES plantas (id_planta)
            )
        ");
    }

    private function seed(): void
    {
        $this->pdo->exec("INSERT INTO especie (id_especie, nombre_especie, activo) VALUES (1, 'Rosa de Castilla', 1)");
        $this->pdo->exec("INSERT INTO especie (id_especie, nombre_especie, activo) VALUES (2, 'Mango Criollo', 1)");
        $this->pdo->exec("INSERT INTO especie (id_especie, nombre_especie, activo) VALUES (3, 'Secreto', 0)");
    }
}
