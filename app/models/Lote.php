<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use SysInescolara\models\AuditLog;
use PDO;
use Throwable;

class Lote extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private ?int $idPlanta = null;
    private ?int $idUbicacion = null;
    private ?string $fechaSiembra = null;
    private int $cantidadInicial = 0;
    private int $cantidadActual = 0;
    private float $costoUnitario = 0.0;
    private ?int $idEstado = null;
    private ?int $idCategoria = null;
    private ?int $idOrigen = null;
    private ?string $observacion = null;
    private ?string $imagen = null;
    private int $activo = 1;
    private float $porcentajeGanancia = 30.0;

    protected array $validationRules = [
        'id_planta'          => ['type' => null,      'required' => true],
        'id_ubicacion'       => ['type' => null,      'required' => false],
        'fecha_siembra'      => ['type' => null,      'required' => true],
        'cantidad_inicial'   => ['type' => 'cantidad','required' => true],
        'cantidad_actual'    => ['type' => 'cantidad','required' => true],
        'costo_unitario'     => ['type' => 'precio',  'required' => false],
        'porcentaje_ganancia'=> ['type' => 'precio',  'required' => false],
    ];

    protected array $fillable = [
        'id_planta', 'id_ubicacion', 'fecha_siembra', 'cantidad_inicial', 'cantidad_actual',
        'costo_unitario', 'id_estado', 'id_categoria', 'id_origen', 'observacion', 'imagen', 'activo',
        'porcentaje_ganancia',
    ];
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
            if (in_array($key, $this->fillable, true)) {
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
            'id_lote'              => 'id',
            'id_planta'            => 'idPlanta',
            'id_ubicacion'         => 'idUbicacion',
            'fecha_siembra'        => 'fechaSiembra',
            'cantidad_inicial'     => 'cantidadInicial',
            'cantidad_actual'      => 'cantidadActual',
            'costo_unitario'       => 'costoUnitario',
            'id_estado'            => 'idEstado',
            'id_categoria'         => 'idCategoria',
            'id_origen'            => 'idOrigen',
            'observacion'          => 'observacion',
            'imagen'               => 'imagen',
            'activo'               => 'activo',
            'porcentaje_ganancia'  => 'porcentajeGanancia',
        ];
        return $map[$column] ?? $column;
    }

    public function getId(): ?int { return $this->id; }
    public function getIdPlanta(): ?int { return $this->idPlanta; }
    public function getIdUbicacion(): ?int { return $this->idUbicacion; }
    public function getFechaSiembra(): ?string { return $this->fechaSiembra; }
    public function getCantidadInicial(): int { return $this->cantidadInicial; }
    public function getCantidadActual(): int { return $this->cantidadActual; }
    public function getCostoUnitario(): float { return $this->costoUnitario; }
    public function getIdEstado(): ?int { return $this->idEstado; }
    public function getIdCategoria(): ?int { return $this->idCategoria; }
    public function getIdOrigen(): ?int { return $this->idOrigen; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function getImagen(): ?string { return $this->imagen; }
    public function isActivo(): bool { return $this->activo === 1; }
    public function getPorcentajeGanancia(): float { return $this->porcentajeGanancia; }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setIdPlanta(int $idPlanta): self { $this->idPlanta = $idPlanta; return $this; }
    public function setIdUbicacion(?int $idUbicacion): self { $this->idUbicacion = $idUbicacion; return $this; }
    public function setFechaSiembra(string $fechaSiembra): self { $this->fechaSiembra = $fechaSiembra; return $this; }
    public function setCantidadInicial(int $cantidadInicial): self { $this->cantidadInicial = max(0, $cantidadInicial); return $this; }
    public function setCantidadActual(int $cantidadActual): self { $this->cantidadActual = max(0, $cantidadActual); return $this; }
    public function setCostoUnitario(float $costoUnitario): self { $this->costoUnitario = max(0, $costoUnitario); return $this; }
    public function setIdEstado(?int $idEstado): self { $this->idEstado = $idEstado; return $this; }
    public function setIdCategoria(?int $idCategoria): self { $this->idCategoria = $idCategoria; return $this; }
    public function setIdOrigen(?int $idOrigen): self { $this->idOrigen = $idOrigen; return $this; }
    public function setObservacion(?string $observacion): self { $this->observacion = $observacion ?: null; return $this; }
    public function setImagen(?string $imagen): self { $this->imagen = $imagen; return $this; }
    public function setActivo(bool $activo): self { $this->activo = $activo ? 1 : 0; return $this; }
    public function setPorcentajeGanancia(float $porcentaje): self { $this->porcentajeGanancia = max(0, $porcentaje); return $this; }

    private function validate(): void
    {
        $this->validateData([
            'id_planta'          => $this->idPlanta,
            'fecha_siembra'      => $this->fechaSiembra,
            'cantidad_inicial'   => $this->cantidadInicial,
            'cantidad_actual'    => $this->cantidadActual,
            'costo_unitario'     => $this->costoUnitario,
            'porcentaje_ganancia'=> $this->porcentajeGanancia,
        ]);
    }

    private function estadoNameById(?int $id): ?string
    {
        if ($id === null) return null;
        foreach ($this->getEstados() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return 'Activo';
    }

    private function categoriaNameById(?int $id): ?string
    {
        if ($id === null) return null;
        foreach ($this->getCategorias() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return null;
    }

    private function origenNameById(?int $id): ?string
    {
        if ($id === null) return null;
        foreach ($this->getOrigenes() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return 'Siembra';
    }

    public function save(): bool
    {
        $this->validate();
        try {
            if ($this->idEstado === null) $this->idEstado = $this->getIdEstadoVivo();
            if ($this->idOrigen === null) $this->idOrigen = $this->getIdOrigenPorNombre('Siembra');

            if ($this->id === null) {
                $sql = "INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual,
                        costo_unitario, id_estado, id_categoria, id_origen, observacion, imagen, porcentaje_ganancia)
                        VALUES (:id_planta, :id_ubicacion, :fecha_siembra, :cantidad_inicial, :cantidad_actual,
                        :costo_unitario, :id_estado, :id_categoria, :id_origen, :observacion, :imagen, :porcentaje_ganancia)";
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute($this->buildParams());
                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'lote', $this->id, null, [
                        'id_planta'          => $this->idPlanta,
                        'id_ubicacion'       => $this->idUbicacion,
                        'fecha_siembra'      => $this->fechaSiembra,
                        'cantidad_inicial'   => $this->cantidadInicial,
                        'costo_unitario'     => $this->costoUnitario,
                        'porcentaje_ganancia'=> $this->porcentajeGanancia,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                $sql = "UPDATE lote SET id_planta = :id_planta, id_ubicacion = :id_ubicacion, fecha_siembra = :fecha_siembra,
                        cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual,
                        costo_unitario = :costo_unitario, id_estado = :id_estado, id_categoria = :id_categoria,
                        id_origen = :id_origen, observacion = :observacion, imagen = :imagen,
                        porcentaje_ganancia = :porcentaje_ganancia
                        WHERE id_lote = :id";
                $stmt = $this->db()->prepare($sql);
                $params = $this->buildParams();
                $params[':id'] = $this->id;
                $success = $stmt->execute($params);
                if ($success) {
                    AuditLog::record('UPDATE', 'lote', $this->id, $oldData, [
                        'id_planta'          => $this->idPlanta,
                        'costo_unitario'     => $this->costoUnitario,
                        'porcentaje_ganancia'=> $this->porcentajeGanancia,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar lote: ' . $e->getMessage());
            return false;
        }
    }

    private function buildParams(): array
    {
        return [
            ':id_planta'          => $this->idPlanta,
            ':id_ubicacion'       => $this->idUbicacion,
            ':fecha_siembra'      => $this->fechaSiembra,
            ':cantidad_inicial'   => $this->cantidadInicial,
            ':cantidad_actual'    => $this->cantidadActual,
            ':costo_unitario'     => $this->costoUnitario,
            ':id_estado'          => $this->idEstado,
            ':id_categoria'       => $this->idCategoria,
            ':id_origen'          => $this->idOrigen,
            ':observacion'        => $this->observacion,
            ':imagen'             => $this->imagen,
            ':porcentaje_ganancia'=> $this->porcentajeGanancia,
        ];
    }

    public static function find(int $id): ?self
    {
        $instance = new static();
        $stmt = $instance->db()->prepare("SELECT * FROM lote WHERE id_lote = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $lote = new static($row);
        $lote->id = (int)$row['id_lote'];
        return $lote;
    }

    public static function all(): array
    {
        $instance = new static();
        try {
            $sql = "SELECT
                        l.id_lote AS id, l.id_planta, l.id_ubicacion, l.fecha_siembra,
                        l.cantidad_inicial, l.cantidad_actual, l.costo_unitario,
                        l.id_estado, l.id_categoria, l.id_origen,
                        l.observacion, l.imagen, l.activo, l.porcentaje_ganancia,
                        p.nombre_comun AS planta_nombre,
                        sp.nombre_especie AS especie_nombre,
                        u.nombre_ubicacion AS ubicacion_nombre,
                        e.nombre AS estado_nombre,
                        c.nombre AS categoria_nombre,
                        o.nombre AS origen_nombre
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN especie sp ON p.id_especie = sp.id_especie AND sp.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    LEFT JOIN estado e ON l.id_estado = e.id_estado
                    LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                    LEFT JOIN origen o ON l.id_origen = o.id_origen
                    WHERE l.activo = 1
                    ORDER BY l.fecha_siembra DESC";
            $stmt = $instance->db()->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener lotes: ' . $e->getMessage());
            return [];
        }
    }

    public function getAll(): array
    {
        return self::all();
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db()->prepare("SELECT
                        l.*,
                        p.nombre_comun AS planta_nombre,
                        sp.nombre_especie AS especie_nombre,
                        e.nombre AS estado_nombre,
                        c.nombre AS categoria_nombre,
                        o.nombre AS origen_nombre
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN especie sp ON p.id_especie = sp.id_especie
                    LEFT JOIN estado e ON l.id_estado = e.id_estado
                    LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                    LEFT JOIN origen o ON l.id_origen = o.id_origen
                    WHERE l.id_lote = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Error en Lote::getById: ' . $e->getMessage());
            return null;
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM lote WHERE id_lote = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $stmt = $this->db()->prepare("UPDATE lote SET activo = 0 WHERE id_lote = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            AuditLog::record('DEACTIVATE', 'lote', $id, $oldData, null);
        }
        return $success;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET activo = 1 WHERE id_lote = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLastInsertId(): ?int
    {
        try {
            return (int)$this->db()->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function loadById(int $id): bool
    {
        $found = self::find($id);
        if ($found) {
            $this->id = $found->getId();
            $this->idPlanta = $found->getIdPlanta();
            $this->idUbicacion = $found->getIdUbicacion();
            $this->fechaSiembra = $found->getFechaSiembra();
            $this->cantidadInicial = $found->getCantidadInicial();
            $this->cantidadActual = $found->getCantidadActual();
            $this->costoUnitario = $found->getCostoUnitario();
            $this->idEstado = $found->getIdEstado();
            $this->idCategoria = $found->getIdCategoria();
            $this->idOrigen = $found->getIdOrigen();
            $this->observacion = $found->getObservacion();
            $this->imagen = $found->getImagen();
            $this->activo = $found->isActivo() ? 1 : 0;
            $this->porcentajeGanancia = $found->getPorcentajeGanancia();
            return true;
        }
        return false;
    }

    protected function deductStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = GREATEST(0, cantidad_actual - :cantidad) WHERE id_lote = :id AND cantidad_actual >= :cantidad2");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id, ':cantidad2' => $cantidad]);
    }

    protected function restoreStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db()->prepare("UPDATE lote SET cantidad_actual = cantidad_actual + :cantidad WHERE id_lote = :id");
        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id]);
    }

    public function getEstados(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_estado AS id, nombre FROM estado WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener estados: ' . $e->getMessage());
            return [];
        }
    }

    public function getCategorias(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_categoria AS id, nombre FROM categoria WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener categorías: ' . $e->getMessage());
            return [];
        }
    }

    public function getOrigenes(): array
    {
        try {
            $stmt = $this->db()->query("SELECT id_origen AS id, nombre FROM origen WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener orígenes: ' . $e->getMessage());
            return [];
        }
    }

    public function getIdEstadoVivo(): int
    {
        $stmt = $this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1");
        return $stmt ? (int)$stmt->fetchColumn() : 1;
    }

    public function getIdOrigenPorNombre(string $nombre): int
    {
        $stmt = $this->db()->prepare("SELECT id_origen FROM origen WHERE nombre = ? LIMIT 1");
        $stmt->execute([$nombre]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : 1;
    }

    public function add($id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $costo_unitario = 0.0, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null, $porcentaje_ganancia = 30.0)
    {
        return $this->fill([
            'id_planta'          => $id_planta,
            'id_ubicacion'       => $id_ubicacion,
            'fecha_siembra'      => $fecha_siembra,
            'cantidad_inicial'   => $cantidad_inicial,
            'cantidad_actual'    => $cantidad_actual,
            'costo_unitario'     => $costo_unitario,
            'id_estado'          => $id_estado,
            'id_categoria'       => $id_categoria,
            'id_origen'          => $id_origen,
            'observacion'        => $observacion,
            'imagen'             => $imagen,
            'porcentaje_ganancia'=> $porcentaje_ganancia,
        ])->save();
    }

    public function update($id, $id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $costo_unitario = 0.0, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null, $porcentaje_ganancia = 30.0)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el lote con ID: $id");
        }
        return $this->setId($id)->fill([
            'id_planta'          => $id_planta,
            'id_ubicacion'       => $id_ubicacion,
            'fecha_siembra'      => $fecha_siembra,
            'cantidad_inicial'   => $cantidad_inicial,
            'cantidad_actual'    => $cantidad_actual,
            'costo_unitario'     => $costo_unitario,
            'id_estado'          => $id_estado,
            'id_categoria'       => $id_categoria,
            'id_origen'          => $id_origen,
            'observacion'        => $observacion,
            'imagen'             => $imagen,
            'porcentaje_ganancia'=> $porcentaje_ganancia,
        ])->save();
    }
}
