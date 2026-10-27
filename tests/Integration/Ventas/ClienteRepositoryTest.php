<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Cliente;
use SysInescolara\Tests\Support\SqliteCliente;
use SysInescolara\Tests\Support\SqliteTestCase;

final class ClienteRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exec('
            CREATE TABLE cliente (
                id_cliente          INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_cedula_cliente TEXT DEFAULT NULL,
                cedula_cliente      TEXT DEFAULT NULL,
                nombre_cliente      TEXT NOT NULL,
                apellido_cliente    TEXT DEFAULT \'\',
                contacto_cliente    TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1
            )
        ');

        SqliteCliente::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteCliente::boot(null);
        parent::tearDown();
    }

    #[Test]
    public function save_inserta_el_cliente_y_asigna_id(): void
    {
        $cliente = new SqliteCliente([
            'nombre_cliente'      => 'Ana',
            'apellido_cliente'    => 'Pérez',
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
            'contacto_cliente'    => '04141234567',
        ]);

        $this->assertTrue($cliente->save());
        $this->assertNotNull($cliente->getId());

        $fila = $this->fetchRow('cliente', $cliente->getId(), 'id_cliente');
        $this->assertNotNull($fila);
        $this->assertSame('Ana', $fila['nombre_cliente']);
        $this->assertSame('Pérez', $fila['apellido_cliente']);
        $this->assertSame('V', $fila['tipo_cedula_cliente']);
        $this->assertSame('12345678', $fila['cedula_cliente']);
        $this->assertSame(1, (int) $fila['activo']);
    }

    #[Test]
    public function save_actualiza_un_cliente_existente(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Ana', 'apellido_cliente' => 'Pérez']);
        $this->assertTrue($cliente->save());
        $id = $cliente->getId();

        $cliente->setNombreCliente('Ana María')->setContactoCliente('0424-0000000');
        $this->assertTrue($cliente->save());

        $fila = $cliente->getById($id);
        $this->assertNotNull($fila);
        $this->assertSame('Ana María', $fila['nombre_cliente']);
        $this->assertSame('0424-0000000', $fila['contacto_cliente']);
        $this->assertSame(1, $this->countRows('cliente'));
    }

    #[Test]
    public function save_devuelve_false_si_el_cliente_ya_no_existe(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Ana']);
        $this->assertTrue($cliente->save());

        $this->exec('DELETE FROM cliente');

        $cliente->setNombreCliente('Otra');
        $this->assertFalse($cliente->save());
        $this->assertSame(0, $this->countRows('cliente'));
    }

    #[Test]
    public function save_con_cedula_invalida_no_inserta_nada(): void
    {
        $cliente = new SqliteCliente([
            'nombre_cliente'      => 'Ana',
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '123',
        ]);

        try {
            $cliente->save();
            $this->fail('Se esperaba una excepción de validación.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Número de C.I. inválido', $e->getMessage());
        }

        $this->assertSame(0, $this->countRows('cliente'));
        $this->assertNull($cliente->getId());
    }

    #[Test]
    public function find_devuelve_el_cliente_o_null(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Luis', 'apellido_cliente' => 'Gómez']);
        $this->assertTrue($cliente->save());

        $encontrado = SqliteCliente::find($cliente->getId());

        $this->assertInstanceOf(Cliente::class, $encontrado);
        $this->assertSame($cliente->getId(), $encontrado->getId());
        $this->assertSame('Luis', $encontrado->getNombreCliente());
        $this->assertSame('Gómez', $encontrado->getApellidoCliente());

        $this->assertNull(SqliteCliente::find(99999));
    }

    #[Test]
    public function getAll_devuelve_solo_activos_ordenados_por_nombre(): void
    {
        foreach (['Zulema', 'Ana', 'Marta'] as $nombre) {
            $cliente = new SqliteCliente(['nombre_cliente' => $nombre, 'apellido_cliente' => 'Apellido']);
            $this->assertTrue($cliente->save());
        }

        $idMarta = (int) $this->pdo->query("SELECT id_cliente FROM cliente WHERE nombre_cliente = 'Marta'")->fetchColumn();
        $this->assertTrue((new SqliteCliente())->delete($idMarta));

        $filas = (new SqliteCliente())->getAll();

        $this->assertSame(['Ana', 'Zulema'], array_column($filas, 'nombre_cliente'));
        $this->assertSame('Ana Apellido', $filas[0]['nombre_completo']);
    }

    #[Test]
    public function getAll_expone_la_cedula_completa_cuando_hay_datos(): void
    {
        $cliente = new SqliteCliente([
            'nombre_cliente'      => 'Ana',
            'apellido_cliente'    => 'Pérez',
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
        ]);
        $this->assertTrue($cliente->save());

        $filas = (new SqliteCliente())->getAll();

        $this->assertCount(1, $filas);
        $this->assertSame('V-12345678', $filas[0]['cedula_completa']);
        $this->assertSame('Ana Pérez', $filas[0]['nombre_completo']);
        $this->assertSame($cliente->getId(), (int) $filas[0]['id']);
    }

    #[Test]
    public function getById_devuelve_la_fila_o_null(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Bulto']);
        $this->assertTrue($cliente->save());

        $fila = $cliente->getById($cliente->getId());
        $this->assertNotNull($fila);
        $this->assertSame($cliente->getId(), (int) $fila['id_cliente']);
        $this->assertSame('Bulto', $fila['nombre_cliente']);

        $this->assertNull($cliente->getById(99999));
    }

    #[Test]
    public function exists_refleja_si_el_cliente_existe(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Guacal']);
        $this->assertTrue($cliente->save());

        $this->assertTrue($cliente->exists($cliente->getId()));
        $this->assertFalse($cliente->exists(99999));
    }

    #[Test]
    public function delete_desactiva_y_restore_reactiva(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Metro', 'apellido_cliente' => 'Línea']);
        $this->assertTrue($cliente->save());
        $id = $cliente->getId();

        $this->assertTrue($cliente->delete($id));
        $this->assertFalse(SqliteCliente::find($id)->isActivo());
        $this->assertSame([], SqliteCliente::all());

        $this->assertTrue($cliente->restore($id));
        $this->assertTrue(SqliteCliente::find($id)->isActivo());
        $this->assertCount(1, SqliteCliente::all());
    }

    #[Test]
    public function loadById_carga_los_atributos_o_devuelve_false(): void
    {
        $cliente = new SqliteCliente([
            'nombre_cliente'      => 'Rosa',
            'apellido_cliente'    => 'Ríos',
            'tipo_cedula_cliente' => 'E',
            'cedula_cliente'      => '87654321',
            'contacto_cliente'    => '0416-1112233',
        ]);
        $this->assertTrue($cliente->save());

        $destino = new SqliteCliente();
        $this->assertTrue($destino->loadById($cliente->getId()));

        $this->assertSame($cliente->getId(), $destino->getId());
        $this->assertSame('Rosa', $destino->getNombreCliente());
        $this->assertSame('Ríos', $destino->getApellidoCliente());
        $this->assertSame('E', $destino->getTipoCedulaCliente());
        $this->assertSame('87654321', $destino->getCedulaCliente());
        $this->assertSame('0416-1112233', $destino->getContactoCliente());
        $this->assertTrue($destino->isActivo());

        $this->assertFalse((new SqliteCliente())->loadById(99999));
    }

    #[Test]
    public function where_filtra_por_columna_y_solo_activos(): void
    {
        foreach (['Rosa', 'Rosa', 'Otra'] as $nombre) {
            $cliente = new SqliteCliente(['nombre_cliente' => $nombre, 'apellido_cliente' => 'Apellido']);
            $this->assertTrue($cliente->save());
        }

        $idOtra = (int) $this->pdo->query("SELECT id_cliente FROM cliente WHERE nombre_cliente = 'Otra'")->fetchColumn();
        $this->assertTrue((new SqliteCliente())->delete($idOtra));

        $resultados = SqliteCliente::where('nombre_cliente', 'Rosa');

        $this->assertCount(2, $resultados);
        foreach ($resultados as $resultado) {
            $this->assertInstanceOf(Cliente::class, $resultado);
            $this->assertSame('Rosa', $resultado->getNombreCliente());
        }
    }

    #[Test]
    public function getLastInsertId_devuelve_el_ultimo_id_insertado(): void
    {
        $cliente = new SqliteCliente(['nombre_cliente' => 'Última']);
        $this->assertTrue($cliente->save());

        $this->assertSame($cliente->getId(), $cliente->getLastInsertId());
    }

    #[Test]
    public function save_devuelve_false_si_la_tabla_no_existe(): void
    {
        $this->exec('DROP TABLE cliente');

        $cliente = new SqliteCliente(['nombre_cliente' => 'Sin tabla']);

        $this->assertFalse($cliente->save());
    }
}
