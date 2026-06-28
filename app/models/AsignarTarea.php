<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;

class AsignarTarea extends Database implements ReadableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idTrabajador = null;
    private ?int $idTarea = null;
    private ?int $idLote = null;
    private ?string $fechaAsignacion = null;
    private ?string $fechaCumplimiento = null;
    private string $estatusTarea = 'pendiente';
    private ?float $horasDedicadas = null;

    protected array $validationRules = [
        'id_trabajador'    => ['type' => null, 'required' => true],
        'id_tarea'         => ['type' => null, 'required' => true],
        'id_lote'          => ['type' => null, 'required' => true],
        'fecha_asignacion' => ['type' => null, 'required' => true],
        'estatus_tarea'    => ['type' => null, 'required' => false],
    ];

    protected array $fillable = ['id_trabajador', 'id_tarea', 'id_lote', 'fecha_asignacion', 'fecha_cumplimiento', 'estatus_tarea', 'horas_dedicadas'];
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
            'id_asignacion'      => 'id',
            'id_trabajador'      => 'idTrabajador',
            'id_tarea'           => 'idTarea',
            'id_lote'            => 'idLote',
            'fecha_asignacion'   => 'fechaAsignacion',
            'fecha_cumplimiento' => 'fechaCumplimiento',
            'estatus_tarea'      => 'estatusTarea',
            'horas_dedicadas'    => 'horasDedicadas',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getIdTrabajador(): ?int { return $this->idTrabajador; }
    public function setIdTrabajador(?int $id): self { $this->idTrabajador = $id; return $this; }
    public function getIdTarea(): ?int { return $this->idTarea; }
    public function setIdTarea(?int $id): self { $this->idTarea = $id; return $this; }
    public function getIdLote(): ?int { return $this->idLote; }
    public function setIdLote(?int $id): self { $this->idLote = $id; return $this; }
    public function getFechaAsignacion(): ?string { return $this->fechaAsignacion; }
    public function setFechaAsignacion(?string $fecha): self { $this->fechaAsignacion = $fecha; return $this; }
    public function getFechaCumplimiento(): ?string { return $this->fechaCumplimiento; }
    public function setFechaCumplimiento(?string $fecha): self { $this->fechaCumplimiento = $fecha; return $this; }
    public function getEstatusTarea(): string { return $this->estatusTarea; }
    public function setEstatusTarea(string $estatus): self { $this->estatusTarea = $estatus; return $this; }
    public function getHorasDedicadas(): ?float { return $this->horasDedicadas; }
    public function setHorasDedicadas(?float $horas): self { $this->horasDedicadas = $horas; return $this; }

    private function validate(): void
    {
        $this->validateData([
            'id_trabajador'    => $this->idTrabajador,
            'id_tarea'         => $this->idTarea,
            'id_lote'          => $this->idLote,
            'fecha_asignacion' => $this->fechaAsignacion,
            'estatus_tarea'    => $this->estatusTarea,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        if ($this->id === null) {
            $sql = "INSERT INTO asignar_tarea (id_trabajador, id_tarea, id_lote, fecha_asignacion, fecha_cumplimiento, estatus_tarea, horas_dedicadas)
                    VALUES (:id_trabajador, :id_tarea, :id_lote, :fecha_asignacion, :fecha_cumplimiento, :estatus_tarea, :horas_dedicadas)";
            $stmt = $this->db()->prepare($sql);
            $success = $stmt->execute([
                ':id_trabajador'    => $this->idTrabajador,
                ':id_tarea'         => $this->idTarea,
                ':id_lote'          => $this->idLote,
                ':fecha_asignacion' => $this->fechaAsignacion,
                ':fecha_cumplimiento' => $this->fechaCumplimiento,
                ':estatus_tarea'    => $this->estatusTarea,
                ':horas_dedicadas'  => $this->horasDedicadas,
            ]);
            if ($success) {
                $this->id = (int) $this->db()->lastInsertId();
                AuditLog::record('CREATE', 'asignar_tarea', $this->id, null, [
                    'id_trabajador'    => $this->idTrabajador,
                    'id_tarea'         => $this->idTarea,
                    'id_lote'          => $this->idLote,
                    'fecha_asignacion' => $this->fechaAsignacion,
                    'estatus_tarea'    => $this->estatusTarea,
                ]);
            }
            return $success;
        } else {
            $oldData = $this->getById($this->id);
            $sql = "UPDATE asignar_tarea SET id_trabajador = :id_trabajador, id_tarea = :id_tarea, id_lote = :id_lote,
                    fecha_asignacion = :fecha_asignacion, fecha_cumplimiento = :fecha_cumplimiento,
                    estatus_tarea = :estatus_tarea, horas_dedicadas = :horas_dedicadas
                    WHERE id_asignacion = :id";
            $stmt = $this->db()->prepare($sql);
            $success = $stmt->execute([
                ':id'                => $this->id,
                ':id_trabajador'     => $this->idTrabajador,
                ':id_tarea'          => $this->idTarea,
                ':id_lote'           => $this->idLote,
                ':fecha_asignacion'  => $this->fechaAsignacion,
                ':fecha_cumplimiento'=> $this->fechaCumplimiento,
                ':estatus_tarea'     => $this->estatusTarea,
                ':horas_dedicadas'   => $this->horasDedicadas,
            ]);
            if ($success) {
                AuditLog::record('UPDATE', 'asignar_tarea', $this->id, $oldData, [
                    'id_trabajador'    => $this->idTrabajador,
                    'id_tarea'         => $this->idTarea,
                    'id_lote'          => $this->idLote,
                    'fecha_asignacion' => $this->fechaAsignacion,
                    'estatus_tarea'    => $this->estatusTarea,
                ]);
            }
            return $success;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM asignar_tarea WHERE id_asignacion = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $assignment = new static($row);
        $assignment->id = (int)$row['id_asignacion'];
        return $assignment;
    }

    public static function all(): array
    {
        $instance = new static();
        $stmt = $instance->db()->query("SELECT * FROM asignar_tarea ORDER BY fecha_asignacion DESC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map(fn($row) => new static($row), $rows);
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM asignar_tarea WHERE $column $operator :value";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    // --- Métodos de la interfaz ---

    public function getById(int $id): ?array
    {
        $sql = "SELECT a.*, t.nombre_tarea, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.id_lote AS codigo_lote
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                WHERE a.id_asignacion = :id";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        $sql = "SELECT a.*, t.nombre_tarea, tr.nombre_trabajador, tr.apellido_trabajador,
                       l.id_lote AS codigo_lote
                FROM asignar_tarea a
                LEFT JOIN tareas t ON a.id_tarea = t.id_tarea
                LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador
                LEFT JOIN lote l ON a.id_lote = l.id_lote
                ORDER BY a.fecha_asignacion DESC";
        $stmt = $this->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM asignar_tarea WHERE id_asignacion = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function complete(int $id, ?string $fechaCumplimiento = null, ?float $horasDedicadas = null): bool
    {
        $sql = "UPDATE asignar_tarea SET estatus_tarea = 'completada', fecha_cumplimiento = :fecha, horas_dedicadas = :horas WHERE id_asignacion = :id";
        $stmt = $this->db()->prepare($sql);
        $success = $stmt->execute([
            ':id'    => $id,
            ':fecha' => $fechaCumplimiento,
            ':horas' => $horasDedicadas,
        ]);
        if ($success) {
            AuditLog::record('UPDATE', 'asignar_tarea', $id, null, [
                'estatus_tarea'      => 'completada',
                'fecha_cumplimiento' => $fechaCumplimiento,
                'horas_dedicadas'    => $horasDedicadas,
            ]);
        }
        return $success;
    }

    public function cancel(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE asignar_tarea SET estatus_tarea = 'cancelada' WHERE id_asignacion = :id");
        $success = $stmt->execute([':id' => $id]);
        if ($success) {
            AuditLog::record('UPDATE', 'asignar_tarea', $id, null, ['estatus_tarea' => 'cancelada']);
        }
        return $success;
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->db()->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
