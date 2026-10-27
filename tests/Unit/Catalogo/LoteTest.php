<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Catalogo;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbLote;

final class LoteTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $lote = new NoDbLote();

        $this->assertNull($lote->getId());
        $this->assertNull($lote->getIdPlanta());
        $this->assertNull($lote->getIdUbicacion());
        $this->assertNull($lote->getFechaSiembra());
        $this->assertSame(0, $lote->getCantidadInicial());
        $this->assertSame(0, $lote->getCantidadActual());
        $this->assertSame(0.0, $lote->getCostoUnitario());
        $this->assertNull($lote->getIdEstado());
        $this->assertNull($lote->getIdCategoria());
        $this->assertNull($lote->getIdOrigen());
        $this->assertNull($lote->getObservacion());
        $this->assertNull($lote->getImagen());
        $this->assertTrue($lote->isActivo());
        $this->assertSame(30.0, $lote->getPorcentajeGanancia());
    }

    #[Test]
    public function los_setters_son_fluentes_y_asignan_los_valores(): void
    {
        $lote = new NoDbLote();

        $this->assertSame($lote, $lote->setId(7));
        $this->assertSame(7, $lote->getId());

        $this->assertSame($lote, $lote->setIdPlanta(3));
        $this->assertSame(3, $lote->getIdPlanta());

        $this->assertSame($lote, $lote->setIdUbicacion(2));
        $this->assertSame(2, $lote->getIdUbicacion());

        $this->assertSame($lote, $lote->setFechaSiembra('2026-01-15'));
        $this->assertSame('2026-01-15', $lote->getFechaSiembra());

        $this->assertSame($lote, $lote->setIdEstado(5));
        $this->assertSame(5, $lote->getIdEstado());

        $this->assertSame($lote, $lote->setIdCategoria(2));
        $this->assertSame(2, $lote->getIdCategoria());

        $this->assertSame($lote, $lote->setIdOrigen(1));
        $this->assertSame(1, $lote->getIdOrigen());

        $this->assertSame($lote, $lote->setImagen('lote.jpg'));
        $this->assertSame('lote.jpg', $lote->getImagen());
    }

    #[Test]
    public function los_setters_numericos_no_permiten_negativos(): void
    {
        $lote = new NoDbLote();

        $this->assertSame($lote, $lote->setCantidadInicial(-5));
        $this->assertSame(0, $lote->getCantidadInicial());

        $this->assertSame($lote, $lote->setCantidadActual(-2));
        $this->assertSame(0, $lote->getCantidadActual());

        $this->assertSame($lote, $lote->setCostoUnitario(-1.5));
        $this->assertSame(0.0, $lote->getCostoUnitario());

        $this->assertSame($lote, $lote->setPorcentajeGanancia(-10.0));
        $this->assertSame(0.0, $lote->getPorcentajeGanancia());

        $lote->setCantidadInicial(10)->setCantidadActual(8)->setCostoUnitario(2.5)->setPorcentajeGanancia(45.0);
        $this->assertSame(10, $lote->getCantidadInicial());
        $this->assertSame(8, $lote->getCantidadActual());
        $this->assertSame(2.5, $lote->getCostoUnitario());
        $this->assertSame(45.0, $lote->getPorcentajeGanancia());
    }

    #[Test]
    public function observacion_vacia_se_normaliza_a_null(): void
    {
        $lote = new NoDbLote();

        $lote->setObservacion('  Lote de prueba  ');
        $this->assertSame('  Lote de prueba  ', $lote->getObservacion());

        $lote->setObservacion('');
        $this->assertNull($lote->getObservacion());
    }

    #[Test]
    public function setActivo_convierte_el_booleano_a_entero(): void
    {
        $lote = new NoDbLote();

        $this->assertSame($lote, $lote->setActivo(false));
        $this->assertFalse($lote->isActivo());

        $lote->setActivo(true);
        $this->assertTrue($lote->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $lote = new NoDbLote();
        $lote->fill([
            'id_planta'  => 1,
            'id_lote'    => 99,
            'desconocido'=> 'ignorado',
        ]);

        $this->assertSame(1, $lote->getIdPlanta());
        $this->assertNull($lote->getId());
    }

    #[Test]
    public function fill_mapea_todas_las_columnas_de_la_tabla(): void
    {
        $lote = new NoDbLote([
            'id_planta'           => 3,
            'id_ubicacion'        => 2,
            'fecha_siembra'       => '2026-03-01',
            'cantidad_inicial'    => 10,
            'cantidad_actual'     => 8,
            'costo_unitario'      => 1.25,
            'id_estado'           => 5,
            'id_categoria'        => 2,
            'id_origen'           => 1,
            'observacion'         => 'Lote A',
            'imagen'              => 'lote.jpg',
            'activo'              => 0,
            'porcentaje_ganancia' => 25.5,
        ]);

        $this->assertSame(3, $lote->getIdPlanta());
        $this->assertSame(2, $lote->getIdUbicacion());
        $this->assertSame('2026-03-01', $lote->getFechaSiembra());
        $this->assertSame(10, $lote->getCantidadInicial());
        $this->assertSame(8, $lote->getCantidadActual());
        $this->assertSame(1.25, $lote->getCostoUnitario());
        $this->assertSame(5, $lote->getIdEstado());
        $this->assertSame(2, $lote->getIdCategoria());
        $this->assertSame(1, $lote->getIdOrigen());
        $this->assertSame('Lote A', $lote->getObservacion());
        $this->assertSame('lote.jpg', $lote->getImagen());
        $this->assertFalse($lote->isActivo());
        $this->assertSame(25.5, $lote->getPorcentajeGanancia());
    }

    #[Test]
    public function save_sin_planta_lanza_excepcion_de_validacion(): void
    {
        $lote = new NoDbLote();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_planta');

        $lote->save();
    }

    #[Test]
    public function save_sin_fecha_de_siembra_lanza_excepcion_de_validacion(): void
    {
        $lote = new NoDbLote(['id_planta' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_siembra');

        $lote->save();
    }

    #[Test]
    public function save_con_cantidad_negativa_lanza_excepcion(): void
    {
        $lote = new NoDbLote([
            'id_planta'        => 1,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => -1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe ser un número entero positivo');

        $lote->save();
    }

    #[Test]
    public function save_con_costo_unitario_invalido_lanza_excepcion(): void
    {
        $lote = new NoDbLote([
            'id_planta'      => 1,
            'fecha_siembra'  => '2026-01-01',
            'costo_unitario' => 1.999,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $lote->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        // Lote::save() captura Throwable y degrada a false.
        $lote = new NoDbLote([
            'id_planta'        => 1,
            'fecha_siembra'    => '2026-01-01',
            'cantidad_inicial' => 10,
            'cantidad_actual'  => 10,
        ]);

        $this->assertFalse($lote->save());
    }

    #[Test]
    public function add_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $lote = new NoDbLote();

        $this->assertFalse($lote->add(1, 1, '2026-01-01', 10, 10));
    }

    #[Test]
    public function los_catalogos_sin_base_de_datos_devuelven_arreglos_vacios(): void
    {
        $lote = new NoDbLote();

        $this->assertSame([], $lote->getEstados());
        $this->assertSame([], $lote->getCategorias());
        $this->assertSame([], $lote->getOrigenes());
    }

    #[Test]
    public function all_y_getById_sin_base_de_datos_degradan(): void
    {
        $lote = new NoDbLote();

        $this->assertSame([], NoDbLote::all());
        $this->assertNull($lote->getById(1));
    }

    #[Test]
    public function getIdEstadoVivo_sin_base_de_datos_propaga_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbLote())->getIdEstadoVivo();
    }

    #[Test]
    public function getIdOrigenPorNombre_sin_base_de_datos_propaga_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbLote())->getIdOrigenPorNombre('Siembra');
    }

    #[Test]
    public function update_de_un_lote_inexistente_sin_base_de_datos_propaga_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        (new NoDbLote())->update(1, 1, 1, '2026-01-01', 1, 1);
    }
}
