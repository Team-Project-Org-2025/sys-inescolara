<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;

class CuentaCobrar extends Database
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_venta'      => ['type' => 'cantidad', 'required' => true],
        'monto'         => ['type' => 'precio',    'required' => true],
        'metodo'        => ['type' => null,        'required' => true],
        'referencia'    => ['type' => null,        'required' => false],
        'fecha_pago'    => ['type' => null,        'required' => true],
        'banco'         => ['type' => null,        'required' => false],
        'id_usuario' => ['type' => 'cantidad',   'required' => true],
        'observaciones' => ['type' => null,        'required' => false],
    ];

    protected array $fillable = [];
    protected array $guarded = ['id'];

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
            'id_venta'  => 'id',
        ];
        return $map[$column] ?? $column;
    }

    public function obtenerTodos(int $start = 0, int $length = 10, string $search = '', string $estadoFilter = ''): array
    {
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

            $where = "v.activo = 1 AND v.tipo_venta = 'credito'";
            $params = [];

            if ($search !== '') {
                $where .= " AND (v.referencia LIKE :search OR c.nombre_cliente LIKE :search2 OR c.apellido_cliente LIKE :search2 OR c.cedula_cliente LIKE :search2)";
                $params[':search'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }

            $having = '';
            if ($estadoFilter !== '') {
                $having = "HAVING estado_cuenta = :estado";
                $params[':estado'] = $estadoFilter;
            }

            $sql = "
                SELECT
                    v.id_venta,
                    v.referencia,
                    v.fecha_venta,
                    v.fecha_vencimiento,
                    v.observaciones,
                    c.id_cliente,
                    CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                    c.tipo_cedula_cliente,
                    c.cedula_cliente,
                    c.contacto_cliente AS contacto,
                    COALESCE(det.monto_total, 0) AS monto_total,
                    COALESCE(pag.total_pagado, 0) AS total_pagado,
                    ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente,
                    CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= COALESCE(det.monto_total, 0) THEN 'pagado'
                        WHEN v.fecha_vencimiento IS NOT NULL AND v.fecha_vencimiento < CURDATE() THEN 'vencido'
                        ELSE 'vigente'
                    END AS estado_cuenta
                FROM venta v
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                LEFT JOIN (
                    SELECT id_venta, SUM(cantidad * precio_unitario) AS monto_total
                    FROM detalle_venta
                    GROUP BY id_venta
                ) det ON v.id_venta = det.id_venta
                LEFT JOIN (
                    SELECT id_venta, SUM(monto) AS total_pagado
                    FROM pago_venta
                    WHERE estado_pago != 'rechazado'
                    GROUP BY id_venta
                ) pag ON v.id_venta = pag.id_venta
                WHERE {$where}
                {$having}
                ORDER BY
                    CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= COALESCE(det.monto_total, 0) THEN 3
                        WHEN v.fecha_vencimiento IS NOT NULL AND v.fecha_vencimiento < CURDATE() THEN 1
                        ELSE 2
                    END,
                    v.fecha_vencimiento ASC
            ";

            $countSql = "SELECT COUNT(*) FROM ({$sql}) AS sub";
            $countStmt = $this->db()->prepare($countSql);
            $countStmt->execute($params);
            $totalFiltered = (int)$countStmt->fetchColumn();

            $sql .= " LIMIT :lim OFFSET :off";
            $stmt = $this->db()->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':lim', $length, PDO::PARAM_INT);
            $stmt->bindValue(':off', $start, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalSql = "SELECT COUNT(*) FROM venta WHERE activo = 1 AND tipo_venta = 'credito'";
            $totalRecords = (int)$this->db()->query($totalSql)->fetchColumn();

            return [
                'data' => $data,
                'total' => $totalRecords,
                'filtered' => $totalFiltered,
            ];
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::obtenerTodos: ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'filtered' => 0];
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) return null;
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

            $stmt = $this->db()->prepare("
                SELECT
                    v.*,
                    CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                    c.tipo_cedula_cliente,
                    c.cedula_cliente,
                    c.contacto_cliente AS contacto,
                    CONCAT(u.nombre_trabajador, ' ', u.apellido_trabajador) AS trabajador,
                    COALESCE(det.monto_total, 0) AS monto_total,
                    COALESCE(pag.total_pagado, 0) AS total_pagado,
                    ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente
                FROM venta v
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                INNER JOIN security.usuarios u ON v.id_trabajador = u.id_usuario
                LEFT JOIN (
                    SELECT id_venta, SUM(cantidad * precio_unitario) AS monto_total
                    FROM detalle_venta
                    GROUP BY id_venta
                ) det ON v.id_venta = det.id_venta
                LEFT JOIN (
                    SELECT id_venta, SUM(monto) AS total_pagado
                    FROM pago_venta
                    WHERE estado_pago != 'rechazado'
                    GROUP BY id_venta
                ) pag ON v.id_venta = pag.id_venta
                WHERE v.id_venta = :id
            ");
            $stmt->execute([':id' => $id]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return null;
            }

            $stmtDet = $this->db()->prepare("
                SELECT
                    dv.*,
                    CONCAT(COALESCE(p.nombre_comun, p.nombre_tecnico), ' (Lote #', dv.id_lote, ')') AS producto
                FROM detalle_venta dv
                LEFT JOIN lote l ON dv.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE dv.id_venta = :id
            ");
            $stmtDet->execute([':id' => $id]);
            $venta['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            $venta['pagos'] = $this->obtenerPagos($id);

            return $venta;
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::obtenerPorId: ' . $e->getMessage());
            return null;
        }
    }

    public function obtenerPagos(int $idVenta): array
    {
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            $stmt = $this->db()->prepare("
                SELECT
                    p.*,
                    CONCAT(u.nombre_trabajador, ' ', u.apellido_trabajador) AS cobrador
                FROM pago_venta p
                LEFT JOIN security.usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_venta = :id
                ORDER BY p.fecha_pago DESC
            ");
            $stmt->execute([':id' => $idVenta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::obtenerPagos: ' . $e->getMessage());
            return [];
        }
    }

    public function registrarPago(int $idVenta, float $monto, string $metodo, ?string $referencia, string $fechaPago, ?string $banco, int $idUsuario, ?string $observaciones): int
    {
        $this->validarPago($idVenta, $monto, $metodo, $referencia, $fechaPago, $banco, $idUsuario);

        try {
            $stmt = $this->db()->prepare("
                INSERT INTO pago_venta (id_venta, metodo, monto, referencia, fecha_pago, banco, id_usuario, observaciones)
                VALUES (:id_venta, :metodo, :monto, :referencia, :fecha_pago, :banco, :id_usuario, :observaciones)
            ");
            $stmt->execute([
                ':id_venta' => $idVenta,
                ':metodo' => $metodo,
                ':monto' => $monto,
                ':referencia' => $referencia,
                ':fecha_pago' => $fechaPago,
                ':banco' => $banco,
                ':id_usuario' => $idUsuario,
                ':observaciones' => $observaciones,
            ]);
            $this->actualizarEstadoVenta($idVenta);
            return (int)$this->db()->lastInsertId();
        } catch (Throwable $e) {
            error_log('Error en CuentaCobrar::registrarPago: ' . $e->getMessage());
            throw $e;
        }
    }

    private function validarPago(int $idVenta, float $monto, string $metodo, ?string $referencia, string $fechaPago, ?string $banco, int $idUsuario): void
    {
        $this->validateData([
            'id_venta'      => $idVenta,
            'monto'         => $monto,
            'metodo'        => $metodo,
            'referencia'    => $referencia,
            'fecha_pago'    => $fechaPago,
            'banco'         => $banco,
            'id_usuario'    => $idUsuario,
        ]);

        if ($idVenta <= 0) {
            throw new \InvalidArgumentException('ID de venta inválido.');
        }
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }
        if ($idUsuario <= 0) {
            throw new \InvalidArgumentException('Debe seleccionar el trabajador que cobró.');
        }

        if (!in_array($metodo, ['efectivo', 'transferencia', 'punto', 'pago_movil', 'otro'], true)) {
            throw new \InvalidArgumentException('Método de pago inválido.');
        }

        $d = \DateTime::createFromFormat('Y-m-d', $fechaPago);
        if (!$d || $d->format('Y-m-d') !== $fechaPago) {
            throw new \InvalidArgumentException('Formato de fecha de pago inválido (Y-m-d).');
        }
        $todayStr = (new \DateTime('today'))->format('Y-m-d');
        if ($fechaPago > $todayStr) {
            throw new \InvalidArgumentException('La fecha de pago no puede ser posterior al día de hoy.');
        }

        $venta = $this->obtenerPorId($idVenta);
        if (!$venta) {
            throw new \InvalidArgumentException('Venta no encontrada.');
        }
        $fechaVenta = (new \DateTime($venta['fecha_venta']))->format('Y-m-d');
        if ($fechaPago < $fechaVenta) {
            throw new \InvalidArgumentException('La fecha de pago no puede ser anterior a la fecha de venta (' . $fechaVenta . ').');
        }

        if (in_array($metodo, ['transferencia', 'pago_movil'], true)) {
            if ($referencia === null || $referencia === '') {
                throw new \InvalidArgumentException('La referencia es requerida para transferencias y pago móvil.');
            }
            if (!preg_match('/^\d{6}$/', $referencia)) {
                throw new \InvalidArgumentException('La referencia debe tener exactamente 6 dígitos numéricos.');
            }
            if ($banco === null || $banco === '') {
                throw new \InvalidArgumentException('El banco es requerido para este método de pago.');
            }
        }
    }

    public function actualizarEstadoVenta(int $idVenta): void
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT
                    COALESCE(det.monto_total, 0) AS monto_total,
                    COALESCE(pag.total_pagado, 0) AS total_pagado
                FROM venta v
                LEFT JOIN (
                    SELECT id_venta, SUM(cantidad * precio_unitario) AS monto_total
                    FROM detalle_venta
                    GROUP BY id_venta
                ) det ON v.id_venta = det.id_venta
                LEFT JOIN (
                    SELECT id_venta, SUM(monto) AS total_pagado
                    FROM pago_venta
                    WHERE estado_pago != 'rechazado'
                    GROUP BY id_venta
                ) pag ON v.id_venta = pag.id_venta
                WHERE v.id_venta = :id
            ");
            $stmt->execute([':id' => $idVenta]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $nuevoEstado = ((float)$row['total_pagado'] >= (float)$row['monto_total']) ? 'completada' : 'pendiente';
                $upd = $this->db()->prepare("UPDATE venta SET estado = :estado WHERE id_venta = :id");
                $upd->execute([':estado' => $nuevoEstado, ':id' => $idVenta]);
            }
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::actualizarEstadoVenta: ' . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerEstadisticas(): array
    {
        try {
            $this->db()->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

            $sql = "
                SELECT
                    COUNT(*) AS total_cuentas,
                    COALESCE(SUM(CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= COALESCE(det.monto_total, 0) THEN 0
                        ELSE ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2)
                    END), 0) AS total_por_cobrar,
                    COUNT(CASE
                        WHEN COALESCE(pag.total_pagado, 0) >= COALESCE(det.monto_total, 0) THEN 1
                    END) AS total_pagadas,
                    COUNT(CASE
                        WHEN v.fecha_vencimiento IS NOT NULL AND v.fecha_vencimiento < CURDATE()
                            AND COALESCE(pag.total_pagado, 0) < COALESCE(det.monto_total, 0) THEN 1
                    END) AS total_vencidas,
                    COUNT(CASE
                        WHEN (v.fecha_vencimiento IS NULL OR v.fecha_vencimiento >= CURDATE())
                            AND COALESCE(pag.total_pagado, 0) < COALESCE(det.monto_total, 0) THEN 1
                    END) AS total_vigentes,
                    COALESCE(SUM(CASE
                        WHEN v.fecha_vencimiento >= CURDATE() OR v.fecha_vencimiento IS NULL THEN ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2)
                        ELSE 0
                    END), 0) AS monto_vigente,
                    COALESCE(SUM(CASE
                        WHEN v.fecha_vencimiento < CURDATE() AND COALESCE(pag.total_pagado, 0) < COALESCE(det.monto_total, 0) THEN ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2)
                        ELSE 0
                    END), 0) AS monto_vencido
                FROM venta v
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                LEFT JOIN (
                    SELECT id_venta, SUM(cantidad * precio_unitario) AS monto_total
                    FROM detalle_venta
                    GROUP BY id_venta
                ) det ON v.id_venta = det.id_venta
                LEFT JOIN (
                    SELECT id_venta, SUM(monto) AS total_pagado
                    FROM pago_venta
                    WHERE estado_pago != 'rechazado'
                    GROUP BY id_venta
                ) pag ON v.id_venta = pag.id_venta
                WHERE v.activo = 1 AND v.tipo_venta = 'credito'
            ";

            $stmt = $this->db()->query($sql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmtPagosMes = $this->db()->query("
                SELECT COALESCE(SUM(monto), 0) AS cobrado_mes
                FROM pago_venta
                WHERE estado_pago != 'rechazado'
                    AND MONTH(fecha_pago) = MONTH(CURDATE())
                    AND YEAR(fecha_pago) = YEAR(CURDATE())
            ");
            $stats['cobrado_mes'] = (float)$stmtPagosMes->fetchColumn();

            return $stats;
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::obtenerEstadisticas: ' . $e->getMessage());
            return [
                'total_cuentas' => 0,
                'total_por_cobrar' => 0,
                'total_pagadas' => 0,
                'total_vencidas' => 0,
                'total_vigentes' => 0,
                'monto_vigente' => 0,
                'monto_vencido' => 0,
                'cobrado_mes' => 0,
            ];
        }
    }

    public function obtenerClientes(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_cliente, CONCAT(nombre_cliente, ' ', apellido_cliente) AS nombre_cliente, contacto_cliente AS contacto FROM cliente WHERE activo = 1 ORDER BY nombre_cliente ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en CuentaCobrar::obtenerClientes: ' . $e->getMessage());
            return [];
        }
    }

    public function iniciarTransaccion(): void
    {
        if (!$this->db()->inTransaction()) {
            $this->db()->beginTransaction();
        }
    }

    public function confirmarTransaccion(): void
    {
        if ($this->db()->inTransaction()) {
            $this->db()->commit();
        }
    }

    public function revertirTransaccion(): void
    {
        if ($this->db()->inTransaction()) {
            $this->db()->rollBack();
        }
    }
}
