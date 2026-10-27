<?php

namespace SysInescolara\Tests\Unit\Especies;

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
    public function setters_son_fluentes_y_normalizan(): void
    {
        $especie = new NoDbEspecie();

        $this->assertSame($especie, $especie->setNombreEspecie('   Rosa de Castilla   '));
        $this->assertSame('Rosa de Castilla', $especie->getNombreEspecie());

        $this->assertSame($especie, $especie->setDescripcion('  Arbusto perenne  '));
        $this->assertSame('Arbusto perenne', $especie->getDescripcion());

        $especie->setDescripcion(null);
        $this->assertNull($especie->getDescripcion());

        $especie->setDescripcion('');
        $this->assertNull($especie->getDescripcion());
    }

    #[Test]
    public function setActivo_convierte_bool_a_entero(): void
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
    public function fill_mapea_columnas_del_modelo_a_sus_propiedades(): void
    {
        $especie = new NoDbEspecie();
        $especie->fill([
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
    public function save_con_nombre_en_el_limite_minimo_es_valido(): void
    {
        // 'Ño' tiene exactamente 2 caracteres: pasa la validación y llega a la BD
        $especie = new NoDbEspecie(['nombre_especie' => 'Ño']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $especie->save();
    }

    #[Test]
    public function save_descripcion_es_texto_libre_sin_patron_de_validacion(): void
    {
        // descripcion type=null: no valida regex, aunque contenga símbolos y números
        $especie = new NoDbEspecie([
            'nombre_especie' => 'Rosa',
            'descripcion'    => 'Flores <b>rojas</b>, nº 12... ¿ok?',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $especie->save();
    }

    #[Test]
    public function save_con_datos_validos_propaga_el_error_de_base_de_datos(): void
    {
        // A diferencia de Planta, Especie::save() NO tiene try/catch:
        // el error de BD escapa en lugar de devolver false (comportamiento
        // conocido del módulo, ver SPEC — bugs DataTables).
        $especie = new NoDbEspecie(['nombre_especie' => 'Rosa de Castilla']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $especie->save();
    }
}
