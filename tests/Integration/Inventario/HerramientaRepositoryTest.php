<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use PDOException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Herramienta;
use SysInescolara\Tests\Support\SqliteHerramienta;
use SysInescolara\Tests\Support\SqliteTestCase;

final class HerramientaRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE herramienta (
                id_herramienta             INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_herramienta         TEXT NOT NULL,
                cantidad                   INTEGER NOT NULL DEFAULT 1,
                tipo                       TEXT DEFAULT NULL,
                estado                     TEXT NOT NULL DEFAULT \'disponible\',
                fecha_adquisicion          DATE DEFAULT NULL,
                fecha_ultimo_mantenimiento DATE DEFAULT NULL,
                observacion                TEXT DEFAULT NULL,
                activo                     INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE asignar_tarea (
                id_asignacion      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario         INTEGER NOT NULL,
                nombre_tarea       TEXT NOT NULL,
                descripcion        TEXT DEFAULT NULL,
                fecha_asignacion   DATE NOT NULL,
                fecha_cumplimiento DATE DEFAULT NULL,
                estatus_tarea      TEXT NOT NULL DEFAULT \'pendiente\',
                horas_dedicadas    DECIMAL(5,2) DEFAULT NULL
            )
        ');

        $this->exec('
            CREATE TABLE uso_herramienta (
                id_uso                      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_asignacion               INTEGER NOT NULL,
                id_herramienta              INTEGER NOT NULL,
                cantidad_usada              DECIMAL(10,2) NOT NULL DEFAULT 1.00,
                fecha_uso                   DATE NOT NULL,
                observacion                 TEXT DEFAULT NULL,
                estado_herramienta_post_uso TEXT NOT NULL DEFAULT \'ok\',
                FOREIGN KEY (id_asignacion) REFERENCES asignar_tarea (id_asignacion),
                FOREIGN KEY (id_herramienta) REFERENCES herramienta (id_herramienta)
            )
        ');

        SqliteHerramienta::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteHerramienta::boot(null);
        parent::tearDown();
    }

    private function crearHerramienta(array $atributos = []): SqliteHerramienta
    {
        $herramienta = new SqliteHerramienta($atributos + [
            'nombre_herramienta' => 'Pala',
            'cantidad'           => 3,
        ]);

        $this->assertTrue($herramienta->save());

        return $herramienta;
    }

    private function crearAsignacion(string $estatus = 'pendiente', string $nombre = 'Limpieza'): int
    {
        return $this->insert('asignar_tarea', [
            'id_usuario'       => 1,
            'nombre_tarea'     => $nombre,
            'fecha_asignacion' => '2026-01-01',
            'estatus_tarea'    => $estatus,
        ]);
    }

    #[Test]
    public function guardar_una_herramienta_nueva_le_asigna_un_id(): void
    {
        $herramienta = new SqliteHerramienta([
            'nombre_herramienta'         => 'Carretilla',
            'cantidad'                   => 2,
            'estado'                     => 'mantenimiento',
            'fecha_ultimo_mantenimiento' => '2026-02-01',
            'observacion'                => 'Rueda nueva',
        ]);

        $this->assertTrue($herramienta->save());
        $this->assertNotNull($herramienta->getId());

        $encontrada = SqliteHerramienta::find($herramienta->getId());
        $this->assertInstanceOf(Herramienta::class, $encontrada);
        $this->assertSame('Carretilla', $encontrada->getNombreHerramienta());
        $this->assertSame(2, $encontrada->getCantidad());
        $this->assertSame('mantenimiento', $encontrada->getEstado());
        $this->assertSame('2026-02-01', $encontrada->getFechaUltimoMantenimiento());
        $this->assertSame('Rueda nueva', $encontrada->getObservacion());
        $this->assertTrue($encontrada->isActivo());
    }

    #[Test]
    public function save_actualiza_una_herramienta_existente(): void
    {
        $herramienta = $this->crearHerramienta();

        $herramienta->setNombreHerramienta('Pala de punta');
        $herramienta->setCantidad(5);
        $this->assertTrue($herramienta->save());

        $fila = $herramienta->getById($herramienta->getId());
        $this->assertSame('Pala de punta', $fila['nombre_herramienta']);
        $this->assertSame(5, $fila['cantidad']);
    }

    #[Test]
    public function all_devuelve_solo_activas_ordenadas_por_nombre(): void
    {
        $this->crearHerramienta(['nombre_herramienta' => 'Zapapico', 'cantidad' => 2]);
        $this->crearHerramienta(['nombre_herramienta' => 'Azada', 'cantidad' => 4]);
        $borrada = $this->crearHerramienta(['nombre_herramienta' => 'Mazo']);
        $this->assertTrue($borrada->delete($borrada->getId()));

        $filas = SqliteHerramienta::all();

        $this->assertSame(['Azada', 'Zapapico'], array_column($filas, 'nombre_herramienta'));
        $this->assertSame(4, $filas[0]['cantidad']);
        $this->assertSame(1, (int) $filas[0]['activo']);
    }

    #[Test]
    public function getAll_es_un_alias_de_all(): void
    {
        $this->crearHerramienta(['nombre_herramienta' => 'Unica']);

        $this->assertSame(SqliteHerramienta::all(), (new SqliteHerramienta())->getAll());
    }

    #[Test]
    public function getLastInsertId_devuelve_el_id_del_ultimo_insert(): void
    {
        $herramienta = $this->crearHerramienta();

        $this->assertSame($herramienta->getId(), $herramienta->getLastInsertId());
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $herramienta = $this->crearHerramienta(['nombre_herramienta' => 'Rastrillo', 'cantidad' => 6]);

        $fila = $herramienta->getById($herramienta->getId());
        $this->assertSame($herramienta->getId(), $fila['id']);
        $this->assertSame('Rastrillo', $fila['nombre_herramienta']);
        $this->assertSame(6, $fila['cantidad']);

        $this->assertNull($herramienta->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_la_herramienta_existe(): void
    {
        $herramienta = $this->crearHerramienta();

        $this->assertTrue($herramienta->exists($herramienta->getId()));
        $this->assertFalse($herramienta->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $herramienta = $this->crearHerramienta();
        $id = $herramienta->getId();

        $this->assertTrue($herramienta->delete($id));
        $this->assertSame(0, (int) $this->fetchRow('herramienta', $id, 'id_herramienta')['activo']);
        $this->assertSame([], SqliteHerramienta::all());

        $this->assertTrue($herramienta->restore($id));
        $this->assertSame(1, (int) $this->fetchRow('herramienta', $id, 'id_herramienta')['activo']);
        $this->assertCount(1, SqliteHerramienta::all());
    }

    #[Test]
    public function where_filtra_solo_activas(): void
    {
        $this->crearHerramienta(['nombre_herramienta' => 'Pala', 'estado' => 'mantenimiento']);
        $this->crearHerramienta(['nombre_herramienta' => 'Azada', 'estado' => 'mantenimiento']);
        $this->crearHerramienta(['nombre_herramienta' => 'Mazo', 'estado' => 'disponible']);
        $inactiva = $this->crearHerramienta(['nombre_herramienta' => 'Pico', 'estado' => 'mantenimiento']);
        $inactiva->delete($inactiva->getId());

        $resultados = SqliteHerramienta::where('estado', 'mantenimiento');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Herramienta::class, $resultado);
            $this->assertSame('mantenimiento', $resultado->getEstado());
        }
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $herramienta = $this->crearHerramienta([
            'nombre_herramienta' => 'Pico',
            'cantidad'           => 2,
            'observacion'        => 'Mango de madera',
        ]);

        $destino = new SqliteHerramienta();
        $this->assertTrue($destino->loadById($herramienta->getId()));
        $this->assertSame($herramienta->getId(), $destino->getId());
        $this->assertSame('Pico', $destino->getNombreHerramienta());
        $this->assertSame(2, $destino->getCantidad());
        $this->assertSame('Mango de madera', $destino->getObservacion());

        $this->assertFalse((new SqliteHerramienta())->loadById(99999));
    }

    #[Test]
    public function recordUsageWithStateUpdate_registra_el_uso_y_actualiza_el_estado(): void
    {
        $herramienta = $this->crearHerramienta(['nombre_herramienta' => 'Pala', 'cantidad' => 3]);
        $idAsignacion = $this->crearAsignacion();

        $usoId = $herramienta->recordUsageWithStateUpdate([
            'id_asignacion'               => $idAsignacion,
            'id_herramienta'              => $herramienta->getId(),
            'cantidad'                    => 2,
            'fecha_uso'                   => '2026-01-02',
            'observacion'                 => 'Uso en invernadero',
            'estado_herramienta_post_uso' => 'mantenimiento',
        ]);

        $this->assertGreaterThan(0, $usoId);

        $uso = $this->fetchRow('uso_herramienta', $usoId, 'id_uso');
        $this->assertSame($idAsignacion, (int) $uso['id_asignacion']);
        $this->assertSame($herramienta->getId(), (int) $uso['id_herramienta']);
        $this->assertSame(2.0, (float) $uso['cantidad_usada']);
        $this->assertSame('2026-01-02', $uso['fecha_uso']);
        $this->assertSame('Uso en invernadero', $uso['observacion']);
        $this->assertSame('mantenimiento', $uso['estado_herramienta_post_uso']);

        $this->assertSame(
            'mantenimiento',
            $this->fetchRow('herramienta', $herramienta->getId(), 'id_herramienta')['estado']
        );
    }

    #[Test]
    public function recordUsageWithStateUpdate_usa_disponible_como_estado_por_defecto(): void
    {
        $herramienta = $this->crearHerramienta();
        $idAsignacion = $this->crearAsignacion();

        $usoId = $herramienta->recordUsageWithStateUpdate([
            'id_asignacion'  => $idAsignacion,
            'id_herramienta' => $herramienta->getId(),
            'fecha_uso'      => '2026-01-03',
        ]);

        $uso = $this->fetchRow('uso_herramienta', $usoId, 'id_uso');
        $this->assertSame(1.0, (float) $uso['cantidad_usada']);
        $this->assertSame('disponible', $uso['estado_herramienta_post_uso']);
        $this->assertSame(
            'disponible',
            $this->fetchRow('herramienta', $herramienta->getId(), 'id_herramienta')['estado']
        );
    }

    #[Test]
    public function recordUsageWithStateUpdate_revierte_la_transaccion_si_falla_el_insert(): void
    {
        $herramienta = $this->crearHerramienta();

        $excepcion = null;
        try {
            $herramienta->recordUsageWithStateUpdate([
                'id_asignacion'               => 99999,
                'id_herramienta'              => $herramienta->getId(),
                'fecha_uso'                   => '2026-01-04',
                'estado_herramienta_post_uso' => 'mantenimiento',
            ]);
        } catch (PDOException $e) {
            $excepcion = $e;
        }

        $this->assertInstanceOf(PDOException::class, $excepcion);
        $this->assertDatabaseCount('uso_herramienta', 0);
        $this->assertSame(
            'disponible',
            $this->fetchRow('herramienta', $herramienta->getId(), 'id_herramienta')['estado']
        );
    }

    #[Test]
    public function getUsages_devuelve_los_usos_con_el_nombre_de_la_tarea(): void
    {
        $herramienta = $this->crearHerramienta(['nombre_herramienta' => 'Pala', 'cantidad' => 5]);
        $primera = $this->crearAsignacion('pendiente', 'Siembra');
        $segunda = $this->crearAsignacion('completada', 'Riego');

        $this->insert('uso_herramienta', [
            'id_asignacion'  => $primera,
            'id_herramienta' => $herramienta->getId(),
            'cantidad_usada' => 1,
            'fecha_uso'      => '2026-01-05',
        ]);
        $this->insert('uso_herramienta', [
            'id_asignacion'  => $segunda,
            'id_herramienta' => $herramienta->getId(),
            'cantidad_usada' => 2,
            'fecha_uso'      => '2026-01-06',
        ]);

        $usos = $herramienta->getUsages($herramienta->getId());

        $this->assertCount(2, $usos);
        $this->assertSame('2026-01-06', $usos[0]['fecha_uso']);
        $this->assertSame('Riego', $usos[0]['nombre_tarea']);
        $this->assertSame('2026-01-05', $usos[1]['fecha_uso']);
        $this->assertSame('Siembra', $usos[1]['nombre_tarea']);
    }

    #[Test]
    public function getAllWithAvailability_descuenta_solo_los_usos_de_tareas_pendientes(): void
    {
        $herramienta = $this->crearHerramienta(['nombre_herramienta' => 'Pala', 'cantidad' => 5]);
        $pendiente = $this->crearAsignacion('pendiente', 'Siembra');
        $completada = $this->crearAsignacion('completada', 'Riego');

        $this->insert('uso_herramienta', [
            'id_asignacion'  => $pendiente,
            'id_herramienta' => $herramienta->getId(),
            'cantidad_usada' => 2,
            'fecha_uso'      => '2026-01-05',
        ]);
        $this->insert('uso_herramienta', [
            'id_asignacion'  => $completada,
            'id_herramienta' => $herramienta->getId(),
            'cantidad_usada' => 3,
            'fecha_uso'      => '2026-01-06',
        ]);

        $filas = $herramienta->getAllWithAvailability();

        $this->assertCount(1, $filas);
        $this->assertSame($herramienta->getId(), $filas[0]['id']);
        $this->assertSame('Pala', $filas[0]['nombre_herramienta']);
        $this->assertSame(5, $filas[0]['cantidad']);
        $this->assertSame(3, $filas[0]['disponibles']);
    }

    #[Test]
    public function getAllWithAvailability_no_devuelve_disponibles_negativos(): void
    {
        $herramienta = $this->crearHerramienta(['nombre_herramienta' => 'Pala', 'cantidad' => 1]);
        $pendiente = $this->crearAsignacion('pendiente', 'Siembra');

        $this->insert('uso_herramienta', [
            'id_asignacion'  => $pendiente,
            'id_herramienta' => $herramienta->getId(),
            'cantidad_usada' => 4,
            'fecha_uso'      => '2026-01-05',
        ]);

        $filas = $herramienta->getAllWithAvailability();

        $this->assertSame(0, $filas[0]['disponibles']);
    }

    #[Test]
    public function getAllWithAvailability_omite_las_herramientas_inactivas(): void
    {
        $activa = $this->crearHerramienta(['nombre_herramienta' => 'Azada']);
        $inactiva = $this->crearHerramienta(['nombre_herramienta' => 'Pico']);
        $inactiva->delete($inactiva->getId());

        $filas = $activa->getAllWithAvailability();

        $this->assertSame(['Azada'], array_column($filas, 'nombre_herramienta'));
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE uso_herramienta');
        $this->exec('DROP TABLE herramienta');

        $herramienta = new SqliteHerramienta(['nombre_herramienta' => 'Pala']);

        $this->assertFalse($herramienta->save());
    }
}
