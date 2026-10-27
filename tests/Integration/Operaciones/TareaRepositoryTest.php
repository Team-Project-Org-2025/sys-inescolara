<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteTarea;
use SysInescolara\Tests\Support\SqliteTestCase;

final class TareaRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->attachSecurityDatabase();

        $this->exec('
            CREATE TABLE "SysInescolara-Seguridad".usuarios (
                id_usuario          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario      TEXT DEFAULT NULL,
                nombre_trabajador   TEXT DEFAULT NULL,
                apellido_trabajador TEXT DEFAULT NULL
            )
        ');

        $this->exec('
            CREATE TABLE asignar_tarea (
                id_asignacion      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario         INTEGER NOT NULL,
                nombre_tarea       TEXT NOT NULL,
                descripcion        TEXT DEFAULT NULL,
                fecha_asignacion   TEXT NOT NULL,
                fecha_cumplimiento TEXT DEFAULT NULL,
                estatus_tarea      TEXT NOT NULL DEFAULT \'pendiente\',
                horas_dedicadas    REAL DEFAULT NULL
            )
        ');

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
            CREATE TABLE uso_herramienta (
                id_uso                      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_asignacion               INTEGER NOT NULL,
                id_herramienta              INTEGER NOT NULL,
                cantidad_usada              REAL NOT NULL DEFAULT 1,
                fecha_uso                   TEXT NOT NULL,
                observacion                 TEXT DEFAULT NULL,
                estado_herramienta_post_uso TEXT NOT NULL DEFAULT \'ok\'
            )
        ');

        $this->exec('
            CREATE TABLE unidad_medida (
                id_unidad_medida     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_unidad_medida TEXT NOT NULL,
                simbolo              TEXT DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE insumo (
                id_insumo              INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida       INTEGER NOT NULL,
                nombre_insumo          TEXT NOT NULL,
                categoria              TEXT DEFAULT NULL,
                stock_actual           REAL NOT NULL DEFAULT 0,
                costo_unitario_actual  REAL NOT NULL DEFAULT 0,
                activo                 INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE registro_insumo (
                id_registro_insumo INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lote            INTEGER DEFAULT NULL,
                id_insumo          INTEGER NOT NULL,
                id_asignacion      INTEGER DEFAULT NULL,
                cantidad           REAL NOT NULL,
                costo_unitario     REAL NOT NULL,
                fecha_registro     TEXT NOT NULL
            )
        ');

        SqliteTarea::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteTarea::boot(null);

        parent::tearDown();
    }

    #[Test]
    public function assign_task_crea_la_asignacion_pendiente(): void
    {
        $id = (new SqliteTarea())->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Riego de almácigos',
            'descripcion'      => 'Regar los almácigos del sector norte',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $this->assertSame(1, $id);
        $this->assertDatabaseHas('asignar_tarea', [
            'id_asignacion'  => 1,
            'id_usuario'     => 1,
            'nombre_tarea'   => 'Riego de almácigos',
            'estatus_tarea'  => 'pendiente',
            'fecha_asignacion' => '2026-02-01',
        ]);
    }

    #[Test]
    public function assign_task_registra_el_uso_de_herramientas(): void
    {
        $idHerramienta = $this->crearHerramienta('Pala', 5);

        $id = (new SqliteTarea())->assignTask(
            [
                'id_usuario'       => 1,
                'nombre_tarea'     => 'Preparación de terreno',
                'fecha_asignacion' => '2026-02-01',
            ],
            [[
                'id_herramienta' => $idHerramienta,
                'cantidad'       => 2,
                'fecha_uso'      => '2026-02-01',
                'observacion'    => 'Pala en buen estado',
            ]]
        );

        $uso = $this->fetchRowWhere('uso_herramienta', ['id_asignacion' => $id]);

        $this->assertNotNull($uso);
        $this->assertSame($idHerramienta, (int) $uso['id_herramienta']);
        $this->assertSame(2.0, (float) $uso['cantidad_usada']);
        $this->assertSame('ok', $uso['estado_herramienta_post_uso']);
        $this->assertSame('Pala en buen estado', $uso['observacion']);
    }

    #[Test]
    public function assign_task_revierte_todo_si_la_herramienta_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Herramienta ID 999 no encontrada.');

        try {
            (new SqliteTarea())->assignTask(
                [
                    'id_usuario'       => 1,
                    'nombre_tarea'     => 'Tarea inválida',
                    'fecha_asignacion' => '2026-02-01',
                ],
                [['id_herramienta' => 999, 'cantidad' => 1]]
            );
        } finally {
            $this->assertDatabaseCount('asignar_tarea', 0);
            $this->assertDatabaseCount('uso_herramienta', 0);
        }
    }

    #[Test]
    public function assign_task_rechaza_cantidades_superiores_al_inventario(): void
    {
        $idHerramienta = $this->crearHerramienta('Rastrillo', 1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "No hay suficientes unidades de la herramienta ID {$idHerramienta}. Disponibles: 1, solicitadas: 3."
        );

        try {
            (new SqliteTarea())->assignTask(
                [
                    'id_usuario'       => 1,
                    'nombre_tarea'     => 'Rastrillar',
                    'fecha_asignacion' => '2026-02-01',
                ],
                [['id_herramienta' => $idHerramienta, 'cantidad' => 3]]
            );
        } finally {
            $this->assertDatabaseCount('asignar_tarea', 0);
        }
    }

    #[Test]
    public function get_assignments_incluye_los_datos_del_usuario_y_ordena_por_fecha_descendente(): void
    {
        $this->crearUsuario(1, 'aperez', 'Ana', 'Pérez');

        $tarea = new SqliteTarea();
        $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Tarea antigua',
            'fecha_asignacion' => '2026-01-01',
        ]);
        $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Tarea reciente',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $asignaciones = $tarea->getAssignments();

        $this->assertCount(2, $asignaciones);
        $this->assertSame([2, 1], array_map(
            static fn (array $fila): int => (int) $fila['id_asignacion'],
            $asignaciones
        ));
        $this->assertSame('Ana', $asignaciones[0]['nombre_trabajador']);
        $this->assertSame('Pérez', $asignaciones[0]['apellido_trabajador']);
        $this->assertSame('aperez', $asignaciones[0]['nombre_usuario']);
    }

    #[Test]
    public function get_assignment_by_id_devuelve_la_fila_o_null(): void
    {
        $this->crearUsuario(7, 'jrojas', 'José', 'Rojas');

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask([
            'id_usuario'       => 7,
            'nombre_tarea'     => 'Inventario',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $fila = $tarea->getAssignmentById($id);

        $this->assertNotNull($fila);
        $this->assertSame('Inventario', $fila['nombre_tarea']);
        $this->assertSame('José', $fila['nombre_trabajador']);
        $this->assertNull($tarea->getAssignmentById(999));
    }

    #[Test]
    public function update_assignment_reemplaza_datos_y_herramientas(): void
    {
        $pala = $this->crearHerramienta('Pala', 5);
        $carretilla = $this->crearHerramienta('Carretilla', 3);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask(
            [
                'id_usuario'       => 1,
                'nombre_tarea'     => 'Tarea original',
                'descripcion'      => 'Descripción original',
                'fecha_asignacion' => '2026-02-01',
            ],
            [['id_herramienta' => $pala, 'cantidad' => 1]]
        );

        $tarea->updateAssignment(
            $id,
            [
                'id_usuario'       => 2,
                'nombre_tarea'     => 'Tarea corregida',
                'descripcion'      => null,
                'fecha_asignacion' => '2026-03-01',
            ],
            [['id_herramienta' => $carretilla, 'cantidad' => 2]]
        );

        $this->assertDatabaseHas('asignar_tarea', [
            'id_asignacion'    => $id,
            'id_usuario'       => 2,
            'nombre_tarea'     => 'Tarea corregida',
            'fecha_asignacion' => '2026-03-01',
        ]);
        $this->assertDatabaseCount('uso_herramienta', 1);
        $this->assertDatabaseHas('uso_herramienta', [
            'id_asignacion'  => $id,
            'id_herramienta' => $carretilla,
        ]);
    }

    #[Test]
    public function update_assignment_falla_si_la_asignacion_no_existe(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Asignación no encontrada: 999');

        (new SqliteTarea())->updateAssignment(999, [
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Inexistente',
            'fecha_asignacion' => '2026-02-01',
        ]);
    }

    #[Test]
    public function get_consumptions_une_insumo_y_unidad_de_medida(): void
    {
        $this->insert('unidad_medida', [
            'nombre_unidad_medida' => 'Kilogramo',
            'simbolo'              => 'kg',
        ]);
        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida'      => 1,
            'nombre_insumo'         => 'Abono orgánico',
            'stock_actual'          => 20,
            'costo_unitario_actual' => 3.5,
        ]);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Fertilización',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $this->insert('registro_insumo', [
            'id_insumo'      => $idInsumo,
            'id_asignacion'  => $id,
            'cantidad'       => 1,
            'costo_unitario' => 3.5,
            'fecha_registro' => '2026-02-01',
        ]);
        $this->insert('registro_insumo', [
            'id_insumo'      => $idInsumo,
            'id_asignacion'  => $id,
            'cantidad'       => 2,
            'costo_unitario' => 3.5,
            'fecha_registro' => '2026-02-05',
        ]);

        $consumos = $tarea->getConsumptions($id);

        $this->assertCount(2, $consumos);
        $this->assertSame('2026-02-05', $consumos[0]['fecha_registro']);
        $this->assertSame('Abono orgánico', $consumos[0]['nombre_insumo']);
        $this->assertSame('kg', $consumos[0]['simbolo']);
    }

    #[Test]
    public function get_tool_usages_une_el_nombre_de_la_herramienta(): void
    {
        $idHerramienta = $this->crearHerramienta('Azada', 4);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask(
            [
                'id_usuario'       => 1,
                'nombre_tarea'     => 'Deshierbe',
                'fecha_asignacion' => '2026-02-01',
            ],
            [['id_herramienta' => $idHerramienta, 'cantidad' => 1, 'fecha_uso' => '2026-02-01']]
        );

        $usos = $tarea->getToolUsages($id);

        $this->assertCount(1, $usos);
        $this->assertSame('Azada', $usos[0]['nombre_herramienta']);
        $this->assertSame($idHerramienta, (int) $usos[0]['id_herramienta']);
    }

    #[Test]
    public function update_tool_estados_actualiza_el_uso_y_la_herramienta(): void
    {
        $idHerramienta = $this->crearHerramienta('Machete', 2);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask(
            [
                'id_usuario'       => 1,
                'nombre_tarea'     => 'Limpieza',
                'fecha_asignacion' => '2026-02-01',
            ],
            [['id_herramienta' => $idHerramienta, 'cantidad' => 1]]
        );

        $uso = $this->fetchRowWhere('uso_herramienta', ['id_asignacion' => $id]);
        $this->assertNotNull($uso);

        $tarea->updateToolEstados($id, [
            ['id_uso' => (int) $uso['id_uso'], 'estado' => 'mantenimiento'],
            ['id_uso' => 0, 'estado' => 'ignorado'],
        ]);

        $this->assertDatabaseHas('uso_herramienta', [
            'id_uso'                      => (int) $uso['id_uso'],
            'estado_herramienta_post_uso' => 'mantenimiento',
        ]);
        $this->assertDatabaseHas('herramienta', [
            'id_herramienta' => $idHerramienta,
            'estado'         => 'mantenimiento',
        ]);
    }

    #[Test]
    public function complete_assignment_cierra_la_tarea_y_descuenta_insumos(): void
    {
        $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Kilogramo', 'simbolo' => 'kg']);
        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida'      => 1,
            'nombre_insumo'         => 'Semilla',
            'stock_actual'          => 10,
            'costo_unitario_actual' => 2.5,
        ]);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Siembra',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $tarea->completeAssignment($id, '2026-02-10', 8.5, [
            ['id_insumo' => $idInsumo, 'cantidad' => 4],
        ]);

        $this->assertDatabaseHas('asignar_tarea', [
            'id_asignacion'      => $id,
            'estatus_tarea'      => 'completada',
            'fecha_cumplimiento' => '2026-02-10',
        ]);

        $asignacion = $this->fetchRow('asignar_tarea', $id, 'id_asignacion');
        $this->assertSame(8.5, (float) $asignacion['horas_dedicadas']);

        $consumo = $this->fetchRowWhere('registro_insumo', ['id_asignacion' => $id]);
        $this->assertNotNull($consumo);
        $this->assertSame(4.0, (float) $consumo['cantidad']);
        $this->assertSame(2.5, (float) $consumo['costo_unitario']);
        $this->assertSame(date('Y-m-d'), $consumo['fecha_registro']);
        $this->assertNull($consumo['id_lote']);

        $insumo = $this->fetchRow('insumo', $idInsumo, 'id_insumo');
        $this->assertSame(6.0, (float) $insumo['stock_actual']);
    }

    #[Test]
    public function complete_assignment_actualiza_el_estado_de_las_herramientas(): void
    {
        $idHerramienta = $this->crearHerramienta('Tijera', 2);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask(
            [
                'id_usuario'       => 1,
                'nombre_tarea'     => 'Poda',
                'fecha_asignacion' => '2026-02-01',
            ],
            [['id_herramienta' => $idHerramienta, 'cantidad' => 1]]
        );

        $uso = $this->fetchRowWhere('uso_herramienta', ['id_asignacion' => $id]);
        $this->assertNotNull($uso);

        $tarea->completeAssignment($id, '2026-02-11', 3.0, [], [
            ['id_uso' => (int) $uso['id_uso'], 'estado' => 'desgastada'],
        ]);

        $this->assertDatabaseHas('uso_herramienta', [
            'id_uso'                      => (int) $uso['id_uso'],
            'estado_herramienta_post_uso' => 'desgastada',
        ]);
        $this->assertDatabaseHas('herramienta', [
            'id_herramienta' => $idHerramienta,
            'estado'         => 'desgastada',
        ]);
    }

    #[Test]
    public function complete_assignment_revierte_si_no_hay_stock_suficiente(): void
    {
        $this->insert('unidad_medida', ['nombre_unidad_medida' => 'Unidad', 'simbolo' => 'ud']);
        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida'      => 1,
            'nombre_insumo'         => 'Maceta',
            'stock_actual'          => 2,
            'costo_unitario_actual' => 1.0,
        ]);

        $tarea = new SqliteTarea();
        $id = $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Trasplante',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Stock insuficiente para insumo ID {$idInsumo}. Disponible: 2, solicitado: 5."
        );

        try {
            $tarea->completeAssignment($id, '2026-02-12', 2.0, [
                ['id_insumo' => $idInsumo, 'cantidad' => 5],
            ]);
        } finally {
            $this->assertDatabaseHas('asignar_tarea', [
                'id_asignacion' => $id,
                'estatus_tarea' => 'pendiente',
            ]);
            $this->assertDatabaseCount('registro_insumo', 0);
        }
    }

    #[Test]
    public function cancel_assignment_cambia_el_estatus(): void
    {
        $tarea = new SqliteTarea();
        $id = $tarea->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Tarea cancelable',
            'fecha_asignacion' => '2026-02-01',
        ]);

        $tarea->cancelAssignment($id);

        $this->assertDatabaseHas('asignar_tarea', [
            'id_asignacion' => $id,
            'estatus_tarea' => 'cancelada',
        ]);
    }

    private function crearUsuario(int $id, string $usuario, string $nombre, string $apellido): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO "SysInescolara-Seguridad".usuarios
                (id_usuario, nombre_usuario, nombre_trabajador, apellido_trabajador)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, $usuario, $nombre, $apellido]);
    }

    private function crearHerramienta(string $nombre, int $cantidad, string $estado = 'disponible'): int
    {
        return $this->insert('herramienta', [
            'nombre_herramienta' => $nombre,
            'cantidad'           => $cantidad,
            'estado'             => $estado,
        ]);
    }
}
