<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Herramienta extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreHerramienta = '';
    private int $cantidad = 1;
    private string $estado = 'disponible';
    private ?string $fechaUltimoMantenimiento = null;
    private ?string $observacion = null;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_herramienta'         => ['type' => 'nombreProducto', 'required' => true],
        'cantidad'                   => ['type' => 'cantidad',      'required' => true],
        'estado'                     => ['type' => null,            'required' => false],
        'fecha_ultimo_mantenimiento' => ['type' => null,            'required' => false],
        'observacion'                => ['type' => null,            'required' => false],
    ];

    protected array $fillable = ['nombre_herramienta', 'cantidad', 'estado', 'fecha_ultimo_mantenimiento', 'observacion', 'activo'];
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
            'id_herramienta'             => 'id',
            'nombre_herramienta'         => 'nombreHerramienta',
            'cantidad'                   => 'cantidad',
            'estado'                     => 'estado',
            'fecha_ultimo_mantenimiento' => 'fechaUltimoMantenimiento',
            'observacion'                => 'observacion',
            'activo'                     => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreHerramienta(): string { return $this->nombreHerramienta; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getEstado(): string { return $this->estado; }
    public function getFechaUltimoMantenimiento(): ?string { return $this->fechaUltimoMantenimiento; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreHerramienta(string $nombreHerramienta): self
    {
        $this->nombreHerramienta = trim($nombreHerramienta);
        return $this;
    }

    public function setCantidad(int $cantidad): self
    {
        $this->cantidad = max(1, $cantidad);
        return $this;
    }

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function setFechaUltimoMantenimiento(?string $fechaUltimoMantenimiento): self
    {
        $this->fechaUltimoMantenimiento = $fechaUltimoMantenimiento;
        return $this;
    }

    public function setObservacion(?string $observacion): self
    {
        $this->observacion = $observacion ? trim($observacion) : null;
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
            'nombre_herramienta'         => $this->nombreHerramienta,
            'cantidad'                   => $this->cantidad,
            'estado'                     => $this->estado,
            'fecha_ultimo_mantenimiento' => $this->fechaUltimoMantenimiento,
            'observacion'                => $this->observacion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO herramienta (nombre_herramienta, cantidad, estado, fecha_ultimo_mantenimiento, observacion, activo) 
                        VALUES (:nombre_herramienta, :cantidad, :estado, :fecha_ultimo_mantenimiento, :observacion, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_herramienta'         => $this->nombreHerramienta,
                    ':cantidad'                   => $this->cantidad,
                    ':estado'                     => $this->estado,
                    ':fecha_ultimo_mantenimiento' => $this->fechaUltimoMantenimiento,
                    ':observacion'                => $this->observacion,
                    ':activo'                     => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'herramienta', $this->id, null, [
                        'nombre_herramienta'         => $this->nombreHerramienta,
                        'cantidad'                   => $this->cantidad,
                        'estado'                     => $this->estado,
                        'fecha_ultimo_mantenimiento' => $this->fechaUltimoMantenimiento,
                        'observacion'                => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE herramienta SET nombre_herramienta = :nombre_herramienta, 
                        cantidad = :cantidad, estado = :estado, 
                        fecha_ultimo_mantenimiento = :fecha_ultimo_mantenimiento, 
                        observacion = :observacion, activo = :activo
                        WHERE id_herramienta = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                         => $this->id,
                    ':nombre_herramienta'         => $this->nombreHerramienta,
                    ':cantidad'                   => $this->cantidad,
                    ':estado'                     => $this->estado,
                    ':fecha_ultimo_mantenimiento' => $this->fechaUltimoMantenimiento,
                    ':observacion'                => $this->observacion,
                    ':activo'                     => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'herramienta', $this->id, $oldData, [
                        'nombre_herramienta'         => $this->nombreHerramienta,
                        'cantidad'                   => $this->cantidad,
                        'estado'                     => $this->estado,
                        'fecha_ultimo_mantenimiento' => $this->fechaUltimoMantenimiento,
                        'observacion'                => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar herramienta: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM herramienta WHERE id_herramienta = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $tool = new static($row);
        $tool->id = (int)$row['id_herramienta'];
        return $tool;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT id_herramienta AS id, nombre_herramienta, cantidad, estado,
                       fecha_ultimo_mantenimiento, observacion, activo
                FROM herramienta
                WHERE activo = 1
                ORDER BY nombre_herramienta ASC";
        $stmt = $instance->db()->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as &$row) {
            $row['cantidad'] = (int)$row['cantidad'];
        }
        return $rows;
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM herramienta WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT id_herramienta AS id, nombre_herramienta, cantidad, estado,
                       fecha_ultimo_mantenimiento, observacion
                FROM herramienta
                WHERE id_herramienta = :id
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['cantidad'] = (int)$row['cantidad'];
            }
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Error en Herramienta::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function exists(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM herramienta WHERE id_herramienta = :id");
            $stmt->execute([':id' => $id]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->getById($id);
            $stmt = $this->db()->prepare("UPDATE herramienta SET activo = 0 WHERE id_herramienta = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'herramienta', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error al desactivar herramienta: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE herramienta SET activo = 1 WHERE id_herramienta = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar herramienta: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->db()->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->nombreHerramienta = $found->getNombreHerramienta();
            $this->cantidad = $found->getCantidad();
            $this->estado = $found->getEstado();
            $this->fechaUltimoMantenimiento = $found->getFechaUltimoMantenimiento();
            $this->observacion = $found->getObservacion();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    // --- Métodos específicos de negocio ---

    public function recordUsageWithStateUpdate(array $usageData): int
    {
        $this->db()->beginTransaction();
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO uso_herramienta (id_asignacion, id_herramienta, cantidad_usada, fecha_uso, observacion, estado_herramienta_post_uso)
                VALUES (:id_asignacion, :id_herramienta, :cantidad_usada, :fecha_uso, :observacion, :estado_herramienta_post_uso)
            ");
            $stmt->execute([
                ':id_asignacion'               => $usageData['id_asignacion'],
                ':id_herramienta'              => $usageData['id_herramienta'],
                ':cantidad_usada'              => $usageData['cantidad'] ?? 1,
                ':fecha_uso'                   => $usageData['fecha_uso'],
                ':observacion'                 => $usageData['observacion'] ?? null,
                ':estado_herramienta_post_uso' => $usageData['estado_herramienta_post_uso'] ?? 'disponible',
            ]);
            $usoId = (int)$this->db()->lastInsertId();

            $stmt = $this->db()->prepare("UPDATE herramienta SET estado = :estado WHERE id_herramienta = :id");
            $stmt->execute([
                ':estado' => $usageData['estado_herramienta_post_uso'] ?? 'disponible',
                ':id'     => $usageData['id_herramienta'],
            ]);

            $this->db()->commit();
            AuditLog::record('CREATE', 'uso_herramienta', $usoId, null, $usageData);
            return $usoId;
        } catch (Throwable $e) {
            $this->db()->rollBack();
            error_log('Error en Herramienta::recordUsageWithStateUpdate: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAllWithAvailability(): array
    {
        try {
            $sql = "
                SELECT
                    h.id_herramienta AS id,
                    h.nombre_herramienta,
                    h.cantidad,
                    h.estado,
                    h.fecha_ultimo_mantenimiento,
                    h.observacion,
                    h.activo,
                    COALESCE((
                        SELECT SUM(COALESCE(uh.cantidad_usada, 1))
                        FROM uso_herramienta uh
                        JOIN asignar_tarea a ON uh.id_asignacion = a.id_asignacion
                        WHERE uh.id_herramienta = h.id_herramienta
                        AND a.estatus_tarea = 'pendiente'
                    ), 0) AS en_uso,
                    (h.cantidad - COALESCE((
                        SELECT SUM(COALESCE(uh.cantidad_usada, 1))
                        FROM uso_herramienta uh
                        JOIN asignar_tarea a ON uh.id_asignacion = a.id_asignacion
                        WHERE uh.id_herramienta = h.id_herramienta
                        AND a.estatus_tarea = 'pendiente'
                    ), 0)) AS disponibles
                FROM herramienta h
                WHERE h.activo = 1
                ORDER BY h.nombre_herramienta ASC
            ";
            $stmt = $this->db()->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as &$row) {
                $row['cantidad'] = (int)$row['cantidad'];
                $row['en_uso'] = (int)$row['en_uso'];
                $row['disponibles'] = (int)$row['disponibles'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('Error en Herramienta::getAllWithAvailability: ' . $e->getMessage());
            return [];
        }
    }

    public function getUsages(int $herramientaId): array
    {
        try {
            $sql = "SELECT u.*, a.id_tarea, t.nombre_tarea
                    FROM uso_herramienta u
                    LEFT JOIN asignar_tarea a ON u.id_asignacion = a.id_asignacion
                    LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                    WHERE u.id_herramienta = :id_herramienta
                    ORDER BY u.fecha_uso DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute([':id_herramienta' => $herramientaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error en Herramienta::getUsages: ' . $e->getMessage());
            return [];
        }
    }
}
