<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Integration\Compras;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Support\SqliteCuentaPagar;
use SysInescolara\Tests\Support\SqliteProveedor;
use SysInescolara\Tests\Support\SqlitePurchase;
use SysInescolara\Tests\Support\SqliteTestCase;

final class CuentaPagarRepositoryTest extends SqliteTestCase
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
        SqliteCuentaPagar::boot($this->pdo);
    }

    protected function tearDown(): void
    {
        SqliteCuentaPagar::boot(null);
        SqlitePurchase::boot(null);
        SqliteProveedor::boot(null);
        parent::tearDown();
    }

    private function crearProveedor(): int
    {
        $proveedor = new SqliteProveedor([
            'nombre_proveedor' => 'Ferretería Los Andes',
            'rif_proveedor'    => 'J-12345678-9',
        ]);
        self::assertTrue($proveedor->save());

        return (int) $proveedor->getId();
    }

    private function crearCompra(string $estado = 'pendiente', float $total = 116.0): int
    {
        $idProveedor = $this->crearProveedor();

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

    private function crearCuenta(int $idCompra, float $monto = 116.0): SqliteCuentaPagar
    {
        $cuenta = new SqliteCuentaPagar();
        self::assertTrue($cuenta->crear($idCompra, $monto, '2026-11-30', 'Compra de insumos'));

        return $cuenta;
    }

    #[Test]
    public function crear_inserta_la_cuenta_con_saldo_pendiente(): void
    {
        $idCompra = $this->crearCompra();

        $cuenta = $this->crearCuenta($idCompra, 116.75);

        $this->assertNotNull($cuenta->getId());
        $fila = $this->fetchRow('cuentas_pagar', (int) $cuenta->getId(), 'id_cuenta_pagar');
        $this->assertSame($idCompra, (int) $fila['id_compra']);
        $this->assertSame(116.75, (float) $fila['monto_total']);
        $this->assertSame(116.75, (float) $fila['saldo_pendiente']);
        $this->assertSame('2026-11-30', $fila['fecha_vencimiento']);
        $this->assertSame('Compra de insumos', $fila['observacion']);
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(1, (int) $fila['activo']);

        $encontrada = SqliteCuentaPagar::find((int) $cuenta->getId());
        $this->assertSame($idCompra, $encontrada->getIdCompra());
        $this->assertSame(116.75, $encontrada->getMontoTotal());
        $this->assertSame('2026-11-30', $encontrada->getFechaVencimiento());
    }

    #[Test]
    public function crear_sin_id_compra_lanza_excepcion_de_validacion(): void
    {
        $cuenta = new SqliteCuentaPagar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_compra');

        $cuenta->save();
    }

    #[Test]
    public function crear_con_monto_negativo_lanza_excepcion_de_validacion(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = new SqliteCuentaPagar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $cuenta->crear($idCompra, -10.0);
    }

    #[Test]
    public function save_actualiza_una_cuenta_existente(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $this->assertTrue($cuenta->loadById($id));
        $cuenta->setMontoTotal(150.0);
        $cuenta->setObservacion('Monto corregido');
        $this->assertTrue($cuenta->save());

        $fila = $cuenta->obtenerPorId($id);
        $this->assertSame(150.0, (float) $fila['monto_total']);
        $this->assertSame(150.0, (float) $fila['saldo_pendiente']);
        $this->assertSame('Monto corregido', $fila['observacion']);
    }

    #[Test]
    public function obtenerPorId_devuelve_estado_pendiente_y_saldo_completo(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);

        $fila = $cuenta->obtenerPorId((int) $cuenta->getId());
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(116.0, (float) $fila['saldo_pendiente']);
        $this->assertSame(116.0, (float) $fila['monto_total']);
        $this->assertSame('Ferretería Los Andes', $fila['nombre_proveedor']);
    }

    #[Test]
    public function obtenerPorId_devuelve_null_si_no_existe(): void
    {
        $this->assertNull((new SqliteCuentaPagar())->obtenerPorId(99999));
    }

    #[Test]
    public function obtenerPorCompra_devuelve_la_cuenta_de_la_compra(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);

        $fila = (new SqliteCuentaPagar())->obtenerPorCompra($idCompra);
        $this->assertSame((int) $cuenta->getId(), (int) $fila['id_cuenta_pagar']);
        $this->assertSame('pendiente', $fila['estado']);

        $this->assertNull((new SqliteCuentaPagar())->obtenerPorCompra(99999));
    }

    #[Test]
    public function obtenerTodas_devuelve_los_datos_de_la_compra_y_del_proveedor(): void
    {
        $idCompra = $this->crearCompra();
        $this->crearCuenta($idCompra, 116.0);

        $filas = (new SqliteCuentaPagar())->obtenerTodas();
        $this->assertCount(1, $filas);
        $this->assertSame($idCompra, (int) $filas[0]['id_compra']);
        $this->assertSame('Ferretería Los Andes', $filas[0]['proveedor_nombre']);
        $this->assertSame('J-12345678-9', $filas[0]['rif_proveedor']);
        $this->assertSame(116.0, (float) $filas[0]['compra_total']);
        $this->assertSame('pendiente', $filas[0]['estado']);
        $this->assertSame(0, (int) $filas[0]['pagos_count']);
    }

    #[Test]
    public function obtenerTodas_excluye_compras_canceladas(): void
    {
        $idCompra = $this->crearCompra('cancelada');
        $this->crearCuenta($idCompra, 116.0);

        $this->assertSame([], (new SqliteCuentaPagar())->obtenerTodas());
    }

    #[Test]
    public function getAll_es_un_alias_de_obtenerTodas(): void
    {
        $idCompra = $this->crearCompra();
        $this->crearCuenta($idCompra, 116.0);

        $cuenta = new SqliteCuentaPagar();
        $this->assertSame($cuenta->obtenerTodas(), $cuenta->getAll());
    }

    #[Test]
    public function obtenerPagos_devuelve_solo_pagos_activos_ordenados_por_fecha(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $idCuenta = (int) $cuenta->getId();

        $this->insert('pago_compra', [
            'id_cuenta_pagar' => $idCuenta,
            'monto'           => 40.0,
            'fecha_pago'      => '2026-10-01',
        ]);
        $idAnulado = $this->insert('pago_compra', [
            'id_cuenta_pagar' => $idCuenta,
            'monto'           => 20.0,
            'fecha_pago'      => '2026-10-10',
        ]);
        $this->insert('pago_compra', [
            'id_cuenta_pagar' => $idCuenta,
            'monto'           => 30.0,
            'fecha_pago'      => '2026-10-20',
        ]);
        $this->exec(sprintf('UPDATE pago_compra SET activo = 0 WHERE id_pago_compra = %d', $idAnulado));

        $pagos = $cuenta->obtenerPagos($idCuenta);
        $this->assertCount(2, $pagos);
        $this->assertSame(30.0, (float) $pagos[0]['monto']);
        $this->assertSame(40.0, (float) $pagos[1]['monto']);
    }

    #[Test]
    public function anularPago_marca_el_pago_como_anulado(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $idCuenta = (int) $cuenta->getId();

        $idPago = $this->insert('pago_compra', [
            'id_cuenta_pagar' => $idCuenta,
            'monto'           => 50.0,
            'fecha_pago'      => '2026-10-27',
        ]);

        $this->assertTrue($cuenta->anularPago($idPago));

        $fila = $this->fetchRow('pago_compra', $idPago, 'id_pago_compra');
        $this->assertSame('anulado', $fila['estado']);
        $this->assertSame(0, (int) $fila['activo']);
        $this->assertSame([], $cuenta->obtenerPagos($idCuenta));
    }

    #[Test]
    public function estado_y_saldo_se_calculan_con_los_pagos_registrados(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $this->insert('pago_compra', [
            'id_cuenta_pagar' => $id,
            'monto'           => 40.0,
            'fecha_pago'      => '2026-10-01',
        ]);

        $fila = $cuenta->obtenerPorId($id);
        $this->assertSame('parcial', $fila['estado']);
        $this->assertSame(76.0, (float) $fila['saldo_pendiente']);

        $this->insert('pago_compra', [
            'id_cuenta_pagar' => $id,
            'monto'           => 76.0,
            'fecha_pago'      => '2026-10-20',
        ]);

        $fila = $cuenta->obtenerPorId($id);
        $this->assertSame('pagada', $fila['estado']);
        $this->assertSame(0.0, (float) $fila['saldo_pendiente']);
    }

    #[Test]
    public function los_pagos_anulados_no_afectan_el_saldo(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $idPago = $this->insert('pago_compra', [
            'id_cuenta_pagar' => $id,
            'monto'           => 40.0,
            'fecha_pago'      => '2026-10-01',
        ]);

        $this->assertTrue($cuenta->anularPago($idPago));

        $fila = $cuenta->obtenerPorId($id);
        $this->assertSame('pendiente', $fila['estado']);
        $this->assertSame(116.0, (float) $fila['saldo_pendiente']);
    }

    #[Test]
    public function registrarPago_sin_cuenta_lanza_excepcion(): void
    {
        $cuenta = new SqliteCuentaPagar();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la cuenta por pagar.');

        $cuenta->registrarPago(99999, 50.0, '2026-10-27', 'transferencia');
    }

    #[Test]
    public function registrarPago_sobre_cuenta_pagada_lanza_excepcion(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $this->insert('pago_compra', [
            'id_cuenta_pagar' => $id,
            'monto'           => 116.0,
            'fecha_pago'      => '2026-10-27',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La cuenta ya está pagada.');

        $cuenta->registrarPago($id, 10.0, '2026-10-28', 'efectivo');
    }

    #[Test]
    public function registrarPago_completo_marca_la_cuenta_y_la_compra_como_pagadas(): void
    {
        $this->markTestSkipped(
            'CuentaPagar::actualizarEstadoCompra() usa "UPDATE compra c SET c.estado = ...", '
            . 'sintaxis válida en MySQL pero no en SQLite; el camino feliz de registrarPago() '
            . 'no es portable. Los guardas y el cálculo de estado/saldo sí se prueban.'
        );
    }

    #[Test]
    public function eliminar_desactiva_y_restaurar_reactiva_la_cuenta(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $this->assertTrue($cuenta->eliminar($id));
        $this->assertNull($cuenta->obtenerPorId($id));
        $this->assertFalse($cuenta->exists($id));
        $this->assertSame([], $cuenta->obtenerTodas());

        $this->assertTrue($cuenta->restaurar($id));
        $this->assertNotNull($cuenta->obtenerPorId($id));
        $this->assertTrue($cuenta->exists($id));
        $this->assertCount(1, $cuenta->obtenerTodas());
    }

    #[Test]
    public function exists_refleja_si_la_cuenta_existe_y_esta_activa(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);

        $this->assertTrue($cuenta->exists((int) $cuenta->getId()));
        $this->assertFalse($cuenta->exists(99999));
    }

    #[Test]
    public function where_filtra_solo_cuentas_activas(): void
    {
        $idCompra = $this->crearCompra();
        $primera = $this->crearCuenta($idCompra, 116.0);
        $segunda = $this->crearCuenta($idCompra, 50.0);

        $cuenta = new SqliteCuentaPagar();
        $this->assertCount(2, $cuenta->where('id_compra', $idCompra));

        $cuenta->eliminar((int) $primera->getId());

        $resultados = $cuenta->where('id_compra', $idCompra);
        $this->assertCount(1, $resultados);
        $this->assertSame(50.0, $resultados[0]->getMontoTotal());
    }

    #[Test]
    public function loadById_carga_los_atributos(): void
    {
        $idCompra = $this->crearCompra();
        $cuenta = $this->crearCuenta($idCompra, 116.0);
        $id = (int) $cuenta->getId();

        $destino = new SqliteCuentaPagar();
        $this->assertTrue($destino->loadById($id));
        $this->assertSame($id, $destino->getId());
        $this->assertSame($idCompra, $destino->getIdCompra());
        $this->assertSame(116.0, $destino->getMontoTotal());
        // `saldo_pendiente`, `estado`, `activo` y las marcas de tiempo no son
        // fillable, por lo que find()/loadById() conservan sus valores por
        // defecto; el saldo real se obtiene con obtenerPorId().
        $this->assertSame(0.0, $destino->getSaldoPendiente());
        $this->assertSame('2026-11-30', $destino->getFechaVencimiento());
        $this->assertSame('pendiente', $destino->getEstado());
        $this->assertSame('Compra de insumos', $destino->getObservacion());
        $this->assertTrue($destino->isActivo());
        $this->assertNull($destino->getCreatedAt());

        $this->assertFalse((new SqliteCuentaPagar())->loadById(99999));
    }
}
