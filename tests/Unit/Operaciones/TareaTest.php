<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Operaciones;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbTarea;

/**
 * Tarea no declara atributos, getters, setters ni `fill()`: toda su lógica
 * vive en SQL. Las pruebas unitarias verifican que ningún método pueda
 * ejecutarse sin una conexión a base de datos.
 */
final class TareaTest extends TestCase
{
    #[Test]
    public function no_expone_atributos_ni_metodos_de_relleno(): void
    {
        $tarea = new NoDbTarea();

        $this->assertSame([], get_object_vars($tarea));
        $this->assertFalse(method_exists($tarea, 'fill'));
        $this->assertFalse(method_exists($tarea, 'getId'));
        $this->assertFalse(method_exists($tarea, 'save'));
    }

    #[Test]
    public function assign_task_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbTarea())->assignTask([
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Riego',
            'fecha_asignacion' => '2026-01-10',
        ]);
    }

    #[Test]
    public function update_assignment_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->updateAssignment(1, [
            'id_usuario'       => 1,
            'nombre_tarea'     => 'Riego',
            'fecha_asignacion' => '2026-01-10',
        ]);
    }

    #[Test]
    public function get_assignments_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->getAssignments();
    }

    #[Test]
    public function get_assignment_by_id_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->getAssignmentById(1);
    }

    #[Test]
    public function get_consumptions_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->getConsumptions(1);
    }

    #[Test]
    public function get_tool_usages_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->getToolUsages(1);
    }

    #[Test]
    public function update_tool_estados_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->updateToolEstados(1, [['id_uso' => 1, 'estado' => 'ok']]);
    }

    #[Test]
    public function complete_assignment_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->completeAssignment(1, '2026-01-15', 8.0);
    }

    #[Test]
    public function cancel_assignment_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTarea())->cancelAssignment(1);
    }
}
