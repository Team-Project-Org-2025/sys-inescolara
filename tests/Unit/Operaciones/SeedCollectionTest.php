<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Operaciones;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbSeedCollection;

/**
 * registerSeedsWithTransaction() instancia Insumo, cuyo constructor abre
 * MySQL y llama a exit() cuando el servidor no responde; por eso no se
 * invoca en pruebas sin base de datos.
 */
final class SeedCollectionTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $recoleccion = new NoDbSeedCollection();

        $this->assertNull($recoleccion->getId());
        $this->assertNull($recoleccion->getIdUsuario());
        $this->assertNull($recoleccion->getIdUbicacion());
        $this->assertNull($recoleccion->getFechaAsignacion());
        $this->assertNull($recoleccion->getFechaRecoleccion());
        $this->assertSame('Pendiente', $recoleccion->getEstatus());
        $this->assertNull($recoleccion->getObservacion());
        $this->assertTrue($recoleccion->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $recoleccion = new NoDbSeedCollection();
        $recoleccion->fill([
            'id_recoleccion'    => 99,
            'id_usuario'        => 3,
            'id_ubicacion'      => 2,
            'fecha_asignacion'  => '2026-02-01',
            'fecha_recoleccion' => '2026-02-02',
            'estatus'           => 'Realizada',
            'observacion'       => 'Semillas secas',
            'activo'            => 0,
            'desconocido'       => 'ignorado',
        ]);

        $this->assertNull($recoleccion->getId());
        $this->assertSame(3, $recoleccion->getIdUsuario());
        $this->assertSame(2, $recoleccion->getIdUbicacion());
        $this->assertSame('2026-02-01', $recoleccion->getFechaAsignacion());
        $this->assertSame('2026-02-02', $recoleccion->getFechaRecoleccion());
        $this->assertSame('Realizada', $recoleccion->getEstatus());
        $this->assertSame('Semillas secas', $recoleccion->getObservacion());
        $this->assertFalse($recoleccion->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes(): void
    {
        $recoleccion = new NoDbSeedCollection();

        $this->assertSame($recoleccion, $recoleccion->setIdUsuario(4));
        $this->assertSame(4, $recoleccion->getIdUsuario());

        $this->assertSame($recoleccion, $recoleccion->setIdUbicacion(5));
        $this->assertSame(5, $recoleccion->getIdUbicacion());

        $this->assertSame($recoleccion, $recoleccion->setFechaAsignacion('2026-05-01'));
        $this->assertSame('2026-05-01', $recoleccion->getFechaAsignacion());

        $this->assertSame($recoleccion, $recoleccion->setFechaRecoleccion('2026-05-02'));
        $this->assertSame('2026-05-02', $recoleccion->getFechaRecoleccion());

        $this->assertSame($recoleccion, $recoleccion->setEstatus('Realizada'));
        $this->assertSame('Realizada', $recoleccion->getEstatus());

        $this->assertSame($recoleccion, $recoleccion->setObservacion('Nota'));
        $this->assertSame('Nota', $recoleccion->getObservacion());

        $this->assertSame($recoleccion, $recoleccion->setActivo(false));
        $this->assertFalse($recoleccion->isActivo());
    }

    #[Test]
    public function save_sin_datos_lanza_excepcion_de_validacion(): void
    {
        $recoleccion = new NoDbSeedCollection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_usuario');

        $recoleccion->save();
    }

    #[Test]
    public function save_sin_ubicacion_lanza_excepcion_de_validacion(): void
    {
        $recoleccion = new NoDbSeedCollection([
            'id_usuario'       => 1,
            'fecha_asignacion' => '2026-02-01',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_ubicacion');

        $recoleccion->save();
    }

    #[Test]
    public function add_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->add(1, 2, '2026-02-01', 'Nota');
    }

    #[Test]
    public function getAll_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbSeedCollection())->getAll());
    }

    #[Test]
    public function getById_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbSeedCollection())->getById(1));
    }

    #[Test]
    public function getDetails_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbSeedCollection())->getDetails(1));
    }

    #[Test]
    public function getDetailsCount_devuelve_cero_sin_base_de_datos(): void
    {
        $this->assertSame(0, (new NoDbSeedCollection())->getDetailsCount(1));
    }

    #[Test]
    public function get_last_insert_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbSeedCollection())->getLastInsertId());
    }

    #[Test]
    public function update_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->update(1, 1, 1, '2026-02-01');
    }

    #[Test]
    public function complete_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->complete(1, '2026-02-05');
    }

    #[Test]
    public function addDetail_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->addDetail(1, 'Rosa', 'Semilla', 1, 2.5);
    }

    #[Test]
    public function delete_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->delete(1);
    }

    #[Test]
    public function restore_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->restore(1);
    }

    #[Test]
    public function exists_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->exists(1);
    }

    #[Test]
    public function find_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbSeedCollection::find(1);
    }

    #[Test]
    public function all_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbSeedCollection::all();
    }

    #[Test]
    public function where_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbSeedCollection::where('estatus', 'Pendiente');
    }

    #[Test]
    public function load_by_id_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbSeedCollection())->loadById(1);
    }

    #[Test]
    public function register_seeds_with_transaction_no_es_portable(): void
    {
        $this->markTestSkipped(
            'registerSeedsWithTransaction() instancia Insumo, que abre MySQL y llama a exit() '
            . 'si el servidor no está disponible; no se puede inyectar el PDO de SQLite.'
        );
    }
}
