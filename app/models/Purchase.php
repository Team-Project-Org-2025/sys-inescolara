<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use PDO;

class Purchase extends Database implements ReadableInterface, DeletableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        c.id_compra AS id, c.id_compra, c.id_proveedor, c.fecha_compra,
                        c.tipo_comprobante, c.numero_comprobante,
                        c.subtotal, c.iva, c.total, c.estado, c.observacion,
                        p.nombre_proveedor AS proveedor_nombre,
                        p.rif_proveedor,
                        (SELECT COUNT(*) FROM compra_detalle d WHERE d.id_compra = c.id_compra AND d.activo = 1) AS items_count
                    FROM compra c
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    WHERE c.activo = 1
                    ORDER BY c.fecha_compra DESC, c.id_compra DESC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Purchase::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.nombre_proveedor AS proveedor_nombre, p.rif_proveedor
                FROM compra c
                LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                WHERE c.id_compra = :id AND c.activo = 1
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Purchase::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function getDetails(int $idCompra): array
    {
        try {
            $stmt = $this->db->prepare("
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
            error_log('Error en Purchase::getDetails: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM compra WHERE id_compra = :id AND activo = 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function add(
        int $idProveedor,
        string $fechaCompra,
        string $tipoComprobante,
        ?string $numeroComprobante,
        float $subtotal,
        float $iva,
        float $total,
        ?string $observacion
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO compra
                (id_proveedor, fecha_compra, tipo_comprobante, numero_comprobante,
                 subtotal, iva, total, observacion)
            VALUES
                (:id_proveedor, :fecha_compra, :tipo_comprobante, :numero_comprobante,
                 :subtotal, :iva, :total, :observacion)
        ");
        return $stmt->execute([
            ':id_proveedor' => $idProveedor,
            ':fecha_compra' => $fechaCompra,
            ':tipo_comprobante' => $tipoComprobante,
            ':numero_comprobante' => $numeroComprobante,
            ':subtotal' => $subtotal,
            ':iva' => $iva,
            ':total' => $total,
            ':observacion' => $observacion,
        ]);
    }

    public function update(
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
        if (!$this->exists($id)) {
            throw new \Exception('No existe la compra solicitada para modificar.');
        }
        $stmt = $this->db->prepare("
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
        return $stmt->execute([
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
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE compra SET activo = 0 WHERE id_compra = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE compra SET activo = 1 WHERE id_compra = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function addDetail(int $idCompra, string $tipoItem, int $idItem, float $cantidad, float $costoUnitario, float $subtotal, ?string $categoriaLote = null, ?int $idUbicacion = null): bool
    {
        $stmt = $this->db->prepare("
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

    public function deleteDetails(int $idCompra): bool
    {
        $stmt = $this->db->prepare("UPDATE compra_detalle SET activo = 0 WHERE id_compra = :id_compra");
        return $stmt->execute([':id_compra' => $idCompra]);
    }

    public function updateEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['pendiente', 'completada', 'cancelada'], true)) {
            throw new \Exception('Estado inválido.');
        }
        if (!$this->exists($id)) {
            throw new \Exception('No existe la compra.');
        }
        $stmt = $this->db->prepare("UPDATE compra SET estado = :estado WHERE id_compra = :id AND activo = 1");
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function aplicarStock(int $idCompra): array
    {
        $details = $this->getDetails($idCompra);
        $results = [];

        // Group plant items by (id_item, categoria_lote)
        $plantGroups = [];
        foreach ($details as $d) {
            if ($d['tipo_item'] !== 'planta') continue;
            $key = $d['id_item'] . '_' . ($d['categoria_lote'] ?? 'germinado');
            if (!isset($plantGroups[$key])) {
                $plantGroups[$key] = [
                    'id_item'    => $d['id_item'],
                    'nombre'     => $d['item_nombre'],
                    'categoria'  => $d['categoria_lote'] ?? 'germinado',
                    'ubicacion'  => $d['id_ubicacion'],
                    'cantidad'   => 0,
                    'costo_total' => 0,
                ];
            }
            $plantGroups[$key]['cantidad'] += $d['cantidad'];
            $plantGroups[$key]['costo_total'] += $d['cantidad'] * $d['costo_unitario'];
        }

        foreach ($details as $d) {
            if ($d['tipo_item'] === 'insumo') {
                $stmt = $this->db->prepare("
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
                $results[] = ['tipo' => 'insumo', 'id' => $d['id_item'], 'nombre' => $d['item_nombre'], 'cantidad' => $d['cantidad']];
            }
        }

        foreach ($plantGroups as $g) {
            $totalPlants = (int)$g['cantidad'];
            $costoUnitario = $totalPlants > 0 ? round($g['costo_total'] / $totalPlants, 2) : 0;

            $stmt = $this->db->prepare("
                INSERT INTO lote
                    (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual,
                     estado, categoria, origen, costo_unitario, observacion)
                VALUES
                    (:id_planta, :id_ubicacion, CURDATE(), :cantidad_ini, :cantidad_act,
                     'Activo', :categoria, 'Compra', :costo_unitario, :observacion)
            ");
            $stmt->execute([
                ':id_planta'       => $g['id_item'],
                ':id_ubicacion'    => $g['ubicacion'],
                ':cantidad_ini'    => $totalPlants,
                ':cantidad_act'    => $totalPlants,
                ':categoria'       => $g['categoria'],
                ':costo_unitario'  => $costoUnitario,
                ':observacion'     => 'Ingresado por compra #' . $idCompra,
            ]);
            $loteId = $this->db->lastInsertId();

            // Update plant total count
            $stmt2 = $this->db->prepare("UPDATE plantas SET cantidad_total = cantidad_total + :cantidad WHERE id_planta = :id_planta");
            $stmt2->execute([':cantidad' => $totalPlants, ':id_planta' => $g['id_item']]);

            $results[] = ['tipo' => 'planta', 'id' => $g['id_item'], 'lote_id' => $loteId, 'nombre' => $g['nombre'], 'cantidad' => $totalPlants, 'costo_unitario' => $costoUnitario];
        }

        return $results;
    }

    public function getLastInsertId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id !== false ? (int) $id : null;
    }
}
