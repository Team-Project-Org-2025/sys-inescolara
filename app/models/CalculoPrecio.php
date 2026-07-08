<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class CalculoPrecio extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idLote = null;
    private float $precioPlantaBase = 0.0;
    private float $costoTotalInsumo = 0.0;
    private float $porcentajeGanancia = 0.0;
    private float $precioFinalSugerido = 0.0;
    private ?string $fechaCalculo = null;
    private int $vigente = 0;

    protected array $validationRules = [
        'id_lote'              => ['type' => null,    'required' => true],
        'precio_planta_base'   => ['type' => 'precio','required' => true],
        'porcentaje_ganancia'  => ['type' => 'precio','required' => true],
        'precio_final_sugerido'=> ['type' => 'precio','required' => true],
        'fecha_calculo'        => ['type' => null,    'required' => true],
    ];

    protected array $fillable = ['id_lote', 'precio_planta_base', 'costo_total_insumo', 'porcentaje_ganancia', 'precio_final_sugerido', 'fecha_calculo', 'vigente'];
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
            'id_calculo'             => 'id',
            'id_lote'                => 'idLote',
            'precio_planta_base'     => 'precioPlantaBase',
            'costo_total_insumo'     => 'costoTotalInsumo',
            'porcentaje_ganancia'    => 'porcentajeGanancia',
            'precio_final_sugerido'  => 'precioFinalSugerido',
            'fecha_calculo'          => 'fechaCalculo',
            'vigente'                => 'vigente',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getIdLote(): ?int { return $this->idLote; }
    public function getPrecioPlantaBase(): float { return $this->precioPlantaBase; }
    public function getCostoTotalInsumo(): float { return $this->costoTotalInsumo; }
    public function getPorcentajeGanancia(): float { return $this->porcentajeGanancia; }
    public function getPrecioFinalSugerido(): float { return $this->precioFinalSugerido; }
    public function getFechaCalculo(): ?string { return $this->fechaCalculo; }
    public function isVigente(): bool { return $this->vigente === 1; }

    public function setIdLote(?int $idLote): self
    {
        $this->idLote = $idLote;
        return $this;
    }

    public function setPrecioPlantaBase(float $precioPlantaBase): self
    {
        $this->precioPlantaBase = max(0, $precioPlantaBase);
        return $this;
    }

    public function setCostoTotalInsumo(float $costoTotalInsumo): self
    {
        $this->costoTotalInsumo = max(0, $costoTotalInsumo);
        return $this;
    }

    public function setPorcentajeGanancia(float $porcentajeGanancia): self
    {
        $this->porcentajeGanancia = max(0, $porcentajeGanancia);
        return $this;
    }

    public function setPrecioFinalSugerido(float $precioFinalSugerido): self
    {
        $this->precioFinalSugerido = max(0, $precioFinalSugerido);
        return $this;
    }

    public function setFechaCalculo(?string $fechaCalculo): self
    {
        $this->fechaCalculo = $fechaCalculo;
        return $this;
    }

    public function setVigente(bool $vigente): self
    {
        $this->vigente = $vigente ? 1 : 0;
        return $this;
    }

    private function validate(): void
    {
        $this->validateData([
            'id_lote'              => $this->idLote,
            'precio_planta_base'   => $this->precioPlantaBase,
            'porcentaje_ganancia'  => $this->porcentajeGanancia,
            'precio_final_sugerido'=> $this->precioFinalSugerido,
            'fecha_calculo'        => $this->fechaCalculo,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $this->db()->beginTransaction();

                $this->desmarcarVigentes($this->idLote);

                $sql = "INSERT INTO calculo_precio
                        (id_lote, precio_planta_base, costo_total_insumo,
                         porcentaje_ganancia, precio_final_sugerido,
                         fecha_calculo, vigente)
                        VALUES
                        (:id_lote, :precio_planta_base, :costo_total_insumo,
                         :porcentaje_ganancia, :precio_final_sugerido,
                         :fecha_calculo, :vigente)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id_lote'              => $this->idLote,
                    ':precio_planta_base'   => $this->precioPlantaBase,
                    ':costo_total_insumo'   => $this->costoTotalInsumo,
                    ':porcentaje_ganancia'  => $this->porcentajeGanancia,
                    ':precio_final_sugerido'=> $this->precioFinalSugerido,
                    ':fecha_calculo'        => $this->fechaCalculo,
                    ':vigente'              => $this->vigente,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    $this->db()->commit();
                    AuditLog::record('CREATE', 'calculo_precio', $this->id, null, [
                        'id_lote'              => $this->idLote,
                        'precio_planta_base'   => $this->precioPlantaBase,
                        'costo_total_insumo'   => $this->costoTotalInsumo,
                        'porcentaje_ganancia'  => $this->porcentajeGanancia,
                        'precio_final_sugerido'=> $this->precioFinalSugerido,
                        'fecha_calculo'        => $this->fechaCalculo,
                        'vigente'              => $this->vigente,
                    ]);
                } else {
                    if ($this->db()->inTransaction()) $this->db()->rollBack();
                }
                return $success;
            } else {
                if (!$this->exists($this->id)) {
                    throw new \Exception('No existe el cálculo de precio solicitado para modificar.');
                }

                $oldData = $this->getById($this->id);
                $sql = "UPDATE calculo_precio SET id_lote = :id_lote,
                        precio_planta_base = :precio_planta_base,
                        costo_total_insumo = :costo_total_insumo,
                        porcentaje_ganancia = :porcentaje_ganancia,
                        precio_final_sugerido = :precio_final_sugerido,
                        fecha_calculo = :fecha_calculo, vigente = :vigente
                        WHERE id_calculo = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                   => $this->id,
                    ':id_lote'              => $this->idLote,
                    ':precio_planta_base'   => $this->precioPlantaBase,
                    ':costo_total_insumo'   => $this->costoTotalInsumo,
                    ':porcentaje_ganancia'  => $this->porcentajeGanancia,
                    ':precio_final_sugerido'=> $this->precioFinalSugerido,
                    ':fecha_calculo'        => $this->fechaCalculo,
                    ':vigente'              => $this->vigente,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'calculo_precio', $this->id, $oldData, [
                        'id_lote'              => $this->idLote,
                        'precio_planta_base'   => $this->precioPlantaBase,
                        'costo_total_insumo'   => $this->costoTotalInsumo,
                        'porcentaje_ganancia'  => $this->porcentajeGanancia,
                        'precio_final_sugerido'=> $this->precioFinalSugerido,
                        'fecha_calculo'        => $this->fechaCalculo,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            error_log('Error al guardar cálculo de precio: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM calculo_precio WHERE id_calculo = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $calc = new static($row);
        $calc->id = (int)$row['id_calculo'];
        return $calc;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT
                    c.id_calculo AS id, c.id_lote, c.precio_planta_base,
                    c.costo_total_insumo, c.porcentaje_ganancia,
                    c.precio_final_sugerido, c.fecha_calculo, c.vigente,
                    l.cantidad_actual,
                    p.nombre_comun AS planta_nombre,
                    p.id_planta
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                ORDER BY c.fecha_calculo DESC, c.id_calculo DESC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM calculo_precio WHERE $column $operator :value";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT c.*, l.cantidad_actual, p.nombre_comun AS planta_nombre, p.id_planta
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE c.id_calculo = :id
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['detalles'] = $this->getDetalles($id);
            }
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::getById: ' . $e->getMessage());
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
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM calculo_precio WHERE id_calculo = :id");
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
            $stmt = $this->db()->prepare("UPDATE calculo_precio SET vigente = 0 WHERE id_calculo = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'calculo_precio', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::delete: ' . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId(): ?int
    {
        try {
            return $this->id ?? (parent::getLastInsertId() ? (int)parent::getLastInsertId() : null);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idLote = $found->getIdLote();
            $this->precioPlantaBase = $found->getPrecioPlantaBase();
            $this->costoTotalInsumo = $found->getCostoTotalInsumo();
            $this->porcentajeGanancia = $found->getPorcentajeGanancia();
            $this->precioFinalSugerido = $found->getPrecioFinalSugerido();
            $this->fechaCalculo = $found->getFechaCalculo();
            $this->vigente = $found->isVigente() ? 1 : 0;
            return true;
        }
        return false;
    }

    // ---- Métodos específicos de negocio ----

    public function addDetalle(int $idCalculo, int $idInsumo, float $monto): bool
    {
        try {
            $stmt = $this->db()->prepare("
                INSERT INTO calculo_precio_detalle (id_calculo, id_insumo, monto)
                VALUES (:id_calculo, :id_insumo, :monto)
            ");
            $stmt->execute([
                ':id_calculo' => $idCalculo,
                ':id_insumo'  => $idInsumo,
                ':monto'      => $monto,
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::addDetalle: ' . $e->getMessage());
            return false;
        }
    }

    public function getDetalles(int $idCalculo): array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT d.id_detalle, d.id_insumo, d.monto, i.nombre_insumo, i.costo_unitario_actual, u.simbolo
                FROM calculo_precio_detalle d
                LEFT JOIN insumo i ON d.id_insumo = i.id_insumo
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                WHERE d.id_calculo = :id_calculo
                ORDER BY d.id_detalle ASC
            ");
            $stmt->execute([':id_calculo' => $idCalculo]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::getDetalles: ' . $e->getMessage());
            return [];
        }
    }

    public function removeDetalle(int $idDetalle): bool
    {
        try {
            $stmt = $this->db()->prepare("DELETE FROM calculo_precio_detalle WHERE id_detalle = :id");
            $stmt->execute([':id' => $idDetalle]);
            return true;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::removeDetalle: ' . $e->getMessage());
            return false;
        }
    }

    public function updateDetalleMonto(int $idDetalle, float $monto): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE calculo_precio_detalle SET monto = :monto WHERE id_detalle = :id");
            $stmt->execute([':monto' => $monto, ':id' => $idDetalle]);
            return true;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::updateDetalleMonto: ' . $e->getMessage());
            return false;
        }
    }

    public function recalcularTotalInsumo(int $idCalculo): float
    {
        try {
            $stmt = $this->db()->prepare("SELECT COALESCE(SUM(monto), 0) FROM calculo_precio_detalle WHERE id_calculo = :id");
            $stmt->execute([':id' => $idCalculo]);
            $total = (float)$stmt->fetchColumn();
            $stmt2 = $this->db()->prepare("UPDATE calculo_precio SET costo_total_insumo = ? WHERE id_calculo = ?");
            $stmt2->execute([$total, $idCalculo]);
            return $total;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::recalcularTotalInsumo: ' . $e->getMessage());
            return 0;
        }
    }

    private function desmarcarVigentes(?int $idLote): void
    {
        if ($idLote === null) return;
        $stmt = $this->db()->prepare("UPDATE calculo_precio SET vigente = 0 WHERE id_lote = ? AND vigente = 1");
        $stmt->execute([$idLote]);
    }

    public function getVigenteByLote(int $idLote): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT c.*, p.nombre_comun AS planta_nombre
                FROM calculo_precio c
                LEFT JOIN lote l ON c.id_lote = l.id_lote
                LEFT JOIN plantas p ON l.id_planta = p.id_planta
                WHERE c.id_lote = :id_lote AND c.vigente = 1
                LIMIT 1
            ");
            $stmt->execute([':id_lote' => $idLote]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['detalles'] = $this->getDetalles((int)$row['id_calculo']);
            }
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::getVigenteByLote: ' . $e->getMessage());
            return null;
        }
    }

    public function saveDetalles(int $idCalculo, array $detalles): void
    {
        try {
            $keepIds = [];
            foreach ($detalles as $d) {
                $idDetalle = (int)($d['id_detalle'] ?? 0);
                $idInsumo  = (int)($d['id_insumo'] ?? 0);
                $monto     = (float)($d['monto'] ?? 0);
                if ($idDetalle > 0) {
                    $this->updateDetalleMonto($idDetalle, $monto);
                    $keepIds[] = $idDetalle;
                } elseif ($idInsumo > 0 && $monto > 0) {
                    $this->addDetalle($idCalculo, $idInsumo, $monto);
                }
            }
            if (!empty($keepIds)) {
                $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
                $stmt = $this->db()->prepare("DELETE FROM calculo_precio_detalle WHERE id_calculo = ? AND id_detalle NOT IN ($placeholders)");
                $stmt->execute(array_merge([$idCalculo], $keepIds));
            }
        } catch (Throwable $e) {
            error_log('Error en CalculoPrecio::saveDetalles: ' . $e->getMessage());
        }
    }
}
