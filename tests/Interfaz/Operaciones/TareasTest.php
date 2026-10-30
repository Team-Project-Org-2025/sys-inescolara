<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class TareasTest extends InterfazTestCase
{
    #[Test]
    public function asigna_y_completa_una_tarea(): void
    {
        $name = $this->unique('Tarea', 100);

        $this->visit('dashboard/tareas');
        $this->waitForDataTable('assignmentsTable');
        $this->click('#btnAssignTask');
        $this->waitForVisibleCss('#assignTaskModal.show');

        $this->fill('#assignTaskForm [name="nombre_tarea"]', $name);
        $this->selectFirstAvailable('#assignTaskForm [name="id_usuario"]');
        $this->setValue('#assignTaskForm [name="fecha_asignacion"]', date('Y-m-d'));
        $this->fill('#assignTaskForm [name="descripcion"]', 'Tarea generada por la suite de interfaz');
        $this->click('#assignTaskForm button[type="submit"]');

        $this->waitUntilGone('#assignTaskModal.show');
        $this->assertToastContains('Tarea asignada');
        $this->waitUntilGone('.swal2-toast', 10);
        $this->waitForDataTable('assignmentsTable');
        $this->assertRowExists('assignmentsTable', $name);

        $row = $this->tableRowWithText('assignmentsTable', $name);
        $this->clickIn($row, '.btn-complete-assign');
        $this->waitForVisibleCss('#completeAssignModal.show');
        $this->setValue('#completeAssignForm [name="fecha_cumplimiento"]', date('Y-m-d'));
        $this->fill('#completeAssignForm [name="horas_dedicadas"]', '1.5');
        $this->click('#completeAssignForm button[type="submit"]');

        $this->waitUntilGone('#completeAssignModal.show');
        $this->assertToastContains('Tarea completada');
    }
}
