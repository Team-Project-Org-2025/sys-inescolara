<?php

namespace SysInescolara\Tests\Unit\Ubicaciones;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbUbicacion;

final class UbicacionTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $ubicacion = new NoDbUbicacion();

        $this->assertNull($ubicacion->getId());
        $this->assertSame('', $ubicacion->getNombreUbicacion());
        $this->assertNull($ubicacion->getDescripcion());
        $this->assertNull($ubicacion->getTipo());
        $this->assertTrue($ubicacion->isActivo());
    }

    #[Test]
    public function setters_son_fluentes_y_normalizan(): void
    {
        $ubicacion = new NoDbUbicacion();

        $this->assertSame($ubicacion, $ubicacion->setNombreUbicacion('   Invernadero A   '));
        $this->assertSame('Invernadero A', $ubicacion->getNombreUbicacion());

        $this->assertSame($ubicacion, $ubicacion->setDescripcion('  Zona de sombra  '));
        $this->assertSame('Zona de sombra', $ubicacion->getDescripcion());

        $ubicacion->setDescripcion(null);
        $this->assertNull($ubicacion->getDescripcion());

        $ubicacion->setDescripcion('');
        $this->assertNull($ubicacion->getDescripcion());

        $ubicacion->setTipo('Interno');
        $this->assertSame('Interno', $ubicacion->getTipo());

        $ubicacion->setTipo('   ');
        // a diferencia de setDescripcion, setTipo solo null-chequea el valor
        // original: un string de espacios pasa y queda '' tras el trim
        $this->assertSame('', $ubicacion->getTipo());
    }

    #[Test]
    public function setActivo_convierte_bool_a_entero(): void
    {
        $ubicacion = new NoDbUbicacion();

        $this->assertSame($ubicacion, $ubicacion->setActivo(false));
        $this->assertFalse($ubicacion->isActivo());

        $ubicacion->setActivo(true);
        $this->assertTrue($ubicacion->isActivo());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $ubicacion = new NoDbUbicacion();
        $ubicacion->fill([
            'nombre_ubicacion' => 'Invernadero A',
            'id'               => 99,
            'id_ubicacion'     => 7,
            'desconocido'      => 'ignorado',
        ]);

        $this->assertSame('Invernadero A', $ubicacion->getNombreUbicacion());
        $this->assertNull($ubicacion->getId());
    }

    #[Test]
    public function fill_mapea_columnas_del_modelo_a_sus_propiedades(): void
    {
        $ubicacion = new NoDbUbicacion();
        $ubicacion->fill([
            'nombre_ubicacion' => 'Almacén Norte',
            'descripcion'      => 'Estanterías 1-4',
            'tipo'             => 'Externo',
            'activo'           => 0,
        ]);

        $this->assertSame('Almacén Norte', $ubicacion->getNombreUbicacion());
        $this->assertSame('Estanterías 1-4', $ubicacion->getDescripcion());
        $this->assertSame('Externo', $ubicacion->getTipo());
        $this->assertFalse($ubicacion->isActivo());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $ubicacion = new NoDbUbicacion();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_ubicacion');

        $ubicacion->save();
    }

    #[Test]
    public function save_con_nombre_demasiado_corto_lanza_excepcion(): void
    {
        $ubicacion = new NoDbUbicacion(['nombre_ubicacion' => 'a']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $ubicacion->save();
    }

    #[Test]
    public function save_con_digitos_en_el_nombre_lanza_excepcion(): void
    {
        $ubicacion = new NoDbUbicacion(['nombre_ubicacion' => 'Invernadero 1']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $ubicacion->save();
    }

    #[Test]
    public function save_con_nombre_en_el_limite_minimo_es_valido_y_devuelve_false(): void
    {
        // 'Ño' tiene exactamente 2 caracteres: pasa la validación, falla la
        // BD (NoDb) y save() captura el Throwable devolviendo false.
        $ubicacion = new NoDbUbicacion(['nombre_ubicacion' => 'Ño']);

        $this->expectErrorLog();

        $this->assertFalse($ubicacion->save());
        $this->assertNull($ubicacion->getId());
    }

    #[Test]
    public function save_descripcion_es_texto_libre_sin_patron_de_validacion(): void
    {
        // descripcion type=null: no valida regex, aunque contenga símbolos y números
        $ubicacion = new NoDbUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'descripcion'      => 'Filas <b>1-4</b>, nº 12... ¿ok?',
        ]);

        $this->expectErrorLog();

        $this->assertFalse($ubicacion->save());
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_cuando_no_hay_bd(): void
    {
        // A diferencia de Especie, Ubicacion::save() captura el Throwable
        // y devuelve false (igual que Planta).
        $ubicacion = new NoDbUbicacion(['nombre_ubicacion' => 'Invernadero A']);

        $this->expectErrorLog();

        $this->assertFalse($ubicacion->save());
        $this->assertNull($ubicacion->getId());
    }
}
