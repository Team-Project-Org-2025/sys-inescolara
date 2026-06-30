<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use SysInescolara\models\AuditLog;

class Venta extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    protected array $validationRules = [
        'id_cliente'    => ['type' => null, 'required' => false],
        'id_trabajador' => ['type' => null, 'required' => true],
        'tipo_venta'    => ['type' => null, 'required' => false],
        'fecha_venta'   => ['type' => null, 'required' => false],
        'observaciones' => ['type' => null, 'required' => false],
    ];

    private const IVA_PORCENTAJE = 16.00;
    private const IVA_MULTIPLICADOR = 1.16;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        v.id_venta AS id,
                        v.id_venta,
                        v.referencia,
                        v.id_cliente,
                        v.id_trabajador,
                        v.tipo_venta,
                        v.estado,
                        v.iva_porcentaje,
                        v.fecha_venta,
                        v.observaciones,
                        v.activo,
                        CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                        c.tipo_cedula_cliente,
                        c.cedula_cliente,
                        c.apellido_cliente,
                        t.nombre_trabajador,
                        t.apellido_trabajador,
                        COALESCE((SELECT SUM(dv.cantidad * dv.precio_unitario) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta), 0) AS monto_subtotal,
                        COALESCE((SELECT SUM(dv.cantidad * dv.precio_unitario) / :iva_mult FROM detalle_venta dv WHERE dv.id_venta = v.id_venta), 0) AS monto_sin_iva,
                        COALESCE((SELECT SUM(pv.monto) FROM pago_venta pv WHERE pv.id_venta = v.id_venta), 0) AS total_pagado
                    FROM venta v
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente AND c.activo = 1
                    LEFT JOIN trabajadores t ON v.id_trabajador = t.id_trabajador AND t.activo = 1
                    WHERE v.activo = 1
                    ORDER BY v.fecha_venta DESC, v.id_venta DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute([':iva_mult' => self::IVA_MULTIPLICADOR]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener ventas: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT
                        v.*,
                        CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                        c.tipo_cedula_cliente,
                        c.cedula_cliente,
                        t.nombre_trabajador,
                        t.apellido_trabajador,
                        COALESCE((SELECT SUM(dv.cantidad * dv.precio_unitario) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta), 0) AS monto_subtotal,
                        COALESCE((SELECT SUM(pv.monto) FROM pago_venta pv WHERE pv.id_venta = v.id_venta), 0) AS total_pagado
                    FROM venta v
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                    LEFT JOIN trabajadores t ON v.id_trabajador = t.id_trabajador
                    WHERE v.id_venta = :id";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error al obtener venta por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function obtenerVentaConDetalles(int $id): ?array
    {
        $venta = $this->getById($id);
        if (!$venta) {
            return null;
        }

        $venta['detalles'] = $this->obtenerDetalles($id);
        $venta['pagos'] = $this->obtenerPagos($id);

        $subtotalConIva = array_sum(array_column($venta['detalles'], 'sub_total'));
        $venta['monto_sin_iva'] = $subtotalConIva / self::IVA_MULTIPLICADOR;
        $venta['monto_iva'] = $venta['monto_sin_iva'] * (self::IVA_PORCENTAJE / 100);
        $venta['monto_total'] = $venta['monto_sin_iva'] + $venta['monto_iva'];

        return $venta;
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM venta WHERE id_venta = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            error_log('Error en exists: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        return $this->cancelar($id);
    }

    public function cancelar(int $id): bool
    {
        $oldData = $this->getById($id);
        try {
            $this->db()->beginTransaction();

            $detalles = $this->obtenerDetalles($id);
            if (empty($detalles)) {
                throw new \Exception('No se encontraron detalles para esta venta.');
            }

            $stmtStockLote = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad, id_estado = IF(cantidad_actual + :cantidad2 > 0, (SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1), id_estado) WHERE id_lote = :id_lote");
            $stmtStockInsumo = $this->db()->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id_insumo");
            foreach ($detalles as $det) {
                if (($det['tipo_item'] ?? 'planta') === 'insumo') {
                    $stmtStockInsumo->execute([
                        ':cantidad' => (int)$det['cantidad'],
                        ':id_insumo' => (int)($det['id_insumo'] ?? 0),
                    ]);
                } else {
                    $stmtStockLote->execute([
                        ':cantidad' => (int)$det['cantidad'],
                        ':cantidad2' => (int)$det['cantidad'],
                        ':id_lote' => (int)$det['id_lote'],
                    ]);
                }
            }

            $stmt = $this->db()->prepare("UPDATE venta SET activo = 0, estado = 'cancelada', updated_at = NOW() WHERE id_venta = :id");
            $stmt->execute([':id' => $id]);

            $this->db()->commit();
            AuditLog::record('DEACTIVATE', 'venta', $id, $oldData, null);
            return true;
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log('Error al cancelar venta: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $this->db()->beginTransaction();

            $stmt = $this->db()->prepare("UPDATE venta SET activo = 1, estado = 'completada', updated_at = NOW() WHERE id_venta = :id");
            $stmt->execute([':id' => $id]);

            $detalles = $this->obtenerDetalles($id);
            $stmtStock = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual - :cantidad WHERE id_lote = :id_lote");
            foreach ($detalles as $det) {
                $stmtStock->execute([
                    ':cantidad' => (int)$det['cantidad'],
                    ':id_lote' => (int)$det['id_lote'],
                ]);
            }

            $this->db()->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log('Error al restaurar venta: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerUltimoId(): ?int
    {
        try {
            $stmt = $this->db()->query("SELECT MAX(id_venta) FROM venta");
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generarReferencia(): string
    {
        try {
            $fecha = date('Ymd');
            $stmt = $this->db()->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(referencia, '-', -1) AS UNSIGNED)) FROM venta WHERE referencia LIKE :patron");
            $stmt->execute([':patron' => "VEN-{$fecha}-%"]);
            $maxNum = (int)$stmt->fetchColumn();
            return sprintf('VEN-%s-%03d', $fecha, $maxNum + 1);
        } catch (\Throwable $e) {
            return 'VEN-' . date('Ymd') . '-001';
        }
    }

    public function agregar(array $datos): int
    {
        $this->validateData([
            'id_cliente'    => $datos['id_cliente'] ?? null,
            'id_trabajador' => $datos['id_trabajador'] ?? null,
            'tipo_venta'    => $datos['tipo_venta'] ?? null,
            'fecha_venta'   => $datos['fecha_venta'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
        ]);
        try {
            $this->db()->beginTransaction();

            $referencia = $this->generarReferencia();

            $estado = ($datos['tipo_venta'] ?? 'contado') === 'credito' ? 'pendiente' : 'completada';
            $fechaVencimiento = null;
            if ($datos['tipo_venta'] === 'credito') {
                $fechaBase = $datos['fecha_venta'] ?? date('Y-m-d H:i:s');
                $fechaVencimiento = date('Y-m-d', strtotime($fechaBase . ' +30 days'));
            }

            $stmt = $this->db()->prepare("INSERT INTO venta
                (referencia, id_cliente, id_trabajador, tipo_venta, estado, iva_porcentaje, fecha_venta, fecha_vencimiento, observaciones)
                VALUES (:referencia, :id_cliente, :id_trabajador, :tipo_venta, :estado, :iva_porcentaje, :fecha_venta, :fecha_vencimiento, :observaciones)");

            $stmt->execute([
                ':referencia'       => $referencia,
                ':id_cliente'       => (int)$datos['id_cliente'],
                ':id_trabajador'    => (int)$datos['id_trabajador'],
                ':tipo_venta'       => $datos['tipo_venta'] ?? 'contado',
                ':estado'           => $estado,
                ':iva_porcentaje'   => self::IVA_PORCENTAJE,
                ':fecha_venta'      => $datos['fecha_venta'] ?? date('Y-m-d H:i:s'),
                ':fecha_vencimiento'=> $fechaVencimiento,
                ':observaciones'    => $datos['observaciones'] ?? null,
            ]);

            $ventaId = (int)$this->db()->lastInsertId();

            $this->agregarDetalles($ventaId, $datos['productos']);

            if (!empty($datos['pagos'])) {
                $this->agregarPagos($ventaId, $datos['pagos'], $datos['tipo_venta'] ?? 'contado');
            }

            $this->db()->commit();
            AuditLog::record('CREATE', 'venta', $ventaId, null, [
                'id_cliente'    => $datos['id_cliente'],
                'id_trabajador' => $datos['id_trabajador'],
                'tipo_venta'    => $datos['tipo_venta'] ?? 'contado',
                'productos'     => count($datos['productos'] ?? []),
                'pagos'         => count($datos['pagos'] ?? []),
            ]);
            return $ventaId;
        } catch (\Throwable $e) {
            $this->db()->rollBack();
            error_log('Error al agregar venta: ' . $e->getMessage());
            throw $e;
        }
    }

    private function agregarDetalles(int $idVenta, array $productos): void
    {
        $stmtDet = $this->db()->prepare("INSERT INTO detalle_venta
            (id_venta, tipo_item, id_lote, id_insumo, cantidad, precio_unitario)
            VALUES (:id_venta, :tipo_item, :id_lote, :id_insumo, :cantidad, :precio_unitario)");

        $stmtStockLote = $this->db()->prepare("UPDATE lote SET
            cantidad_actual = cantidad_actual - :cantidad
            WHERE id_lote = :id_lote");

        $stmtStockInsumo = $this->db()->prepare("UPDATE insumo SET
            stock_actual = stock_actual - :cantidad
            WHERE id_insumo = :id_insumo AND stock_actual >= :cantidad2");

        foreach ($productos as $item) {
            $tipoItem = $item['tipo_item'] ?? 'planta';
            $idLote = (int)($item['id_lote'] ?? 0);
            $idInsumo = (int)($item['id_insumo'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 0);

            if ($tipoItem === 'insumo') {
                if ($idInsumo <= 0) {
                    throw new \Exception("Insumo inválido.");
                }
                $stmtCheck = $this->db()->prepare("SELECT stock_actual FROM insumo WHERE id_insumo = :id AND activo = 1");
                $stmtCheck->execute([':id' => $idInsumo]);
                $insumo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$insumo) {
                    throw new \Exception("El insumo ID {$idInsumo} no existe.");
                }
                if ((float)$insumo['stock_actual'] < $cantidad) {
                    throw new \Exception("Stock insuficiente de insumo. Disponible: {$insumo['stock_actual']}, solicitado: {$cantidad}.");
                }

                $stmtDet->execute([
                    ':id_venta'       => $idVenta,
                    ':tipo_item'      => 'insumo',
                    ':id_lote'        => null,
                    ':id_insumo'      => $idInsumo,
                    ':cantidad'       => $cantidad,
                    ':precio_unitario'=> $item['precio_unitario'] ?? 0,
                ]);

                $stmtStockInsumo->execute([
                    ':cantidad'  => $cantidad,
                    ':id_insumo' => $idInsumo,
                    ':cantidad2' => $cantidad,
                ]);
            } else {
                if ($idLote <= 0) {
                    throw new \Exception("El lote es requerido.");
                }
                $stmtCheck = $this->db()->prepare("SELECT cantidad_actual FROM lote WHERE id_lote = :id_lote AND activo = 1");
                $stmtCheck->execute([':id_lote' => $idLote]);
                $lote = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$lote) {
                    throw new \Exception("El lote ID {$idLote} no existe.");
                }
                if ((int)$lote['cantidad_actual'] < $cantidad) {
                    throw new \Exception("Stock insuficiente en el lote ID {$idLote}. Disponible: {$lote['cantidad_actual']}, solicitado: {$cantidad}.");
                }

                $stmtDet->execute([
                    ':id_venta'       => $idVenta,
                    ':tipo_item'      => 'planta',
                    ':id_lote'        => $idLote,
                    ':id_insumo'      => null,
                    ':cantidad'       => $cantidad,
                    ':precio_unitario'=> $item['precio_unitario'] ?? 0,
                ]);

                $stmtStockLote->execute([
                    ':cantidad'  => $cantidad,
                    ':id_lote'   => $idLote,
                ]);
            }
        }
    }

    private function agregarPagos(int $idVenta, array $pagos, string $tipoVenta = 'contado'): void
    {
        $totalProductos = 0;
        $detalles = $this->obtenerDetalles($idVenta);
        foreach ($detalles as $d) {
            $totalProductos += (float)$d['sub_total'];
        }

        $totalPagos = 0;
        $stmtPago = $this->db()->prepare("INSERT INTO pago_venta
            (id_venta, metodo, monto, referencia)
            VALUES (:id_venta, :metodo, :monto, :referencia)");

        foreach ($pagos as $pago) {
            $monto = (float)($pago['monto'] ?? 0);
            $totalPagos += $monto;

            $stmtPago->execute([
                ':id_venta'  => $idVenta,
                ':metodo'    => $pago['metodo'] ?? 'efectivo',
                ':monto'     => $monto,
                ':referencia'=> $pago['referencia'] ?? null,
            ]);
        }

        if ($tipoVenta !== 'credito' && abs($totalPagos - $totalProductos) > 0.01) {
            throw new \Exception("El total de pagos ({$totalPagos}) no coincide con el total de la venta ({$totalProductos}).");
        }
    }

    public function obtenerDetalles(int $idVenta): array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                                            dv.id_detalle_venta,
                                            dv.id_venta,
                                            dv.tipo_item,
                                            dv.id_lote,
                                            dv.id_insumo,
                                            dv.cantidad,
                                            dv.precio_unitario,
                                            (dv.cantidad * dv.precio_unitario) AS sub_total,
                                            CASE dv.tipo_item
                                                WHEN 'insumo' THEN i.stock_actual
                                                ELSE l.cantidad_actual
                                            END AS cantidad_actual,
                                            CASE dv.tipo_item
                                                WHEN 'insumo' THEN i.nombre_insumo
                                                ELSE p.nombre_comun
                                            END AS planta_nombre,
                                            CASE dv.tipo_item
                                                WHEN 'insumo' THEN u.simbolo
                                                ELSE e.nombre_especie
                                            END AS especie_nombre,
                                            CASE dv.tipo_item
                                                WHEN 'insumo' THEN i.nombre_insumo
                                                ELSE p.nombre_tecnico
                                            END AS nombre_tecnico,
                                            CASE dv.tipo_item
                                                WHEN 'insumo' THEN u.simbolo
                                                ELSE NULL
                                            END AS unidad_simbolo
                                        FROM detalle_venta dv
                                        LEFT JOIN lote l ON dv.id_lote = l.id_lote
                                        LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                        LEFT JOIN especie e ON p.id_especie = e.id_especie
                                        LEFT JOIN insumo i ON dv.id_insumo = i.id_insumo
                                        LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                                        WHERE dv.id_venta = :id_venta
                                        ORDER BY dv.id_detalle_venta ASC");
            $stmt->execute([':id_venta' => $idVenta]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener detalles de venta: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerPagos(int $idVenta): array
    {
        try {
            $stmt = $this->db()->prepare("SELECT * FROM pago_venta WHERE id_venta = :id_venta ORDER BY created_at ASC");
            $stmt->execute([':id_venta' => $idVenta]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener pagos: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerLotesDisponibles(string $query): array
    {
        try {
            $searchTerm = "%{$query}%";
            $hasFilter = $query !== '';

            $plantaSql = "SELECT
                            l.id_lote,
                            'planta' AS tipo_item,
                            l.cantidad_actual,
                            p.nombre_comun AS planta_nombre,
                            p.nombre_comun AS nombre,
                            e.nombre_especie AS especie_nombre,
                            e.nombre_especie AS detalle,
                            c.precio_final_sugerido AS precio_unitario,
                            NULL AS unidad_simbolo
                        FROM lote l
                        JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                        LEFT JOIN especie e ON p.id_especie = e.id_especie
                        LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote AND c.vigente = 1
                        WHERE l.activo = 1
                        AND l.cantidad_actual > 0
                        AND c.id_calculo IS NOT NULL";
            if ($hasFilter) {
                $plantaSql .= " AND (p.nombre_comun LIKE ? OR e.nombre_especie LIKE ? OR p.nombre_tecnico LIKE ?)";
            }
            $plantaSql .= " ORDER BY p.nombre_comun ASC";
            if ($hasFilter) {
                $plantaSql .= " LIMIT 20";
            }

            $stmt = $this->db()->prepare($plantaSql);
            if ($hasFilter) {
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            } else {
                $stmt->execute();
            }
            $plantas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($plantas as &$p) {
                $p['id_insumo'] = null;
            }
            unset($p);

            $insumoSql = "SELECT
                            i.id_insumo AS id_lote,
                            'insumo' AS tipo_item,
                            i.stock_actual AS cantidad_actual,
                            i.categoria AS estado,
                            i.nombre_insumo AS planta_nombre,
                            i.nombre_insumo AS nombre,
                            u.simbolo AS especie_nombre,
                            u.simbolo AS detalle,
                            i.costo_unitario_actual AS precio_unitario,
                            u.simbolo AS unidad_simbolo,
                            i.id_insumo
                        FROM insumo i
                        LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida AND u.activo = 1
                        WHERE i.activo = 1
                        AND i.stock_actual > 0
                        AND i.costo_unitario_actual > 0";
            if ($hasFilter) {
                $insumoSql .= " AND (i.nombre_insumo LIKE ? OR i.categoria LIKE ?)";
            }
            $insumoSql .= " ORDER BY i.nombre_insumo ASC";
            if ($hasFilter) {
                $insumoSql .= " LIMIT 10";
            }

            $stmt2 = $this->db()->prepare($insumoSql);
            if ($hasFilter) {
                $stmt2->execute([$searchTerm, $searchTerm]);
            } else {
                $stmt2->execute();
            }
            $insumos = $stmt2 ? $stmt2->fetchAll(PDO::FETCH_ASSOC) : [];

            return array_merge($plantas, $insumos);
        } catch (\Throwable $e) {
            error_log('Error al buscar productos: ' . $e->getMessage());
            return [];
        }
    }

    public function buscarClientes(string $query): array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                                            id_cliente,
                                            CONCAT(nombre_cliente, ' ', apellido_cliente) AS nombre_cliente,
                                            tipo_cedula_cliente,
                                            cedula_cliente,
                                            apellido_cliente,
                                            contacto_cliente
                                        FROM cliente
                                        WHERE activo = 1
                                        AND (nombre_cliente LIKE ? OR apellido_cliente LIKE ? OR contacto_cliente LIKE ? OR cedula_cliente LIKE ?)
                                        ORDER BY nombre_cliente ASC, apellido_cliente ASC
                                        LIMIT 10");
            $searchTerm = "%{$query}%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al buscar clientes: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerTrabajadoresActivos(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_trabajador, nombre_trabajador, apellido_trabajador, cedula_trabajador FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error al obtener trabajadores: ' . $e->getMessage());
            return [];
        }
    }

    public function getLastInsertId(): ?int
    {
        return $this->obtenerUltimoId();
    }
}
