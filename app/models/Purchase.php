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

    private array $schemaCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $key = "$table.$column";
        if (array_key_exists($key, $this->schemaCache)) {
            return $this->schemaCache[$key];
        }
        try {
            $stmt = $this->db()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            return $this->schemaCache[$key] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return $this->schemaCache[$key] = false;
        }
    }

    private function fkEstado(): bool { return $this->hasColumn('lote', 'id_estado'); }

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

    // ============================================================
    //  Consultas
    // ============================================================

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
                    d.id_detalle, d.tipo_item, d.id_item, d.cantidad, d.costo_unitario, d.subtotal,
                    d.categoria_lote, d.id_ubicacion,
                    CASE
                        WHEN d.tipo_item = 'insumo' THEN i.nombre_insumo
                        WHEN d.tipo_item = 'herramienta' THEN h.nombre_herramienta
                        WHEN d.tipo_item = 'planta' THEN p.nombre_comun
                    END AS item_nombre
                FROM compra_detalle d
                LEFT JOIN insumo i ON d.tipo_item = 'insumo' AND d.id_item = i.id_insumo
                LEFT JOIN herramienta h ON d.tipo_item = 'herramienta' AND d.id_item = h.id_herramienta
                LEFT JOIN plantas p ON d.tipo_item = 'planta' AND d.id_item = p.id_planta
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

    // ============================================================
    //  CRUD
    // ============================================================

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
            'fecha_compra' => $fechaCompra,
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
            'fecha_compra' => $fechaCompra,
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

    // ============================================================
    //  Detalles
    // ============================================================

    public function agregarDetalle(int $idCompra, string $tipoItem, int $idItem, float $cantidad, float $costoUnitario, float $subtotal, ?string $categoriaLote = null, ?int $idUbicacion = null): bool
    {
        $stmt = $this->db()->prepare("
            INSERT INTO compra_detalle (id_compra, tipo_item, id_item, cantidad, costo_unitario, subtotal, categoria_lote, id_ubicacion)
            VALUES (:id_compra, :tipo_item, :id_item, :cantidad, :costo_unitario, :subtotal, :categoria_lote, :id_ubicacion)
        ");
        return $stmt->execute([
            ':id_compra' => $idCompra,
            ':tipo_item' => $tipoItem,
            ':id_item' => $idItem,
            ':cantidad' => $cantidad,
            ':costo_unitario' => $costoUnitario,
            ':subtotal' => $subtotal,
            ':categoria_lote' => $categoriaLote,
            ':id_ubicacion' => $idUbicacion,
        ]);
    }

    public function eliminarDetalles(int $idCompra): bool
    {
        $stmt = $this->db()->prepare("UPDATE compra_detalle SET activo = 0 WHERE id_compra = :id_compra");
        return $stmt->execute([':id_compra' => $idCompra]);
    }

    // ============================================================
    //  Cuenta por pagar (misma conexión, misma TX)
    // ============================================================

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

    // ============================================================
    //  Estado
    // ============================================================

    public function actualizarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['pendiente', 'recibida', 'cancelada'], true)) {
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
        $stmt = $this->db()->prepare("UPDATE compra SET estado = 'recibida' WHERE id_compra = :id AND activo = 1");
        $result = $stmt->execute([':id' => $id]);
        AuditLog::record('UPDATE', 'compra', $id, ['estado' => 'pendiente', 'fecha_recepcion' => null], ['estado' => 'recibida', 'stock_aplicado' => true]);
        return $result;
    }

    // ============================================================
    //  Stock y lotes
    // ============================================================

    public function aplicarStock(int $idCompra): array
    {
        $detalles = $this->obtenerDetalles($idCompra);
        $resultados = [];

        $this->iniciarTransaccion();

        try {
            // Agrupar plantas por (id_item, categoria_lote)
            $gruposPlantas = [];
            foreach ($detalles as $d) {
                if ($d['tipo_item'] !== 'planta') continue;
                $clave = $d['id_item'] . '_' . ($d['categoria_lote'] ?? 'germinado');
                if (!isset($gruposPlantas[$clave])) {
                    $gruposPlantas[$clave] = [
                        'id_item'    => $d['id_item'],
                        'nombre'     => $d['item_nombre'],
                        'categoria'  => $d['categoria_lote'] ?? 'germinado',
                        'ubicacion'  => $d['id_ubicacion'],
                        'cantidad'   => 0,
                        'costo_total' => 0,
                    ];
                }
                $gruposPlantas[$clave]['cantidad'] += $d['cantidad'];
                $gruposPlantas[$clave]['costo_total'] += $d['cantidad'] * $d['costo_unitario'];
            }

            // Procesar cada detalle según su tipo
            foreach ($detalles as $d) {
                if ($d['tipo_item'] === 'insumo') {
                    $stmt = $this->db()->prepare("
                        UPDATE insumo
                        SET stock_actual = stock_actual + :cantidad,
                            costo_unitario_actual = :costo_unitario
                        WHERE id_insumo = :id_insumo AND activo = 1
                    ");
                    $stmt->execute([
                        ':cantidad' => $d['cantidad'],
                        ':costo_unitario' => $d['costo_unitario'],
                        ':id_insumo' => $d['id_item'],
                    ]);
                    $resultados[] = ['tipo' => 'insumo', 'id' => $d['id_item'], 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad']];
                }

                if ($d['tipo_item'] === 'herramienta') {
                    $stmt = $this->db()->prepare("
                        UPDATE herramienta
                        SET estado = 'disponible',
                            fecha_adquisicion = COALESCE(fecha_adquisicion, CURDATE())
                        WHERE id_herramienta = :id_herramienta AND activo = 1
                    ");
                    $stmt->execute([':id_herramienta' => $d['id_item']]);
                    $resultados[] = ['tipo' => 'herramienta', 'id' => $d['id_item'], 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad']];
                }
            }

            // Crear lotes para plantas agrupadas
            foreach ($gruposPlantas as $g) {
                $totalPlantas = (int)$g['cantidad'];
                $costoUnitario = $totalPlantas > 0 ? round($g['costo_total'] / $totalPlantas, 2) : 0;

                $estadoVivo = 'Vivo';
                $origenCompra = 'Compra';
                $fk = $this->fkEstado();
                if ($fk) {
                    $idEstadoVivo = (int)$this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1")->fetchColumn();
                    $idOrigenCompra = (int)$this->db()->query("SELECT id_origen FROM origen WHERE nombre = 'Compra' LIMIT 1")->fetchColumn();
                } else {
                    $idEstadoVivo = 0;
                    $idOrigenCompra = 0;
                }
                $stmt = $this->db()->prepare($fk
                    ? "INSERT INTO lote
                        (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual,
                         id_estado, id_origen, costo_unitario, observacion)
                       VALUES
                        (:id_planta, :id_ubicacion, CURDATE(), :cantidad_ini, :cantidad_act,
                         :id_estado, :id_origen, :costo_unitario, :observacion)"
                    : "INSERT INTO lote
                        (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual,
                         estado, origen, costo_unitario, observacion)
                       VALUES
                        (:id_planta, :id_ubicacion, CURDATE(), :cantidad_ini, :cantidad_act,
                         :estado, :origen, :costo_unitario, :observacion)"
                );
                $stmt->execute($fk
                    ? [
                        ':id_planta'       => $g['id_item'],
                        ':id_ubicacion'    => $g['ubicacion'],
                        ':cantidad_ini'    => $totalPlantas,
                        ':cantidad_act'    => $totalPlantas,
                        ':id_estado'       => $idEstadoVivo,
                        ':id_origen'       => $idOrigenCompra,
                        ':costo_unitario'  => $costoUnitario,
                        ':observacion'     => 'Ingresado por compra #' . $idCompra,
                    ]
                    : [
                        ':id_planta'       => $g['id_item'],
                        ':id_ubicacion'    => $g['ubicacion'],
                        ':cantidad_ini'    => $totalPlantas,
                        ':cantidad_act'    => $totalPlantas,
                        ':estado'          => $estadoVivo,
                        ':origen'          => $origenCompra,
                        ':costo_unitario'  => $costoUnitario,
                        ':observacion'     => 'Ingresado por compra #' . $idCompra,
                    ]
                );
                $loteId = $this->db()->lastInsertId();

                $stmt2 = $this->db()->prepare("UPDATE plantas SET cantidad_total = cantidad_total + :cantidad WHERE id_planta = :id_planta");
                $stmt2->execute([':cantidad' => $totalPlantas, ':id_planta' => $g['id_item']]);

                $resultados[] = ['tipo' => 'planta', 'id' => $g['id_item'], 'lote_id' => $loteId, 'nombre' => $g['nombre'], 'cantidad' => $totalPlantas, 'costo_unitario' => $costoUnitario];
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
