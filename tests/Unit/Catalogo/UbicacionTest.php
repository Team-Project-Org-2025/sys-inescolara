<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Catalogo;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
    public function los_setters_son_fluentes_y_recortan_espacios(): void
    {
        $ubicacion = new NoDbUbicacion();

        $this->assertSame($ubicacion, $ubicacion->setNombreUbicacion('  Invernadero 1  '));
        $this->assertSame('Invernadero 1', $ubicacion->getNombreUbicacion());

        $this->assertSame($ubicacion, $ubicacion->setDescripcion('  Área techada  '));
        $this->assertSame('Área techada', $ubicacion->getDescripcion());

        $this->assertSame($ubicacion, $ubicacion->setTipo('  zona norte  '));
        $this->assertSame('zona norte', $ubicacion->getTipo());
    }

    #[Test]
    public function descripcion_y_tipo_vacios_se_normalizan_a_null(): void
    {
        $ubicacion = new NoDbUbicacion([
            'nombre_ubicacion' => 'Patio',
            'descripcion'      => 'Algo',
            'tipo'             => 'patio',
        ]);

        $ubicacion->setDescripcion('');
        $ubicacion->setTipo('');

        $this->assertNull($ubicacion->getDescripcion());
        $this->assertNull($ubicacion->getTipo());
    }

    #[Test]
    public function setActivo_convierte_el_booleano_a_entero(): void
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
            'nombre_ubicacion' => 'Vivero',
            'id_ubicacion'     => 99,
            'desconocido'      => 'ignorado',
        ]);

        $this->assertSame('Vivero', $ubicacion->getNombreUbicacion());
        $this->assertNull($ubicacion->getId());
    }

    #[Test]
    public function fill_mapea_las_columnas_de_la_tabla(): void
    {
        $ubicacion = new NoDbUbicacion([
            'nombre_ubicacion' => 'Galpón',
            'descripcion'      => 'Depósito de insumos',
            'tipo'             => 'almacén',
            'activo'           => 0,
        ]);

        $this->assertSame('Galpón', $ubicacion->getNombreUbicacion());
        $this->assertSame('Depósito de insumos', $ubicacion->getDescripcion());
        $this->assertSame('almacén', $ubicacion->getTipo());
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
    public function save_con_nombre_invalido_lanza_excepcion(): void
    {
        $ubicacion = new NoDbUbicacion(['nombre_ubicacion' => 'Zona 4']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $ubicacion->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        // Ubicacion::save() captura Throwable y degrada a false.
        $ubicacion = new NoDbUbicacion([
            'nombre_ubicacion' => 'Invernadero',
            'tipo'             => 'zona norte',
        ]);

        $this->assertFalse($ubicacion->save());
    }

    #[Test]
    public function getById_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbUbicacion())->getById(1));
    }

    #[Test]
    public function exists_sin_base_de_datos_devuelve_false(): void
    {
        $this->assertFalse((new NoDbUbicacion())->exists(1));
    }

    #[Test]
    public function all_sin_base_de_datos_propaga_runtime_exception(): void
    {
        // all() no captura el fallo del fallback de columna ni de la consulta.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbUbicacion::all();
    }
}
