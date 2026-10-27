<?php

namespace SysInescolara\Tests\Integration\Ubicaciones;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\models\Ubicacion;
use SysInescolara\Tests\Support\SqliteUbicacion;

/**
 * Integra el modelo Ubicacion con SQLite en memoria usando el SQL real del
 * modelo. Incluye la tabla lote (fk_lote_ubicacion) para ejercitar el
 * bloqueo de delete cuando hay lotes activos vinculados.
 *
 * Ojo: en SQLite no existe information_schema, así que tipoColumn() cae en
 * 'zona' (la columna real del esquema definitivo). El esquema de prueba usa
 * `zona`, igual que producción.
 */
final class UbicacionRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->createSchema();

        SqliteUbicacion::boot($this->pdo);

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
        SqliteUbicacion::boot(null);
        $_SESSION = [];
    }

    #[Test]
    public function guardar_ubicacion_nueva_le_asigna_un_id(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'descripcion'      => 'Filas 1-4',
            'tipo'             => 'Interno',
        ]);

        $this->assertTrue($ubicacion->save());
        $this->assertNotNull($ubicacion->getId());

        $found = SqliteUbicacion::find($ubicacion->getId());
        $this->assertInstanceOf(Ubicacion::class, $found);
        $this->assertSame('Invernadero A', $found->getNombreUbicacion());
        $this->assertSame('Filas 1-4', $found->getDescripcion());
        $this->assertTrue($found->isActivo());

        // La zona persistió en la columna real
        $zona = $this->pdo->query("SELECT zona FROM ubicacion")->fetchColumn();
        $this->assertSame('Interno', $zona);
    }

    #[Test]
    public function find_no_mapea_la_columna_zona_porque_no_es_fillable(): void
    {
        // Comportamiento documentado: SELECT * trae 'zona', pero fill() solo
        // acepta 'tipo' (fillable), así que find() pierde el tipo. Los
        // flujos del controlador no lo notan porque reasignan desde POST;
        // all() y getById() sí lo aliasan como 'tipo'.
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'tipo'             => 'Externo',
        ]);
        $this->assertTrue($ubicacion->save());

        $found = SqliteUbicacion::find($ubicacion->getId());
        $this->assertNull($found->getTipo());

        $todas = SqliteUbicacion::all();
        $this->assertSame('Externo', $todas[0]['tipo']);
    }

    #[Test]
    public function save_actualiza_la_ubicacion_existente(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertTrue($ubicacion->save());

        $ubicacion->setNombreUbicacion('Invernadero B')
            ->setDescripcion('Filas 5-8')
            ->setTipo('Externo');
        $this->assertTrue($ubicacion->save());

        $found = SqliteUbicacion::find($ubicacion->getId());
        $this->assertSame('Invernadero B', $found->getNombreUbicacion());
        $this->assertSame('Filas 5-8', $found->getDescripcion());
        $this->assertSame(
            'Externo',
            $this->pdo->query("SELECT zona FROM ubicacion")->fetchColumn()
        );
    }

    #[Test]
    public function save_con_descripcion_null_limpia_el_campo(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'descripcion'      => 'Texto temporal',
        ]);
        $this->assertTrue($ubicacion->save());

        $ubicacion->setDescripcion(null);
        $this->assertTrue($ubicacion->save());

        $fila = $ubicacion->getById($ubicacion->getId());
        $this->assertNull($fila['descripcion']);
    }

    #[Test]
    public function save_con_tipo_null_limpia_la_zona(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'tipo'             => 'Interno',
        ]);
        $this->assertTrue($ubicacion->save());

        $ubicacion->setTipo(null);
        $this->assertTrue($ubicacion->save());

        $this->assertNull($ubicacion->getById($ubicacion->getId())['tipo']);
    }

    #[Test]
    public function getAll_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        foreach (['Zeta', 'Almacén', 'Bodega', 'Alfa'] as $nombre) {
            $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => $nombre]);
            $this->assertTrue($ubicacion->save());
        }

        // where() no setea el id en las instancias, se obtiene de la BD
        $idBorrada = (int) $this->pdo->query("SELECT id_ubicacion FROM ubicacion WHERE nombre_ubicacion = 'Bodega'")->fetchColumn();
        $borrada = SqliteUbicacion::where('nombre_ubicacion', 'Bodega')[0];
        $this->assertTrue($borrada->delete($idBorrada));

        $nombres = array_column((new SqliteUbicacion())->getAll(), 'nombre_ubicacion');
        $this->assertSame(['Alfa', 'Almacén', 'Zeta'], $nombres);
    }

    #[Test]
    public function getAll_devuelve_arrays_con_clave_id_y_tipo_aliasado(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'descripcion'      => 'Desc',
            'tipo'             => 'Interno',
        ]);
        $this->assertTrue($ubicacion->save());

        $filas = (new SqliteUbicacion())->getAll();
        $this->assertCount(1, $filas);
        $this->assertSame(
            ['id', 'nombre_ubicacion', 'descripcion', 'tipo', 'activo'],
            array_keys($filas[0])
        );
        $this->assertSame('Invernadero A', $filas[0]['nombre_ubicacion']);
        $this->assertSame('Interno', $filas[0]['tipo']);
    }

    #[Test]
    public function all_devuelve_filas_con_id_y_tipo_aliasados(): void
    {
        // A diferencia de Especie, Ubicacion::all() devuelve arrays planos
        // (FETCH_ASSOC) con id_ubicacion AS id y zona AS tipo.
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A', 'tipo' => 'Interno']);
        $this->assertTrue($ubicacion->save());

        $todas = SqliteUbicacion::all();
        $this->assertCount(1, $todas);
        $this->assertIsArray($todas[0]);
        $this->assertSame($ubicacion->getId(), $todas[0]['id']);
        $this->assertSame('Invernadero A', $todas[0]['nombre_ubicacion']);
        $this->assertSame('Interno', $todas[0]['tipo']);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null_si_no_existe(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'descripcion'      => 'Desc',
            'tipo'             => 'Externo',
        ]);
        $this->assertTrue($ubicacion->save());

        $fila = $ubicacion->getById($ubicacion->getId());
        $this->assertIsArray($fila);
        $this->assertSame($ubicacion->getId(), $fila['id']);
        $this->assertSame('Invernadero A', $fila['nombre_ubicacion']);
        $this->assertSame('Externo', $fila['tipo']);

        $this->assertNull($ubicacion->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_la_ubicacion_esta_registrada(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertTrue($ubicacion->save());

        $this->assertTrue($ubicacion->exists($ubicacion->getId()));
        $this->assertFalse($ubicacion->exists(99999));
    }

    #[Test]
    public function delete_realiza_un_borrado_logico_y_restore_reactiva(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->assertTrue($ubicacion->delete($id));

        $borrada = SqliteUbicacion::find($id);
        $this->assertInstanceOf(Ubicacion::class, $borrada);
        $this->assertFalse($borrada->isActivo());
        $this->assertSame([], (new SqliteUbicacion())->getAll());
        $this->assertTrue($ubicacion->exists($id));

        $this->assertTrue($ubicacion->restore($id));
        $this->assertTrue(SqliteUbicacion::find($id)->isActivo());
        $this->assertCount(1, (new SqliteUbicacion())->getAll());
    }

    #[Test]
    public function delete_con_lotes_activos_lanza_excepcion_con_mensaje_especifico(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->pdo->exec("INSERT INTO lote (id_ubicacion, activo) VALUES ($id, 1)");

        // delete() registra el error con error_log antes de re-lanzar
        $this->expectErrorLog();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'No se puede desactivar la ubicación: Existen lotes vinculados en el inventario activo.'
        );

        $ubicacion->delete($id);
    }

    #[Test]
    public function delete_con_lote_inactivo_no_bloquea_la_desactivacion(): void
    {
        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertTrue($ubicacion->save());
        $id = $ubicacion->getId();

        $this->pdo->exec("INSERT INTO lote (id_ubicacion, activo) VALUES ($id, 0)");

        $this->assertTrue($ubicacion->delete($id));
        $this->assertFalse(SqliteUbicacion::find($id)->isActivo());
    }

    #[Test]
    public function where_filtra_por_columna_solo_entre_activas(): void
    {
        foreach (['Invernadero', 'Bodega', 'Invernadero'] as $nombre) {
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
    public function getByTipo_filtra_por_la_columna_zona(): void
    {
        $interna = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A', 'tipo' => 'Interno']);
        $this->assertTrue($interna->save());
        $externa = new SqliteUbicacion(['nombre_ubicacion' => 'Bodega', 'tipo' => 'Externo']);
        $this->assertTrue($externa->save());
        $sinTipo = new SqliteUbicacion(['nombre_ubicacion' => 'Patio']);
        $this->assertTrue($sinTipo->save());

        $resultados = (new SqliteUbicacion())->getByTipo('Interno');
        $this->assertCount(1, $resultados);
        $this->assertSame('Invernadero A', $resultados[0]['nombre_ubicacion']);
        $this->assertSame('Interno', $resultados[0]['tipo']);
    }

    #[Test]
    public function loadById_carga_los_atributos_en_la_instancia(): void
    {
        $ubicacion = new SqliteUbicacion([
            'nombre_ubicacion' => 'Invernadero A',
            'descripcion'      => 'Filas 1-4',
        ]);
        $this->assertTrue($ubicacion->save());

        $otra = new SqliteUbicacion();
        $this->assertTrue($otra->loadById($ubicacion->getId()));
        $this->assertSame('Invernadero A', $otra->getNombreUbicacion());
        $this->assertSame('Filas 1-4', $otra->getDescripcion());

        $this->assertFalse($otra->loadById(99999));
    }

    #[Test]
    public function save_devuelve_false_si_falta_la_tabla(): void
    {
        $this->expectErrorLog();

        $this->pdo->exec('DROP TABLE ubicacion');

        $ubicacion = new SqliteUbicacion(['nombre_ubicacion' => 'Invernadero A']);
        $this->assertFalse($ubicacion->save());
    }

    #[Test]
    public function getAll_propaga_excepcion_si_falta_la_tabla(): void
    {
        // all() NO tiene try/catch: a diferencia de save(), el error escapa
        $this->pdo->exec('DROP TABLE ubicacion');

        $this->expectException(\PDOException::class);
        (new SqliteUbicacion())->getAll();
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE ubicacion (
                id_ubicacion     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_ubicacion VARCHAR(100) NOT NULL,
                descripcion      VARCHAR(255) DEFAULT NULL,
                zona             VARCHAR(50) DEFAULT NULL,
                activo           TINYINT(1) NOT NULL DEFAULT 1
            )
        ");
        $this->pdo->exec("
            CREATE TABLE lote (
                id_lote       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_ubicacion  INTEGER DEFAULT NULL,
                activo        TINYINT(1) NOT NULL DEFAULT 1,
                FOREIGN KEY (id_ubicacion) REFERENCES ubicacion (id_ubicacion)
            )
        ");
    }
}
