<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Inventario;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteTestCase;
use SysInescolara\Tests\Support\SqliteUsoHerramienta;

final class UsoHerramientaRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE herramienta (
                id_herramienta     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_herramienta TEXT NOT NULL,
                cantidad           INTEGER NOT NULL DEFAULT 1,
                tipo               TEXT DEFAULT NULL,
                estado             TEXT NOT NULL DEFAULT \'disponible\',
                activo             INTEGER NOT NULL DEFAULT 1
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

        $this->insert('herramienta', [
            'nombre_herramienta' => 'Pala',
            'cantidad'           => 3,
            'tipo'               => 'manual',
            'estado'             => 'disponible',
            'activo'             => 1,
        ]);
        $this->insert('asignar_tarea', [
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Siembra',
            'fecha_asignacion' => '2026-01-01',
            'estatus_tarea'    => 'pendiente',
        ]);
        $this->insert('asignar_tarea', [
            'id_usuario'       => 2,
            'nombre_tarea'     => 'Riego',
            'fecha_asignacion' => '2026-01-02',
            'estatus_tarea'    => 'completada',
        ]);

        SqliteUsoHerramienta::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteUsoHerramienta::boot(null);
        parent::tearDown();
    }

    private function crearUso(array $atributos = []): int
    {
        return $this->insert('uso_herramienta', $atributos + [
            'id_asignacion'               => 1,
            'id_herramienta'              => 1,
            'cantidad_usada'              => 1,
            'fecha_uso'                   => '2026-02-01',
            'observacion'                 => null,
            'estado_herramienta_post_uso' => 'ok',
        ]);
    }

    #[Test]
    public function getAll_devuelve_los_usos_con_herramienta_y_tarea(): void
    {
        $this->crearUso(['fecha_uso' => '2026-02-01']);
        $this->crearUso(['id_asignacion' => 2, 'fecha_uso' => '2026-02-05']);

        $filas = (new SqliteUsoHerramienta())->getAll();

        $this->assertCount(2, $filas);
        $this->assertSame('2026-02-05', $filas[0]['fecha_uso']);
        $this->assertSame('Pala', $filas[0]['nombre_herramienta']);
        $this->assertSame('manual', $filas[0]['tipo']);
        $this->assertSame('Riego', $filas[0]['nombre_tarea']);
        $this->assertSame('Siembra', $filas[1]['nombre_tarea']);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $idUso = $this->crearUso(['cantidad_usada' => 2.5, 'observacion' => 'Uso puntual']);

        $fila = (new SqliteUsoHerramienta())->getById($idUso);

        $this->assertSame($idUso, (int) $fila['id_uso']);
        $this->assertSame(1, (int) $fila['id_asignacion']);
        $this->assertSame(1, (int) $fila['id_herramienta']);
        $this->assertSame(2.5, (float) $fila['cantidad_usada']);
        $this->assertSame('Uso puntual', $fila['observacion']);

        $this->assertNull((new SqliteUsoHerramienta())->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_uso_existe(): void
    {
        $idUso = $this->crearUso();
        $uso = new SqliteUsoHerramienta();

        $this->assertTrue($uso->exists($idUso));
        $this->assertFalse($uso->exists(99999));
    }

    #[Test]
    public function getByAssignment_filtra_por_asignacion(): void
    {
        $this->crearUso(['id_asignacion' => 1, 'fecha_uso' => '2026-02-01']);
        $this->crearUso(['id_asignacion' => 1, 'fecha_uso' => '2026-02-03']);
        $this->crearUso(['id_asignacion' => 2, 'fecha_uso' => '2026-02-02']);

        $filas = (new SqliteUsoHerramienta())->getByAssignment(1);

        $this->assertCount(2, $filas);
        $this->assertSame('2026-02-03', $filas[0]['fecha_uso']);
        $this->assertSame('2026-02-01', $filas[1]['fecha_uso']);
        foreach ($filas as $fila) {
            $this->assertSame(1, (int) $fila['id_asignacion']);
            $this->assertSame('Pala', $fila['nombre_herramienta']);
        }
    }

    #[Test]
    public function getByAssignment_devuelve_lista_vacia_si_no_hay_usos(): void
    {
        $this->assertSame([], (new SqliteUsoHerramienta())->getByAssignment(99));
    }

    #[Test]
    public function add_inserta_el_uso_con_el_estado_por_defecto(): void
    {
        $uso = new SqliteUsoHerramienta();

        $this->assertTrue($uso->add([
            'id_asignacion'  => 1,
            'id_herramienta' => 1,
            'fecha_uso'      => '2026-03-01',
        ]));

        $fila = $this->fetchRowWhere('uso_herramienta', ['fecha_uso' => '2026-03-01']);

        $this->assertNotNull($fila);
        $this->assertSame(1, (int) $fila['id_asignacion']);
        $this->assertSame(1, (int) $fila['id_herramienta']);
        $this->assertSame(1.0, (float) $fila['cantidad_usada']);
        $this->assertNull($fila['observacion']);
        $this->assertSame('ok', $fila['estado_herramienta_post_uso']);
    }

    #[Test]
    public function add_inserta_los_campos_opcionales(): void
    {
        $uso = new SqliteUsoHerramienta();

        $this->assertTrue($uso->add([
            'id_asignacion'               => 2,
            'id_herramienta'              => 1,
            'fecha_uso'                   => '2026-03-02',
            'observacion'                 => 'Se devolvio limpia',
            'estado_herramienta_post_uso' => 'mantenimiento',
        ]));

        $fila = $this->fetchRowWhere('uso_herramienta', ['fecha_uso' => '2026-03-02']);

        $this->assertNotNull($fila);
        $this->assertSame('Se devolvio limpia', $fila['observacion']);
        $this->assertSame('mantenimiento', $fila['estado_herramienta_post_uso']);
    }

    #[Test]
    public function add_sin_datos_requeridos_lanza_excepcion_de_validacion(): void
    {
        $uso = new SqliteUsoHerramienta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_asignacion');

        $uso->add([]);
    }

    #[Test]
    public function delete_elimina_fisicamente_el_uso(): void
    {
        $idUso = $this->crearUso();
        $uso = new SqliteUsoHerramienta();

        $this->assertTrue($uso->delete($idUso));

        $this->assertNull($this->fetchRow('uso_herramienta', $idUso, 'id_uso'));
        $this->assertFalse($uso->exists($idUso));
    }

    #[Test]
    public function delete_devuelve_true_aunque_el_uso_no_exista(): void
    {
        $this->assertTrue((new SqliteUsoHerramienta())->delete(99999));
        $this->assertDatabaseCount('uso_herramienta', 0);
    }
}
