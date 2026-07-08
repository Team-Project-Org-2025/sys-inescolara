<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Insumo extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreInsumo = '';
    private ?int $idUnidadMedida = null;
    private ?string $categoria = null;
    private float $stockActual = 0.0;
    private float $costoUnitarioActual = 0.0;
    private int $activo = 1;

    protected array $validationRules = [
        'nombre_insumo'          => ['type' => 'nombreProducto', 'required' => true],
        'id_unidad_medida'       => ['type' => null,             'required' => true],
        'categoria'              => ['type' => null,             'required' => false],
        'stock_actual'           => ['type' => 'precio',         'required' => false],
        'costo_unitario_actual'  => ['type' => 'precio',         'required' => false],
    ];

    protected array $fillable = ['nombre_insumo', 'id_unidad_medida', 'categoria', 'stock_actual', 'costo_unitario_actual', 'activo'];
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
            'id_insumo'             => 'id',
            'nombre_insumo'         => 'nombreInsumo',
            'id_unidad_medida'      => 'idUnidadMedida',
            'categoria'             => 'categoria',
            'stock_actual'          => 'stockActual',
            'costo_unitario_actual' => 'costoUnitarioActual',
            'activo'                => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    // --- Getters y Setters ---
    public function getId(): ?int { return $this->id; }
    public function getNombreInsumo(): string { return $this->nombreInsumo; }
    public function getIdUnidadMedida(): ?int { return $this->idUnidadMedida; }
    public function getCategoria(): ?string { return $this->categoria; }
    public function getStockActual(): float { return $this->stockActual; }
    public function getCostoUnitarioActual(): float { return $this->costoUnitarioActual; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setNombreInsumo(string $nombreInsumo): self
    {
        $this->nombreInsumo = trim($nombreInsumo);
        return $this;
    }

    public function setIdUnidadMedida(?int $idUnidadMedida): self
    {
        $this->idUnidadMedida = $idUnidadMedida;
        return $this;
    }

    public function setCategoria(?string $categoria): self
    {
        $this->categoria = $categoria ? trim($categoria) : null;
        return $this;
    }

    public function setStockActual(float $stockActual): self
    {
        $this->stockActual = max(0, $stockActual);
        return $this;
    }

    public function setCostoUnitarioActual(float $costoUnitarioActual): self
    {
        $this->costoUnitarioActual = max(0, $costoUnitarioActual);
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
            'nombre_insumo'          => $this->nombreInsumo,
            'id_unidad_medida'       => $this->idUnidadMedida,
            'categoria'              => $this->categoria,
            'stock_actual'           => $this->stockActual,
            'costo_unitario_actual'  => $this->costoUnitarioActual,
        ]);
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO insumo (nombre_insumo, id_unidad_medida, categoria, stock_actual, costo_unitario_actual, activo) 
                        VALUES (:nombre_insumo, :id_unidad_medida, :categoria, :stock_actual, :costo_unitario_actual, :activo)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_insumo'          => $this->nombreInsumo,
                    ':id_unidad_medida'       => $this->idUnidadMedida,
                    ':categoria'              => $this->categoria,
                    ':stock_actual'           => $this->stockActual,
                    ':costo_unitario_actual'  => $this->costoUnitarioActual,
                    ':activo'                 => $this->activo,
                ]);

                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'insumo', $this->id, null, [
                        'nombre_insumo'          => $this->nombreInsumo,
                        'id_unidad_medida'       => $this->idUnidadMedida,
                        'categoria'              => $this->categoria,
                        'stock_actual'           => $this->stockActual,
                        'costo_unitario_actual'  => $this->costoUnitarioActual,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE insumo SET nombre_insumo = :nombre_insumo, id_unidad_medida = :id_unidad_medida, 
                        categoria = :categoria, stock_actual = :stock_actual, 
                        costo_unitario_actual = :costo_unitario_actual, activo = :activo
                        WHERE id_insumo = :id";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute([
                    ':id'                     => $this->id,
                    ':nombre_insumo'          => $this->nombreInsumo,
                    ':id_unidad_medida'       => $this->idUnidadMedida,
                    ':categoria'              => $this->categoria,
                    ':stock_actual'           => $this->stockActual,
                    ':costo_unitario_actual'  => $this->costoUnitarioActual,
                    ':activo'                 => $this->activo,
                ]);
                if ($success) {
                    AuditLog::record('UPDATE', 'insumo', $this->id, $oldData, [
                        'nombre_insumo'          => $this->nombreInsumo,
                        'id_unidad_medida'       => $this->idUnidadMedida,
                        'categoria'              => $this->categoria,
                        'stock_actual'           => $this->stockActual,
                        'costo_unitario_actual'  => $this->costoUnitarioActual,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar insumo: ' . $e->getMessage());
            return false;
        }
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM insumo WHERE id_insumo = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $insumo = new static($row);
        $insumo->id = (int)$row['id_insumo'];
        return $insumo;
    }

    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT i.id_insumo AS id, i.id_insumo, i.nombre_insumo, i.id_unidad_medida, i.categoria, i.stock_actual, i.costo_unitario_actual, i.activo,
                       u.nombre_unidad_medida, u.simbolo
                FROM insumo i
                LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida AND u.activo = 1
                WHERE i.activo = 1
                ORDER BY i.nombre_insumo ASC";
        $stmt = $instance->db()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $sql = "SELECT * FROM insumo WHERE $column $operator :value AND activo = 1";
        $stmt = $instance->db()->prepare($sql);
        $stmt->execute([':value' => $value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new static($row), $rows);
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT i.*, u.nombre_unidad_medida, u.simbolo
                            FROM insumo i
                            LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida
                            WHERE i.id_insumo = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener insumo por ID: ' . $e->getMessage());
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
            $stmt = $this->db()->prepare("SELECT COUNT(*) FROM insumo WHERE id_insumo = :id");
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
            $stmt = $this->db()->prepare("UPDATE insumo SET activo = 0 WHERE id_insumo = :id");
            $stmt->execute([':id' => $id]);
            AuditLog::record('DEACTIVATE', 'insumo', $id, $oldData, null);
            return true;
        } catch (Throwable $e) {
            error_log('Error al desactivar insumo: ' . $e->getMessage());
            return false;
        }
    }

    public function restore(int $id): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE insumo SET activo = 1 WHERE id_insumo = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al restaurar insumo: ' . $e->getMessage());
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
            $this->nombreInsumo = $found->getNombreInsumo();
            $this->idUnidadMedida = $found->getIdUnidadMedida();
            $this->categoria = $found->getCategoria();
            $this->stockActual = $found->getStockActual();
            $this->costoUnitarioActual = $found->getCostoUnitarioActual();
            $this->activo = $found->isActivo() ? 1 : 0;
            return true;
        }
        return false;
    }

    // --- Métodos específicos de negocio ---

    public function findByNameAndCategory(string $name, string $category): ?array
    {
        try {
            $stmt = $this->db()->prepare("
                SELECT id_insumo, nombre_insumo, stock_actual
                FROM insumo
                WHERE nombre_insumo = :nombre AND categoria = :categoria AND activo = 1
                LIMIT 1
            ");
            $stmt->execute([':nombre' => $name, ':categoria' => $category]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error en Insumo::findByNameAndCategory: ' . $e->getMessage());
            return null;
        }
    }

    public function increaseStock(int $id, float $quantity): bool
    {
        try {
            $stmt = $this->db()->prepare("UPDATE insumo SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id");
            return $stmt->execute([':id' => $id, ':cantidad' => $quantity]);
        } catch (Throwable $e) {
            error_log('Error en Insumo::increaseStock: ' . $e->getMessage());
            return false;
        }
    }
}
