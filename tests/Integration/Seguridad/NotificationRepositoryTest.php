<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Seguridad;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteNotification;
use SysInescolara\Tests\Support\SqliteTestCase;

final class NotificationRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec(<<<'SQL'
            CREATE TABLE roles (
                id_rol     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_rol TEXT DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE usuarios (
                id_usuario     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario TEXT NOT NULL UNIQUE,
                password_hash  TEXT NOT NULL,
                id_rol         INTEGER DEFAULT NULL
            )
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE notificaciones (
                id_notificacion INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario      INTEGER DEFAULT NULL,
                titulo          TEXT DEFAULT NULL,
                mensaje         TEXT DEFAULT NULL,
                tipo            TEXT DEFAULT NULL,
                leida           INTEGER DEFAULT 0,
                link            TEXT DEFAULT NULL,
                fecha_creacion  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
            )
        SQL);

        $this->insert('roles', ['nombre_rol' => 'Administrador']);
        $this->insert('usuarios', [
            'nombre_usuario' => 'admin',
            'password_hash' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ]);
        $this->insert('usuarios', [
            'nombre_usuario' => 'juan',
            'password_hash' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'id_rol' => 1,
        ]);

        SqliteNotification::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteNotification::boot(null);
        parent::tearDown();
    }

    private function insertarNotificacion(
        int $usuarioId,
        string $titulo,
        string $tipo = 'info',
        int $leida = 0,
        string $fecha = '2024-01-01 10:00:00'
    ): int {
        return $this->insert('notificaciones', [
            'id_usuario' => $usuarioId,
            'titulo' => $titulo,
            'mensaje' => 'Mensaje de ' . $titulo,
            'tipo' => $tipo,
            'leida' => $leida,
            'link' => '/tareas',
            'fecha_creacion' => $fecha,
        ]);
    }

    #[Test]
    public function getUnreadCount_cuenta_solo_las_no_leidas_del_usuario(): void
    {
        $this->insertarNotificacion(1, 'A');
        $this->insertarNotificacion(1, 'B');
        $this->insertarNotificacion(1, 'C', 'info', 1);
        $this->insertarNotificacion(2, 'D');

        $this->assertSame(2, (new SqliteNotification())->getUnreadCount(1));
        $this->assertSame(1, (new SqliteNotification())->getUnreadCount(2));
        $this->assertSame(0, (new SqliteNotification())->getUnreadCount(99999));
    }

    #[Test]
    public function getRecent_devuelve_las_no_leidas_mas_recientes_respetando_el_limite(): void
    {
        $this->insertarNotificacion(1, 'Vieja', 'info', 0, '2024-01-01 08:00:00');
        $this->insertarNotificacion(1, 'Nueva', 'warning', 0, '2024-01-03 08:00:00');
        $this->insertarNotificacion(1, 'Media', 'info', 0, '2024-01-02 08:00:00');
        $this->insertarNotificacion(1, 'Leída', 'info', 1, '2024-01-04 08:00:00');

        $recientes = (new SqliteNotification())->getRecent(1, 2);

        $this->assertCount(2, $recientes);
        $this->assertSame(['Nueva', 'Media'], array_column($recientes, 'titulo'));
        $this->assertSame('warning', $recientes[0]['tipo']);
        $this->assertSame(0, $recientes[0]['leida']);
    }

    #[Test]
    public function getRecent_sin_notificaciones_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new SqliteNotification())->getRecent(1));
    }

    #[Test]
    public function getAll_no_es_portable_a_sqlite_y_degrada_a_pagina_vacia(): void
    {
        $this->insertarNotificacion(1, 'A');
        $this->insertarNotificacion(1, 'B');

        // SQL_CALC_FOUND_ROWS / FOUND_ROWS() son exclusivos de MySQL: SQLite
        // rechaza la consulta y el modelo devuelve la página vacía.
        $resultado = (new SqliteNotification())->getAll(1, 2, 1);

        $this->assertSame(
            ['data' => [], 'total' => 0, 'page' => 2, 'perPage' => 1],
            $resultado
        );
    }

    #[Test]
    public function markAsRead_marca_solo_la_notificacion_del_usuario(): void
    {
        $propia = $this->insertarNotificacion(1, 'Propia');
        $ajena = $this->insertarNotificacion(2, 'Ajena');
        $modelo = new SqliteNotification();

        $this->assertTrue($modelo->markAsRead($propia, 1));

        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $propia, 'leida' => 1]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $ajena, 'leida' => 0]);
    }

    #[Test]
    public function markAsRead_con_usuario_equivocado_no_modifica_nada(): void
    {
        $id = $this->insertarNotificacion(1, 'Propia');

        $this->assertTrue((new SqliteNotification())->markAsRead($id, 2));

        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $id, 'leida' => 0]);
    }

    #[Test]
    public function markAllAsRead_marca_todas_las_notificaciones_del_usuario(): void
    {
        $this->insertarNotificacion(1, 'A');
        $this->insertarNotificacion(1, 'B');
        $this->insertarNotificacion(2, 'C');

        $this->assertTrue((new SqliteNotification())->markAllAsRead(1));

        $this->assertDatabaseCount('notificaciones', 0, ['id_usuario' => 1, 'leida' => 0]);
        $this->assertDatabaseCount('notificaciones', 1, ['id_usuario' => 2, 'leida' => 0]);
    }

    #[Test]
    public function markAllWarningsAsRead_solo_marca_las_de_tipo_warning(): void
    {
        $this->insertarNotificacion(1, 'Alerta', 'warning');
        $this->insertarNotificacion(1, 'Info', 'info');
        $this->insertarNotificacion(2, 'Alerta ajena', 'warning');

        $this->assertTrue((new SqliteNotification())->markAllWarningsAsRead(1));

        $this->assertDatabaseCount('notificaciones', 0, ['id_usuario' => 1, 'tipo' => 'warning', 'leida' => 0]);
        $this->assertDatabaseCount('notificaciones', 1, ['id_usuario' => 1, 'tipo' => 'info', 'leida' => 0]);
        $this->assertDatabaseCount('notificaciones', 1, ['id_usuario' => 2, 'leida' => 0]);
    }

    #[Test]
    public function markTaskAssignedAsRead_no_es_portable_a_sqlite_por_el_limite_del_update(): void
    {
        $id = $this->insertarNotificacion(1, 'Nueva tarea', 'task_assigned');

        // SQLite (sin SQLITE_ENABLE_UPDATE_DELETE_LIMIT) rechaza
        // `UPDATE ... LIMIT 1`; el modelo captura el error y devuelve false.
        $resultado = (new SqliteNotification())->markTaskAssignedAsRead(1, 'Podar plantas');

        $this->assertFalse($resultado);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $id, 'leida' => 0]);
    }

    #[Test]
    public function create_inserta_la_notificacion_con_los_valores_por_defecto(): void
    {
        $this->assertTrue((new SqliteNotification())->create(1, 'Título', 'Mensaje'));

        $fila = $this->fetchRowWhere('notificaciones', ['titulo' => 'Título']);
        $this->assertNotNull($fila);
        $this->assertSame(1, (int) $fila['id_usuario']);
        $this->assertSame('Mensaje', $fila['mensaje']);
        $this->assertSame('info', $fila['tipo']);
        $this->assertSame(0, (int) $fila['leida']);
        $this->assertNull($fila['link']);
        $this->assertNotNull($fila['fecha_creacion']);
    }

    #[Test]
    public function create_guarda_el_tipo_y_el_enlace_cuando_se_indican(): void
    {
        $this->assertTrue((new SqliteNotification())->create(1, 'Alerta', null, 'warning', '/inventario'));

        $fila = $this->fetchRowWhere('notificaciones', ['titulo' => 'Alerta']);
        $this->assertNotNull($fila);
        $this->assertNull($fila['mensaje']);
        $this->assertSame('warning', $fila['tipo']);
        $this->assertSame('/inventario', $fila['link']);
    }

    #[Test]
    public function create_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE notificaciones');

        $this->assertFalse((new SqliteNotification())->create(1, 'Título'));
    }

    #[Test]
    public function existsByTitle_solo_considera_las_notificaciones_no_leidas(): void
    {
        $this->insertarNotificacion(1, 'Alerta');
        $modelo = new SqliteNotification();

        $this->assertTrue($modelo->existsByTitle(1, 'Alerta'));
        $this->assertFalse($modelo->existsByTitle(2, 'Alerta'));
        $this->assertFalse($modelo->existsByTitle(1, 'Otra'));

        $modelo->markAllAsRead(1);

        $this->assertFalse($modelo->existsByTitle(1, 'Alerta'));
    }

    #[Test]
    public function delete_elimina_solo_la_notificacion_del_usuario(): void
    {
        $propia = $this->insertarNotificacion(1, 'Propia');
        $ajena = $this->insertarNotificacion(2, 'Ajena');
        $modelo = new SqliteNotification();

        $this->assertTrue($modelo->delete($propia, 1));

        $this->assertDatabaseMissing('notificaciones', ['id_notificacion' => $propia]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $ajena]);
    }

    #[Test]
    public function delete_con_usuario_equivocado_no_elimina_nada(): void
    {
        $id = $this->insertarNotificacion(1, 'Propia');

        $this->assertTrue((new SqliteNotification())->delete($id, 2));

        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => $id]);
    }
}
