<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Catalogo;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbEspecie;

final class EspecieTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $especie = new NoDbEspecie();

        $this->assertNull($especie->getId());
        $this->assertSame('', $especie->getNombreEspecie());
        $this->assertNull($especie->getDescripcion());
        $this->assertTrue($especie->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_recortan_espacios(): void
    {
        $especie = new NoDbEspecie();

        $this->assertSame($especie, $especie->setNombreEspecie('  Rosa de Castilla  '));
        $this->assertSame('Rosa de Castilla', $especie->getNombreEspecie());

        $this->assertSame($especie, $especie->setDescripcion('  Arbusto perenne  '));
        $this->assertSame('Arbusto perenne', $especie->getDescripcion());

        $especie->setDescripcion('');
        $this->assertNull($especie->getDescripcion());
    }

    #[Test]
    public function setActivo_convierte_el_booleano_a_entero(): void
    {
        $especie = new NoDbEspecie();

        $this->assertSame($especie, $especie->setActivo(false));
        $this->assertFalse($especie->isActivo());

        $especie->setActivo(true);
        $this->assertTrue($especie->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $especie = new NoDbEspecie();
        $especie->fill([
            'nombre_especie' => 'Rosa',
            'id'             => 99,
            'id_especie'     => 7,
            'desconocido'    => 'ignorado',
        ]);

        $this->assertSame('Rosa', $especie->getNombreEspecie());
        $this->assertNull($especie->getId());
    }

    #[Test]
    public function fill_mapea_las_columnas_de_la_tabla(): void
    {
        $especie = new NoDbEspecie([
            'nombre_especie' => 'Mango Criollo',
            'descripcion'    => 'Frutal nativo',
            'activo'         => 0,
        ]);

        $this->assertSame('Mango Criollo', $especie->getNombreEspecie());
        $this->assertSame('Frutal nativo', $especie->getDescripcion());
        $this->assertFalse($especie->isActivo());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $especie = new NoDbEspecie();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_especie');

        $especie->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $especie = new NoDbEspecie(['nombre_especie' => 'a']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $especie->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $especie = new NoDbEspecie(['nombre_especie' => 'Rosa1']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $especie->save();
    }

    #[Test]
    public function save_con_datos_validos_propaga_el_error_de_base_de_datos(): void
    {
        // Especie::save() no captura errores de BD: el RuntimeException escapa.
        $especie = new NoDbEspecie([
            'nombre_especie' => 'Rosa de Castilla',
            'descripcion'    => 'Flores <b>rojas</b>, nº 12... ¿ok?',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $especie->save();
    }

    #[Test]
    public function find_sin_base_de_datos_propaga_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbEspecie::find(1);
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        // getAll() tiene try/catch: degrada a arreglo vacío.
        $this->assertSame([], (new NoDbEspecie())->getAll());
    }

    #[Test]
    public function getLastInsertId_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbEspecie())->getLastInsertId());
    }
}
