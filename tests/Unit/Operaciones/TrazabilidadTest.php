<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Operaciones;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbTrazabilidad;

/**
 * Trazabilidad::save() resuelve el nombre del estado con `new Lote()`, que
 * abre MySQL; por eso la ruta de guardado con datos válidos no es portable
 * al doble sin base de datos y solo se cubre la validación previa.
 */
final class TrazabilidadTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $trazabilidad = new NoDbTrazabilidad();

        $this->assertNull($trazabilidad->getId());
        $this->assertNull($trazabilidad->getIdLote());
        $this->assertSame(0, $trazabilidad->getCantidad());
        $this->assertNull($trazabilidad->getIdEstado());
        $this->assertNull($trazabilidad->getFechaRegistro());
        $this->assertNull($trazabilidad->getObservacion());
        $this->assertTrue($trazabilidad->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $trazabilidad = new NoDbTrazabilidad();
        $trazabilidad->fill([
            'id_trazabilidad' => 99,
            'id_lote'         => 3,
            'cantidad'        => 12,
            'id_estado'       => 6,
            'fecha_registro'  => '2026-01-05',
            'observacion'     => ' Revisión ',
            'activo'          => 0,
            'desconocido'     => 'ignorado',
        ]);

        $this->assertNull($trazabilidad->getId());
        $this->assertSame(3, $trazabilidad->getIdLote());
        $this->assertSame(12, $trazabilidad->getCantidad());
        $this->assertSame(6, $trazabilidad->getIdEstado());
        $this->assertSame('2026-01-05', $trazabilidad->getFechaRegistro());
        $this->assertSame(' Revisión ', $trazabilidad->getObservacion());
        $this->assertFalse($trazabilidad->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_valores(): void
    {
        $trazabilidad = new NoDbTrazabilidad();

        $this->assertSame($trazabilidad, $trazabilidad->setIdLote(7));
        $this->assertSame(7, $trazabilidad->getIdLote());

        $this->assertSame($trazabilidad, $trazabilidad->setCantidad(-5));
        $this->assertSame(0, $trazabilidad->getCantidad());

        $trazabilidad->setCantidad(4);
        $this->assertSame(4, $trazabilidad->getCantidad());

        $this->assertSame($trazabilidad, $trazabilidad->setIdEstado(6));
        $this->assertSame(6, $trazabilidad->getIdEstado());

        $this->assertSame($trazabilidad, $trazabilidad->setFechaRegistro('2026-02-01'));
        $this->assertSame('2026-02-01', $trazabilidad->getFechaRegistro());

        $this->assertSame($trazabilidad, $trazabilidad->setObservacion('  húmedo  '));
        $this->assertSame('húmedo', $trazabilidad->getObservacion());

        $trazabilidad->setObservacion('');
        $this->assertNull($trazabilidad->getObservacion());

        $this->assertSame($trazabilidad, $trazabilidad->setActivo(false));
        $this->assertFalse($trazabilidad->isActivo());
    }

    #[Test]
    public function save_sin_datos_lanza_excepcion_de_validacion(): void
    {
        $trazabilidad = new NoDbTrazabilidad();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_lote');

        $trazabilidad->save();
    }

    #[Test]
    public function save_sin_fecha_de_registro_lanza_excepcion_de_validacion(): void
    {
        $trazabilidad = new NoDbTrazabilidad([
            'id_lote'   => 1,
            'cantidad'  => 5,
            'id_estado' => 6,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_registro');

        $trazabilidad->save();
    }

    #[Test]
    public function get_by_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbTrazabilidad())->getById(1));
    }

    #[Test]
    public function exists_devuelve_false_sin_base_de_datos(): void
    {
        $this->assertFalse((new NoDbTrazabilidad())->exists(1));
    }

    #[Test]
    public function delete_y_restore_devuelven_false_sin_base_de_datos(): void
    {
        $trazabilidad = new NoDbTrazabilidad();

        $this->assertFalse($trazabilidad->delete(1));
        $this->assertFalse($trazabilidad->restore(1));
    }

    #[Test]
    public function get_last_insert_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbTrazabilidad())->getLastInsertId());
    }

    #[Test]
    public function get_available_batches_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbTrazabilidad())->getAvailableBatches());
        $this->assertSame([], (new NoDbTrazabilidad())->getAvailableBatches(3));
    }

    #[Test]
    public function los_ajustes_de_stock_devuelven_false_sin_base_de_datos(): void
    {
        $trazabilidad = new NoDbTrazabilidad();

        $this->assertFalse($trazabilidad->deductBatchStock(1, 2));
        $this->assertFalse($trazabilidad->restoreBatchStock(1, 2));
    }

    #[Test]
    public function find_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbTrazabilidad::find(1);
    }

    #[Test]
    public function all_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbTrazabilidad::all();
    }

    #[Test]
    public function where_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbTrazabilidad::where('id_lote', 1);
    }

    #[Test]
    public function load_by_id_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbTrazabilidad())->loadById(1);
    }
}
