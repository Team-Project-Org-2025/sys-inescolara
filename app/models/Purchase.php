<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use SysInescolara\models\AuditLog;
use Throwable;

class Purchase extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_proveedor'       => ['type' => null,   'required' => true],
        'fecha_compra'       => ['type' => null,   'required' => true],
        'tipo_comprobante'   => ['type' => null,   'required' => true],
        'numero_comprobante' => ['type' => null,   'required' => false],
        'subtotal'           => ['type' => 'precio','required' => true],
        'iva'                => ['type' => 'precio','required' => true],
        'total'              => ['type' => 'precio','required' => true],
        'observacion'        => ['type' => null,   'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        return $this->obtenerTodas();
    }

    public function getById(int $id): ?array
    {
        return $this->obtenerPorId($id);
    }

    public function delete(int $id): bool
    {
        return $this->eliminar($id);
    }

    public function exists(int $id): bool
    {
        return $this->existe($id);
    }

    public function iniciarTransaccion(): bool
    {
        return $this->db()->beginTransaction();
    }

    public function confirmarTransaccion(): bool
    {
        return $this->db()->commit();
    }

    public function revertirTransaccion(): bool
    {
        return $this->db()->rollBack();
    }

    public function obtenerTodas(): array
    {
        try {
            $sql = "SELECT
                        c.id_compra AS id, c.id_compra, c.id_proveedor, c.fecha_compra,
                        c.tipo_comprobante, c.numero_comprobante,
                        c.subtotal, c.iva, c.total, c.estado, c.observacion,
                        p.nombre_proveedor AS proveedor_nombre,
                        p.rif_proveedor,
                        (SELECT COUNT(*) FROM compra_detalle d WHERE d.id_compra = c.id_compra AND d.activo = 1) AS items_count,
                        (
                            SELECT COUNT(*)
                            FROM cuentas_pagar cp
                            JOIN pago_compra pg ON cp.id_cuenta_pagar = pg.id_cuenta_pagar
                            WHERE cp.id_compra = c.id_compra
                              AND pg.estado IN ('registrado','confirmado')
                              AND pg.activo = 1
                              AND cp.activo = 1
                        ) AS pagos_count
                    FROM compra c
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    WHERE c.activo = 1
                    ORDER BY c.fecha_compra DESC, c.id_compra DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Purchase::obtenerTodas: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT c.*, p.nombre_proveedor AS proveedor_nombre, p.rif_proveedor
                FROM compra c
                LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                WHERE c.id_compra = :id AND c.activo = 1
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Purchase::obtenerPorId: ' . $e->getMessage());
            return null;
        }
    }

    public function obtenerDetalles(int $idCompra): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT
                    d.id_detalle, d.id_compra, d.tipo_item,
                    COALESCE(d.id_insumo, d.id_herramienta, d.id_planta) AS id_item,
                    d.id_insumo, d.id_herramienta, d.id_planta,
                    d.cantidad, d.costo_unitario,
                    (d.cantidad * d.costo_unitario) AS subtotal,
                    d.categoria_lote, d.id_ubicacion,
                    CASE
                        WHEN d.id_insumo IS NOT NULL THEN i.nombre_insumo
                        WHEN d.id_herramienta IS NOT NULL THEN h.nombre_herramienta
                        WHEN d.id_planta IS NOT NULL THEN p.nombre_comun
                    END AS item_nombre
                FROM compra_detalle d
                LEFT JOIN insumo i ON d.id_insumo = i.id_insumo
                LEFT JOIN herramienta h ON d.id_herramienta = h.id_herramienta
                LEFT JOIN plantas p ON d.id_planta = p.id_planta
                WHERE d.id_compra = :id_compra AND d.activo = 1
                ORDER BY d.id_detalle ASC
            ");
            $stmt->execute([':id_compra' => $idCompra]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Purchase::obtenerDetalles: ' . $e->getMessage());
            return [];
        }
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM compra WHERE id_compra = :id AND activo = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function agregar(
        int $idProveedor,
        string $fechaCompra,
        string $tipoComprobante,
        ?string $numeroComprobante,
        float $subtotal,
        float $iva,
        float $total,
        ?string $observacion
    ): bool {
        $this->validateData([
            'id_proveedor' => $idProveedor,
            'fecha_compra' => $fechaCompra,
            'tipo_comprobante' => $tipoComprobante,
            'numero_comprobante' => $numeroComprobante,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db()->prepare("
            INSERT INTO compra
                (id_proveedor, fecha_compra, tipo_comprobante, numero_comprobante,
                 subtotal, iva, total, observacion)
            VALUES
                (:id_proveedor, :fecha_compra, :tipo_comprobante, :numero_comprobante,
                 :subtotal, :iva, :total, :observacion)
        ");
        $result = $stmt->execute([
            ':id_proveedor' => $idProveedor,
            ':fecha_compra' => $fechaCompra,
            ':tipo_comprobante' => $tipoComprobante,
            ':numero_comprobante' => $numeroComprobante,
            ':subtotal' => $subtotal,
            ':iva' => $iva,
            ':total' => $total,
            ':observacion' => $observacion,
        ]);
        $nuevoId = $this->db()->lastInsertId();
        AuditLog::record('CREATE', 'compra', $nuevoId, null, [
            'id_proveedor' => $idProveedor,
            ':fecha_compra' => $fechaCompra,
            'total' => $total,
        ]);
        return $result;
    }

    public function actualizar(
        int $id,
        int $idProveedor,
        string $fechaCompra,
        string $tipoComprobante,
        ?string $numeroComprobante,
        float $subtotal,
        float $iva,
        float $total,
        ?string $observacion
    ): bool {
        $this->validateData([
            'id_proveedor' => $idProveedor,
            'fecha_compra' => $fechaCompra,
            'tipo_comprobante' => $tipoComprobante,
            'numero_comprobante' => $numeroComprobante,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'observacion' => $observacion,
        ]);
        if (!$this->existe($id)) {
            throw new \Exception('No existe la compra solicitada para modificar.');
        }
        $stmt = $this->db()->prepare("
            UPDATE compra
            SET id_proveedor = :id_proveedor,
                fecha_compra = :fecha_compra,
                tipo_comprobante = :tipo_comprobante,
                numero_comprobante = :numero_comprobante,
                subtotal = :subtotal,
                iva = :iva,
                total = :total,
                observacion = :observacion
            WHERE id_compra = :id AND activo = 1
        ");
        $oldData = $this->obtenerPorId($id);
        $result = $stmt->execute([
            ':id' => $id,
            ':id_proveedor' => $idProveedor,
            ':fecha_compra' => $fechaCompra,
            ':tipo_comprobante' => $tipoComprobante,
            ':numero_comprobante' => $numeroComprobante,
            ':subtotal' => $subtotal,
            ':iva' => $iva,
            ':total' => $total,
            ':observacion' => $observacion,
        ]);
        AuditLog::record('UPDATE', 'compra', $id, $oldData, [
            'id_proveedor' => $idProveedor,
            'total' => $total,
        ]);
        return $result;
    }

    public function eliminar(int $id): bool
    {
        $oldData = $this->obtenerPorId($id);
        $stmt = $this->db()->prepare("UPDATE compra SET activo = 0 WHERE id_compra = :id");
        $result = $stmt->execute([':id' => $id]);
        AuditLog::record('DEACTIVATE', 'compra', $id, $oldData, null);
        return $result;
    }

    public function restaurar(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE compra SET activo = 1 WHERE id_compra = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function agregarDetalle(
        int $idCompra,
        string $tipoItem,
        ?int $idInsumo,
        ?int $idHerramienta,
        ?int $idPlanta,
        float $cantidad,
        float $costoUnitario,
        ?string $categoriaLote = null,
        ?int $idUbicacion = null
    ): bool {
        $stmt = $this->db()->prepare("
            INSERT INTO compra_detalle (id_compra, tipo_item, id_insumo, id_herramienta, id_planta, cantidad, costo_unitario, categoria_lote, id_ubicacion)
            VALUES (:id_compra, :tipo_item, :id_insumo, :id_herramienta, :id_planta, :cantidad, :costo_unitario, :categoria_lote, :id_ubicacion)
        ");
        return $stmt->execute([
            ':id_compra'      => $idCompra,
            ':tipo_item'      => $tipoItem,
            ':id_insumo'      => $idInsumo,
            ':id_herramienta' => $idHerramienta,
            ':id_planta'      => $idPlanta,
            ':cantidad'       => $cantidad,
            ':costo_unitario' => $costoUnitario,
            ':categoria_lote' => $categoriaLote,
            ':id_ubicacion'   => $idUbicacion,
        ]);
    }

    public function eliminarDetalles(int $idCompra): bool
    {
        $stmt = $this->db()->prepare("UPDATE compra_detalle SET activo = 0 WHERE id_compra = :id_compra");
        return $stmt->execute([':id_compra' => $idCompra]);
    }

    public function crearCuentaPagar(int $idCompra, float $total): bool
    {
        $stmt = $this->db()->prepare("
            INSERT INTO cuentas_pagar (id_compra, monto_total, saldo_pendiente)
            VALUES (:id_compra, :monto_total, :saldo_pendiente)
        ");
        return $stmt->execute([
            ':id_compra' => $idCompra,
            ':monto_total' => $total,
            ':saldo_pendiente' => $total,
        ]);
    }

    public function actualizarCuentaPagar(int $idCompra, float $total): bool
    {
        $stmt = $this->db()->prepare("
            UPDATE cuentas_pagar
            SET monto_total = :total, saldo_pendiente = :saldo_pendiente
            WHERE id_compra = :id_compra AND activo = 1
        ");
        $stmt->execute([':total' => $total, ':saldo_pendiente' => $total, ':id_compra' => $idCompra]);
        return $stmt->rowCount() > 0;
    }

    public function eliminarCuentaPagarPorCompra(int $idCompra): bool
    {
        $stmt = $this->db()->prepare("UPDATE cuentas_pagar SET activo = 0 WHERE id_compra = :id_compra");
        return $stmt->execute([':id_compra' => $idCompra]);
    }

    public function tienePagosCuentaPagar(int $idCompra): bool
    {
        $stmt = $this->db()->prepare("
            SELECT COUNT(*)
            FROM cuentas_pagar cp
            JOIN pago_compra pg ON cp.id_cuenta_pagar = pg.id_cuenta_pagar
            WHERE cp.id_compra = :id_compra
              AND pg.estado IN ('registrado','confirmado')
              AND pg.activo = 1
              AND cp.activo = 1
        ");
        $stmt->execute([':id_compra' => $idCompra]);
        return $stmt->fetchColumn() > 0;
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['pendiente', 'recibida', 'pagada', 'cancelada'], true)) {
            throw new \Exception('Estado inválido.');
        }
        $compra = $this->obtenerPorId($id);
        if (!$compra) {
            throw new \Exception('No existe la compra.');
        }
        $oldEstado = $compra['estado'];
        $stmt = $this->db()->prepare("UPDATE compra SET estado = :estado WHERE id_compra = :id AND activo = 1");
        $result = $stmt->execute([':estado' => $estado, ':id' => $id]);
        AuditLog::record('UPDATE', 'compra', $id, ['estado' => $oldEstado], ['estado' => $estado]);
        return $result;
    }

    public function marcarRecibida(int $id): bool
    {
        $compra = $this->obtenerPorId($id);
        if (!$compra) {
            throw new \Exception('No existe la compra.');
        }
        if ($compra['estado'] !== 'pendiente') {
            throw new \Exception('Solo se pueden recibir compras pendientes.');
        }
        $stmt = $this->db()->prepare("UPDATE compra SET estado = 'recibida', fecha_recepcion = CURDATE() WHERE id_compra = :id AND activo = 1");
        $result = $stmt->execute([':id' => $id]);
        AuditLog::record('UPDATE', 'compra', $id, ['estado' => 'pendiente'], ['estado' => 'recibida']);
        return $result;
    }

    public function aplicarStock(int $idCompra): array
    {
        $detalles = $this->obtenerDetalles($idCompra);
        $resultados = [];

        $this->iniciarTransaccion();

        try {
            foreach ($detalles as $d) {
                if ($d['id_insumo'] !== null) {
                    $stmt = $this->db()->prepare("
                        UPDATE insumo
                        SET stock_actual = stock_actual + :cantidad,
                            costo_unitario_actual = :costo_unitario
                        WHERE id_insumo = :id_insumo AND activo = 1
                    ");
                    $stmt->execute([
                        ':cantidad' => $d['cantidad'],
                        ':costo_unitario' => $d['costo_unitario'],
                        ':id_insumo' => $d['id_insumo'],
                    ]);
                    $resultados[] = ['tipo' => 'insumo', 'id' => $d['id_insumo'], 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad']];
                }

                if ($d['id_herramienta'] !== null) {
                    $stmt = $this->db()->prepare("
                        UPDATE herramienta
                        SET estado = 'disponible',
                            fecha_adquisicion = COALESCE(fecha_adquisicion, CURDATE())
                        WHERE id_herramienta = :id_herramienta AND activo = 1
                    ");
                    $stmt->execute([':id_herramienta' => $d['id_herramienta']]);
                    $resultados[] = ['tipo' => 'herramienta', 'id' => $d['id_herramienta'], 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad']];
                }

                if ($d['id_planta'] !== null) {
                    $costoUnitario = $d['costo_unitario'];
                    $categoriaLote = $d['categoria_lote'] ?? 'germinado';
                    $catIdMap = ['germinado' => 1, 'plántula' => 2, 'adulto' => 3];
                    $idCategoria = $catIdMap[$categoriaLote] ?? 1;
                    $stmt = $this->db()->prepare("
                        INSERT INTO lote
                            (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual,
                             costo_unitario, id_estado, id_categoria, id_origen, observacion)
                        VALUES
                            (:id_planta, :id_ubicacion, CURDATE(), :cantidad_ini, :cantidad_act,
                             :costo_unitario, 5, :id_categoria, 4, :observacion)
                    ");
                    $stmt->execute([
                        ':id_planta'      => $d['id_planta'],
                        ':id_ubicacion'   => $d['id_ubicacion'],
                        ':cantidad_ini'   => $d['cantidad'],
                        ':cantidad_act'   => $d['cantidad'],
                        ':costo_unitario' => $costoUnitario,
                        ':id_categoria'   => $idCategoria,
                        ':observacion'    => 'Ingresado por compra #' . $idCompra,
                    ]);
                    $loteId = $this->db()->lastInsertId();

                    $stmt2 = $this->db()->prepare("UPDATE plantas SET cantidad_total = cantidad_total + :cantidad WHERE id_planta = :id_planta");
                    $stmt2->execute([':cantidad' => $d['cantidad'], ':id_planta' => $d['id_planta']]);

                    $resultados[] = ['tipo' => 'planta', 'id' => $d['id_planta'], 'lote_id' => $loteId, 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad'], 'costo_unitario' => $costoUnitario];
                }
            }

            $this->confirmarTransaccion();
        } catch (\Exception $e) {
            $this->revertirTransaccion();
            throw $e;
        }

        return $resultados;
    }

    public function obtenerUltimoId(): ?int
    {
        $id = $this->db()->lastInsertId();
        return $id !== false ? (int) $id : null;
    }
}
