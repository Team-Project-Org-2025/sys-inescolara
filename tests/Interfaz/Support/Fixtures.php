<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

final class Fixtures
{
    private static array $cache = [];

    public static function especie(): array
    {
        return self::$cache['especie'] ??= (function (): array {
            $name = 'E2E_Especie_' . Config::runId();
            Db::execute(
                'INSERT INTO especie (nombre_especie, descripcion, activo) VALUES (?, ?, 1)',
                [$name, 'Especie generada por la suite de interfaz']
            );
            return ['id' => (string) Db::lastId(), 'name' => $name];
        })();
    }

    public static function ubicacion(string $tipo = 'Interno'): array
    {
        $key = 'ubicacion_' . mb_strtolower($tipo);
        return self::$cache[$key] ??= (function () use ($tipo): array {
            $name = 'E2E_Ubicacion_' . $tipo . '_' . Config::runId();
            $tipoColumn = Db::scalar("SHOW COLUMNS FROM ubicacion LIKE 'tipo'") ? 'tipo' : 'zona';
            Db::execute(
                "INSERT INTO ubicacion (nombre_ubicacion, descripcion, {$tipoColumn}, activo) VALUES (?, ?, ?, 1)",
                [$name, 'Ubicación generada por la suite de interfaz', $tipo]
            );
            return ['id' => (string) Db::lastId(), 'name' => $name];
        })();
    }

    public static function unidad(): array
    {
        return self::$cache['unidad'] ??= (function (): array {
            $name = 'E2E_Unidad_' . Config::runId();
            Db::execute(
                'INSERT INTO unidad_medida (nombre_unidad_medida, simbolo, activo) VALUES (?, ?, 1)',
                [$name, 'u']
            );
            return ['id' => (string) Db::lastId(), 'name' => $name];
        })();
    }

    public static function planta(?array $especie = null): array
    {
        if (isset(self::$cache['planta'])) {
            return self::$cache['planta'];
        }
        $especie ??= self::especie();
        $name = 'E2E_Planta_' . Config::runId();
        Db::execute(
            'INSERT INTO plantas (id_especie, nombre_comun, nombre_tecnico, cantidad_total, activo) VALUES (?, ?, ?, 0, 1)',
            [$especie['id'], $name, 'E2E technical ' . Config::runId()]
        );
        return self::$cache['planta'] = ['id' => (string) Db::lastId(), 'name' => $name, 'especie' => $especie];
    }

    public static function insumo(?array $unidad = null): array
    {
        if (isset(self::$cache['insumo'])) {
            return self::$cache['insumo'];
        }
        $unidad ??= self::unidad();
        $name = 'E2E_Insumo_' . Config::runId();
        Db::execute(
            'INSERT INTO insumo (id_unidad_medida, nombre_insumo, categoria, stock_actual, costo_unitario_actual, activo) VALUES (?, ?, ?, 10, 1, 1)',
            [$unidad['id'], $name, 'E2E']
        );
        return self::$cache['insumo'] = ['id' => (string) Db::lastId(), 'name' => $name, 'unidad' => $unidad];
    }

    public static function cuarentena(int $cantidad = 5): array
    {
        $run = Config::runId() . '_' . bin2hex(random_bytes(2));

        Db::execute(
            'INSERT INTO especie (nombre_especie, descripcion, activo) VALUES (?, ?, 1)',
            ['E2E Especie ' . $run, 'Especie de cuarentena E2E']
        );
        $especieId = (string) Db::lastId();

        Db::execute(
            'INSERT INTO plantas (id_especie, nombre_comun, nombre_tecnico, cantidad_total, activo) VALUES (?, ?, ?, 0, 1)',
            [$especieId, 'E2E Planta ' . $run, 'E2E technical ' . $run]
        );
        $plantaId = (string) Db::lastId();

        $ubicacionNombre = 'E2E Ubicacion ' . $run;
        $tipoColumn = Db::scalar("SHOW COLUMNS FROM ubicacion LIKE 'tipo'") ? 'tipo' : 'zona';
        Db::execute(
            "INSERT INTO ubicacion (nombre_ubicacion, descripcion, {$tipoColumn}, activo) VALUES (?, ?, ?, 1)",
            [$ubicacionNombre, 'Ubicacion de cuarentena E2E', 'Interno']
        );
        $ubicacionId = (string) Db::lastId();

        $estado = Db::row('SELECT id_estado FROM estado ORDER BY id_estado ASC LIMIT 1');
        $origen = Db::row('SELECT id_origen FROM origen ORDER BY id_origen ASC LIMIT 1');
        $categoria = Db::row('SELECT id_categoria FROM categoria ORDER BY id_categoria ASC LIMIT 1');
        $stock = $cantidad + 5;

        Db::execute(
            'INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, costo_unitario, id_estado, id_categoria, id_origen, observacion, activo, porcentaje_ganancia) '
            . 'VALUES (?, ?, ?, ?, ?, 2.50, ?, ?, ?, ?, 1, 30)',
            [
                $plantaId,
                $ubicacionId,
                date('Y-m-d', strtotime('-30 days')),
                $stock,
                $stock,
                $estado['id_estado'] ?? null,
                $categoria['id_categoria'] ?? null,
                $origen['id_origen'] ?? null,
                'Lote de cuarentena E2E',
            ]
        );
        $loteId = (string) Db::lastId();

        Db::execute(
            'INSERT INTO trazabilidad (id_lote, cantidad, estado_salud, observacion, activo, fecha_registro) VALUES (?, ?, ?, ?, 1, ?)',
            [$loteId, $cantidad, 'cuarentena', 'Cuarentena generada por la suite de interfaz', date('Y-m-d')]
        );
        $trazabilidadId = (string) Db::lastId();

        Db::execute(
            'UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - ?) WHERE id_lote = ?',
            [$cantidad, $loteId]
        );

        return [
            'id' => $trazabilidadId,
            'lote_id' => $loteId,
            'planta_name' => 'E2E Planta ' . $run,
            'cantidad' => $cantidad,
        ];
    }

    public static function cliente(): array
    {
        $name = 'E2E Cliente ' . Config::runId() . ' ' . bin2hex(random_bytes(2));
        Db::execute(
            'INSERT INTO cliente (tipo_cedula_cliente, cedula_cliente, nombre_cliente, apellido_cliente, contacto_cliente, activo) VALUES (?, ?, ?, ?, ?, 1)',
            ['V', (string) random_int(10000000, 99999999), $name, 'E2E', '04120000000']
        );

        return ['id' => (string) Db::lastId(), 'name' => $name];
    }

    public static function cuentaPagar(): array
    {
        $proveedor = self::proveedor();
        $insumo = self::insumo();

        Db::execute(
            'INSERT INTO compra (id_proveedor, fecha_compra, subtotal, iva, total, estado, activo) VALUES (?, ?, ?, ?, ?, ?, 1)',
            [$proveedor['id'], date('Y-m-d'), 20.00, 0.00, 20.00, 'pendiente']
        );
        $compraId = (string) Db::lastId();

        Db::execute(
            'INSERT INTO compra_detalle (id_compra, tipo_item, id_insumo, cantidad, costo_unitario, activo) VALUES (?, ?, ?, ?, ?, 1)',
            [$compraId, 'insumo', $insumo['id'], 2, 10.00]
        );

        Db::execute(
            'INSERT INTO cuentas_pagar (id_compra, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) VALUES (?, ?, ?, ?, ?, 1)',
            [$compraId, 20.00, 20.00, date('Y-m-d', strtotime('+30 days')), 'pendiente']
        );

        return [
            'id' => (string) Db::lastId(),
            'compra_id' => $compraId,
            'proveedor' => $proveedor,
            'total' => 20.00,
        ];
    }

    public static function ventaCredito(): array
    {
        $cliente = self::cliente();
        $lote = self::lote();
        $usuario = Db::row(
            'SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?',
            [Config::user()],
            'security'
        );
        $referencia = 'E2E-' . strtoupper(bin2hex(random_bytes(4)));

        Db::execute(
            'INSERT INTO venta (referencia, id_cliente, id_usuario, tipo_venta, estado, fecha_venta, fecha_vencimiento, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $referencia,
                $cliente['id'],
                $usuario['id_usuario'] ?? 1,
                'credito',
                'pendiente',
                date('Y-m-d H:i:s'),
                date('Y-m-d', strtotime('+30 days')),
            ]
        );
        $ventaId = (string) Db::lastId();

        Db::execute(
            'INSERT INTO detalle_venta (id_venta, tipo_item, id_lote, id_insumo, cantidad, precio_unitario) VALUES (?, ?, ?, NULL, ?, ?)',
            [$ventaId, 'planta', $lote['id'], 2, 10.00]
        );

        return [
            'id' => $ventaId,
            'referencia' => $referencia,
            'cliente' => $cliente,
            'total' => 20.00,
        ];
    }

    public static function usuario(): array
    {
        $name = 'E2E Usuario ' . Config::runId() . ' ' . bin2hex(random_bytes(2));
        $email = strtolower(str_replace(' ', '', $name)) . '@example.test';
        $role = Db::row('SELECT id_rol FROM roles ORDER BY id_rol ASC LIMIT 1', [], 'security');

        Db::execute(
            'INSERT INTO usuarios (nombre_usuario, correo_electronico, password_hash, id_rol, estatus) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, password_hash('E2ePass123!', PASSWORD_DEFAULT), $role['id_rol'] ?? 1, 'Activo'],
            'security'
        );

        return ['id' => (string) Db::lastId('security'), 'name' => $name, 'email' => $email];
    }

    public static function proveedor(): array
    {
        $name = 'E2E Proveedor ' . Config::runId();
        $rif = sprintf('J-%09d', random_int(0, 999999999));
        Db::execute(
            'INSERT INTO proveedores (nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor, activo) VALUES (?, ?, ?, ?, 1)',
            [$name, $rif, 'Contacto E2E', '04120000000']
        );

        return ['id' => (string) Db::lastId(), 'name' => $name, 'rif' => $rif];
    }

    public static function lote(?array $planta = null, ?array $ubicacion = null): array
    {
        if (isset(self::$cache['lote'])) {
            return self::$cache['lote'];
        }
        $planta ??= self::planta();
        $ubicacion ??= self::ubicacion();
        $estado = Db::row('SELECT id_estado FROM estado ORDER BY id_estado ASC LIMIT 1');
        $origen = Db::row('SELECT id_origen FROM origen ORDER BY id_origen ASC LIMIT 1');
        $categoria = Db::row('SELECT id_categoria FROM categoria ORDER BY id_categoria ASC LIMIT 1');

        Db::execute(
            'INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, costo_unitario, id_estado, id_categoria, id_origen, observacion, activo, porcentaje_ganancia) '
            . 'VALUES (?, ?, ?, 20, 20, 2.50, ?, ?, ?, ?, 1, 30)',
            [
                $planta['id'],
                $ubicacion['id'],
                date('Y-m-d', strtotime('-30 days')),
                $estado['id_estado'] ?? null,
                $categoria['id_categoria'] ?? null,
                $origen['id_origen'] ?? null,
                'Lote generado por la suite de interfaz',
            ]
        );

        return self::$cache['lote'] = [
            'id' => (string) Db::lastId(),
            'planta' => $planta,
            'ubicacion' => $ubicacion,
            'cantidad' => 20,
        ];
    }
}
