<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;
use SysInescolara\models\AuditLog;

class Merma extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idTrazabilidad = null;
    private ?int $idLote = null;
    private int $cantidad = 0;
    private ?string $motivo = null;
    private ?string $descripcion = null;
    private ?string $fechaMerma = null;
    private float $impactoEconomico = 0.0;
    private ?int $idUsuarioRegistra = null;
    private int $activo = 1;

    protected array $validationRules = [
        'id_trazabilidad' => ['type' => null,       'required' => true],
        'cantidad'        => ['type' => 'cantidad', 'required' => true],
        'motivo'          => ['type' => null,       'required' => true],
        'fecha_merma'     => ['type' => null,       'required' => true],
        'descripcion'     => ['type' => null,       'required' => false],
    ];

    protected array $fillable = ['id_trazabilidad', 'id_lote', 'cantidad', 'motivo', 'descripcion', 'fecha_merma', 'impacto_economico', 'id_usuario_registra', 'activo'];
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
            'id_merma'            => 'id',
            'id_trazabilidad'     => 'idTrazabilidad',
            'id_lote'             => 'idLote',
            'cantidad'            => 'cantidad',
            'motivo'              => 'motivo',
            'descripcion'         => 'descripcion',
            'fecha_merma'         => 'fechaMerma',
            'impacto_economico'   => 'impactoEconomico',
            'id_usuario_registra' => 'idUsuarioRegistra',
            'activo'              => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    public function getId(): ?int { return $this->id; }
    public function getIdTrazabilidad(): ?int { return $this->idTrazabilidad; }
    public function getIdLote(): ?int { return $this->idLote; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getMotivo(): ?string { return $this->motivo; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getFechaMerma(): ?string { return $this->fechaMerma; }
    public function getImpactoEconomico(): float { return $this->impactoEconomico; }
    public function getIdUsuarioRegistra(): ?int { return $this->idUsuarioRegistra; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setIdTrazabilidad(int $idTrazabilidad): self { $this->idTrazabilidad = $idTrazabilidad; return $this; }
    public function setIdLote(?int $idLote): self { $this->idLote = $idLote; return $this; }
    public function setCantidad(int $cantidad): self { $this->cantidad = max(0, $cantidad); return $this; }
    public function setMotivo(string $motivo): self { $this->motivo = $motivo; return $this; }
    public function setDescripcion(?string $descripcion): self { $this->descripcion = $descripcion; return $this; }
    public function setFechaMerma(string $fechaMerma): self { $this->fechaMerma = $fechaMerma; return $this; }
    public function setImpactoEconomico(float $impactoEconomico): self { $this->impactoEconomico = $impactoEconomico; return $this; }
    public function setIdUsuarioRegistra(int $idUsuarioRegistra): self { $this->idUsuarioRegistra = $idUsuarioRegistra; return $this; }
    public function setActivo(bool $activo): self { $this->activo = $activo ? 1 : 0; return $this; }

    private function validate(): void
    {
        $this->validateData([
            'id_trazabilidad' => $this->idTrazabilidad,
            'cantidad'        => $this->cantidad,
            'motivo'          => $this->motivo,
            'fecha_merma'     => $this->fechaMerma,
            'descripcion'     => $this->descripcion,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $stmt = $this->db()->prepare("
                    INSERT INTO mermas_historico (id_trazabilidad, id_lote, cantidad, motivo, descripcion, fecha_merma, impacto_economico, id_usuario_registra)
                    VALUES (:id_trazabilidad, :id_lote, :cantidad, :motivo, :descripcion, :fecha_merma, :impacto_economico, :id_usuario_registra)
                ");
                $success = $stmt->execute([
                    ':id_trazabilidad'     => $this->idTrazabilidad,
                    ':id_lote'             => $this->idLote,
                    ':cantidad'            => $this->cantidad,
                    ':motivo'              => $this->motivo,
                    ':descripcion'         => $this->descripcion,
                    ':fecha_merma'         => $this->fechaMerma,
                    ':impacto_economico'   => $this->impactoEconomico,
                    ':id_usuario_registra' => $this->idUsuarioRegistra,
                ]);
                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'mermas_historico', $this->id, null, [
                        'id_trazabilidad' => $this->idTrazabilidad,
                        'id_lote'         => $this->idLote,
                        'cantidad'        => $this->cantidad,
                        'motivo'          => $this->motivo,
                        'descripcion'     => $this->descripcion,
                        'fecha_merma'     => $this->fechaMerma,
                        'impacto_economico' => $this->impactoEconomico,
                    ]);
                }
                return $success;
            }
            return false;
        } catch (Throwable $e) {
            error_log('Error al guardar merma: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM mermas_historico WHERE id_merma = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $merma = new static($row);
        $merma->id = (int)$row['id_merma'];
        return $merma;
    }

    public static function all(): array
    {
        $instance = new static();
        try {
            $sql = "SELECT
                        m.id_merma AS id,
                        m.id_merma,
                        m.id_trazabilidad,
                        m.id_lote,
                        m.cantidad,
                        m.motivo,
                        m.descripcion,
                        m.fecha_merma,
                        m.impacto_economico,
                        m.id_usuario_registra,
                        m.activo,
                        m.created_at,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        e.nombre AS estado_salud,
                        t.fecha_registro AS fecha_cuarentena,
                        NULL AS usuario_registra
                    FROM mermas_historico m
                    LEFT JOIN trazabilidad t ON m.id_trazabilidad = t.id_trazabilidad
                    LEFT JOIN lote l ON m.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN estado e ON t.id_estado = e.id_estado
                    WHERE m.activo = 1
                    ORDER BY m.fecha_merma DESC, m.id_merma DESC";
            $stmt = $instance->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en Merma::all: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT m.*,
                       COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                       e.nombre AS estado_salud,
                       NULL AS usuario_registra
                FROM mermas_historico m
                LEFT JOIN trazabilidad t ON m.id_trazabilidad = t.id_trazabilidad
                LEFT JOIN lote l ON m.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                LEFT JOIN estado e ON t.id_estado = e.id_estado
                WHERE m.id_merma = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error en Merma::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM mermas_historico WHERE id_merma = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE mermas_historico SET activo = 0 WHERE id_merma = :id");
        $success = $stmt->execute([':id' => $id]);
        if ($success) {
            AuditLog::record('DEACTIVATE', 'mermas_historico', $id, $oldData, null);
        }
        return $success;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE mermas_historico SET activo = 1 WHERE id_merma = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idTrazabilidad = $found->getIdTrazabilidad();
            $this->idLote = $found->getIdLote();
            $this->cantidad = $found->getCantidad();
            $this->motivo = $found->getMotivo();
            $this->descripcion = $found->getDescripcion();
            $this->fechaMerma = $found->getFechaMerma();
            $this->impactoEconomico = $found->getImpactoEconomico();
            $this->idUsuarioRegistra = $found->getIdUsuarioRegistra();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->db()->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAvailableQuarantine(): array
    {
        try {
            $sql = "SELECT
                        t.id_trazabilidad AS id,
                        t.id_lote,
                        t.cantidad,
                        e.nombre AS estado_salud,
                        t.fecha_registro,
                        COALESCE(p.nombre_comun, CONCAT('Planta #', CAST(l.id_planta AS CHAR))) AS planta_nombre,
                        l.cantidad_actual AS lote_stock,
                        u.nombre_ubicacion,
                        c.precio_final_sugerido AS precio_unitario
                    FROM trazabilidad t
                    LEFT JOIN lote l ON t.id_lote = l.id_lote AND l.activo = 1
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote
                    LEFT JOIN estado e ON t.id_estado = e.id_estado
                    WHERE t.activo = 1 AND t.cantidad > 0
                      AND t.id_estado IN (6, 7)
                    ORDER BY t.fecha_registro DESC, p.nombre_comun ASC";
            $stmt = $this->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en Merma::getAvailableQuarantine: ' . $e->getMessage());
            return [];
        }
    }

    public function registerLoss(int $idTrazabilidad, int $cantidad, string $motivo, ?string $descripcion, string $fecha, int $idUsuario): int
    {
        $quarantine = $this->getQuarantineInfo($idTrazabilidad);
        if (!$quarantine) {
            throw new \Exception('El registro de cuarentena seleccionado no existe.');
        }
        if ($cantidad > (int)$quarantine['cantidad']) {
            throw new \Exception("Esta cuarentena solo tiene {$quarantine['cantidad']} ejemplares disponibles.");
        }

        $precioUnitario = (float)($quarantine['precio_unitario'] ?? 0);

        $this->setIdTrazabilidad($idTrazabilidad)
             ->setIdLote((int)$quarantine['id_lote'])
             ->setCantidad($cantidad)
             ->setMotivo($motivo)
             ->setDescripcion($descripcion)
             ->setFechaMerma($fecha)
             ->setImpactoEconomico($cantidad * $precioUnitario)
             ->setIdUsuarioRegistra($idUsuario);

        try {
            $this->beginTransaction();

            if (!$this->save()) {
                throw new \Exception('Error al insertar registro de merma.');
            }

            $this->deductQuarantineStock($idTrazabilidad, $cantidad);

            $this->commit();
            return $this->id;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function getQuarantineInfo(int $idTrazabilidad): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT t.id_trazabilidad, t.id_lote, t.cantidad,
                   c.precio_final_sugerido AS precio_unitario
            FROM trazabilidad t
            LEFT JOIN lote l ON t.id_lote = l.id_lote
            LEFT JOIN calculo_precio c ON l.id_lote = c.id_lote
            WHERE t.id_trazabilidad = :id AND t.activo = 1
        ");
        $stmt->execute([':id' => $idTrazabilidad]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function deductQuarantineStock(int $idTrazabilidad, int $cantidad): void
    {
        $stmt = $this->db()->prepare("UPDATE trazabilidad SET cantidad = GREATEST(0, cantidad - :cantidad) WHERE id_trazabilidad = :id AND cantidad >= :check");
        $stmt->execute([':cantidad' => $cantidad, ':id' => $idTrazabilidad, ':check' => $cantidad]);

        $stmt = $this->db()->prepare("UPDATE trazabilidad SET id_estado = 7 WHERE id_trazabilidad = :id AND cantidad = 0");
        $stmt->execute([':id' => $idTrazabilidad]);
    }
}
