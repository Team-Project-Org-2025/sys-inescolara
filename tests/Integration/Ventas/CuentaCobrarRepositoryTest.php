<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteCuentaCobrar;
use SysInescolara\Tests\Support\SqliteTestCase;

final class CuentaCobrarRepositoryTest extends SqliteTestCase
{
    private int $clienteId;
    private int $ventaVencidaId;
    private int $ventaVigenteId;
    private int $loteId;

    protected function setUp(): void
    {
        parent::setUp();

        // CuentaCobrar ejecuta `SET NAMES ...` (MySQL) antes de consultar.
        // SqliteMysqlPdo lo convierte en no-op para poder ejecutar el SQL real.
        $this->pdo = SqliteMysqlPdo::create();
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
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE plantas (
                id_planta      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_especie     INTEGER DEFAULT NULL,
                nombre_tecnico TEXT DEFAULT \'\',
                nombre_comun   TEXT DEFAULT NULL,
                activo         INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE lote (
                id_lote         INTEGER PRIMARY KEY AUTOINCREMENT,
                id_planta       INTEGER NOT NULL,
                cantidad_actual INTEGER NOT NULL DEFAULT 0,
                activo          INTEGER NOT NULL DEFAULT 1
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
                fecha_venta       TEXT NOT NULL,
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
                FOREIGN KEY (id_venta) REFERENCES venta (id_venta)
            )
        ');

        $this->exec('
            CREATE TABLE pago_venta (
                id_pago       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_venta      INTEGER NOT NULL,
                metodo        TEXT NOT NULL,
                monto         REAL NOT NULL,
                referencia    TEXT DEFAULT NULL,
                fecha_pago    TEXT DEFAULT NULL,
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
                estatus             TEXT DEFAULT \'Activo\'
            )
        ');

        $this->pdo->exec('
            INSERT INTO "SysInescolara-Seguridad".usuarios
                (id_usuario, nombre_usuario, nombre_trabajador, apellido_trabajador, estatus)
            VALUES (1, \'carlos\', \'Carlos\', \'Ruiz\', \'Activo\')
        ');

        $this->clienteId = $this->insert('cliente', [
            'tipo_cedula_cliente' => 'V',
            'cedula_cliente'      => '12345678',
            'nombre_cliente'      => 'Ana',
            'apellido_cliente'    => 'Pérez',
            'contacto_cliente'    => '04141234567',
            'activo'              => 1,
        ]);

        $especieId = $this->insert('especie', ['nombre_especie' => 'Rosa', 'activo' => 1]);
        $plantaId = $this->insert('plantas', [
            'id_especie'     => $especieId,
            'nombre_tecnico' => 'Rosa spp',
            'nombre_comun'   => 'Rosa roja',
            'activo'         => 1,
        ]);
        $this->loteId = $this->insert('lote', [
            'id_planta'       => $plantaId,
            'cantidad_actual' => 20,
            'activo'          => 1,
        ]);

        $this->ventaVencidaId = $this->crearVentaCredito(
            'VEN-CRED-001',
            date('Y-m-d H:i:s', strtotime('-40 days')),
            date('Y-m-d', strtotime('-10 days')),
            2,
            100.0
        );

        $this->ventaVigenteId = $this->crearVentaCredito(
            'VEN-CRED-002',
            date('Y-m-d H:i:s', strtotime('-10 days')),
            date('Y-m-d', strtotime('+10 days')),
            3,
            100.0
        );

        $this->insert('pago_venta', [
            'id_venta'   => $this->ventaVigenteId,
            'metodo'     => 'efectivo',
            'monto'      => 100.0,
            'fecha_pago' => date('Y-m-d H:i:s'),
            'estado_pago' => 'registrado',
            'id_usuario' => 1,
        ]);

        // Venta de contado: no debe aparecer en las cuentas por cobrar.
        $this->insert('venta', [
            'referencia'  => 'VEN-CONT-001',
            'id_cliente'  => $this->clienteId,
            'id_usuario'  => 1,
            'tipo_venta'  => 'contado',
            'estado'      => 'completada',
            'fecha_venta' => date('Y-m-d H:i:s'),
            'activo'      => 1,
        ]);

        SqliteCuentaCobrar::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteCuentaCobrar::boot(null);
        parent::tearDown();
    }

    private function crearVentaCredito(string $referencia, string $fechaVenta, string $vencimiento, int $cantidad, float $precio): int
    {
        $ventaId = $this->insert('venta', [
            'referencia'        => $referencia,
            'id_cliente'        => $this->clienteId,
            'id_usuario'        => 1,
            'tipo_venta'        => 'credito',
            'estado'            => 'pendiente',
            'fecha_venta'       => $fechaVenta,
            'fecha_vencimiento' => $vencimiento,
            'activo'            => 1,
        ]);

        $this->insert('detalle_venta', [
            'id_venta'        => $ventaId,
            'tipo_item'       => 'planta',
            'id_lote'         => $this->loteId,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precio,
        ]);

        return $ventaId;
    }

    #[Test]
    public function obtenerTodos_devuelve_las_cuentas_de_credito_ordenadas(): void
    {
        $resultado = (new SqliteCuentaCobrar())->obtenerTodos();

        $this->assertSame(2, $resultado['total']);
        $this->assertSame(2, $resultado['filtered']);
        $this->assertCount(2, $resultado['data']);

        $this->assertSame($this->ventaVencidaId, (int) $resultado['data'][0]['id_venta']);
        $this->assertSame('vencido', $resultado['data'][0]['estado_cuenta']);
        $this->assertSame('Ana Pérez', $resultado['data'][0]['nombre_cliente']);
        $this->assertSame(200.0, (float) $resultado['data'][0]['monto_total']);
        $this->assertSame(0.0, (float) $resultado['data'][0]['total_pagado']);
        $this->assertSame(200.0, (float) $resultado['data'][0]['saldo_pendiente']);

        $this->assertSame($this->ventaVigenteId, (int) $resultado['data'][1]['id_venta']);
        $this->assertSame('vigente', $resultado['data'][1]['estado_cuenta']);
        $this->assertSame(100.0, (float) $resultado['data'][1]['total_pagado']);
        $this->assertSame(200.0, (float) $resultado['data'][1]['saldo_pendiente']);
    }

    #[Test]
    public function obtenerTodos_marca_pagada_la_cuenta_saldada(): void
    {
        $this->insert('pago_venta', [
            'id_venta'   => $this->ventaVigenteId,
            'metodo'     => 'efectivo',
            'monto'      => 200.0,
            'fecha_pago' => date('Y-m-d H:i:s'),
        ]);

        $resultado = (new SqliteCuentaCobrar())->obtenerTodos();

        $this->assertSame('pagado', $resultado['data'][1]['estado_cuenta']);
        $this->assertSame(0.0, (float) $resultado['data'][1]['saldo_pendiente']);
    }

    #[Test]
    public function obtenerTodos_pagina_los_resultados(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $primeraPagina = $cuenta->obtenerTodos(0, 1);
        $this->assertCount(1, $primeraPagina['data']);
        $this->assertSame(2, $primeraPagina['filtered']);
        $this->assertSame($this->ventaVencidaId, (int) $primeraPagina['data'][0]['id_venta']);

        $segundaPagina = $cuenta->obtenerTodos(1, 1);
        $this->assertCount(1, $segundaPagina['data']);
        $this->assertSame($this->ventaVigenteId, (int) $segundaPagina['data'][0]['id_venta']);
    }

    #[Test]
    public function obtenerTodos_busca_por_cliente_o_referencia(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $porCliente = $cuenta->obtenerTodos(0, 10, 'Ana');
        $this->assertSame(2, $porCliente['filtered']);

        $porReferencia = $cuenta->obtenerTodos(0, 10, 'VEN-CRED-002');
        $this->assertSame(1, $porReferencia['filtered']);
        $this->assertSame($this->ventaVigenteId, (int) $porReferencia['data'][0]['id_venta']);

        $sinResultados = $cuenta->obtenerTodos(0, 10, 'NO-EXISTE');
        $this->assertSame(0, $sinResultados['filtered']);
        $this->assertSame([], $sinResultados['data']);
        $this->assertSame(2, $sinResultados['total']);
    }

    #[Test]
    public function obtenerTodos_con_filtro_de_estado_no_es_portable_a_sqlite(): void
    {
        $this->markTestSkipped(
            'obtenerTodos usa HAVING sin GROUP BY para filtrar estado_cuenta; SQLite '
            . 'rechaza esa consulta ("HAVING clause on a non-aggregate query") y el '
            . 'modelo devuelve el fallback vacío. El resto de la consulta sí se prueba.'
        );
    }

    #[Test]
    public function obtenerPorId_devuelve_la_venta_con_detalles_y_pagos(): void
    {
        $cuenta = (new SqliteCuentaCobrar())->obtenerPorId($this->ventaVigenteId);

        $this->assertNotNull($cuenta);
        $this->assertSame($this->ventaVigenteId, (int) $cuenta['id_venta']);
        $this->assertSame('Ana Pérez', $cuenta['nombre_cliente']);
        $this->assertSame('V', $cuenta['tipo_cedula_cliente']);
        $this->assertSame('12345678', $cuenta['cedula_cliente']);
        $this->assertSame('04141234567', $cuenta['contacto']);
        $this->assertSame('Carlos Ruiz', $cuenta['trabajador']);
        $this->assertSame(300.0, (float) $cuenta['monto_total']);
        $this->assertSame(100.0, (float) $cuenta['total_pagado']);
        $this->assertSame(200.0, (float) $cuenta['saldo_pendiente']);

        $this->assertCount(1, $cuenta['detalles']);
        $this->assertSame('Rosa roja (Lote #' . $this->loteId . ')', $cuenta['detalles'][0]['producto']);
        $this->assertSame(3, (int) $cuenta['detalles'][0]['cantidad']);

        $this->assertCount(1, $cuenta['pagos']);
        $this->assertSame('Carlos Ruiz', $cuenta['pagos'][0]['cobrador']);
    }

    #[Test]
    public function obtenerPorId_devuelve_null_si_no_existe(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $this->assertNull($cuenta->obtenerPorId(99999));
        $this->assertNull($cuenta->obtenerPorId(0));
    }

    #[Test]
    public function obtenerPagos_devuelve_los_pagos_con_el_cobrador(): void
    {
        $pagos = (new SqliteCuentaCobrar())->obtenerPagos($this->ventaVigenteId);

        $this->assertCount(1, $pagos);
        $this->assertSame(100.0, (float) $pagos[0]['monto']);
        $this->assertSame('efectivo', $pagos[0]['metodo']);
        $this->assertSame('Carlos Ruiz', $pagos[0]['cobrador']);

        $this->assertSame([], (new SqliteCuentaCobrar())->obtenerPagos($this->ventaVencidaId));
    }

    #[Test]
    public function registrarPago_completa_la_venta_cuando_cubre_el_total(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $id = $cuenta->registrarPago(
            $this->ventaVigenteId,
            200.0,
            'efectivo',
            null,
            date('Y-m-d'),
            null,
            1,
            'Pago final'
        );

        $this->assertGreaterThan(0, $id);
        $this->assertDatabaseHas('pago_venta', [
            'id_pago'  => $id,
            'id_venta' => $this->ventaVigenteId,
            'monto'    => 200.0,
            'metodo'   => 'efectivo',
        ]);

        $venta = $this->fetchRow('venta', $this->ventaVigenteId, 'id_venta');
        $this->assertSame('completada', $venta['estado']);
    }

    #[Test]
    public function registrarPago_parcial_mantiene_la_venta_pendiente(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $id = $cuenta->registrarPago(
            $this->ventaVencidaId,
            50.0,
            'efectivo',
            null,
            date('Y-m-d'),
            null,
            1,
            null
        );

        $this->assertGreaterThan(0, $id);
        $this->assertSame('pendiente', $this->fetchRow('venta', $this->ventaVencidaId, 'id_venta')['estado']);
        $this->assertSame(50.0, (float) $this->fetchRow('pago_venta', $id, 'id_pago')['monto']);
    }

    #[Test]
    public function registrarPago_con_transferencia_exige_referencia_y_banco(): void
    {
        $cuenta = new SqliteCuentaCobrar();
        $hoy = date('Y-m-d');

        try {
            $cuenta->registrarPago($this->ventaVencidaId, 50.0, 'transferencia', null, $hoy, 'Banesco', 1, null);
            $this->fail('Se esperaba error por referencia ausente.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('La referencia es requerida para transferencias y pago móvil.', $e->getMessage());
        }

        try {
            $cuenta->registrarPago($this->ventaVencidaId, 50.0, 'transferencia', '12345', $hoy, 'Banesco', 1, null);
            $this->fail('Se esperaba error por referencia inválida.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('La referencia debe tener exactamente 6 dígitos numéricos.', $e->getMessage());
        }

        try {
            $cuenta->registrarPago($this->ventaVencidaId, 50.0, 'transferencia', '123456', $hoy, null, 1, null);
            $this->fail('Se esperaba error por banco ausente.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('El banco es requerido para este método de pago.', $e->getMessage());
        }

        $id = $cuenta->registrarPago($this->ventaVencidaId, 50.0, 'transferencia', '123456', $hoy, 'Banesco', 1, null);

        $this->assertDatabaseHas('pago_venta', [
            'id_pago'    => $id,
            'referencia' => '123456',
            'banco'      => 'Banesco',
        ]);
    }

    #[Test]
    public function registrarPago_rechaza_una_fecha_anterior_a_la_venta(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La fecha de pago no puede ser anterior a la fecha de venta');

        $cuenta->registrarPago(
            $this->ventaVencidaId,
            50.0,
            'efectivo',
            null,
            date('Y-m-d', strtotime('-41 days')),
            null,
            1,
            null
        );
    }

    #[Test]
    public function registrarPago_rechaza_una_venta_inexistente(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Venta no encontrada.');

        $cuenta->registrarPago(99999, 50.0, 'efectivo', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function actualizarEstadoVenta_marca_pendiente_o_completada(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $cuenta->actualizarEstadoVenta($this->ventaVigenteId);
        $this->assertSame('pendiente', $this->fetchRow('venta', $this->ventaVigenteId, 'id_venta')['estado']);

        $this->insert('pago_venta', [
            'id_venta'   => $this->ventaVigenteId,
            'metodo'     => 'efectivo',
            'monto'      => 200.0,
            'fecha_pago' => date('Y-m-d H:i:s'),
        ]);

        $cuenta->actualizarEstadoVenta($this->ventaVigenteId);
        $this->assertSame('completada', $this->fetchRow('venta', $this->ventaVigenteId, 'id_venta')['estado']);
    }

    #[Test]
    public function obtenerEstadisticas_calcula_los_totales_del_mes(): void
    {
        $estadisticas = (new SqliteCuentaCobrar())->obtenerEstadisticas();

        $this->assertSame(2, (int) $estadisticas['total_cuentas']);
        $this->assertSame(400.0, (float) $estadisticas['total_por_cobrar']);
        $this->assertSame(0, (int) $estadisticas['total_pagadas']);
        $this->assertSame(1, (int) $estadisticas['total_vencidas']);
        $this->assertSame(1, (int) $estadisticas['total_vigentes']);
        $this->assertSame(200.0, (float) $estadisticas['monto_vigente']);
        $this->assertSame(200.0, (float) $estadisticas['monto_vencido']);
        $this->assertSame(100.0, (float) $estadisticas['cobrado_mes']);
    }

    #[Test]
    public function obtenerEstadisticas_ignora_los_pagos_rechazados(): void
    {
        $this->insert('pago_venta', [
            'id_venta'    => $this->ventaVencidaId,
            'metodo'      => 'efectivo',
            'monto'       => 200.0,
            'fecha_pago'  => date('Y-m-d H:i:s'),
            'estado_pago' => 'rechazado',
        ]);

        $estadisticas = (new SqliteCuentaCobrar())->obtenerEstadisticas();

        $this->assertSame(400.0, (float) $estadisticas['total_por_cobrar']);
        $this->assertSame(100.0, (float) $estadisticas['cobrado_mes']);
    }

    #[Test]
    public function obtenerClientes_devuelve_solo_clientes_activos(): void
    {
        $this->insert('cliente', [
            'nombre_cliente'   => 'Zulema',
            'apellido_cliente' => 'Inactiva',
            'activo'           => 0,
        ]);

        $clientes = (new SqliteCuentaCobrar())->obtenerClientes();

        $this->assertCount(1, $clientes);
        $this->assertSame('Ana Pérez', $clientes[0]['nombre_cliente']);
        $this->assertSame('04141234567', $clientes[0]['contacto']);
    }

    #[Test]
    public function las_transacciones_se_pueden_confirmar_o_revertir(): void
    {
        $cuenta = new SqliteCuentaCobrar();

        $cuenta->iniciarTransaccion();
        $cuenta->iniciarTransaccion();
        $this->assertTrue($this->pdo->inTransaction());

        $this->insert('cliente', ['nombre_cliente' => 'Temporal', 'activo' => 1]);
        $cuenta->revertirTransaccion();

        $this->assertFalse($this->pdo->inTransaction());
        $this->assertDatabaseMissing('cliente', ['nombre_cliente' => 'Temporal']);

        $cuenta->iniciarTransaccion();
        $this->insert('cliente', ['nombre_cliente' => 'Confirmado', 'activo' => 1]);
        $cuenta->confirmarTransaccion();

        $this->assertFalse($this->pdo->inTransaction());
        $this->assertDatabaseHas('cliente', ['nombre_cliente' => 'Confirmado']);
    }
}
