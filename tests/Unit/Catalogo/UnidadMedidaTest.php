<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Catalogo;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbUnidadMedida;

final class UnidadMedidaTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $unidad = new NoDbUnidadMedida();

        $this->assertNull($unidad->getId());
        $this->assertSame('', $unidad->getNombreUnidadMedida());
        $this->assertTrue($unidad->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_recortan_espacios(): void
    {
        $unidad = new NoDbUnidadMedida();

        $this->assertSame($unidad, $unidad->setNombreUnidadMedida('  Kilogramo  '));
        $this->assertSame('Kilogramo', $unidad->getNombreUnidadMedida());

        $this->assertSame($unidad, $unidad->setActivo(false));
        $this->assertFalse($unidad->isActivo());

        $unidad->setActivo(true);
        $this->assertTrue($unidad->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $unidad = new NoDbUnidadMedida();
        $unidad->fill([
            'nombre_unidad_medida' => 'Litro',
            'id'                   => 99,
            'desconocido'          => 'ignorado',
        ]);

        $this->assertSame('Litro', $unidad->getNombreUnidadMedida());
        $this->assertNull($unidad->getId());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $unidad = new NoDbUnidadMedida();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_unidad_medida');

        $unidad->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $unidad = new NoDbUnidadMedida(['nombre_unidad_medida' => 'a']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $unidad->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $unidad = new NoDbUnidadMedida(['nombre_unidad_medida' => 'Kg2']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $unidad->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $unidad = new NoDbUnidadMedida(['nombre_unidad_medida' => 'Metro cuadrado']);

        $this->assertFalse($unidad->save());
    }

    #[Test]
    public function getById_y_exists_sin_base_de_datos_degradan(): void
    {
        $unidad = new NoDbUnidadMedida();

        $this->assertNull($unidad->getById(1));
        $this->assertFalse($unidad->exists(1));
    }

    #[Test]
    public function delete_y_restore_sin_base_de_datos_devuelven_false(): void
    {
        $unidad = new NoDbUnidadMedida();

        $this->assertFalse($unidad->delete(1));
        $this->assertFalse($unidad->restore(1));
    }

    #[Test]
    public function getLastInsertId_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUnidadMedida())->getLastInsertId());
    }

    #[Test]
    public function all_sin_base_de_datos_propaga_runtime_exception(): void
    {
        // all() no captura errores de conexión.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbUnidadMedida::all();
    }
}
