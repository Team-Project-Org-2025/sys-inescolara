<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\models\Venta;
use SysInescolara\Tests\Support\SqliteTestCase;
use SysInescolara\Tests\Support\SqliteVenta;

final class VentaRepositoryTest extends SqliteTestCase
{
    private int $clienteId;
    private int $loteId;
    private int $insumoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachSecurityDatabase();

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

        $this->exec('
            CREATE TABLE especie (
                id_especie     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_especie TEXT NOT NULL,
                descripcion    TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT \'\',
                nombre_comun   TEXT DEFAULT NULL,
                cantidad_total INTEGER NOT NULL DEFAULT 0,
                imagen         TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (id_especie) REFERENCES especie (id_especie)
            )
        ');

        $this->exec('
            CREATE TABLE estado (
                id_estado INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre    TEXT NOT NULL,
                activo    INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE lote (
                id_lote             INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta           INTEGER NOT NULL,
                id_ubicacion        INTEGER DEFAULT NULL,
                fecha_siembra       TEXT NOT NULL,
                cantidad_inicial    INTEGER NOT NULL,
                cantidad_actual     INTEGER NOT NULL,
                costo_unitario      REAL DEFAULT 0.00,
                id_estado           INTEGER DEFAULT NULL,
                id_categoria        INTEGER DEFAULT NULL,
                id_origen           INTEGER DEFAULT NULL,
                observacion         TEXT DEFAULT NULL,
                imagen              TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia REAL NOT NULL DEFAULT 30.00,
                FOREIGN KEY (id_planta) REFERENCES plantas (id_planta),
                FOREIGN KEY (id_estado) REFERENCES estado (id_estado)
            )
        ');

        $this->exec('
            CREATE TABLE unidad_medida (
                id_unidad_medida     INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_unidad_medida TEXT NOT NULL,
                simbolo              TEXT DEFAULT NULL,
                activo               INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE insumo (
                id_insumo              INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida       INTEGER NOT NULL,
                nombre_insumo          TEXT NOT NULL,
                categoria              TEXT DEFAULT NULL,
                stock_actual           REAL NOT NULL DEFAULT 0.00,
                costo_unitario_actual  REAL NOT NULL DEFAULT 0.00,
                activo                 INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE venta (
                id_venta          INTEGER PRIMARY KEY AUTOINCREMENT,
                referencia        TEXT NOT NULL,
                id_cliente        INTEGER NOT NULL,
                id_usuario        INTEGER NOT NULL,
                tipo_venta        TEXT NOT NULL DEFAULT \'contado\',
                estado            TEXT NOT NULL DEFAULT \'completada\',
                iva_porcentaje    REAL NOT NULL DEFAULT 16.00,
                fecha_venta       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_vencimiento TEXT DEFAULT NULL,
                observaciones     TEXT DEFAULT NULL,
                activo            INTEGER NOT NULL DEFAULT 1,
                created_at        TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at        TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
            )
        ');

        $this->exec('
            CREATE TABLE detalle_venta (
                id_detalle_venta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_venta         INTEGER NOT NULL,
                tipo_item        TEXT NOT NULL DEFAULT \'planta\',
                id_lote          INTEGER DEFAULT NULL,
                id_insumo        INTEGER DEFAULT NULL,
                cantidad         INTEGER NOT NULL,
                precio_unitario  REAL NOT NULL,
                FOREIGN KEY (id_venta) REFERENCES venta (id_venta),
                FOREIGN KEY (id_lote) REFERENCES lote (id_lote)
            )
        ');

        $this->exec('
            CREATE TABLE pago_venta (
                id_pago       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_venta      INTEGER NOT NULL,
                metodo        TEXT NOT NULL,
                monto         REAL NOT NULL,
                referencia    TEXT DEFAULT NULL,
                fecha_pago    TEXT DEFAULT CURRENT_TIMESTAMP,
                estado_pago   TEXT NOT NULL DEFAULT \'registrado\',
                banco         TEXT DEFAULT NULL,
                id_usuario    INTEGER DEFAULT NULL,
                observaciones TEXT DEFAULT NULL,
                created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
            )
        ');

        $this->exec('
            CREATE TABLE "SysInescolara-Seguridad".usuarios (
                id_usuario          INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_usuario      TEXT NOT NULL,
                nombre_trabajador   TEXT DEFAULT NULL,
                apellido_trabajador TEXT DEFAULT NULL,
                cedula_trabajador   TEXT DEFAULT NULL,
                telefono_trabajador TEXT DEFAULT NULL,
                cargo               TEXT DEFAULT NULL,
                estatus             TEXT DEFAULT \'Activo\'
            )
        ');

        $this->pdo->exec('
            INSERT INTO "SysInescolara-Seguridad".usuarios
                (id_usuario, nombre_usuario, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo, estatus)
            VALUES
                (1, \'vendedor\', \'Carlos\', \'Ruiz\', \'V11111111\', \'04141111111\', \'Vendedor\', \'Activo\')
        ');

        $this->exec("INSERT INTO estado (id_estado, nombre, activo) VALUES (5, 'vivo', 1), (6, 'cuarentena', 1), (7, 'muerto', 1)");

        $this->clienteId = $this->crearCliente();
        $this->loteId = $this->crearLote(10);
        $this->insumoId = $this->crearInsumo(5.0);

        SqliteVenta::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteVenta::boot(null);
        parent::tearDown();
    }

    private function crearCliente(string $nombre = 'Ana', string $apellido = 'Pérez'): int
    {
        return $this->insert('cliente', [
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
            'nombre_cliente'      => $nombre,
            'apellido_cliente'    => $apellido,
            'contacto_cliente'    => '04141234567',
            'activo'              => 1,
        ]);
    }

    private function crearLote(int $cantidad = 10, string $nombreComun = 'Rosa roja'): int
    {
        $especieId = $this->insert('especie', [
            'nombre_especie' => 'Rosa',
            'descripcion'    => 'Rosa de jardín',
            'activo'         => 1,
        ]);

        $plantaId = $this->insert('plantas', [
            'id_especie'     => $especieId,
            'nombre_tecnico' => 'Rosa spp',
            'nombre_comun'   => $nombreComun,
            'cantidad_total' => $cantidad,
            'activo'         => 1,
        ]);

        return $this->insert('lote', [
            'id_planta'           => $plantaId,
            'fecha_siembra'       => '2026-01-15',
            'cantidad_inicial'    => $cantidad,
            'cantidad_actual'     => $cantidad,
            'costo_unitario'      => 50.0,
            'id_estado'           => 5,
            'activo'              => 1,
            'porcentaje_ganancia' => 30.0,
        ]);
    }

    private function crearInsumo(float $stock = 5.0): int
    {
        $unidadId = $this->insert('unidad_medida', [
            'nombre_unidad_medida' => 'Kilogramo',
            'simbolo'              => 'kg',
            'activo'               => 1,
        ]);

        return $this->insert('insumo', [
            'id_unidad_medida'      => $unidadId,
            'nombre_insumo'         => 'Fertilizante',
            'categoria'             => 'abono',
            'stock_actual'          => $stock,
            'costo_unitario_actual' => 10.0,
            'activo'                => 1,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>>|null $pagos
     * @param array<int, array<string, mixed>>|null $productos
     */
    private function registrarVenta(
        array $datos = [],
        ?array $productos = null,
        ?array $pagos = null
    ): int {
        $venta = new SqliteVenta();

        $datos += [
            'id_cliente' => $this->clienteId,
            'id_usuario' => 1,
            'tipo_venta' => 'contado',
        ];
        $datos['productos'] = $productos ?? [
            ['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 100.0],
        ];

        if ($pagos !== null) {
            $datos['pagos'] = $pagos;
        } elseif ($datos['tipo_venta'] === 'contado') {
            $datos['pagos'] = [['metodo' => 'efectivo', 'monto' => 200.0]];
        }

        return $venta->agregar($datos);
    }

    #[Test]
    public function agregar_venta_de_contado_con_pago_completo(): void
    {
        $venta = new SqliteVenta();

        $id = $venta->agregar([
            'id_cliente'    => $this->clienteId,
            'id_usuario'    => 1,
            'tipo_venta'    => 'contado',
            'observaciones' => 'Venta de prueba',
            'productos'     => [['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 100.0]],
            'pagos'         => [['metodo' => 'efectivo', 'monto' => 200.0]],
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertSame($id, $venta->getId());
        $this->assertSame('completada', $venta->getEstado());
        $this->assertStringStartsWith('VEN-' . date('Ymd') . '-', (string) $venta->getReferencia());
        $this->assertNull($venta->getFechaVencimiento());

        $fila = $this->fetchRow('venta', $id, 'id_venta');
        $this->assertNotNull($fila);
        $this->assertSame('contado', $fila['tipo_venta']);
        $this->assertSame('completada', $fila['estado']);
        $this->assertSame('Venta de prueba', $fila['observaciones']);
        $this->assertSame(1, (int) $fila['activo']);
        $this->assertNull($fila['fecha_vencimiento']);

        $lote = $this->fetchRow('lote', $this->loteId, 'id_lote');
        $this->assertSame(8, (int) $lote['cantidad_actual']);

        $this->assertDatabaseCount('detalle_venta', 1, [
            'id_venta'  => $id,
            'tipo_item' => 'planta',
            'id_lote'   => $this->loteId,
            'id_insumo' => null,
            'cantidad'  => 2,
        ]);
        $this->assertDatabaseCount('pago_venta', 1, [
            'id_venta' => $id,
            'metodo'   => 'efectivo',
            'monto'    => 200.0,
        ]);
    }

    #[Test]
    public function agregar_venta_de_credito_sin_pago_queda_pendiente(): void
    {
        $fechaVenta = date('Y-m-d H:i:s');

        $venta = new SqliteVenta();
        $id = $venta->agregar([
            'id_cliente'  => $this->clienteId,
            'id_usuario'  => 1,
            'tipo_venta'  => 'credito',
            'fecha_venta' => $fechaVenta,
            'productos'   => [['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 100.0]],
        ]);

        $this->assertSame('pendiente', $venta->getEstado());

        $fila = $this->fetchRow('venta', $id, 'id_venta');
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(
            date('Y-m-d', strtotime($fechaVenta . ' +30 days')),
            $fila['fecha_vencimiento']
        );
        $this->assertSame(0, $this->countRows('pago_venta'));
        $this->assertSame(8, (int) $this->fetchRow('lote', $this->loteId, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function agregar_venta_de_credito_con_pago_parcial_registra_el_pago(): void
    {
        $id = $this->registrarVenta(
            ['tipo_venta' => 'credito'],
            null,
            [['metodo' => 'transferencia', 'monto' => 50.0, 'referencia' => '123456']]
        );

        $this->assertSame(1, $this->countRows('pago_venta'));
        $this->assertDatabaseHas('pago_venta', [
            'id_venta'   => $id,
            'metodo'     => 'transferencia',
            'monto'      => 50.0,
            'referencia' => '123456',
        ]);

        $fila = (new SqliteVenta())->getById($id);
        $this->assertNotNull($fila);
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(50.0, (float) $fila['total_pagado']);
        $this->assertSame(200.0, (float) $fila['monto_subtotal']);
    }

    #[Test]
    public function agregar_venta_de_contado_con_pago_incorrecto_lanza_excepcion_y_revierte(): void
    {
        $venta = new SqliteVenta();

        try {
            $venta->agregar([
                'id_cliente' => $this->clienteId,
                'id_usuario' => 1,
                'tipo_venta' => 'contado',
                'productos'  => [['id_lote' => $this->loteId, 'cantidad' => 2, 'precio_unitario' => 100.0]],
                'pagos'      => [['metodo' => 'efectivo', 'monto' => 10.0]],
            ]);
            $this->fail('Se esperaba una excepción por pagos incompletos.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('no coincide con el total de la venta', $e->getMessage());
        }

        $this->assertSame(0, $this->countRows('venta'));
        $this->assertSame(0, $this->countRows('detalle_venta'));
        $this->assertSame(0, $this->countRows('pago_venta'));
        $this->assertSame(10, (int) $this->fetchRow('lote', $this->loteId, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function agregar_venta_con_insumo_descuenta_el_stock(): void
    {
        $id = $this->registrarVenta(
            [],
            [['tipo_item' => 'insumo', 'id_insumo' => $this->insumoId, 'cantidad' => 2, 'precio_unitario' => 20.0]],
            [['metodo' => 'efectivo', 'monto' => 40.0]]
        );

        $this->assertGreaterThan(0, $id);

        $insumo = $this->fetchRow('insumo', $this->insumoId, 'id_insumo');
        $this->assertSame(3.0, (float) $insumo['stock_actual']);

        $this->assertDatabaseCount('detalle_venta', 1, [
            'id_venta'  => $id,
            'tipo_item' => 'insumo',
            'id_lote'   => null,
            'id_insumo' => $this->insumoId,
            'cantidad'  => 2,
        ]);
    }

    #[Test]
    public function agregar_venta_con_lote_inexistente_lanza_excepcion_y_revierte(): void
    {
        $venta = new SqliteVenta();

        try {
            $venta->agregar([
                'id_cliente' => $this->clienteId,
                'id_usuario' => 1,
                'productos'  => [['id_lote' => 9999, 'cantidad' => 1, 'precio_unitario' => 10.0]],
                'pagos'      => [['metodo' => 'efectivo', 'monto' => 10.0]],
            ]);
            $this->fail('Se esperaba una excepción por lote inexistente.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('El lote ID 9999 no existe.', $e->getMessage());
        }

        $this->assertSame(0, $this->countRows('venta'));
        $this->assertSame(0, $this->countRows('detalle_venta'));
    }

    #[Test]
    public function agregar_sin_id_usuario_lanza_excepcion_de_validacion(): void
    {
        $venta = new SqliteVenta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_usuario');

        $venta->agregar([
            'id_cliente' => $this->clienteId,
            'productos'  => [['id_lote' => $this->loteId, 'cantidad' => 1, 'precio_unitario' => 10.0]],
        ]);
    }

    #[Test]
    public function getAll_devuelve_ventas_activas_con_cliente_y_totales(): void
    {
        $id = $this->registrarVenta();

        $filas = (new SqliteVenta())->getAll();

        $this->assertCount(1, $filas);
        $this->assertSame($id, (int) $filas[0]['id']);
        $this->assertSame('Ana Pérez', $filas[0]['nombre_cliente']);
        $this->assertSame('vendedor', $filas[0]['nombre_usuario']);
        $this->assertSame(200, (int) $filas[0]['monto_subtotal']);
        $this->assertSame(200, (int) $filas[0]['total_pagado']);
        $this->assertEqualsWithDelta(172.41, (float) $filas[0]['monto_sin_iva'], 0.01);
    }

    #[Test]
    public function getAll_omite_las_ventas_canceladas(): void
    {
        $id = $this->registrarVenta();
        $this->assertTrue((new SqliteVenta())->cancelar($id));

        $this->assertSame([], (new SqliteVenta())->getAll());
        $this->assertSame([], SqliteVenta::all());
    }

    #[Test]
    public function getById_devuelve_la_venta_con_totales_o_null(): void
    {
        $id = $this->registrarVenta();

        $fila = (new SqliteVenta())->getById($id);

        $this->assertNotNull($fila);
        $this->assertSame($id, (int) $fila['id_venta']);
        $this->assertSame('contado', $fila['tipo_venta']);
        $this->assertSame('completada', $fila['estado']);
        $this->assertSame('Ana Pérez', $fila['nombre_cliente']);
        $this->assertSame(200, (int) $fila['monto_subtotal']);
        $this->assertSame(200, (int) $fila['total_pagado']);

        $this->assertNull((new SqliteVenta())->getById(99999));
    }

    #[Test]
    public function obtenerVentaConDetalles_incluye_detalles_pagos_y_montos(): void
    {
        $id = $this->registrarVenta();

        $venta = (new SqliteVenta())->obtenerVentaConDetalles($id);

        $this->assertNotNull($venta);
        $this->assertCount(1, $venta['detalles']);
        $this->assertCount(1, $venta['pagos']);
        $this->assertEqualsWithDelta(172.4138, (float) $venta['monto_sin_iva'], 0.001);
        $this->assertEqualsWithDelta(27.5862, (float) $venta['monto_iva'], 0.001);
        $this->assertEqualsWithDelta(200.0, (float) $venta['monto_total'], 0.001);

        $this->assertNull((new SqliteVenta())->obtenerVentaConDetalles(99999));
    }

    #[Test]
    public function obtenerDetalles_devuelve_los_nombres_de_planta_y_especie(): void
    {
        $id = $this->registrarVenta();

        $detalles = (new SqliteVenta())->obtenerDetalles($id);

        $this->assertCount(1, $detalles);
        $this->assertSame('planta', $detalles[0]['tipo_item']);
        $this->assertSame('Rosa roja', $detalles[0]['planta_nombre']);
        $this->assertSame('Rosa', $detalles[0]['especie_nombre']);
        $this->assertSame('Rosa spp', $detalles[0]['nombre_tecnico']);
        $this->assertNull($detalles[0]['unidad_simbolo']);
        $this->assertSame(2, (int) $detalles[0]['cantidad']);
        $this->assertSame(200, (int) $detalles[0]['sub_total']);
        $this->assertSame(8, (int) $detalles[0]['cantidad_actual']);
    }

    #[Test]
    public function obtenerDetalles_de_un_insumo_devuelve_la_unidad_de_medida(): void
    {
        $id = $this->registrarVenta(
            [],
            [['tipo_item' => 'insumo', 'id_insumo' => $this->insumoId, 'cantidad' => 2, 'precio_unitario' => 20.0]],
            [['metodo' => 'efectivo', 'monto' => 40.0]]
        );

        $detalles = (new SqliteVenta())->obtenerDetalles($id);

        $this->assertCount(1, $detalles);
        $this->assertSame('insumo', $detalles[0]['tipo_item']);
        $this->assertSame('Fertilizante', $detalles[0]['planta_nombre']);
        $this->assertSame('kg', $detalles[0]['especie_nombre']);
        $this->assertSame('kg', $detalles[0]['unidad_simbolo']);
        $this->assertSame(3, (int) $detalles[0]['cantidad_actual']);
    }

    #[Test]
    public function obtenerPagos_devuelve_los_pagos_registrados(): void
    {
        $id = $this->registrarVenta(
            ['tipo_venta' => 'credito'],
            null,
            [
                ['metodo' => 'efectivo', 'monto' => 50.0],
                ['metodo' => 'transferencia', 'monto' => 25.0, 'referencia' => '654321'],
            ]
        );

        $pagos = (new SqliteVenta())->obtenerPagos($id);

        $this->assertCount(2, $pagos);
        $this->assertSame(75.0, array_sum(array_column($pagos, 'monto')));
    }

    #[Test]
    public function cancelar_devuelve_el_stock_y_marca_la_venta_como_cancelada(): void
    {
        $id = $this->registrarVenta();
        $this->assertSame(8, (int) $this->fetchRow('lote', $this->loteId, 'id_lote')['cantidad_actual']);
        $this->exec('UPDATE lote SET id_estado = 7');

        $this->assertTrue((new SqliteVenta())->cancelar($id));

        $venta = $this->fetchRow('venta', $id, 'id_venta');
        $this->assertSame(0, (int) $venta['activo']);
        $this->assertSame('cancelada', $venta['estado']);

        $lote = $this->fetchRow('lote', $this->loteId, 'id_lote');
        $this->assertSame(10, (int) $lote['cantidad_actual']);
        $this->assertSame(5, (int) $lote['id_estado']);
    }

    #[Test]
    public function cancelar_una_venta_sin_detalles_devuelve_false(): void
    {
        $id = $this->insert('venta', [
            'referencia' => 'VEN-SIN-DETALLE',
            'id_cliente' => $this->clienteId,
            'id_usuario' => 1,
            'tipo_venta' => 'contado',
            'estado'     => 'completada',
            'activo'     => 1,
        ]);

        $this->assertFalse((new SqliteVenta())->cancelar($id));
        $this->assertSame(1, (int) $this->fetchRow('venta', $id, 'id_venta')['activo']);
    }

    #[Test]
    public function restore_reactiva_la_venta_y_descuenta_el_stock(): void
    {
        $id = $this->registrarVenta();
        $venta = new SqliteVenta();
        $this->assertTrue($venta->cancelar($id));

        $this->assertTrue($venta->restore($id));

        $fila = $this->fetchRow('venta', $id, 'id_venta');
        $this->assertSame(1, (int) $fila['activo']);
        $this->assertSame('completada', $fila['estado']);
        $this->assertSame(8, (int) $this->fetchRow('lote', $this->loteId, 'id_lote')['cantidad_actual']);
    }

    #[Test]
    public function delete_es_un_alias_de_cancelar(): void
    {
        $id = $this->registrarVenta();

        $this->assertTrue((new SqliteVenta())->delete($id));

        $fila = $this->fetchRow('venta', $id, 'id_venta');
        $this->assertSame(0, (int) $fila['activo']);
        $this->assertSame('cancelada', $fila['estado']);
    }

    #[Test]
    public function obtenerUltimoId_y_getLastInsertId_devuelven_la_venta_creada(): void
    {
        $id = $this->registrarVenta();

        $venta = new SqliteVenta();

        $this->assertSame($id, $venta->obtenerUltimoId());

        $venta->loadById($id);
        $this->assertSame($id, $venta->getLastInsertId());
        $this->assertSame('contado', $venta->getTipoVenta());
        $this->assertSame('completada', $venta->getEstado());
        $this->assertSame($this->clienteId, $venta->getIdCliente());
        $this->assertSame(1, $venta->getIdUsuario());
    }

    #[Test]
    public function find_devuelve_la_venta_o_null(): void
    {
        $id = $this->registrarVenta();

        $encontrada = SqliteVenta::find($id);

        $this->assertInstanceOf(Venta::class, $encontrada);
        $this->assertSame($id, $encontrada->getId());
        $this->assertSame('contado', $encontrada->getTipoVenta());

        $this->assertNull(SqliteVenta::find(99999));
    }

    #[Test]
    public function buscarClientes_filtra_por_nombre(): void
    {
        $venta = new SqliteVenta();

        $resultados = $venta->buscarClientes('Ana');

        $this->assertCount(1, $resultados);
        $this->assertSame('Ana Pérez', $resultados[0]['nombre_cliente']);
        $this->assertSame('12345678', $resultados[0]['cedula_cliente']);

        $this->assertSame([], $venta->buscarClientes('NoExiste'));
    }

    #[Test]
    public function obtenerLotesDisponibles_devuelve_plantas_e_insumos_con_stock(): void
    {
        $venta = new SqliteVenta();

        $productos = $venta->obtenerLotesDisponibles('');

        $this->assertCount(2, $productos);
        $this->assertSame('planta', $productos[0]['tipo_item']);
        $this->assertSame('Rosa roja', $productos[0]['planta_nombre']);
        $this->assertEqualsWithDelta(65.0, (float) $productos[0]['precio_unitario'], 0.01);
        $this->assertSame('insumo', $productos[1]['tipo_item']);
        $this->assertSame('Fertilizante', $productos[1]['planta_nombre']);

        $filtrados = $venta->obtenerLotesDisponibles('Rosa');
        $this->assertCount(1, $filtrados);
        $this->assertSame('planta', $filtrados[0]['tipo_item']);
    }

    #[Test]
    public function obtenerTrabajadoresActivos_devuelve_solo_usuarios_activos(): void
    {
        $this->pdo->exec('
            INSERT INTO "SysInescolara-Seguridad".usuarios
                (id_usuario, nombre_usuario, nombre_trabajador, apellido_trabajador, estatus)
            VALUES (2, \'inactivo\', \'Pedro\', \'Pérez\', \'Inactivo\')
        ');

        $trabajadores = (new SqliteVenta())->obtenerTrabajadoresActivos();

        $this->assertCount(1, $trabajadores);
        $this->assertSame('Carlos', $trabajadores[0]['nombre_trabajador']);
        $this->assertSame('Vendedor', $trabajadores[0]['cargo']);
    }
}
