<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Compras;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteProveedor;
use SysInescolara\Tests\Support\SqlitePurchase;
use SysInescolara\Tests\Support\SqliteTestCase;

final class PurchaseRepositoryTest extends SqliteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        $this->exec('
            CREATE TABLE compra (
                id_compra          INTEGER PRIMARY KEY AUTOINCREMENT,
                id_proveedor       INTEGER NOT NULL,
                fecha_compra       TEXT NOT NULL,
                fecha_recepcion    TEXT DEFAULT NULL,
                tipo_comprobante   TEXT DEFAULT \'Factura\',
                numero_comprobante TEXT DEFAULT NULL,
                subtotal           NUMERIC NOT NULL DEFAULT 0,
                iva                NUMERIC NOT NULL DEFAULT 0,
                total              NUMERIC NOT NULL DEFAULT 0,
                estado             TEXT NOT NULL DEFAULT \'pendiente\',
                observacion        TEXT DEFAULT NULL,
                activo             INTEGER NOT NULL DEFAULT 1,
                created_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->exec('
            CREATE TABLE compra_detalle (
                id_detalle      INTEGER PRIMARY KEY AUTOINCREMENT,
                id_compra       INTEGER NOT NULL,
                tipo_item       TEXT DEFAULT NULL,
                id_insumo       INTEGER DEFAULT NULL,
                id_herramienta  INTEGER DEFAULT NULL,
                id_planta       INTEGER DEFAULT NULL,
                categoria_lote  TEXT DEFAULT \'germinado\',
                id_ubicacion    INTEGER DEFAULT NULL,
                cantidad        NUMERIC NOT NULL,
                costo_unitario  NUMERIC NOT NULL,
                activo          INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE insumo (
                id_insumo              INTEGER PRIMARY KEY AUTOINCREMENT,
                id_unidad_medida       INTEGER NOT NULL,
                nombre_insumo          TEXT NOT NULL,
                categoria              TEXT DEFAULT NULL,
                stock_actual           NUMERIC NOT NULL DEFAULT 0,
                costo_unitario_actual  NUMERIC NOT NULL DEFAULT 0,
                activo                 INTEGER NOT NULL DEFAULT 1
            )
        ');

        $this->exec('
            CREATE TABLE herramienta (
                id_herramienta             INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_herramienta         TEXT NOT NULL,
                cantidad                   INTEGER NOT NULL DEFAULT 1,
                tipo                       TEXT DEFAULT NULL,
                estado                     TEXT NOT NULL DEFAULT \'disponible\',
                fecha_adquisicion          TEXT DEFAULT NULL,
                fecha_ultimo_mantenimiento TEXT DEFAULT NULL,
                observacion                TEXT DEFAULT NULL,
                activo                     INTEGER NOT NULL DEFAULT 1
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
                activo         INTEGER NOT NULL DEFAULT 1
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
                costo_unitario      NUMERIC DEFAULT 0,
                id_estado           INTEGER DEFAULT NULL,
                id_categoria        INTEGER DEFAULT NULL,
                id_origen           INTEGER DEFAULT NULL,
                observacion         TEXT DEFAULT NULL,
                imagen              TEXT DEFAULT NULL,
                activo              INTEGER NOT NULL DEFAULT 1,
                porcentaje_ganancia NUMERIC NOT NULL DEFAULT 30
            )
        ');

        $this->exec('
            CREATE TABLE cuentas_pagar (
                id_cuenta_pagar   INTEGER PRIMARY KEY AUTOINCREMENT,
                id_compra         INTEGER NOT NULL,
                monto_total       NUMERIC NOT NULL DEFAULT 0,
                saldo_pendiente   NUMERIC NOT NULL DEFAULT 0,
                fecha_vencimiento TEXT DEFAULT NULL,
                estado            TEXT NOT NULL DEFAULT \'pendiente\',
                observacion       TEXT DEFAULT NULL,
                activo            INTEGER NOT NULL DEFAULT 1,
                created_at        TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at        TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->exec('
            CREATE TABLE pago_compra (
                id_pago_compra   INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cuenta_pagar  INTEGER NOT NULL,
                monto            NUMERIC NOT NULL,
                tipo_pago        TEXT DEFAULT NULL,
                referencia       TEXT DEFAULT NULL,
                fecha_pago       TEXT DEFAULT NULL,
                observacion      TEXT DEFAULT NULL,
                estado           TEXT NOT NULL DEFAULT \'registrado\',
                activo           INTEGER NOT NULL DEFAULT 1,
                created_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        SqliteProveedor::boot($this->pdo);
        SqlitePurchase::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqlitePurchase::boot(null);
        SqliteProveedor::boot(null);
        parent::tearDown();
    }

    private function crearProveedor(string $nombre = 'Ferretería Los Andes', string $rif = 'J-12345678-9'): int
    {
        $proveedor = new SqliteProveedor(['nombre_proveedor' => $nombre, 'rif_proveedor' => $rif]);
        self::assertTrue($proveedor->save());

        return (int) $proveedor->getId();
    }

    private function crearCompra(int $idProveedor, string $estado = 'pendiente', float $total = 116.0): int
    {
        $compra = new SqlitePurchase();
        self::assertTrue($compra->agregar(
            $idProveedor,
            '2026-10-27',
            'Factura',
            'F-001',
            100.0,
            16.0,
            $total,
            'Compra de prueba'
        ));

        $id = (int) $compra->obtenerUltimoId();

        if ($estado !== 'pendiente') {
            $this->exec(sprintf("UPDATE compra SET estado = '%s' WHERE id_compra = %d", $estado, $id));
        }

        return $id;
    }

    #[Test]
    public function agregar_crea_una_compra_pendiente(): void
    {
        $idProveedor = $this->crearProveedor();

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->agregar(
            $idProveedor,
            '2026-10-27',
            'Factura',
            'F-001',
            100.0,
            16.0,
            116.0,
            'Compra inicial'
        ));

        $id = $compra->obtenerUltimoId();
        $this->assertNotNull($id);

        $fila = $this->fetchRow('compra', $id, 'id_compra');
        $this->assertSame($idProveedor, (int) $fila['id_proveedor']);
        $this->assertSame('2026-10-27', $fila['fecha_compra']);
        $this->assertSame('Factura', $fila['tipo_comprobante']);
        $this->assertSame('F-001', $fila['numero_comprobante']);
        $this->assertSame(116.0, (float) $fila['total']);
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertNull($fila['fecha_recepcion']);
        $this->assertSame(1, (int) $fila['activo']);
    }

    #[Test]
    public function getAll_devuelve_compras_con_proveedor_y_conteos(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida' => 1,
            'nombre_insumo'    => 'Sustrato',
        ]);
        $compra = new SqlitePurchase();
        $this->assertTrue($compra->agregarDetalle($id, 'insumo', $idInsumo, null, null, 5.0, 12.5));

        $filas = $compra->getAll();
        $this->assertCount(1, $filas);
        $this->assertSame($id, (int) $filas[0]['id']);
        $this->assertSame('Ferretería Los Andes', $filas[0]['proveedor_nombre']);
        $this->assertSame('J-12345678-9', $filas[0]['rif_proveedor']);
        $this->assertSame(1, (int) $filas[0]['items_count']);
        $this->assertSame(0, (int) $filas[0]['pagos_count']);
    }

    #[Test]
    public function getById_devuelve_la_compra_o_null(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $fila = (new SqlitePurchase())->getById($id);
        $this->assertSame($id, (int) $fila['id_compra']);
        $this->assertSame('Ferretería Los Andes', $fila['proveedor_nombre']);
        $this->assertSame(116.0, (float) $fila['total']);

        $this->assertNull((new SqlitePurchase())->getById(99999));
    }

    #[Test]
    public function actualizar_modifica_los_datos_de_la_compra(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->actualizar(
            $id,
            $idProveedor,
            '2026-10-28',
            'Nota de entrega',
            'N-002',
            172.41,
            27.59,
            200.0,
            'Compra actualizada'
        ));

        $fila = $compra->getById($id);
        $this->assertSame('2026-10-28', $fila['fecha_compra']);
        $this->assertSame('Nota de entrega', $fila['tipo_comprobante']);
        $this->assertSame('N-002', $fila['numero_comprobante']);
        $this->assertSame(200.0, (float) $fila['total']);
        $this->assertSame('Compra actualizada', $fila['observacion']);
    }

    #[Test]
    public function actualizar_con_id_inexistente_lanza_excepcion(): void
    {
        $compra = new SqlitePurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la compra solicitada para modificar.');

        $compra->actualizar(99999, 1, '2026-10-27', 'Factura', null, 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function agregar_con_datos_invalidos_lanza_excepcion_de_validacion(): void
    {
        $compra = new SqlitePurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: tipo_comprobante');

        $compra->agregar(1, '2026-10-27', '', null, 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function agregarDetalle_y_obtenerDetalles_devuelven_los_items(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida' => 1,
            'nombre_insumo'    => 'Sustrato',
        ]);
        $idHerramienta = $this->insert('herramienta', [
            'nombre_herramienta' => 'Pala',
        ]);
        $idPlanta = $this->insert('plantas', [
            'nombre_comun' => 'Orquídea',
        ]);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->agregarDetalle($id, 'insumo', $idInsumo, null, null, 5.0, 12.5));
        $this->assertTrue($compra->agregarDetalle($id, 'herramienta', null, $idHerramienta, null, 1.0, 30.0));
        $this->assertTrue($compra->agregarDetalle($id, 'planta', null, null, $idPlanta, 10.0, 3.25, 'adulto'));

        $detalles = $compra->obtenerDetalles($id);
        $this->assertCount(3, $detalles);

        $this->assertSame('insumo', $detalles[0]['tipo_item']);
        $this->assertSame($idInsumo, (int) $detalles[0]['id_item']);
        $this->assertSame('Sustrato', $detalles[0]['item_nombre']);
        $this->assertSame(5.0, (float) $detalles[0]['cantidad']);
        $this->assertSame(62.5, (float) $detalles[0]['subtotal']);

        $this->assertSame('herramienta', $detalles[1]['tipo_item']);
        $this->assertSame($idHerramienta, (int) $detalles[1]['id_item']);
        $this->assertSame('Pala', $detalles[1]['item_nombre']);
        $this->assertSame(30.0, (float) $detalles[1]['subtotal']);

        $this->assertSame('planta', $detalles[2]['tipo_item']);
        $this->assertSame($idPlanta, (int) $detalles[2]['id_item']);
        $this->assertSame('Orquídea', $detalles[2]['item_nombre']);
        $this->assertSame('adulto', $detalles[2]['categoria_lote']);
        $this->assertSame(32.5, (float) $detalles[2]['subtotal']);
    }

    #[Test]
    public function obtenerDetalles_sin_detalles_devuelve_arreglo_vacio(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $this->assertSame([], (new SqlitePurchase())->obtenerDetalles($id));
    }

    #[Test]
    public function eliminarDetalles_desactiva_los_detalles(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida' => 1,
            'nombre_insumo'    => 'Sustrato',
        ]);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->agregarDetalle($id, 'insumo', $idInsumo, null, null, 5.0, 12.5));
        $this->assertTrue($compra->eliminarDetalles($id));

        $this->assertSame([], $compra->obtenerDetalles($id));
        $this->assertDatabaseCount('compra_detalle', 1, ['id_compra' => $id, 'activo' => 0]);
    }

    #[Test]
    public function crearCuentaPagar_inserta_la_cuenta_con_saldo_inicial(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->crearCuentaPagar($id, 116.0));

        $fila = $this->fetchRowWhere('cuentas_pagar', ['id_compra' => $id]);
        $this->assertSame(116.0, (float) $fila['monto_total']);
        $this->assertSame(116.0, (float) $fila['saldo_pendiente']);
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(1, (int) $fila['activo']);
    }

    #[Test]
    public function actualizarCuentaPagar_modifica_monto_y_saldo(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->crearCuentaPagar($id, 116.0));
        $this->assertTrue($compra->actualizarCuentaPagar($id, 200.0));

        $fila = $this->fetchRowWhere('cuentas_pagar', ['id_compra' => $id]);
        $this->assertSame(200.0, (float) $fila['monto_total']);
        $this->assertSame(200.0, (float) $fila['saldo_pendiente']);
    }

    #[Test]
    public function actualizarCuentaPagar_devuelve_false_si_no_existe_la_cuenta(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $this->assertFalse((new SqlitePurchase())->actualizarCuentaPagar($id, 50.0));
    }

    #[Test]
    public function tienePagosCuentaPagar_detecta_pagos_activos(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->crearCuentaPagar($id, 116.0));
        $idCuenta = (int) $this->pdo->lastInsertId();

        $this->assertFalse($compra->tienePagosCuentaPagar($id));

        $idPago = $this->insert('pago_compra', [
            'id_cuenta_pagar' => $idCuenta,
            'monto'           => 50.0,
            'fecha_pago'      => '2026-10-27',
        ]);

        $this->assertTrue($compra->tienePagosCuentaPagar($id));

        $this->exec(sprintf('UPDATE pago_compra SET activo = 0 WHERE id_pago_compra = %d', $idPago));

        $this->assertFalse($compra->tienePagosCuentaPagar($id));
    }

    #[Test]
    public function actualizarEstado_cambia_el_estado_de_la_compra(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->actualizarEstado($id, 'recibida'));

        $fila = $compra->getById($id);
        $this->assertSame('recibida', $fila['estado']);
    }

    #[Test]
    public function actualizarEstado_con_estado_invalido_lanza_excepcion(): void
    {
        $compra = new SqlitePurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido.');

        $compra->actualizarEstado(1, 'despachada');
    }

    #[Test]
    public function actualizarEstado_con_id_inexistente_lanza_excepcion(): void
    {
        $compra = new SqlitePurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la compra.');

        $compra->actualizarEstado(99999, 'recibida');
    }

    #[Test]
    public function marcarRecibida_cambia_el_estado_y_registra_la_fecha(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->marcarRecibida($id));

        $fila = $compra->getById($id);
        $this->assertSame('recibida', $fila['estado']);
        $this->assertSame(date('Y-m-d'), $fila['fecha_recepcion']);
    }

    #[Test]
    public function marcarRecibida_con_compra_no_pendiente_lanza_excepcion(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->marcarRecibida($id));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solo se pueden recibir compras pendientes.');

        $compra->marcarRecibida($id);
    }

    #[Test]
    public function marcarRecibida_con_id_inexistente_lanza_excepcion(): void
    {
        $compra = new SqlitePurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la compra.');

        $compra->marcarRecibida(99999);
    }

    #[Test]
    public function aplicarStock_actualiza_insumo_herramienta_y_planta(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $idInsumo = $this->insert('insumo', [
            'id_unidad_medida'    => 1,
            'nombre_insumo'       => 'Sustrato',
            'stock_actual'        => 0,
            'costo_unitario_actual' => 0,
        ]);
        $idHerramienta = $this->insert('herramienta', [
            'nombre_herramienta' => 'Pala',
            'estado'             => 'en_reparacion',
        ]);
        $idPlanta = $this->insert('plantas', [
            'nombre_comun'   => 'Orquídea',
            'cantidad_total' => 0,
        ]);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->agregarDetalle($id, 'insumo', $idInsumo, null, null, 5.0, 12.5));
        $this->assertTrue($compra->agregarDetalle($id, 'herramienta', null, $idHerramienta, null, 1.0, 30.0));
        $this->assertTrue($compra->agregarDetalle($id, 'planta', null, null, $idPlanta, 10.0, 3.25, 'adulto'));

        $resultados = $compra->aplicarStock($id);

        $this->assertSame(['insumo', 'herramienta', 'planta'], array_column($resultados, 'tipo'));

        $insumo = $this->fetchRow('insumo', $idInsumo, 'id_insumo');
        $this->assertSame(5.0, (float) $insumo['stock_actual']);
        $this->assertSame(12.5, (float) $insumo['costo_unitario_actual']);

        $herramienta = $this->fetchRow('herramienta', $idHerramienta, 'id_herramienta');
        $this->assertSame('disponible', $herramienta['estado']);
        $this->assertSame(date('Y-m-d'), $herramienta['fecha_adquisicion']);

        $planta = $this->fetchRow('plantas', $idPlanta, 'id_planta');
        $this->assertSame(10, (int) $planta['cantidad_total']);

        $lote = $this->fetchRowWhere('lote', ['id_planta' => $idPlanta]);
        $this->assertNotNull($lote);
        $this->assertSame(10, (int) $lote['cantidad_inicial']);
        $this->assertSame(10, (int) $lote['cantidad_actual']);
        $this->assertSame(3.25, (float) $lote['costo_unitario']);
        $this->assertSame(3, (int) $lote['id_categoria']);
        $this->assertSame(4, (int) $lote['id_origen']);
        $this->assertSame(5, (int) $lote['id_estado']);
        $this->assertSame(date('Y-m-d'), $lote['fecha_siembra']);
        $this->assertSame('Ingresado por compra #' . $id, $lote['observacion']);
    }

    #[Test]
    public function aplicarStock_sin_detalles_no_cambia_nada(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $resultados = (new SqlitePurchase())->aplicarStock($id);

        $this->assertSame([], $resultados);
    }

    #[Test]
    public function eliminar_desactiva_y_restaurar_reactiva_la_compra(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->eliminar($id));

        $fila = $this->fetchRow('compra', $id, 'id_compra');
        $this->assertSame(0, (int) $fila['activo']);
        $this->assertNull($compra->getById($id));
        $this->assertSame([], $compra->getAll());

        $this->assertTrue($compra->restaurar($id));

        $fila = $this->fetchRow('compra', $id, 'id_compra');
        $this->assertSame(1, (int) $fila['activo']);
        $this->assertCount(1, $compra->getAll());
    }

    #[Test]
    public function exists_refleja_si_la_compra_existe_y_esta_activa(): void
    {
        $idProveedor = $this->crearProveedor();
        $id = $this->crearCompra($idProveedor);

        $compra = new SqlitePurchase();
        $this->assertTrue($compra->exists($id));
        $this->assertFalse($compra->exists(99999));

        $compra->eliminar($id);
        $this->assertFalse($compra->exists($id));
    }

    #[Test]
    public function las_transacciones_se_inician_y_revierten(): void
    {
        $compra = new SqlitePurchase();

        $this->assertTrue($compra->iniciarTransaccion());
        $this->assertTrue($compra->revertirTransaccion());
    }
}
