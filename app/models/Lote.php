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
    private ?int $idEstado = null;
    private ?int $idCategoria = null;
    private ?int $idOrigen = null;
    private ?string $observacion = null;
    private ?string $imagen = null;
    private int $activo = 1;

    private array $schemaCache = [];

    protected array $validationRules = [
        'id_planta'       => ['type' => null,      'required' => true],
        'id_ubicacion'    => ['type' => null,      'required' => true],
        'fecha_siembra'   => ['type' => null,      'required' => true],
        'cantidad_inicial'=> ['type' => 'cantidad','required' => true],
        'cantidad_actual' => ['type' => 'cantidad','required' => true],
        'id_estado'       => ['type' => null,      'required' => false],
        'id_categoria'    => ['type' => null,      'required' => false],
        'id_origen'       => ['type' => null,      'required' => false],
        'observacion'     => ['type' => null,      'required' => false],
    ];

    protected array $fillable = [
        'id_planta', 'id_ubicacion', 'fecha_siembra', 'cantidad_inicial', 'cantidad_actual',
        'id_estado', 'id_categoria', 'id_origen', 'observacion', 'imagen', 'activo',
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
            'id_lote'            => 'id',
            'id_planta'          => 'idPlanta',
            'id_ubicacion'       => 'idUbicacion',
            'fecha_siembra'      => 'fechaSiembra',
            'cantidad_inicial'   => 'cantidadInicial',
            'cantidad_actual'    => 'cantidadActual',
            'id_estado'          => 'idEstado',
            'id_categoria'       => 'idCategoria',
            'id_origen'          => 'idOrigen',
            'observacion'        => 'observacion',
            'imagen'             => 'imagen',
            'activo'             => 'activo',
        ];
        return $map[$column] ?? $column;
    }

    public function getId(): ?int { return $this->id; }
    public function getIdPlanta(): ?int { return $this->idPlanta; }
    public function getIdUbicacion(): ?int { return $this->idUbicacion; }
    public function getFechaSiembra(): ?string { return $this->fechaSiembra; }
    public function getCantidadInicial(): int { return $this->cantidadInicial; }
    public function getCantidadActual(): int { return $this->cantidadActual; }
    public function getIdEstado(): ?int { return $this->idEstado; }
    public function getIdCategoria(): ?int { return $this->idCategoria; }
    public function getIdOrigen(): ?int { return $this->idOrigen; }
    public function getObservacion(): ?string { return $this->observacion; }
    public function getImagen(): ?string { return $this->imagen; }
    public function isActivo(): bool { return $this->activo === 1; }

    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setIdPlanta(int $idPlanta): self { $this->idPlanta = $idPlanta; return $this; }
    public function setIdUbicacion(int $idUbicacion): self { $this->idUbicacion = $idUbicacion; return $this; }
    public function setFechaSiembra(string $fechaSiembra): self { $this->fechaSiembra = $fechaSiembra; return $this; }
    public function setCantidadInicial(int $cantidadInicial): self { $this->cantidadInicial = max(0, $cantidadInicial); return $this; }
    public function setCantidadActual(int $cantidadActual): self { $this->cantidadActual = max(0, $cantidadActual); return $this; }
    public function setIdEstado(?int $idEstado): self { $this->idEstado = $idEstado; return $this; }
    public function setIdCategoria(?int $idCategoria): self { $this->idCategoria = $idCategoria; return $this; }
    public function setIdOrigen(?int $idOrigen): self { $this->idOrigen = $idOrigen; return $this; }
    public function setObservacion(?string $observacion): self { $this->observacion = $observacion ?: null; return $this; }
    public function setImagen(?string $imagen): self { $this->imagen = $imagen; return $this; }
    public function setActivo(bool $activo): self { $this->activo = $activo ? 1 : 0; return $this; }

    private function validate(): void
    {
        $this->validateData([
            'id_planta'       => $this->idPlanta,
            'id_ubicacion'    => $this->idUbicacion,
            'fecha_siembra'   => $this->fechaSiembra,
            'cantidad_inicial'=> $this->cantidadInicial,
            'cantidad_actual' => $this->cantidadActual,
            'id_estado'       => $this->idEstado,
            'id_categoria'    => $this->idCategoria,
            'id_origen'       => $this->idOrigen,
            'observacion'     => $this->observacion,
        ]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = "$table.$column";
        if (array_key_exists($key, $this->schemaCache)) {
            return $this->schemaCache[$key];
        }
        try {
            $stmt = $this->db()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            return $this->schemaCache[$key] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return $this->schemaCache[$key] = false;
        }
    }

    private function fkEstado(): bool { return $this->hasColumn('lote', 'id_estado'); }
    private function fkCategoria(): bool { return $this->hasColumn('lote', 'id_categoria'); }
    private function fkOrigen(): bool { return $this->hasColumn('lote', 'id_origen'); }

    private function estadoNameById(int $id): string
    {
        foreach ($this->getEstados() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return 'Activo';
    }

    private function categoriaNameById(?int $id): ?string
    {
        if ($id === null || $id <= 0) return null;
        foreach ($this->getCategorias() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return null;
    }

    private function origenNameById(?int $id): ?string
    {
        if ($id === null || $id <= 0) return null;
        foreach ($this->getOrigenes() as $e) {
            if ((int)$e['id'] === $id) return (string)$e['nombre'];
        }
        return 'Siembra';
    }

    public function save(): bool
    {
        $this->validate();

        try {
            if ($this->fkEstado()) {
                if ($this->idEstado === null) $this->idEstado = $this->getIdEstadoVivo();
                if ($this->idOrigen === null) $this->idOrigen = $this->getIdOrigenPorNombre('Siembra');
            } else {
                $this->idEstado = $this->idEstado ?? $this->getIdEstadoVivo();
                $this->idOrigen = $this->idOrigen ?? $this->getIdOrigenPorNombre('Siembra');
            }

            if ($this->id === null) {
                if ($this->fkEstado()) {
                    $sql = "INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, id_estado, id_categoria, id_origen, observacion, imagen)
                            VALUES (:id_planta, :id_ubicacion, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :id_estado, :id_categoria, :id_origen, :observacion, :imagen)";
                } else {
                    $sql = "INSERT INTO lote (id_planta, id_ubicacion, fecha_siembra, cantidad_inicial, cantidad_actual, estado, categoria, origen, observacion, imagen)
                            VALUES (:id_planta, :id_ubicacion, :fecha_siembra, :cantidad_inicial, :cantidad_actual, :estado, :categoria, :origen, :observacion, :imagen)";
                }
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute($this->buildInsertParams());
                if ($success) {
                    $this->id = (int) $this->db()->lastInsertId();
                    AuditLog::record('CREATE', 'lote', $this->id, null, [
                        'id_planta'        => $this->idPlanta,
                        'id_ubicacion'     => $this->idUbicacion,
                        'fecha_siembra'    => $this->fechaSiembra,
                        'cantidad_inicial' => $this->cantidadInicial,
                        'cantidad_actual'  => $this->cantidadActual,
                        'id_estado'        => $this->idEstado,
                        'id_origen'        => $this->idOrigen,
                        'observacion'      => $this->observacion,
                    ]);
                }
                return $success;
            } else {
                $oldData = $this->getById($this->id);
                if ($this->fkEstado()) {
                    $sql = "UPDATE lote SET id_planta = :id_planta, id_ubicacion = :id_ubicacion, fecha_siembra = :fecha_siembra,
                            cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual,
                            id_estado = :id_estado, id_categoria = :id_categoria, id_origen = :id_origen,
                            observacion = :observacion, imagen = :imagen
                            WHERE id_lote = :id";
                } else {
                    $sql = "UPDATE lote SET id_planta = :id_planta, id_ubicacion = :id_ubicacion, fecha_siembra = :fecha_siembra,
                            cantidad_inicial = :cantidad_inicial, cantidad_actual = :cantidad_actual,
                            estado = :estado, categoria = :categoria, origen = :origen,
                            observacion = :observacion, imagen = :imagen
                            WHERE id_lote = :id";
                }
                $stmt = $this->db()->prepare($sql);
                $success = $stmt->execute($this->buildUpdateParams());
                if ($success) {
                    AuditLog::record('UPDATE', 'lote', $this->id, $oldData, [
                        'id_planta'        => $this->idPlanta,
                        'id_ubicacion'     => $this->idUbicacion,
                        'fecha_siembra'    => $this->fechaSiembra,
                        'cantidad_inicial' => $this->cantidadInicial,
                        'cantidad_actual'  => $this->cantidadActual,
                        'id_estado'        => $this->idEstado,
                        'id_origen'        => $this->idOrigen,
                        'observacion'      => $this->observacion,
                    ]);
                }
                return $success;
            }
        } catch (Throwable $e) {
            error_log('Error al guardar lote: ' . $e->getMessage());
            return false;
        }
    }

    private function buildInsertParams(): array
    {
        $base = [
            ':id_planta'        => $this->idPlanta,
            ':id_ubicacion'     => $this->idUbicacion,
            ':fecha_siembra'    => $this->fechaSiembra,
            ':cantidad_inicial' => $this->cantidadInicial,
            ':cantidad_actual'  => $this->cantidadActual,
            ':observacion'      => $this->observacion,
            ':imagen'           => $this->imagen,
        ];
        if ($this->fkEstado()) {
            $base[':id_estado']    = $this->idEstado;
            $base[':id_categoria'] = $this->idCategoria;
            $base[':id_origen']    = $this->idOrigen;
        } else {
            $base[':estado']   = $this->estadoNameById((int)$this->idEstado);
            $base[':categoria']= $this->categoriaNameById($this->idCategoria);
            $base[':origen']   = $this->origenNameById($this->idOrigen);
        }
        return $base;
    }

    private function buildUpdateParams(): array
    {
        $params = $this->buildInsertParams();
        $params[':id'] = $this->id;
        return $params;
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
            if ($instance->fkEstado()) {
                $sql = "SELECT
                            l.id_lote AS id, l.id_planta, l.id_ubicacion, l.fecha_siembra,
                            l.cantidad_inicial, l.cantidad_actual, l.observacion, l.imagen, l.activo,
                            l.id_estado, l.id_categoria, l.id_origen,
                            e.nombre AS estado_nombre,
                            c.nombre AS categoria_nombre,
                            o.nombre AS origen_nombre,
                            p.nombre_comun AS planta_nombre,
                            sp.nombre_especie AS especie_nombre,
                            u.nombre_ubicacion AS ubicacion_nombre,
                            cp.precio_final_sugerido AS precio_unitario
                        FROM lote l
                        LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                        LEFT JOIN especie sp ON p.id_especie = sp.id_especie AND sp.activo = 1
                        LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                        LEFT JOIN estado e ON l.id_estado = e.id_estado
                        LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                        LEFT JOIN origen o ON l.id_origen = o.id_origen
                        LEFT JOIN calculo_precio cp ON l.id_lote = cp.id_lote
                        WHERE l.activo = 1
                        ORDER BY l.fecha_siembra DESC";
            } else {
                $sql = "SELECT
                            l.id_lote AS id, l.id_planta, l.id_ubicacion, l.fecha_siembra,
                            l.cantidad_inicial, l.cantidad_actual, l.observacion, l.imagen, l.activo,
                            l.estado AS estado_nombre,
                            l.categoria AS categoria_nombre,
                            l.origen AS origen_nombre,
                            p.nombre_comun AS planta_nombre,
                            sp.nombre_especie AS especie_nombre,
                            u.nombre_ubicacion AS ubicacion_nombre,
                            cp.precio_final_sugerido AS precio_unitario
                        FROM lote l
                        LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                        LEFT JOIN especie sp ON p.id_especie = sp.id_especie AND sp.activo = 1
                        LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                        LEFT JOIN calculo_precio cp ON l.id_lote = cp.id_lote
                        WHERE l.activo = 1
                        ORDER BY l.fecha_siembra DESC";
            }
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
        if ($this->fkEstado()) {
            $stmt = $this->db()->prepare("SELECT
                                            l.*,
                                            e.nombre AS estado_nombre,
                                            c.nombre AS categoria_nombre,
                                            o.nombre AS origen_nombre,
                                            p.nombre_comun AS planta_nombre,
                                            sp.nombre_especie AS especie_nombre
                                        FROM lote l
                                        LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                        LEFT JOIN especie sp ON p.id_especie = sp.id_especie
                                        LEFT JOIN estado e ON l.id_estado = e.id_estado
                                        LEFT JOIN categoria c ON l.id_categoria = c.id_categoria
                                        LEFT JOIN origen o ON l.id_origen = o.id_origen
                                        WHERE l.id_lote = :id");
        } else {
            $stmt = $this->db()->prepare("SELECT
                                            l.*,
                                            l.estado AS estado_nombre,
                                            l.categoria AS categoria_nombre,
                                            l.origen AS origen_nombre,
                                            p.nombre_comun AS planta_nombre,
                                            sp.nombre_especie AS especie_nombre
                                        FROM lote l
                                        LEFT JOIN plantas p ON l.id_planta = p.id_planta
                                        LEFT JOIN especie sp ON p.id_especie = sp.id_especie
                                        WHERE l.id_lote = :id");
        }
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

    public function getEstados(): array
    {
        if ($this->fkEstado()) {
            $stmt = $this->db()->query("SELECT id_estado AS id, nombre FROM estado WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        try {
            $stmt = $this->db()->query(
                "SELECT DISTINCT estado AS nombre FROM lote WHERE estado IS NOT NULL AND estado != ''
                 UNION
                 SELECT DISTINCT estado_salud AS nombre FROM trazabilidad WHERE estado_salud IS NOT NULL AND estado_salud != ''
                 ORDER BY nombre ASC"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $out = [];
            $i = 1;
            foreach ($rows as $r) {
                $out[] = ['id' => $i++, 'nombre' => (string)$r['nombre']];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('Error al obtener estados: ' . $e->getMessage());
            return [];
        }
    }

    public function getCategorias(): array
    {
        if ($this->fkCategoria()) {
            $stmt = $this->db()->query("SELECT id_categoria AS id, nombre FROM categoria WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        try {
            $stmt = $this->db()->query(
                "SELECT DISTINCT categoria AS nombre FROM lote WHERE categoria IS NOT NULL AND categoria != ''
                 ORDER BY nombre ASC"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $out = [];
            $i = 1;
            foreach ($rows as $r) {
                $out[] = ['id' => $i++, 'nombre' => (string)$r['nombre']];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('Error al obtener categorías: ' . $e->getMessage());
            return [];
        }
    }

    public function getOrigenes(): array
    {
        if ($this->fkOrigen()) {
            $stmt = $this->db()->query("SELECT id_origen AS id, nombre FROM origen WHERE activo = 1 ORDER BY nombre ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        try {
            $stmt = $this->db()->query(
                "SELECT DISTINCT origen AS nombre FROM lote WHERE origen IS NOT NULL AND origen != ''
                 ORDER BY nombre ASC"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $out = [];
            $i = 1;
            foreach ($rows as $r) {
                $out[] = ['id' => $i++, 'nombre' => (string)$r['nombre']];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('Error al obtener orígenes: ' . $e->getMessage());
            return [];
        }
    }

    public function getIdEstadoVivo(): int
    {
        if ($this->fkEstado()) {
            $stmt = $this->db()->query("SELECT id_estado FROM estado WHERE nombre = 'vivo' LIMIT 1");
            return (int)$stmt->fetchColumn();
        }
        foreach ($this->getEstados() as $e) {
            if (strcasecmp((string)$e['nombre'], 'Vivo') === 0) return (int)$e['id'];
        }
        return 1;
    }

    public function getIdOrigenPorNombre(string $nombre): int
    {
        if ($this->fkOrigen()) {
            $stmt = $this->db()->prepare("SELECT id_origen FROM origen WHERE nombre = ? LIMIT 1");
            $stmt->execute([$nombre]);
            return (int)$stmt->fetchColumn();
        }
        foreach ($this->getOrigenes() as $o) {
            if (strcasecmp((string)$o['nombre'], $nombre) === 0) return (int)$o['id'];
        }
        return 1;
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
            $this->idEstado = $found->getIdEstado();
            $this->idCategoria = $found->getIdCategoria();
            $this->idOrigen = $found->getIdOrigen();
            $this->observacion = $found->getObservacion();
            $this->imagen = $found->getImagen();
            $this->activo = $found->isActivo() ? 1 : 0;
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

    public function add($id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null)
    {
        return $this->fill([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'id_estado' => $id_estado,
            'id_categoria' => $id_categoria,
            'id_origen' => $id_origen,
            'observacion' => $observacion,
            'imagen' => $imagen,
        ])->save();
    }

    public function update($id, $id_planta, $id_ubicacion, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $id_estado = null, $id_categoria = null, $id_origen = null, $observacion = null, $imagen = null)
    {
        if (!$this->exists($id)) {
            throw new \Exception("No existe el lote con ID: $id");
        }
        return $this->setId($id)->fill([
            'id_planta' => $id_planta,
            'id_ubicacion' => $id_ubicacion,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'id_estado' => $id_estado,
            'id_categoria' => $id_categoria,
            'id_origen' => $id_origen,
            'observacion' => $observacion,
            'imagen' => $imagen,
        ])->save();
    }
}
