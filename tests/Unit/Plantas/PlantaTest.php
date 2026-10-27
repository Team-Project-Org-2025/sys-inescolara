<?php

namespace SysInescolara\Tests\Unit\Plantas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
    public function setters_son_fluentes_y_normalizan_los_nombres(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setNombreComun('   Rosa de Castilla   '));
        $this->assertSame('Rosa de Castilla', $planta->getNombreComun());

        $this->assertSame($planta, $planta->setNombreTecnico('  Rosa damascena  '));
        $this->assertSame('Rosa damascena', $planta->getNombreTecnico());

        $planta->setNombreTecnico(null);
        $this->assertNull($planta->getNombreTecnico());
    }

    #[Test]
    public function setCantidadTotal_anchorea_valores_negativos_en_cero(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setCantidadTotal(-5));
        $this->assertSame(0, $planta->getCantidadTotal());

        $planta->setCantidadTotal(12);
        $this->assertSame(12, $planta->getCantidadTotal());
    }

    #[Test]
    public function setActivo_convierte_bool_a_entero(): void
    {
        $planta = new NoDbPlanta();

        $this->assertSame($planta, $planta->setActivo(false));
        $this->assertFalse($planta->isActivo());

        $planta->setActivo(true);
        $this->assertTrue($planta->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $planta = new NoDbPlanta();
        $planta->fill([
            'nombre_comun' => 'Rosa',
            'id'           => 99,
            'desconocido'  => 'ignorado',
        ]);

        $this->assertSame('Rosa', $planta->getNombreComun());
        $this->assertNull($planta->getId());
    }

    #[Test]
    public function fill_mapea_columnas_del_modelo_a_sus_propiedades(): void
    {
        $planta = new NoDbPlanta();
        $planta->fill([
            'id_especie'     => 7,
            'nombre_tecnico' => 'R. damascena',
            'imagen'         => 'plant_1.png',
            'cantidad_total' => 3,
            'activo'         => 0,
        ]);

        $this->assertSame(7, $planta->getIdEspecie());
        $this->assertSame('R. damascena', $planta->getNombreTecnico());
        $this->assertSame('plant_1.png', $planta->getImagen());
        $this->assertSame(3, $planta->getCantidadTotal());
        $this->assertFalse($planta->isActivo());
    }

    #[Test]
    public function save_sin_nombre_comun_lanza_excepcion_de_validacion(): void
    {
        $planta = new NoDbPlanta(['id_especie' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_comun');

        $planta->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $planta = new NoDbPlanta(['nombre_comun' => 'a', 'id_especie' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $planta->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $planta = new NoDbPlanta(['nombre_comun' => 'Rosa1', 'id_especie' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $planta->save();
    }

    #[Test]
    public function save_sin_especie_lanza_excepcion_por_campo_requerido(): void
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
            'nombre_tecnico' => 'Rosa2024',
            'id_especie'     => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $planta->save();
    }

    #[Test]
    public function save_con_datos_validos_registra_el_error_y_devuelve_false(): void
    {
        // PHPUnit captura error_log() y falla si no fue invocado
        $this->expectErrorLog();

        $planta = new NoDbPlanta([
            'nombre_comun' => 'Ñandú Ñandú',
            'id_especie'   => 1,
        ]);

        $this->assertFalse($planta->save());
    }
}
