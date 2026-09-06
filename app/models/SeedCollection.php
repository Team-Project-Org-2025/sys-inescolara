<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use SysInescolara\models\AuditLog;

class SeedCollection extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idUsuario = null;
    private ?int $idUbicacion = null;
    private ?string $fechaAsignacion = null;
    private ?string $fechaRecoleccion = null;
    private string $estatus = 'Pendiente';
    private ?string $observacion = null;
    private int $activo = 1;

    protected array $validationRules = [
        'id_usuario'       => ['type' => 'cantidad','required' => true],
        'id_ubicacion'     => ['type' => 'cantidad','required' => true],
        'fecha_asignacion' => ['type' => null,      'required' => true],
        'observacion'      => ['type' => null,      'required' => false],
    ];

    protected array $fillable = ['id_usuario', 'id_ubicacion', 'fecha_asignacion', 'fecha_recoleccion', 'estatus', 'observacion', 'activo'];
    protected array $guarded = ['id_recoleccion'];

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
            'id_recoleccion'    => 'id',
            'id_usuario'       => 'idUsuario',
            'id_ubicacion'      => 'idUbicacion',
            'fecha_asignacion'  => 'fechaAsignacion',
            'fecha_recoleccion' => 'fechaRecoleccion',
            'estatus'           => 'estatus',
            'observacion'       => 'observacion',
            'activo'            => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function getIdUsuario(): ?int { return $this->idUsuario; }
    public function getIdUbicacion(): ?int { return $this->idUbicacion; }
    public function getFechaAsignacion(): ?string { return $this->fechaAsignacion; }
    public function getFechaRecoleccion(): ?string { return $this->fechaRecoleccion; }
    public function getEstatus(): string { return $this->estatus; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function isActivo(): bool { return $this->activo === 1; }

    // --- Setters ---
    public function setIdUsuario(?int $idUsuario): self
    {
        $this->idUsuario = $idUsuario;
        return $this;
    }

    public function setIdUbicacion(?int $idUbicacion): self
    {
        $this->idUbicacion = $idUbicacion;
        return $this;
    }

    public function setFechaAsignacion(?string $fechaAsignacion): self
    {
        $this->fechaAsignacion = $fechaAsignacion;
        return $this;
    }

    public function setFechaRecoleccion(?string $fechaRecoleccion): self
    {
        $this->fechaRecoleccion = $fechaRecoleccion;
        return $this;
    }

    public function setEstatus(string $estatus): self
    {
        $this->estatus = $estatus;
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
            'id_usuario'       => $this->idUsuario,
            'id_ubicacion'     => $this->idUbicacion,
            'fecha_asignacion' => $this->fechaAsignacion,
            'observacion'      => $this->observacion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO recoleccion_semillas (id_usuario, id_ubicacion, fecha_asignacion, fecha_recoleccion, estatus, observacion, activo)
                        VALUES (:id_usuario, :id_ubicacion, :fecha_asignacion, :fecha_recoleccion, :estatus, :observacion, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id_usuario'        => $this->idUsuario,
                    ':id_ubicacion'      => $this->idUbicacion,
                    ':fecha_asignacion'  => $this->fechaAsignacion,
                    ':fecha_recoleccion' => $this->fechaRecoleccion,
                    ':estatus'           => $this->estatus,
                    ':observacion'       => $this->observacion,
                    ':activo'            => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'recoleccion_semillas', $this->id, null, [
                        'id_usuario'       => $this->idUsuario,
                        'id_ubicacion'     => $this->idUbicacion,
                        'fecha_asignacion' => $this->fechaAsignacion,
                        'estatus'          => $this->estatus,
                        'observacion'      => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE recoleccion_semillas SET id_usuario = :id_usuario, id_ubicacion = :id_ubicacion,
                        fecha_asignacion = :fecha_asignacion, fecha_recoleccion = :fecha_recoleccion,
                        estatus = :estatus, observacion = :observacion, activo = :activo
                        WHERE id_recoleccion = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                => $this->id,
                    ':id_usuario'        => $this->idUsuario,
                    ':id_ubicacion'      => $this->idUbicacion,
                    ':fecha_asignacion'  => $this->fechaAsignacion,
                    ':fecha_recoleccion' => $this->fechaRecoleccion,
                    ':estatus'           => $this->estatus,
                    ':observacion'       => $this->observacion,
                    ':activo'            => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'recoleccion_semillas', $this->id, $oldData, [
                        'id_usuario'       => $this->idUsuario,
                        'id_ubicacion'     => $this->idUbicacion,
                        'fecha_asignacion' => $this->fechaAsignacion,
                        'estatus'          => $this->estatus,
                        'observacion'      => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar recolección: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM recoleccion_semillas WHERE id_recoleccion = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $rec = new static($row);
        $rec->id = (int)$row['id_recoleccion'];
        return $rec;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM recoleccion_semillas WHERE activo = 1 ORDER BY fecha_asignacion DESC, id_recoleccion DESC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM recoleccion_semillas WHERE $column $operator :value AND activo = 1";
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
            $this->idUsuario = $found->getIdUsuario();
            $this->idUbicacion = $found->getIdUbicacion();
            $this->fechaAsignacion = $found->getFechaAsignacion();
            $this->fechaRecoleccion = $found->getFechaRecoleccion();
            $this->estatus = $found->getEstatus();
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
                        r.id_recoleccion AS id,
                        r.id_usuario,
                        r.id_ubicacion,
                        r.fecha_asignacion,
                        r.fecha_recoleccion,
                        r.estatus,
                        r.observacion,
                        (SELECT COUNT(*) FROM recoleccion_semillas_detalle d WHERE d.id_recoleccion = r.id_recoleccion) AS total_detalles,
                        CONCAT(u.nombre, ' ', u.apellido) AS usuario_nombre,
                        ub.nombre_ubicacion
                    FROM recoleccion_semillas r
                    LEFT JOIN security.usuarios u ON r.id_usuario = u.id_usuario
                    LEFT JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
                    WHERE r.activo = 1
                    ORDER BY r.fecha_asignacion DESC, r.id_recoleccion DESC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT r.*,
                       CONCAT(u.nombre, ' ', u.apellido) AS usuario_nombre,
                       ub.nombre_ubicacion
                FROM recoleccion_semillas r
                LEFT JOIN security.usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
                WHERE r.id_recoleccion = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM recoleccion_semillas WHERE id_recoleccion = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE recoleccion_semillas SET activo = 0 WHERE id_recoleccion = :id");
        $result = $stmt->execute([':id' => $id]);
        AuditLog::record('DEACTIVATE', 'recoleccion_semillas', $id, $oldData, null);
        return $result;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE recoleccion_semillas SET activo = 1 WHERE id_recoleccion = :id");
        return $stmt->execute([':id' => $id]);
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

    public function add(int $idUsuario, int $idUbicacion, string $fechaAsignacion, ?string $observacion = null): bool
    {
        $this->fill([
            'id_usuario'       => $idUsuario,
            'id_ubicacion'     => $idUbicacion,
            'fecha_asignacion' => $fechaAsignacion,
            'estatus'          => 'Pendiente',
            'observacion'      => $observacion,
        ]);
        return $this->save();
    }

    public function update(int $id, int $idUsuario, int $idUbicacion, string $fechaAsignacion, ?string $observacion = null): bool
    {
        if (!$this->loadById($id)) {
            throw new \Exception('No existe la recolección solicitada para modificar.');
        }
        $this->fill([
            'id_usuario'       => $idUsuario,
            'id_ubicacion'     => $idUbicacion,
            'fecha_asignacion' => $fechaAsignacion,
            'observacion'      => $observacion,
        ]);
        return $this->save();
    }

    public function complete(int $id, string $fechaRecoleccion): bool
    {
        if (!$this->exists($id)) {
            throw new \Exception('No existe la recolección solicitada.');
        }
        $stmt = $this->db()->prepare("
            UPDATE recoleccion_semillas
            SET estatus = 'Realizada',
                fecha_recoleccion = :fecha_recoleccion
            WHERE id_recoleccion = :id AND estatus = 'Pendiente'
        ");
        $oldData = $this->getById($id);
        $result = $stmt->execute([
            ':id' => $id,
            ':fecha_recoleccion' => $fechaRecoleccion,
        ]);
        AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $oldData, [
            'estatus' => 'Realizada', 'fecha_recoleccion' => $fechaRecoleccion,
        ]);
        return $result;
    }

    public function addDetail(int $idRecoleccion, ?string $plantaOrigen, string $nombreSemilla, int $idUnidadMedida, float $cantidad, ?int $idInsumo = null): bool
    {
        $stmt = $this->db()->prepare("
            INSERT INTO recoleccion_semillas_detalle
                (id_recoleccion, planta_origen, nombre_semilla, id_unidad_medida, cantidad, id_insumo)
            VALUES
                (:id_recoleccion, :planta_origen, :nombre_semilla, :id_unidad_medida, :cantidad, :id_insumo)
        ");
        return $stmt->execute([
            ':id_recoleccion'   => $idRecoleccion,
            ':planta_origen'    => $plantaOrigen,
            ':nombre_semilla'   => $nombreSemilla,
            ':id_unidad_medida' => $idUnidadMedida,
            ':cantidad'         => $cantidad,
            ':id_insumo'        => $idInsumo,
        ]);
    }

    public function getDetails(int $idRecoleccion): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT d.*,
                       u.nombre_unidad_medida, u.simbolo,
                       i.nombre_insumo AS insumo_nombre
                FROM recoleccion_semillas_detalle d
                LEFT JOIN unidad_medida u ON d.id_unidad_medida = u.id_unidad_medida
                LEFT JOIN insumo i ON d.id_insumo = i.id_insumo
                WHERE d.id_recoleccion = :id
                ORDER BY d.id_recoleccion_detalle ASC
            ");
            $stmt->execute([':id' => $idRecoleccion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Error en SeedCollection::getDetails: ' . $e->getMessage());
            return [];
        }
    }

    public function getDetailsCount(int $idRecoleccion): int
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM recoleccion_semillas_detalle WHERE id_recoleccion = :id");
            $stmt->execute([':id' => $idRecoleccion]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function registerSeedsWithTransaction(int $idRecoleccion, array $items): int
    {
        $suppliesModel = new Insumo();
        $createdCount = 0;

        try {
            $this->db()->beginTransaction();

            foreach ($items as $item) {
                $nombreSemilla = trim((string)($item['nombre_semilla'] ?? ''));
                if ($nombreSemilla === '') continue;
                $idUnidadMedida = (int)($item['id_unidad_medida'] ?? 0);
                if ($idUnidadMedida <= 0) continue;
                $cantidad = floatval($item['cantidad'] ?? 0);
                if ($cantidad <= 0) continue;
                $plantaOrigen = trim((string)($item['planta_origen'] ?? ''));
                if ($plantaOrigen === '') $plantaOrigen = null;

                $existing = $suppliesModel->findByNameAndCategory($nombreSemilla, 'Semillas');

                if ($existing) {
                    $supplyId = (int)$existing['id_insumo'];
                    $ok = $suppliesModel->increaseStock($supplyId, $cantidad);
                } else {
                    $nuevoInsumo = new Insumo([
                        'nombre_insumo'          => $nombreSemilla,
                        'id_unidad_medida'       => $idUnidadMedida,
                        'categoria'              => 'Semillas',
                        'stock_actual'           => $cantidad,
                        'costo_unitario_actual'  => 0,
                    ]);
                    $ok = $nuevoInsumo->save();
                    if (!$ok) continue;
                    $supplyId = $nuevoInsumo->getId();
                }

                if (!$ok) continue;

                $ok = $this->addDetail($idRecoleccion, $plantaOrigen, $nombreSemilla, $idUnidadMedida, $cantidad, $supplyId);
                if (!$ok) continue;

                $createdCount++;
            }

            if ($createdCount === 0) {
                $this->db()->rollBack();
                return 0;
            }

            $this->db()->commit();
            $oldData = $this->getById($idRecoleccion);
            AuditLog::record('UPDATE', 'recoleccion_semillas', $idRecoleccion, $oldData, [
                'insumos_registrados' => $createdCount,
            ]);
            return $createdCount;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            error_log('Error en SeedCollection::registerSeedsWithTransaction: ' . $e->getMessage());
            throw $e;
        }
    }
}
