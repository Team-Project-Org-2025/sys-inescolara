<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use PDO;

class Ampliacion extends Database implements ReadableInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        mp.id_movimiento_planta AS id,
                        mp.tipo_movimiento,
                        mp.fecha_movimiento,
                        mp.observacion,
                        CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS gestor_nombre,
                        COALESCE(CONCAT(c.nombre_cliente, ' ', c.apellido_cliente), '—') AS cliente_nombre,
                        c.tipo_cedula_cliente,
                        c.cedula_cliente,
                        (SELECT COUNT(*) FROM movimiento_planta_detalle d WHERE d.id_movimiento_planta = mp.id_movimiento_planta AND d.tipo = 'salida' AND d.activo = 1) AS total_salida,
                        (SELECT COUNT(*) FROM movimiento_planta_detalle d WHERE d.id_movimiento_planta = mp.id_movimiento_planta AND d.tipo = 'entrada' AND d.activo = 1) AS total_entrada
                    FROM movimiento_planta mp
                    LEFT JOIN cliente c ON mp.id_cliente = c.id_cliente
                    LEFT JOIN trabajadores t ON mp.id_trabajador_gestor = t.id_trabajador
                    WHERE mp.activo = 1
                    ORDER BY mp.fecha_movimiento DESC, mp.id_movimiento_planta DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Ampliacion::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT mp.*,
                       CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS gestor_nombre,
                       COALESCE(CONCAT(c.nombre_cliente, ' ', c.apellido_cliente), '—') AS cliente_nombre,
                       c.tipo_cedula_cliente,
                       c.cedula_cliente
                FROM movimiento_planta mp
                LEFT JOIN cliente c ON mp.id_cliente = c.id_cliente
                LEFT JOIN trabajadores t ON mp.id_trabajador_gestor = t.id_trabajador
                WHERE mp.id_movimiento_planta = :id
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['detalles'] = $this->getDetails($id);
            }
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('Error en Ampliacion::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM movimiento_planta WHERE id_movimiento_planta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        try {
            $this->db()->beginTransaction();
            $stmt1 = $this->db()->prepare("UPDATE movimiento_planta SET activo = 0 WHERE id_movimiento_planta = :id");
            $stmt1->execute([':id' => $id]);
            $stmt2 = $this->db()->prepare("UPDATE movimiento_planta_detalle SET activo = 0 WHERE id_movimiento_planta = :id");
            $stmt2->execute([':id' => $id]);
            $this->db()->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en Ampliacion::delete: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $this->db()->beginTransaction();
            $stmt1 = $this->db()->prepare("UPDATE movimiento_planta SET activo = 1 WHERE id_movimiento_planta = :id");
            $stmt1->execute([':id' => $id]);
            $stmt2 = $this->db()->prepare("UPDATE movimiento_planta_detalle SET activo = 1 WHERE id_movimiento_planta = :id");
            $stmt2->execute([':id' => $id]);
            $this->db()->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en Ampliacion::restore: ' . $e->getMessage());
            return false;
        }
    }

    public function getDetails(int $id): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT d.*,
                       l.cantidad_actual AS lote_stock_actual,
                       p.nombre_comun AS planta_nombre,
                       u.nombre_ubicacion AS ubicacion_nombre
                FROM movimiento_planta_detalle d
                LEFT JOIN lote l ON d.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion
                WHERE d.id_movimiento_planta = :id AND d.activo = 1
                ORDER BY d.tipo ASC, d.id_detalle_mov_planta ASC
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en Ampliacion::getDetails: ' . $e->getMessage());
            return [];
        }
    }

    public function getAvailableLots(): array
    {
        try {
            $sql = "SELECT
                        l.id_lote AS id,
                        l.id_lote,
                        l.cantidad_actual,
                        l.estado,
                        COALESCE(NULLIF(p.nombre_comun, ''), p.nombre_tecnico, 'Sin nombre') AS planta_nombre,
                        u.nombre_ubicacion AS ubicacion_nombre
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    WHERE l.activo = 1 AND l.cantidad_actual > 0
                    ORDER BY p.nombre_comun ASC, l.id_lote ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Ampliacion::getAvailableLots: ' . $e->getMessage());
            return [];
        }
    }

    public function getPlants(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_planta AS id, nombre_comun, nombre_tecnico FROM plantas WHERE activo = 1 ORDER BY nombre_comun ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en Ampliacion::getPlants: ' . $e->getMessage());
            return [];
        }
    }

    public function getLocations(): array
    {
        $loc = new Ubicacion();
        return $loc->getAll();
    }

    public function getSpecies(): array
    {
        $sp = new Especie();
        return $sp->getAll();
    }

    private function createPlant(string $nombreComun, ?string $nombreTecnico, int $idEspecie): int
    {
        $stmt = $this->db()->prepare("INSERT INTO plantas (nombre_comun, nombre_tecnico, id_especie, activo) VALUES (:nombre, :tecnico, :especie, 1)");
        $stmt->execute([
            ':nombre'  => $nombreComun,
            ':tecnico' => $nombreTecnico ?: null,
            ':especie' => $idEspecie,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function getLastInsertId(): ?int
    {
        try {
            $id = $this->db()->lastInsertId();
            return $id !== false ? (int)$id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function registerExchange(array $data): int
    {
        $idCliente = (int)($data['id_cliente'] ?? 0);
        $idTrabajador = (int)($data['id_trabajador_gestor'] ?? 0);
        $fecha = trim((string)($data['fecha_movimiento'] ?? date('Y-m-d')));
        $observacion = trim((string)($data['observacion'] ?? ''));
        if ($observacion === '') $observacion = null;

        $salidaItems = $data['salida_items'] ?? [];
        $entradaItems = $data['entrada_items'] ?? [];

        if (empty($salidaItems) && empty($entradaItems)) {
            throw new \Exception('Debe agregar al menos un item de salida o entrada.');
        }

        try {
            $this->db()->beginTransaction();

            $stmt = $this->db()->prepare("
                INSERT INTO movimiento_planta (tipo_movimiento, id_cliente, id_trabajador_gestor, fecha_movimiento, observacion)
                VALUES ('intercambio', :id_cliente, :id_trabajador, :fecha, :observacion)
            ");
            $stmt->execute([
                ':id_cliente' => $idCliente > 0 ? $idCliente : null,
                ':id_trabajador' => $idTrabajador,
                ':fecha' => $fecha,
                ':observacion' => $observacion,
            ]);
            $movimientoId = (int)$this->db()->lastInsertId();

            $stmtDetail = $this->db()->prepare("
                INSERT INTO movimiento_planta_detalle (id_movimiento_planta, id_lote, tipo, cantidad, precio_unitario, sub_total)
                VALUES (:id_mov, :id_lote, :tipo, :cantidad, NULL, NULL)
            ");

            $stmtUpdateLot = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :cantidad2");

            $stmtFindLot = $this->db()->prepare("SELECT id_lote FROM lote WHERE id_planta = :id_planta AND id_ubicacion = :id_ubicacion AND activo = 1 LIMIT 1");

            $stmtCreateLot = $this->db()->prepare("
                INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, estado, origen, observacion)
                VALUES (:id_planta, :id_ubicacion, :fecha, :cantidad_ini, :cantidad_act, 'Activo', 'Intercambio', :observacion)
            ");

            $stmtIncreaseLot = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");

            $itemCount = 0;

            foreach ($salidaItems as $item) {
                $idLote = (int)($item['id_lote'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($idLote <= 0 || $cantidad <= 0) continue;

                $stmtUpdateLot->execute([
                    ':cantidad' => $cantidad,
                    ':id' => $idLote,
                    ':cantidad2' => $cantidad,
                ]);
                if ($stmtUpdateLot->rowCount() === 0) {
                    throw new \Exception("Stock insuficiente en el lote seleccionado para salida.");
                }

                $stmtDetail->execute([
                    ':id_mov' => $movimientoId,
                    ':id_lote' => $idLote,
                    ':tipo' => 'salida',
                    ':cantidad' => $cantidad,
                ]);
                $itemCount++;
            }

            foreach ($entradaItems as $item) {
                $idPlanta = (int)($item['id_planta'] ?? 0);
                $idUbicacion = (int)($item['id_ubicacion'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($idUbicacion <= 0 || $cantidad <= 0) continue;

                if ($idPlanta <= 0 && !empty($item['nueva_planta_nombre'])) {
                    $nuevoNombre = trim((string)$item['nueva_planta_nombre']);
                    $nuevoTecnico = !empty($item['nueva_planta_tecnico']) ? trim((string)$item['nueva_planta_tecnico']) : null;
                    $nuevoIdEspecie = (int)($item['nueva_planta_id_especie'] ?? 0);
                    if ($nuevoIdEspecie <= 0) {
                        throw new \Exception("Debe seleccionar una especie para la nueva planta '{$nuevoNombre}'.");
                    }
                    $idPlanta = $this->createPlant($nuevoNombre, $nuevoTecnico, $nuevoIdEspecie);
                }

                if ($idPlanta <= 0) continue;

                $stmtFindLot->execute([
                    ':id_planta' => $idPlanta,
                    ':id_ubicacion' => $idUbicacion,
                ]);
                $existingLot = $stmtFindLot->fetch(PDO::FETCH_ASSOC);

                if ($existingLot) {
                    $idLote = (int)$existingLot['id_lote'];
                    $stmtIncreaseLot->execute([':cantidad' => $cantidad, ':id' => $idLote]);
                } else {
                    $stmtCreateLot->execute([
                        ':id_planta' => $idPlanta,
                        ':id_ubicacion' => $idUbicacion,
                        ':fecha' => $fecha,
                        ':cantidad_ini' => $cantidad,
                        ':cantidad_act' => $cantidad,
                        ':observacion' => $observacion,
                    ]);
                    $idLote = (int)$this->db()->lastInsertId();
                }

                $stmtDetail->execute([
                    ':id_mov' => $movimientoId,
                    ':id_lote' => $idLote,
                    ':tipo' => 'entrada',
                    ':cantidad' => $cantidad,
                ]);
                $itemCount++;
            }

            if ($itemCount === 0) {
                $this->db()->rollBack();
                throw new \Exception('No se registró ningún item. Verifique los datos.');
            }

            $this->db()->commit();
            return $movimientoId;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en Ampliacion::registerExchange: ' . $e->getMessage());
            throw $e;
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
            error_log('Error al buscar clientes en Ampliacion: ' . $e->getMessage());
            return [];
        }
    }
}
