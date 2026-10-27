<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Compras;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Proveedor;
use SysInescolara\Tests\Support\SqliteProveedor;
use SysInescolara\Tests\Support\SqliteTestCase;

final class ProveedorRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `rif_proveedor` se declara nullable porque el modelo lo trata como
        // opcional (validationRules required => false), aunque el dump de
        // desarrollo lo marque NOT NULL.
        $this->exec('
            CREATE TABLE proveedores (
                id_proveedor       INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_proveedor   TEXT NOT NULL,
                rif_proveedor      TEXT DEFAULT NULL,
                contacto_vendedor  TEXT DEFAULT NULL,
                telefono_proveedor TEXT DEFAULT NULL,
                activo             INTEGER NOT NULL DEFAULT 1
            )
        ');

        SqliteProveedor::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteProveedor::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function guardar_un_proveedor_nuevo_le_asigna_un_id(): void
    {
        $proveedor = new SqliteProveedor([
            'nombre_proveedor'   => 'Ferretería Los Andes',
            'rif_proveedor'      => 'J-12345678-9',
            'contacto_vendedor'  => 'Ana Pérez',
            'telefono_proveedor' => '04141234567',
        ]);

        $this->assertTrue($proveedor->save());
        $this->assertNotNull($proveedor->getId());

        $encontrado = SqliteProveedor::find($proveedor->getId());
        $this->assertInstanceOf(Proveedor::class, $encontrado);
        $this->assertSame('Ferretería Los Andes', $encontrado->getNombreProveedor());
        $this->assertSame('J-12345678-9', $encontrado->getRifProveedor());
        $this->assertSame('Ana Pérez', $encontrado->getContactoVendedor());
        $this->assertSame('04141234567', $encontrado->getTelefonoProveedor());
        $this->assertTrue($encontrado->isActivo());
    }

    #[Test]
    public function save_actualiza_un_proveedor_existente(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur', 'rif_proveedor' => 'V-12345678-9']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $proveedor->setNombreProveedor('Agro Sur Campo');
        $proveedor->setActivo(false);
        $this->assertTrue($proveedor->save());

        $fila = $proveedor->getById($id);
        $this->assertSame('Agro Sur Campo', $fila['nombre_proveedor']);
        $this->assertSame(0, (int) $fila['activo']);
    }

    #[Test]
    public function find_devuelve_null_si_el_proveedor_no_existe(): void
    {
        $this->assertNull(SqliteProveedor::find(99999));
    }

    #[Test]
    public function all_devuelve_solo_activos_ordenados_por_nombre(): void
    {
        foreach (['Zeta', 'Alfa', 'Mebra'] as $nombre) {
            $proveedor = new SqliteProveedor(['nombre_proveedor' => $nombre]);
            $this->assertTrue($proveedor->save());
        }

        $idBorrado = (int) $this->pdo
            ->query("SELECT id_proveedor FROM proveedores WHERE nombre_proveedor = 'Mebra'")
            ->fetchColumn();
        (new SqliteProveedor())->delete($idBorrado);

        $nombres = array_column(SqliteProveedor::all(), 'nombre_proveedor');
        $this->assertSame(['Alfa', 'Zeta'], $nombres);
    }

    #[Test]
    public function where_filtra_solo_proveedores_activos(): void
    {
        foreach (['Rosa', 'Rosa', 'Otra'] as $nombre) {
            $proveedor = new SqliteProveedor(['nombre_proveedor' => $nombre]);
            $this->assertTrue($proveedor->save());
        }

        $resultados = SqliteProveedor::where('nombre_proveedor', 'Rosa');
        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Proveedor::class, $resultado);
            $this->assertSame('Rosa', $resultado->getNombreProveedor());
        }
    }

    #[Test]
    public function get_by_rif_devuelve_el_id_y_null_cuando_no_existe(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur', 'rif_proveedor' => 'J-12345678-9']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $encontrado = (new SqliteProveedor())->getByRif('J-12345678-9');
        $this->assertSame($id, (int) $encontrado['id']);

        $this->assertNull((new SqliteProveedor())->getByRif('V-99999999-9'));
    }

    #[Test]
    public function get_by_rif_ignora_proveedores_desactivados(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur', 'rif_proveedor' => 'J-12345678-9']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        (new SqliteProveedor())->delete($id);

        $this->assertNull((new SqliteProveedor())->getByRif('J-12345678-9'));
    }

    #[Test]
    public function getAll_devuelve_filas_con_alias_id(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur', 'rif_proveedor' => 'V-12345678-9']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $filas = (new SqliteProveedor())->getAll();
        $this->assertCount(1, $filas);
        $this->assertSame($id, (int) $filas[0]['id']);
        $this->assertSame('Agro Sur', $filas[0]['nombre_proveedor']);
        $this->assertSame('V-12345678-9', $filas[0]['rif_proveedor']);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Bulto Agro']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $fila = $proveedor->getById($id);
        $this->assertSame($id, (int) $fila['id_proveedor']);
        $this->assertSame('Bulto Agro', $fila['nombre_proveedor']);

        $this->assertNull($proveedor->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_proveedor_existe(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $this->assertTrue($proveedor->exists($id));
        $this->assertFalse($proveedor->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Metro Agro']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $this->assertTrue($proveedor->delete($id));
        $this->assertFalse(SqliteProveedor::find($id)->isActivo());
        $this->assertSame([], SqliteProveedor::all());

        $this->assertTrue($proveedor->restore($id));
        $this->assertTrue(SqliteProveedor::find($id)->isActivo());
        $this->assertCount(1, SqliteProveedor::all());
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $proveedor = new SqliteProveedor([
            'nombre_proveedor'   => 'Semillas del Llano',
            'rif_proveedor'      => 'G-12345678-9',
            'contacto_vendedor'  => 'María Gómez',
            'telefono_proveedor' => '04161234567',
        ]);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $destino = new SqliteProveedor();
        $this->assertTrue($destino->loadById($id));
        $this->assertSame($id, $destino->getId());
        $this->assertSame('Semillas del Llano', $destino->getNombreProveedor());
        $this->assertSame('G-12345678-9', $destino->getRifProveedor());
        $this->assertSame('María Gómez', $destino->getContactoVendedor());
        $this->assertSame('04161234567', $destino->getTelefonoProveedor());

        $this->assertFalse((new SqliteProveedor())->loadById(99999));
    }

    #[Test]
    public function add_crea_un_proveedor(): void
    {
        $proveedor = new SqliteProveedor();

        $this->assertTrue($proveedor->add('Ferretería Los Andes', 'J-12345678-9', 'Ana Pérez', '04141234567'));

        $encontrado = SqliteProveedor::find((int) $proveedor->getId());
        $this->assertSame('Ferretería Los Andes', $encontrado->getNombreProveedor());
        $this->assertSame('J-12345678-9', $encontrado->getRifProveedor());
    }

    #[Test]
    public function update_modifica_un_proveedor_existente(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur', 'rif_proveedor' => 'V-12345678-9']);
        $this->assertTrue($proveedor->save());
        $id = (int) $proveedor->getId();

        $this->assertTrue($proveedor->update($id, 'Agro Sur Campo', 'J-12345678-9', 'Luis Pérez', '04141234567'));

        $fila = $proveedor->getById($id);
        $this->assertSame('Agro Sur Campo', $fila['nombre_proveedor']);
        $this->assertSame('J-12345678-9', $fila['rif_proveedor']);
        $this->assertSame('Luis Pérez', $fila['contacto_vendedor']);
    }

    #[Test]
    public function update_con_id_inexistente_lanza_excepcion(): void
    {
        $proveedor = new SqliteProveedor();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe el proveedor con ID: 99999');

        $proveedor->update(99999, 'Agro Sur');
    }

    #[Test]
    public function save_con_nombre_invalido_lanza_excepcion_de_validacion(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro 123']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe contener solo letras (2-50 caracteres)');

        $proveedor->save();
    }

    #[Test]
    public function getLastInsertId_devuelve_el_id_del_ultimo_insert(): void
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Agro Sur']);
        $this->assertTrue($proveedor->save());

        $this->assertSame($proveedor->getId(), $proveedor->getLastInsertId());
    }

    #[Test]
    public function save_propaga_el_error_si_la_tabla_no_existe(): void
    {
        // Proveedor::save() captura `Throwable` sin importarlo; al resolver el
        // nombre dentro del namespace del modelo el catch no coincide y la
        // PDOException se propaga. Se documenta el comportamiento real.
        $this->exec('DROP TABLE proveedores');

        $proveedor = new SqliteProveedor(['nombre_proveedor' => 'Hectarea Agro']);

        $this->expectException(\PDOException::class);

        $proveedor->save();
    }
}
