<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;

class CuentaPagar extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_compra'       => ['type' => null,   'required' => true],
        'monto_total'     => ['type' => 'precio','required' => true],
        'fecha_vencimiento'=> ['type' => null,   'required' => false],
        'observacion'     => ['type' => null,   'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    // ============================================================
    //  Interfaces (wrappers)
    // ============================================================

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

    // ============================================================
    //  Transacciones
    // ============================================================

    protected function iniciarTransaccion(): bool
    {
        return $this->db->beginTransaction();
    }

    protected function confirmarTransaccion(): bool
    {
        return $this->db->commit();
    }

    protected function revertirTransaccion(): bool
    {
        return $this->db->rollBack();
    }

    // ============================================================
    //  Consultas
    // ============================================================

    public function obtenerTodas(): array
    {
        try {
            $sql = "SELECT
                        cp.id_cuenta_pagar AS id, cp.id_cuenta_pagar, cp.id_compra, cp.monto_total,
                        cp.saldo_pendiente, cp.fecha_vencimiento, cp.estado, cp.observacion,
                        c.fecha_compra, c.total AS compra_total,
                        p.nombre_proveedor AS proveedor_nombre,
                        p.rif_proveedor,
                        (SELECT COUNT(*) FROM pago_compra pg WHERE pg.id_cuenta_pagar = cp.id_cuenta_pagar AND pg.activo = 1) AS pagos_count
                    FROM cuentas_pagar cp
                    JOIN compra c ON cp.id_compra = c.id_compra
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    WHERE cp.activo = 1
                    ORDER BY cp.created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en CuentaPagar::obtenerTodas: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cp.*, c.fecha_compra, c.total AS compra_total,
                       p.nombre_proveedor, p.rif_proveedor
                FROM cuentas_pagar cp
                JOIN compra c ON cp.id_compra = c.id_compra
                LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                WHERE cp.id_cuenta_pagar = :id AND cp.activo = 1
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en CuentaPagar::obtenerPorId: ' . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorCompra(int $idCompra): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cp.*
                FROM cuentas_pagar cp
                WHERE cp.id_compra = :id_compra AND cp.activo = 1
                ORDER BY cp.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([':id_compra' => $idCompra]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en CuentaPagar::obtenerPorCompra: ' . $e->getMessage());
            return null;
        }
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cuentas_pagar WHERE id_cuenta_pagar = :id AND activo = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function obtenerPagos(int $idCuentaPagar): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM pago_compra
                WHERE id_cuenta_pagar = :id_cuenta_pagar AND activo = 1
                ORDER BY fecha_pago DESC, created_at DESC
            ");
            $stmt->execute([':id_cuenta_pagar' => $idCuentaPagar]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en CuentaPagar::obtenerPagos: ' . $e->getMessage());
            return [];
        }
    }

    // ============================================================
    //  CRUD
    // ============================================================

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE cuentas_pagar SET activo = 0 WHERE id_cuenta_pagar = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restaurar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE cuentas_pagar SET activo = 1 WHERE id_cuenta_pagar = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerUltimoId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }

    // ============================================================
    //  Cuentas
    // ============================================================

    public function crear(int $idCompra, float $montoTotal, ?string $fechaVencimiento = null, ?string $observacion = null): bool
    {
        $this->validateData([
            'id_compra' => $idCompra,
            'monto_total' => $montoTotal,
            'fecha_vencimiento' => $fechaVencimiento,
            'observacion' => $observacion,
        ]);
        $stmt = $this->db->prepare("
            INSERT INTO cuentas_pagar (id_compra, monto_total, saldo_pendiente, fecha_vencimiento, observacion)
            VALUES (:id_compra, :monto_total, :saldo_pendiente, :fecha_vencimiento, :observacion)
        ");
        return $stmt->execute([
            ':id_compra' => $idCompra,
            ':monto_total' => $montoTotal,
            ':saldo_pendiente' => $montoTotal,
            ':fecha_vencimiento' => $fechaVencimiento,
            ':observacion' => $observacion,
        ]);
    }

    private function actualizarSaldo(int $idCuentaPagar): bool
    {
        $stmt = $this->db->prepare("
            UPDATE cuentas_pagar cp
            SET cp.saldo_pendiente = cp.monto_total - (
                SELECT COALESCE(SUM(pg.monto), 0)
                FROM pago_compra pg
                WHERE pg.id_cuenta_pagar = cp.id_cuenta_pagar
                  AND pg.estado = 'confirmado'
                  AND pg.activo = 1
            )
            WHERE cp.id_cuenta_pagar = :id_cuenta_pagar
        ");
        $stmt->execute([':id_cuenta_pagar' => $idCuentaPagar]);

        // Actualizar estado según saldo
        $stmt2 = $this->db->prepare("
            UPDATE cuentas_pagar cp
            SET cp.estado = CASE
                WHEN cp.saldo_pendiente <= 0 THEN 'pagada'
                WHEN cp.saldo_pendiente < cp.monto_total THEN 'parcial'
                ELSE 'pendiente'
            END
            WHERE cp.id_cuenta_pagar = :id_cuenta_pagar
        ");
        return $stmt2->execute([':id_cuenta_pagar' => $idCuentaPagar]);
    }

    private function actualizarEstadoCompra(int $idCompra): void
    {
        $stmt = $this->db->prepare("
            UPDATE compra c
            SET c.estado = CASE
                WHEN (
                    SELECT COALESCE(SUM(pg.monto), 0)
                    FROM cuentas_pagar cp
                    JOIN pago_compra pg ON pg.id_cuenta_pagar = cp.id_cuenta_pagar
                    WHERE cp.id_compra = c.id_compra
                      AND pg.estado = 'confirmado'
                      AND pg.activo = 1
                      AND cp.activo = 1
                ) >= c.total THEN 'pagada'
                ELSE c.estado
            END
            WHERE c.id_compra = :id_compra AND c.estado = 'recibida'
        ");
        $stmt->execute([':id_compra' => $idCompra]);
    }

    // ============================================================
    //  Pagos
    // ============================================================

    public function registrarPago(
        int $idCuentaPagar,
        float $monto,
        string $fechaPago,
        ?string $tipoPago = null,
        ?string $referencia = null,
        ?string $observacion = null
    ): bool {
        $cuenta = $this->obtenerPorId($idCuentaPagar);
        if (!$cuenta) throw new \Exception('No existe la cuenta por pagar.');
        if ($cuenta['estado'] === 'pagada') throw new \Exception('La cuenta ya está pagada.');

        $this->iniciarTransaccion();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO pago_compra (id_cuenta_pagar, monto, tipo_pago, referencia, fecha_pago, observacion)
                VALUES (:id_cuenta_pagar, :monto, :tipo_pago, :referencia, :fecha_pago, :observacion)
            ");
            $stmt->execute([
                ':id_cuenta_pagar' => $idCuentaPagar,
                ':monto' => $monto,
                ':tipo_pago' => $tipoPago,
                ':referencia' => $referencia,
                ':fecha_pago' => $fechaPago,
                ':observacion' => $observacion,
            ]);

            $this->actualizarSaldo($idCuentaPagar);
            $this->actualizarEstadoCompra($cuenta['id_compra']);

            $this->confirmarTransaccion();
            return true;
        } catch (\Exception $e) {
            $this->revertirTransaccion();
            throw $e;
        }
    }

    public function anularPago(int $idPagoCompra): bool
    {
        $stmt = $this->db->prepare("UPDATE pago_compra SET estado = 'anulado', activo = 0 WHERE id_pago_compra = :id_pago_compra AND activo = 1");
        $stmt->execute([':id_pago_compra' => $idPagoCompra]);

        // Obtener cuenta asociada
        $stmt2 = $this->db->prepare("SELECT id_cuenta_pagar FROM pago_compra WHERE id_pago_compra = :id_pago_compra");
        $stmt2->execute([':id_pago_compra' => $idPagoCompra]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->actualizarSaldo((int)$row['id_cuenta_pagar']);
        }

        return true;
    }
}
