<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;
use SysInescolara\models\AuditLog;

class Ampliacion extends Database implements ReadableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $tipoMovimiento = 'intercambio';
    private ?int $idCliente = null;
    private ?int $idTrabajadorGestor = null;
    private ?string $fechaMovimiento = null;
    private ?string $observacion = null;
    private int $activo = 1;

    protected array $validationRules = [
        'tipo_movimiento'      => ['type' => null,      'required' => true],
        'id_cliente'           => ['type' => 'cantidad','required' => false],
        'id_trabajador_gestor' => ['type' => 'cantidad','required' => true],
        'fecha_movimiento'     => ['type' => null,      'required' => true],
        'observacion'          => ['type' => null,      'required' => false],
    ];

    protected array $fillable = ['tipo_movimiento', 'id_cliente', 'id_trabajador_gestor', 'fecha_movimiento', 'observacion', 'activo'];
    protected array $guarded = ['id_movimiento_planta'];

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
            'id_movimiento_planta'  => 'id',
            'tipo_movimiento'       => 'tipoMovimiento',
            'id_cliente'            => 'idCliente',
            'id_trabajador_gestor'  => 'idTrabajadorGestor',
            'fecha_movimiento'      => 'fechaMovimiento',
            'observacion'           => 'observacion',
            'activo'                => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getTipoMovimiento(): string { return $this->tipoMovimiento; }
    public function getIdCliente(): ?int { return $this->idCliente; }
    public function getIdTrabajadorGestor(): ?int { return $this->idTrabajadorGestor; }
    public function getFechaMovimiento(): ?string { return $this->fechaMovimiento; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function isActivo(): bool { return $this->activo === 1; }

    // --- Setters ---
    public function setTipoMovimiento(string $tipoMovimiento): self
    {
        $this->tipoMovimiento = $tipoMovimiento;
        return $this;
    }

    public function setIdCliente(?int $idCliente): self
    {
        $this->idCliente = $idCliente;
        return $this;
    }

    public function setIdTrabajadorGestor(?int $idTrabajadorGestor): self
    {
        $this->idTrabajadorGestor = $idTrabajadorGestor;
        return $this;
    }

    public function setFechaMovimiento(?string $fechaMovimiento): self
    {
        $this->fechaMovimiento = $fechaMovimiento;
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
            'tipo_movimiento'      => $this->tipoMovimiento,
            'id_cliente'           => $this->idCliente,
            'id_trabajador_gestor' => $this->idTrabajadorGestor,
            'fecha_movimiento'     => $this->fechaMovimiento,
            'observacion'          => $this->observacion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO movimiento_planta (tipo_movimiento, id_cliente, id_trabajador_gestor, fecha_movimiento, observacion, activo)
                        VALUES (:tipo_movimiento, :id_cliente, :id_trabajador_gestor, :fecha_movimiento, :observacion, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':tipo_movimiento'      => $this->tipoMovimiento,
                    ':id_cliente'           => $this->idCliente,
                    ':id_trabajador_gestor' => $this->idTrabajadorGestor,
                    ':fecha_movimiento'     => $this->fechaMovimiento,
                    ':observacion'          => $this->observacion,
                    ':activo'               => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'movimiento_planta', $this->id, null, [
                        'tipo_movimiento'      => $this->tipoMovimiento,
                        'id_cliente'           => $this->idCliente,
                        'id_trabajador_gestor' => $this->idTrabajadorGestor,
                        'fecha_movimiento'     => $this->fechaMovimiento,
                        'observacion'          => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE movimiento_planta SET tipo_movimiento = :tipo_movimiento,
                        id_cliente = :id_cliente, id_trabajador_gestor = :id_trabajador_gestor,
                        fecha_movimiento = :fecha_movimiento, observacion = :observacion,
                        activo = :activo
                        WHERE id_movimiento_planta = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                   => $this->id,
                    ':tipo_movimiento'      => $this->tipoMovimiento,
                    ':id_cliente'           => $this->idCliente,
                    ':id_trabajador_gestor' => $this->idTrabajadorGestor,
                    ':fecha_movimiento'     => $this->fechaMovimiento,
                    ':observacion'          => $this->observacion,
                    ':activo'               => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'movimiento_planta', $this->id, $oldData, [
                        'tipo_movimiento'      => $this->tipoMovimiento,
                        'id_cliente'           => $this->idCliente,
                        'id_trabajador_gestor' => $this->idTrabajadorGestor,
                        'fecha_movimiento'     => $this->fechaMovimiento,
                        'observacion'          => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar ampliación: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM movimiento_planta WHERE id_movimiento_planta = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $mov = new static($row);
        $mov->id = (int)$row['id_movimiento_planta'];
        return $mov;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM movimiento_planta WHERE activo = 1 ORDER BY fecha_movimiento DESC, id_movimiento_planta DESC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM movimiento_planta WHERE $column $operator :value AND activo = 1";
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
            $this->tipoMovimiento = $found->getTipoMovimiento();
            $this->idCliente = $found->getIdCliente();
            $this->idTrabajadorGestor = $found->getIdTrabajadorGestor();
            $this->fechaMovimiento = $found->getFechaMovimiento();
            $this->observacion = $found->getObservacion();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
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
        $oldData = $this->getById($id);
        try {
            $this->db()->beginTransaction();
            $stmt1 = $this->db()->prepare("UPDATE movimiento_planta SET activo = 0 WHERE id_movimiento_planta = :id");
            $stmt1->execute([':id' => $id]);
            $stmt2 = $this->db()->prepare("UPDATE movimiento_planta_detalle SET activo = 0 WHERE id_movimiento_planta = :id");
            $stmt2->execute([':id' => $id]);
            $this->db()->commit();
            AuditLog::record('DEACTIVATE', 'movimiento_planta', $id, $oldData, null);
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
                       l.id_planta,
                       l.id_ubicacion,
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
                        l.id_estado,
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

            $idEstadoVivoAmpliacion = (int)$this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1")->fetchColumn();
            $idOrigenIntercambio = (int)$this->db()->query("SELECT id_origen FROM origen WHERE nombre = 'Ampliación' LIMIT 1")->fetchColumn();
            $stmtFindLot = $this->db()->prepare("SELECT id_lote FROM lote WHERE id_planta = :id_planta AND id_ubicacion = :id_ubicacion AND activo = 1 LIMIT 1");

            $stmtCreateLot = $this->db()->prepare("
                INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, id_estado, id_origen, observacion)
                VALUES (:id_planta, :id_ubicacion, :fecha, :cantidad_ini, :cantidad_act, :id_estado, :id_origen, :observacion)
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
                        ':id_estado' => $idEstadoVivoAmpliacion,
                        ':id_origen' => $idOrigenIntercambio,
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

            AuditLog::record('CREATE', 'movimiento_planta', $movimientoId, null, [
                'tipo' => 'intercambio',
                'id_cliente' => $idCliente,
                'id_trabajador' => $idTrabajador,
                'salida_count' => count($salidaItems),
                'entrada_count' => count($entradaItems),
            ]);

            return $movimientoId;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en Ampliacion::registerExchange: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $oldMain = $this->getById($id);
        if (!$oldMain) {
            throw new \Exception('No existe la ampliación');
        }
        $oldDetails = $oldMain['detalles'] ?? [];

        $idCliente = (int)($data['id_cliente'] ?? 0);
        $idTrabajador = (int)($data['id_trabajador_gestor'] ?? 0);
        $fecha = trim((string)($data['fecha_movimiento'] ?? date('Y-m-d')));
        $observacion = trim((string)($data['observacion'] ?? ''));
        if ($observacion === '') $observacion = null;

        $salidaItems = $data['salida_items'] ?? [];
        $entradaItems = $data['entrada_items'] ?? [];

        try {
            $this->db()->beginTransaction();

            // 1) Reverse old stock
            foreach ($oldDetails as $det) {
                if ($det['tipo'] === 'salida') {
                    $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cant WHERE id_lote = :id")
                        ->execute([':cant' => (int)$det['cantidad'], ':id' => (int)$det['id_lote']]);
                } elseif ($det['tipo'] === 'entrada') {
                    $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cant) WHERE id_lote = :id AND cantidad_actual >= :cant2");
                    $stmt->execute([':cant' => (int)$det['cantidad'], ':id' => (int)$det['id_lote'], ':cant2' => (int)$det['cantidad']]);
                    if ($stmt->rowCount() === 0) {
                        throw new \Exception("Stock insuficiente para revertir entrada del lote #{$det['id_lote']}.");
                    }
                }
            }

            // 2) Soft-delete old details
            $this->db()->prepare("UPDATE movimiento_planta_detalle SET activo = 0 WHERE id_movimiento_planta = :id")
                ->execute([':id' => $id]);

            // 3) Update main record
            $this->db()->prepare("UPDATE movimiento_planta SET id_cliente = :cli, id_trabajador_gestor = :tra, fecha_movimiento = :fec, observacion = :obs WHERE id_movimiento_planta = :id")
                ->execute([
                    ':cli' => $idCliente > 0 ? $idCliente : null,
                    ':tra' => $idTrabajador,
                    ':fec' => $fecha,
                    ':obs' => $observacion,
                    ':id'  => $id,
                ]);

            // 4) Insert new items (same logic as registerExchange)
            $stmtDetail = $this->db()->prepare("
                INSERT INTO movimiento_planta_detalle (id_movimiento_planta, id_lote, tipo, cantidad, precio_unitario, sub_total)
                VALUES (:id_mov, :id_lote, :tipo, :cantidad, NULL, NULL)
            ");
            $stmtUpdateLot = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :cantidad2");
            $idEstadoVivo = (int)$this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1")->fetchColumn();
            $idOrigenIntercambio = (int)$this->db()->query("SELECT id_origen FROM origen WHERE nombre = 'Ampliación' LIMIT 1")->fetchColumn();
            $stmtFindLot = $this->db()->prepare("SELECT id_lote FROM lote WHERE id_planta = :id_planta AND id_ubicacion = :id_ubicacion AND activo = 1 LIMIT 1");
            $stmtCreateLot = $this->db()->prepare("
                INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, id_estado, id_origen, observacion)
                VALUES (:id_planta, :id_ubicacion, :fecha, :cantidad_ini, :cantidad_act, :id_estado, :id_origen, :observacion)
            ");
            $stmtIncreaseLot = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");

            $itemCount = 0;

            foreach ($salidaItems as $item) {
                $idLote = (int)($item['id_lote'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($idLote <= 0 || $cantidad <= 0) continue;

                $stmtUpdateLot->execute([':cantidad' => $cantidad, ':id' => $idLote, ':cantidad2' => $cantidad]);
                if ($stmtUpdateLot->rowCount() === 0) {
                    throw new \Exception("Stock insuficiente en el lote seleccionado para salida.");
                }
                $stmtDetail->execute([':id_mov' => $id, ':id_lote' => $idLote, ':tipo' => 'salida', ':cantidad' => $cantidad]);
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

                $stmtFindLot->execute([':id_planta' => $idPlanta, ':id_ubicacion' => $idUbicacion]);
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
                        ':id_estado' => $idEstadoVivo,
                        ':id_origen' => $idOrigenIntercambio,
                        ':observacion' => $observacion,
                    ]);
                    $idLote = (int)$this->db()->lastInsertId();
                }

                $stmtDetail->execute([':id_mov' => $id, ':id_lote' => $idLote, ':tipo' => 'entrada', ':cantidad' => $cantidad]);
                $itemCount++;
            }

            if ($itemCount === 0) {
                $this->db()->rollBack();
                throw new \Exception('No se registró ningún item. Verifique los datos.');
            }

            $this->db()->commit();

            AuditLog::record('UPDATE', 'movimiento_planta', $id, [
                'old' => ['id_cliente' => $oldMain['id_cliente'], 'fecha' => $oldMain['fecha_movimiento'], 'detalles_count' => count($oldDetails)],
            ], [
                'new' => ['id_cliente' => $idCliente, 'fecha' => $fecha, 'salida_count' => count($salidaItems), 'entrada_count' => count($entradaItems)],
            ]);

            return true;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error en Ampliacion::update: ' . $e->getMessage());
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
