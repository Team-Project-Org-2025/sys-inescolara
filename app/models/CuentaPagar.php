<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;
use SysInescolara\models\AuditLog;

class CuentaPagar extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idCompra = null;
    private float $montoTotal = 0.00;
    private float $saldoPendiente = 0.00;
    private ?string $fechaVencimiento = null;
    private string $estado = 'pendiente';
    private ?string $observacion = null;
    private int $activo = 1;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    protected array $validationRules = [
        'id_compra'         => ['type' => null,   'required' => true],
        'monto_total'       => ['type' => 'precio','required' => true],
        'fecha_vencimiento' => ['type' => null,   'required' => false],
        'observacion'       => ['type' => null,   'required' => false],
    ];

    protected array $fillable = ['id_compra', 'monto_total', 'fecha_vencimiento', 'observacion'];
    protected array $guarded = ['id_cuenta_pagar'];

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable, true)) {
                $property = $this->mapColumnToProperty($key);
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }
        }
        return $this;
    }

    private function mapColumnToProperty(string $column): string
    {
        $map = [
            'id_cuenta_pagar'  => 'id',
            'id_compra'        => 'idCompra',
            'monto_total'      => 'montoTotal',
            'saldo_pendiente'  => 'saldoPendiente',
            'fecha_vencimiento'=> 'fechaVencimiento',
            'estado'           => 'estado',
            'observacion'      => 'observacion',
            'activo'           => 'activo',
            'created_at'       => 'createdAt',
            'updated_at'       => 'updatedAt',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getIdCompra(): ?int { return $this->idCompra; }
    public function getMontoTotal(): float { return $this->montoTotal; }
    public function getSaldoPendiente(): float { return $this->saldoPendiente; }
    public function getFechaVencimiento(): ?string { return $this->fechaVencimiento; }
    public function getEstado(): string { return $this->estado; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function isActivo(): bool { return $this->activo === 1; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // --- Setters ---
    public function setIdCompra(?int $idCompra): self
    {
        $this->idCompra = $idCompra;
        return $this;
    }

    public function setMontoTotal(float $montoTotal): self
    {
        $this->montoTotal = max(0, $montoTotal);
        return $this;
    }

    public function setFechaVencimiento(?string $fechaVencimiento): self
    {
        $this->fechaVencimiento = $fechaVencimiento;
        return $this;
    }

    public function setObservacion(?string $observacion): self
    {
        $this->observacion = $observacion;
        return $this;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo ? 1 : 0;
        return $this;
    }

    private function validate(): void
    {
        $this->validateData([
            'id_compra'         => $this->idCompra,
            'monto_total'       => $this->montoTotal,
            'fecha_vencimiento' => $this->fechaVencimiento,
            'observacion'       => $this->observacion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO cuentas_pagar (id_compra, monto_total, saldo_pendiente, fecha_vencimiento, observacion, activo)
                        VALUES (:id_compra, :monto_total, :saldo_pendiente, :fecha_vencimiento, :observacion, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id_compra'         => $this->idCompra,
                    ':monto_total'       => $this->montoTotal,
                    ':saldo_pendiente'   => $this->montoTotal,
                    ':fecha_vencimiento' => $this->fechaVencimiento,
                    ':observacion'       => $this->observacion,
                    ':activo'            => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'cuentas_pagar', $this->id, null, [
                        'id_compra'         => $this->idCompra,
                        'monto_total'       => $this->montoTotal,
                        'saldo_pendiente'   => $this->montoTotal,
                        'fecha_vencimiento' => $this->fechaVencimiento,
                        'observacion'       => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->obtenerPorId($this->id);
                $sql = "UPDATE cuentas_pagar SET id_compra = :id_compra, monto_total = :monto_total,
                        saldo_pendiente = :saldo_pendiente, fecha_vencimiento = :fecha_vencimiento,
                        observacion = :observacion, activo = :activo
                        WHERE id_cuenta_pagar = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                => $this->id,
                    ':id_compra'         => $this->idCompra,
                    ':monto_total'       => $this->montoTotal,
                    ':saldo_pendiente'   => $this->saldoPendiente,
                    ':fecha_vencimiento' => $this->fechaVencimiento,
                    ':observacion'       => $this->observacion,
                    ':activo'            => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'cuentas_pagar', $this->id, $oldData, [
                        'id_compra'         => $this->idCompra,
                        'monto_total'       => $this->montoTotal,
                        'saldo_pendiente'   => $this->saldoPendiente,
                        'fecha_vencimiento' => $this->fechaVencimiento,
                        'observacion'       => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar cuenta por pagar: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM cuentas_pagar WHERE id_cuenta_pagar = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $cuenta = new static($row);
        $cuenta->id = (int)$row['id_cuenta_pagar'];
        return $cuenta;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM cuentas_pagar WHERE activo = 1 ORDER BY created_at DESC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM cuentas_pagar WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idCompra = $found->getIdCompra();
            $this->montoTotal = $found->getMontoTotal();
            $this->saldoPendiente = $found->getSaldoPendiente();
            $this->fechaVencimiento = $found->getFechaVencimiento();
            $this->estado = $found->getEstado();
            $this->observacion = $found->getObservacion();
            $this->activo = $found->isActivo() ? 1 : 0;
            $this->createdAt = $found->getCreatedAt();
            $this->updatedAt = $found->getUpdatedAt();
            return true;
        }
        return false;
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


    protected function iniciarTransaccion(): bool
    {
        return $this->db()->beginTransaction();
    }

    protected function confirmarTransaccion(): bool
    {
        return $this->db()->commit();
    }

    protected function revertirTransaccion(): bool
    {
        return $this->db()->rollBack();
    }

    public function obtenerTodas(): array
    {
        try {
            $sql = "SELECT
                        cp.id_cuenta_pagar AS id, cp.id_cuenta_pagar, cp.id_compra, cp.monto_total,
                        ROUND(cp.monto_total - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente,
                        cp.fecha_vencimiento,
                        CASE
                            WHEN COALESCE(pag.total_pagado, 0) >= cp.monto_total THEN 'pagada'
                            WHEN COALESCE(pag.total_pagado, 0) > 0 THEN 'parcial'
                            ELSE 'pendiente'
                        END AS estado,
                        cp.observacion,
                        c.fecha_compra, c.total AS compra_total,
                        p.nombre_proveedor AS proveedor_nombre,
                        p.rif_proveedor,
                        (SELECT COUNT(*) FROM pago_compra pg WHERE pg.id_cuenta_pagar = cp.id_cuenta_pagar AND pg.activo = 1) AS pagos_count
                    FROM cuentas_pagar cp
                    JOIN compra c ON cp.id_compra = c.id_compra
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    LEFT JOIN (
                        SELECT id_cuenta_pagar, SUM(monto) AS total_pagado
                        FROM pago_compra
                        WHERE estado IN ('registrado', 'confirmado') AND activo = 1
                        GROUP BY id_cuenta_pagar
                    ) pag ON cp.id_cuenta_pagar = pag.id_cuenta_pagar
                    WHERE cp.activo = 1
                      AND c.activo = 1
                      AND c.estado != 'cancelada'
                    ORDER BY cp.created_at DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en CuentaPagar::obtenerTodas: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT
                    cp.id_cuenta_pagar, cp.id_compra, cp.monto_total,
                    ROUND(cp.monto_total - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente,
                    cp.fecha_vencimiento,
                    CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= cp.monto_total THEN 'pagada'
                        WHEN COALESCE(pag.total_pagado, 0) > 0 THEN 'parcial'
                        ELSE 'pendiente'
                    END AS estado,
                    cp.observacion, cp.activo, cp.created_at,
                    c.fecha_compra, c.total AS compra_total,
                    p.nombre_proveedor, p.rif_proveedor
                FROM cuentas_pagar cp
                JOIN compra c ON cp.id_compra = c.id_compra
                LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                LEFT JOIN (
                    SELECT id_cuenta_pagar, SUM(monto) AS total_pagado
                    FROM pago_compra
                    WHERE estado IN ('registrado', 'confirmado') AND activo = 1
                    GROUP BY id_cuenta_pagar
                ) pag ON cp.id_cuenta_pagar = pag.id_cuenta_pagar
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
            $stmt = $this->db()->prepare("
                SELECT
                    cp.id_cuenta_pagar, cp.id_compra, cp.monto_total,
                    ROUND(cp.monto_total - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente,
                    cp.fecha_vencimiento,
                    CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= cp.monto_total THEN 'pagada'
                        WHEN COALESCE(pag.total_pagado, 0) > 0 THEN 'parcial'
                        ELSE 'pendiente'
                    END AS estado,
                    cp.observacion, cp.activo, cp.created_at
                FROM cuentas_pagar cp
                LEFT JOIN (
                    SELECT id_cuenta_pagar, SUM(monto) AS total_pagado
                    FROM pago_compra
                    WHERE estado IN ('registrado', 'confirmado') AND activo = 1
                    GROUP BY id_cuenta_pagar
                ) pag ON cp.id_cuenta_pagar = pag.id_cuenta_pagar
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
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM cuentas_pagar WHERE id_cuenta_pagar = :id AND activo = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function obtenerPagos(int $idCuentaPagar): array
    {
        try {
            $stmt = $this->db()->prepare("
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

    public function eliminar(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE cuentas_pagar SET activo = 0 WHERE id_cuenta_pagar = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restaurar(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE cuentas_pagar SET activo = 1 WHERE id_cuenta_pagar = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerUltimoId(): ?int
    {
        $id = $this->db()->lastInsertId();
        return $id !== false ? (int) $id : null;
    }


    public function crear(int $idCompra, float $montoTotal, ?string $fechaVencimiento = null, ?string $observacion = null): bool
    {
        $this->fill([
            'id_compra'         => $idCompra,
            'monto_total'       => $montoTotal,
            'fecha_vencimiento' => $fechaVencimiento,
            'observacion'       => $observacion,
        ]);
        return $this->save();
    }

    private function actualizarEstadoCompra(int $idCompra): void
    {
        $stmt = $this->db()->prepare("
            UPDATE compra c
            SET c.estado = CASE
                WHEN (
                    SELECT COALESCE(SUM(pg.monto), 0)
                    FROM cuentas_pagar cp
                    JOIN pago_compra pg ON pg.id_cuenta_pagar = cp.id_cuenta_pagar
                    WHERE cp.id_compra = c.id_compra
                  AND pg.estado IN ('registrado', 'confirmado')
                      AND pg.activo = 1
                      AND cp.activo = 1
                ) >= c.total THEN 'pagada'
                ELSE c.estado
            END
            WHERE c.id_compra = :id_compra AND c.estado = 'recibida'
        ");
        $stmt->execute([':id_compra' => $idCompra]);
    }


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
            $stmt = $this->db()->prepare("
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

            $this->actualizarEstadoCompra($cuenta['id_compra']);

            $this->confirmarTransaccion();
            $nuevoId = (int)$this->db()->lastInsertId();
            AuditLog::record('CREATE', 'pago_compra', $nuevoId, null, [
                'id_cuenta_pagar' => $idCuentaPagar,
                'monto'           => $monto,
                'tipo_pago'       => $tipoPago,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->revertirTransaccion();
            throw $e;
        }
    }

    public function anularPago(int $idPagoCompra): bool
    {
        $stmtOld = $this->db()->prepare("SELECT * FROM pago_compra WHERE id_pago_compra = :id");
        $stmtOld->execute([':id' => $idPagoCompra]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt = $this->db()->prepare("UPDATE pago_compra SET estado = 'anulado', activo = 0 WHERE id_pago_compra = :id_pago_compra AND activo = 1");
        $result = $stmt->execute([':id_pago_compra' => $idPagoCompra]);
        AuditLog::record('DEACTIVATE', 'pago_compra', $idPagoCompra, $oldData, null);
        return $result;
    }
}
