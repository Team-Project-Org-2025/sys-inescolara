<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Operaciones;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbAmpliacion;

/**
 * Omitidas en unitarias porque instancian modelos que abren MySQL y llaman a
 * exit() cuando el servidor no está disponible: getLocations() (Ubicacion),
 * getSpecies() (Especie) y getGestores() (Usuario).
 */
final class AmpliacionTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $ampliacion = new NoDbAmpliacion();

        $this->assertNull($ampliacion->getId());
        $this->assertSame('intercambio', $ampliacion->getTipoMovimiento());
        $this->assertNull($ampliacion->getIdCliente());
        $this->assertNull($ampliacion->getIdUsuarioGestor());
        $this->assertNull($ampliacion->getFechaMovimiento());
        $this->assertNull($ampliacion->getObservacion());
        $this->assertTrue($ampliacion->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $ampliacion = new NoDbAmpliacion();
        $ampliacion->fill([
            'id_movimiento_planta' => 99,
            'tipo_movimiento'      => 'donacion',
            'id_cliente'           => 2,
            'id_usuario_gestor'    => 3,
            'fecha_movimiento'     => '2026-02-10',
            'observacion'          => 'Entrega',
            'activo'               => 0,
            'desconocido'          => 'ignorado',
        ]);

        $this->assertNull($ampliacion->getId());
        $this->assertSame('donacion', $ampliacion->getTipoMovimiento());
        $this->assertSame(2, $ampliacion->getIdCliente());
        $this->assertSame(3, $ampliacion->getIdUsuarioGestor());
        $this->assertSame('2026-02-10', $ampliacion->getFechaMovimiento());
        $this->assertSame('Entrega', $ampliacion->getObservacion());
        $this->assertFalse($ampliacion->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes(): void
    {
        $ampliacion = new NoDbAmpliacion();

        $this->assertSame($ampliacion, $ampliacion->setTipoMovimiento('venta'));
        $this->assertSame('venta', $ampliacion->getTipoMovimiento());

        $this->assertSame($ampliacion, $ampliacion->setIdCliente(4));
        $this->assertSame(4, $ampliacion->getIdCliente());

        $this->assertSame($ampliacion, $ampliacion->setIdUsuarioGestor(5));
        $this->assertSame(5, $ampliacion->getIdUsuarioGestor());

        $this->assertSame($ampliacion, $ampliacion->setFechaMovimiento('2026-04-01'));
        $this->assertSame('2026-04-01', $ampliacion->getFechaMovimiento());

        $this->assertSame($ampliacion, $ampliacion->setObservacion('Nota'));
        $this->assertSame('Nota', $ampliacion->getObservacion());

        $this->assertSame($ampliacion, $ampliacion->setActivo(false));
        $this->assertFalse($ampliacion->isActivo());
    }

    #[Test]
    public function save_sin_gestor_lanza_excepcion_de_validacion(): void
    {
        $ampliacion = new NoDbAmpliacion(['fecha_movimiento' => '2026-02-01']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_usuario_gestor');

        $ampliacion->save();
    }

    #[Test]
    public function save_sin_fecha_lanza_excepcion_de_validacion(): void
    {
        $ampliacion = new NoDbAmpliacion([
            'id_usuario_gestor' => 1,
            'fecha_movimiento'  => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_movimiento');

        $ampliacion->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $ampliacion = new NoDbAmpliacion([
            'id_cliente'        => 1,
            'id_usuario_gestor' => 2,
            'fecha_movimiento'  => '2026-02-01',
        ]);

        $this->assertFalse($ampliacion->save());
    }

    #[Test]
    public function getAll_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbAmpliacion())->getAll());
    }

    #[Test]
    public function getById_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbAmpliacion())->getById(1));
    }

    #[Test]
    public function getDetails_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbAmpliacion())->getDetails(1));
    }

    #[Test]
    public function getAvailableLots_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbAmpliacion())->getAvailableLots());
    }

    #[Test]
    public function getPlants_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbAmpliacion())->getPlants());
    }

    #[Test]
    public function buscarClientes_devuelve_array_vacio_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbAmpliacion())->buscarClientes('María'));
    }

    #[Test]
    public function delete_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbAmpliacion())->delete(1);
    }

    #[Test]
    public function restore_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbAmpliacion())->restore(1);
    }

    #[Test]
    public function update_sin_registro_lanza_excepcion(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No existe la ampliación');

        (new NoDbAmpliacion())->update(999, ['id_usuario_gestor' => 1]);
    }

    #[Test]
    public function register_exchange_sin_items_lanza_excepcion(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debe agregar al menos un item de salida o entrada.');

        (new NoDbAmpliacion())->registerExchange(['id_usuario_gestor' => 1]);
    }

    #[Test]
    public function register_exchange_con_items_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbAmpliacion())->registerExchange([
            'id_usuario_gestor' => 1,
            'salida_items'      => [['id_lote' => 1, 'cantidad' => 1]],
        ]);
    }

    #[Test]
    public function exists_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbAmpliacion())->exists(1);
    }

    #[Test]
    public function find_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbAmpliacion::find(1);
    }

    #[Test]
    public function all_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbAmpliacion::all();
    }

    #[Test]
    public function where_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbAmpliacion::where('tipo_movimiento', 'venta');
    }

    #[Test]
    public function load_by_id_requiere_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbAmpliacion())->loadById(1);
    }

    #[Test]
    public function get_last_insert_id_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbAmpliacion())->getLastInsertId());
    }

    #[Test]
    public function los_metodos_que_instancian_otros_modelos_no_son_portables(): void
    {
        $this->markTestSkipped(
            'getLocations(), getSpecies() y getGestores() instancian Ubicacion, Especie y Usuario; '
            . 'sus constructores abren MySQL y llaman a exit() si no hay servidor.'
        );
    }
}
