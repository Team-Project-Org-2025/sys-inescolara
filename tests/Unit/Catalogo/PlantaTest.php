<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Catalogo;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbPlanta;

final class PlantaTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $planta = new NoDbPlanta();

        $this->assertNull($planta->getId());
        $this->assertSame('', $planta->getNombreComun());
        $this->assertNull($planta->getNombreTecnico());
        $this->assertNull($planta->getIdEspecie());
        $this->assertNull($planta->getImagen());
        $this->assertSame(0, $planta->getCantidadTotal());
        $this->assertTrue($planta->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_recortan_espacios(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setNombreComun('  Rosa  '));
        $this->assertSame('Rosa', $planta->getNombreComun());

        $this->assertSame($planta, $planta->setNombreTecnico('  Rosa gallica  '));
        $this->assertSame('Rosa gallica', $planta->getNombreTecnico());

        $planta->setNombreTecnico('');
        $this->assertNull($planta->getNombreTecnico());
    }

    #[Test]
    public function setCantidadTotal_nunca_es_negativa(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setCantidadTotal(-8));
        $this->assertSame(0, $planta->getCantidadTotal());

        $planta->setCantidadTotal(15);
        $this->assertSame(15, $planta->getCantidadTotal());
    }

    #[Test]
    public function setters_de_relaciones_y_estado(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setIdEspecie(4));
        $this->assertSame(4, $planta->getIdEspecie());

        $this->assertSame($planta, $planta->setImagen('rosa.jpg'));
        $this->assertSame('rosa.jpg', $planta->getImagen());

        $this->assertSame($planta, $planta->setActivo(false));
        $this->assertFalse($planta->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $planta = new NoDbPlanta();
        $planta->fill([
            'nombre_comun'   => 'Rosa',
            'id_planta'      => 99,
            'desconocido'    => 'ignorado',
        ]);

        $this->assertSame('Rosa', $planta->getNombreComun());
        $this->assertNull($planta->getId());
    }

    #[Test]
    public function fill_mapea_las_columnas_de_la_tabla(): void
    {
        $planta = new NoDbPlanta([
            'nombre_comun'   => 'Ficus',
            'nombre_tecnico' => 'Ficus benjamina',
            'id_especie'     => 3,
            'imagen'         => 'ficus.jpg',
            'cantidad_total' => 12,
            'activo'         => 0,
        ]);

        $this->assertSame('Ficus', $planta->getNombreComun());
        $this->assertSame('Ficus benjamina', $planta->getNombreTecnico());
        $this->assertSame(3, $planta->getIdEspecie());
        $this->assertSame('ficus.jpg', $planta->getImagen());
        $this->assertSame(12, $planta->getCantidadTotal());
        $this->assertFalse($planta->isActivo());
    }

    #[Test]
    public function save_sin_nombre_comun_lanza_excepcion_de_validacion(): void
    {
        $planta = new NoDbPlanta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_comun');

        $planta->save();
    }

    #[Test]
    public function save_sin_especie_lanza_excepcion_de_validacion(): void
    {
        $planta = new NoDbPlanta(['nombre_comun' => 'Rosa']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_especie');

        $planta->save();
    }

    #[Test]
    public function save_con_nombre_tecnico_invalido_lanza_excepcion(): void
    {
        $planta = new NoDbPlanta([
            'nombre_comun'   => 'Rosa',
            'id_especie'     => 1,
            'nombre_tecnico' => 'Rosa1',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $planta->save();
    }

    #[Test]
    public function save_con_cantidad_negativa_lanza_excepcion(): void
    {
        $planta = new NoDbPlanta([
            'nombre_comun'   => 'Rosa',
            'id_especie'     => 1,
            'cantidad_total' => -3,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe ser un número entero positivo');

        $planta->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        // Planta::save() captura Throwable y degrada a false.
        $planta = new NoDbPlanta([
            'nombre_comun'   => 'Rosa',
            'id_especie'     => 1,
            'cantidad_total' => 10,
        ]);

        $this->assertFalse($planta->save());
    }

    #[Test]
    public function find_sin_base_de_datos_propaga_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbPlanta::find(1);
    }
}
