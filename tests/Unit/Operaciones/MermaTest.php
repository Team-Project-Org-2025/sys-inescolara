<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Operaciones;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbMerma;

final class MermaTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $merma = new NoDbMerma();

        $this->assertNull($merma->getId());
        $this->assertNull($merma->getIdTrazabilidad());
        $this->assertNull($merma->getIdLote());
        $this->assertSame(0, $merma->getCantidad());
        $this->assertNull($merma->getMotivo());
        $this->assertNull($merma->getDescripcion());
        $this->assertNull($merma->getFechaMerma());
        $this->assertSame(0.0, $merma->getImpactoEconomico());
        $this->assertNull($merma->getIdUsuarioRegistra());
        $this->assertTrue($merma->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $merma = new NoDbMerma();
        $merma->fill([
            'id_merma'            => 99,
            'id_trazabilidad'     => 4,
            'id_lote'             => 2,
            'cantidad'            => 3,
            'motivo'              => 'plaga',
            'descripcion'         => 'Hojas afectadas',
            'fecha_merma'         => '2026-02-05',
            'impacto_economico'   => '37.50',
            'id_usuario_registra' => 8,
            'activo'              => 0,
            'desconocido'         => 'ignorado',
        ]);

        $this->assertNull($merma->getId());
        $this->assertSame(4, $merma->getIdTrazabilidad());
        $this->assertSame(2, $merma->getIdLote());
        $this->assertSame(3, $merma->getCantidad());
        $this->assertSame('plaga', $merma->getMotivo());
        $this->assertSame('Hojas afectadas', $merma->getDescripcion());
        $this->assertSame('2026-02-05', $merma->getFechaMerma());
        $this->assertSame(37.5, $merma->getImpactoEconomico());
        $this->assertSame(8, $merma->getIdUsuarioRegistra());
        $this->assertFalse($merma->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_valores(): void
    {
        $merma = new NoDbMerma();

        $this->assertSame($merma, $merma->setId(5));
        $this->assertSame(5, $merma->getId());

        $this->assertSame($merma, $merma->setIdTrazabilidad(2));
        $this->assertSame(2, $merma->getIdTrazabilidad());

        $this->assertSame($merma, $merma->setIdLote(3));
        $this->assertSame(3, $merma->getIdLote());

        $this->assertSame($merma, $merma->setCantidad(-9));
        $this->assertSame(0, $merma->getCantidad());

        $merma->setCantidad(6);
        $this->assertSame(6, $merma->getCantidad());

        $this->assertSame($merma, $merma->setMotivo('enfermedad'));
        $this->assertSame('enfermedad', $merma->getMotivo());

        $this->assertSame($merma, $merma->setDescripcion('Detalle'));
        $this->assertSame('Detalle', $merma->getDescripcion());

        $this->assertSame($merma, $merma->setFechaMerma('2026-03-01'));
        $this->assertSame('2026-03-01', $merma->getFechaMerma());

        $this->assertSame($merma, $merma->setImpactoEconomico(15.75));
        $this->assertSame(15.75, $merma->getImpactoEconomico());

        $this->assertSame($merma, $merma->setIdUsuarioRegistra(9));
        $this->assertSame(9, $merma->getIdUsuarioRegistra());

        $this->assertSame($merma, $merma->setActivo(false));
        $this->assertFalse($merma->isActivo());
    }

    #[Test]
    public function save_sin_datos_lanza_excepcion_de_validacion(): void
    {
        $merma = new NoDbMerma();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_trazabilidad');

        $merma->save();
    }

    #[Test]
    public function save_sin_motivo_lanza_excepcion_de_validacion(): void
    {
        $merma = new NoDbMerma([
            'id_trazabilidad' => 1,
            'cantidad'        => 2,
            'fecha_merma'     => '2026-02-01',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: motivo');

        $merma->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $merma = new NoDbMerma([
            'id_trazabilidad' => 1,
            'id_lote'         => 2,
            'cantidad'        => 3,
            'motivo'          => 'plaga',
            'fecha_merma'     => '2026-02-01',
        ]);

        $this->assertFalse($merma->save());
    }

    #[Test]
    public function get_by_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbMerma())->getById(1));
    }

    #[Test]
    public function get_last_insert_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbMerma())->getLastInsertId());
    }

    #[Test]
    public function get_available_quarantine_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbMerma())->getAvailableQuarantine());
    }

    #[Test]
    public function find_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbMerma::find(1);
    }

    #[Test]
    public function all_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbMerma::all();
    }

    #[Test]
    public function getAll_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->getAll();
    }

    #[Test]
    public function exists_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->exists(1);
    }

    #[Test]
    public function delete_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->delete(1);
    }

    #[Test]
    public function restore_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->restore(1);
    }

    #[Test]
    public function load_by_id_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->loadById(1);
    }

    #[Test]
    public function register_loss_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbMerma())->registerLoss(1, 2, 'plaga', null, '2026-02-01', 3);
    }
}
