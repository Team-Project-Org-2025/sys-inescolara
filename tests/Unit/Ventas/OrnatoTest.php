<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbOrnato;

final class OrnatoTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $ornato = new NoDbOrnato();

        $this->assertNull($ornato->getId());
        $this->assertNull($ornato->getIdCliente());
        $this->assertSame('Venta', $ornato->getTipoOrnato());
        $this->assertNull($ornato->getDescripcion());
        $this->assertNull($ornato->getUbicacion());
        $this->assertSame(0.0, $ornato->getMontoTotal());
        $this->assertNull($ornato->getFecha());
        $this->assertTrue($ornato->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_no_aceptan_montos_negativos(): void
    {
        $ornato = new NoDbOrnato();

        $this->assertSame($ornato, $ornato->setIdCliente(3));
        $this->assertSame(3, $ornato->getIdCliente());

        $this->assertSame($ornato, $ornato->setTipoOrnato('Donacion'));
        $this->assertSame('Donacion', $ornato->getTipoOrnato());

        $this->assertSame($ornato, $ornato->setDescripcion('Arreglo floral'));
        $this->assertSame('Arreglo floral', $ornato->getDescripcion());

        $this->assertSame($ornato, $ornato->setUbicacion('Entrada principal'));
        $this->assertSame('Entrada principal', $ornato->getUbicacion());

        $this->assertSame($ornato, $ornato->setFecha('2026-10-27'));
        $this->assertSame('2026-10-27', $ornato->getFecha());

        $this->assertSame($ornato, $ornato->setMontoTotal(-10.0));
        $this->assertSame(0.0, $ornato->getMontoTotal());

        $ornato->setMontoTotal(150.75);
        $this->assertSame(150.75, $ornato->getMontoTotal());

        $this->assertSame($ornato, $ornato->setActivo(false));
        $this->assertFalse($ornato->isActivo());

        $ornato->setActivo(true);
        $this->assertTrue($ornato->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $ornato = new NoDbOrnato();
        $ornato->fill([
            'id_ornato'   => 8,
            'id_cliente'  => 2,
            'tipo_ornato' => 'Donacion',
            'descripcion' => 'Centro de mesa',
            'ubicacion'   => 'Salón',
            'monto_total' => 25.5,
            'fecha'       => '2026-10-01',
            'activo'      => 0,
            'desconocido' => 'ignorado',
        ]);

        $this->assertNull($ornato->getId());
        $this->assertSame(2, $ornato->getIdCliente());
        $this->assertSame('Donacion', $ornato->getTipoOrnato());
        $this->assertSame('Centro de mesa', $ornato->getDescripcion());
        $this->assertSame('Salón', $ornato->getUbicacion());
        $this->assertSame(25.5, $ornato->getMontoTotal());
        $this->assertSame('2026-10-01', $ornato->getFecha());
        $this->assertFalse($ornato->isActivo());
    }

    #[Test]
    public function save_sin_cliente_lanza_excepcion_de_validacion(): void
    {
        $ornato = new NoDbOrnato(['fecha' => '2026-10-27']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_cliente');

        $ornato->save();
    }

    #[Test]
    public function save_sin_fecha_lanza_excepcion_de_validacion(): void
    {
        $ornato = new NoDbOrnato(['id_cliente' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha');

        $ornato->save();
    }

    #[Test]
    public function save_sin_tipo_de_ornato_lanza_excepcion_de_validacion(): void
    {
        $ornato = new NoDbOrnato([
            'id_cliente'  => 1,
            'tipo_ornato' => '',
            'fecha'       => '2026-10-27',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: tipo_ornato');

        $ornato->save();
    }

    #[Test]
    public function save_con_monto_negativo_lanza_excepcion_de_formato(): void
    {
        $ornato = new NoDbOrnato([
            'id_cliente'  => 1,
            'monto_total' => -15.0,
            'fecha'       => '2026-10-27',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $ornato->save();
    }

    #[Test]
    public function save_con_datos_validos_no_puede_conectar_sin_base_de_datos(): void
    {
        $ornato = new NoDbOrnato([
            'id_cliente'  => 1,
            'tipo_ornato' => 'Venta',
            'monto_total' => 100.0,
            'fecha'       => '2026-10-27',
        ]);

        // Ornato::save() captura `Throwable` sin importar la clase: en el
        // namespace del modelo ese tipo no existe, por lo que la excepción
        // de conexión se propaga en lugar de devolver false.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $ornato->save();
    }

    #[Test]
    public function agregar_sin_cliente_lanza_excepcion_de_validacion(): void
    {
        $ornato = new NoDbOrnato();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_cliente');

        $ornato->agregar(['fecha' => '2026-10-27', 'monto_total' => 10.0]);
    }

    #[Test]
    public function getAll_devuelve_lista_vacia_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbOrnato())->getAll());
    }

    #[Test]
    public function getById_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbOrnato())->getById(1));
    }

    #[Test]
    public function exists_devuelve_false_sin_base_de_datos(): void
    {
        $this->assertFalse((new NoDbOrnato())->exists(1));
    }

    #[Test]
    public function delete_y_restore_devuelven_false_sin_base_de_datos(): void
    {
        $ornato = new NoDbOrnato();

        $this->assertFalse($ornato->delete(1));
        $this->assertFalse($ornato->restore(1));
    }

    #[Test]
    public function obtenerDetalles_y_buscarClientes_devuelven_listas_vacias_sin_base_de_datos(): void
    {
        $ornato = new NoDbOrnato();

        $this->assertSame([], $ornato->obtenerDetalles(1));
        $this->assertSame([], $ornato->buscarClientes('Ana'));
    }

    #[Test]
    public function obtenerUltimoId_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbOrnato())->obtenerUltimoId());
    }

    #[Test]
    public function agregarDetalles_no_puede_abrir_transaccion_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbOrnato())->agregarDetalles(1, [['id_lote' => 1, 'cantidad' => 1]]);
    }

    #[Test]
    public function actualizar_un_ornato_inexistente_no_puede_consultar_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbOrnato())->actualizar(99, ['descripcion' => 'Nueva']);
    }

    #[Test]
    public function all_no_puede_consultar_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        NoDbOrnato::all();
    }
}
