<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use SysInescolara\interfaces\ReadableInterface;
use SysInescolara\interfaces\DeletableInterface;
use SysInescolara\traits\ValidationTrait;
use PDO;
use Throwable;

class Plant extends Database implements ReadableInterface, DeletableInterface
{
    use ValidationTrait;

    private ?int $id = null;
    private string $nombreComun = '';         
    private ?string $nombreTecnico = null;
    private ?int $idEspecie = null;
    private ?string $imagen = null;
    private int $cantidadTotal = 0;
    private bool $activo = true;

    protected array $validationRules = [
        'nombre_comun'   => ['type' => 'nombre',   'required' => true],
        'nombre_tecnico' => ['type' => 'nombre',   'required' => false],
        'id_especie'     => ['type' => 'cantidad', 'required' => true], 
        'cantidad_total' => ['type' => 'cantidad', 'required' => false],
    ];

    public function __construct()
    {
        parent::__construct();
    }

 
    public function getId(): ?int { return $this->id; }
    public function getNombreComun(): string { return $this->nombreComun; }
    public function getNombreTecnico(): ?string { return $this->nombreTecnico; }
    public function getIdEspecie(): ?int { return $this->idEspecie; }
    public function getImagen(): ?string { return $this->imagen; }
    public function getCantidadTotal(): int { return $this->cantidadTotal; }
    public function isActivo(): bool { return $this->activo; }


    public function setNombreComun(string $nombreComun): self 
    {
        $this->nombreComun = trim($nombreComun);
        return $this;
    }

    public function setNombreTecnico(?string $nombreTecnico): self 
    {
        $this->nombreTecnico = $nombreTecnico ? trim($nombreTecnico) : null;
        return $this;
    }

    public function setIdEspecie(?int $idEspecie): self 
    {
        $this->idEspecie = $idEspecie;
        return $this;
    }

    public function setImagen(?string $imagen): self 
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function setCantidadTotal(int $cantidadTotal): self 
    {
        $this->cantidadTotal = max(0, $cantidadTotal);
        return $this;
    }

    public function setActivo(bool $activo): self 
    {
        $this->activo = $activo;
        return $this;
    }

    public function save(): bool
    {
        $this->validateData([
            'nombre_comun'   => $this->nombreComun,
            'nombre_tecnico' => $this->nombreTecnico,
            'id_especie'     => $this->idEspecie,
            'cantidad_total' => $this->cantidadTotal,
        ]);

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO plantas (nombre_comun, nombre_tecnico, id_especie, imagen, cantidad_total, activo) 
                        VALUES (:nombre_comun, :nombre_tecnico, :id_especie, :imagen, :cantidad_total, :activo)";
                $stmt = $this->db->prepare($sql);
                $success = $stmt->execute([
                    ':nombre_comun'   => $this->nombreComun,
                    ':nombre_tecnico' => $this->nombreTecnico,
                    ':id_especie'     => $this->idEspecie,
                    ':imagen'         => $this->imagen,
                    ':cantidad_total' => $this->cantidadTotal,
                    ':activo'         => $this->activo ? 1 : 0,
                ]);
                
                if ($success) {
                    $this->id = (int) $this->db->lastInsertId();
                }
                return $success;
            } else {
                $sql = "UPDATE plantas SET nombre_comun = :nombre_comun, nombre_tecnico = :nombre_tecnico, 
                        id_especie = :id_especie, imagen = :imagen, cantidad_total = :cantidad_total 
                        WHERE id_planta = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    ':id'             => $this->id,
                    ':nombre_comun'   => $this->nombreComun,
                    ':nombre_tecnico' => $this->nombreTecnico,
                    ':id_especie'     => $this->idEspecie,
                    ':imagen'         => $this->imagen,
                    ':cantidad_total' => $this->cantidadTotal,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Error al guardar planta: ' . $e->getMessage());
            return false;
        }
    }

    public function loadById(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $this->id = (int) $data['id_planta'];
            $this->nombreComun = (string) $data['nombre_comun'];
            $this->nombreTecnico = $data['nombre_tecnico'];
            $this->idEspecie = $data['id_especie'] !== null ? (int) $data['id_especie'] : null;
            $this->imagen = $data['imagen'];
            $this->cantidadTotal = (int) ($data['cantidad_total'] ?? 0);
            $this->activo = (bool) $data['activo'];
            return true;
        }
        return false;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_especie AS especie_id, 
                    p.imagen, p.activo, p.cantidad_total,
                    e.nombre_especie AS especie_nombre
                FROM plantas p
                LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                WHERE p.id_planta = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        p.id_planta AS id, p.nombre_comun, p.nombre_tecnico, p.id_especie AS especie_id, 
                        p.imagen, p.activo, p.cantidad_total,
                        e.nombre_especie AS especie_nombre,
                        (SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta AND l2.activo = 1) AS stock_lotes,
                        (SELECT cp.precio_final_sugerido
                         FROM calculo_precio cp
                         JOIN lote l ON cp.id_lote = l.id_lote
                         WHERE l.id_planta = p.id_planta
                         ORDER BY cp.fecha_calculo DESC
                         LIMIT 1) AS precio_vigente
                    FROM plantas p
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    WHERE p.activo = 1
                    ORDER BY p.nombre_comun ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('Error al obtener plantas: ' . $e->getMessage());
            return [];
        }
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM plantas WHERE id_planta = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plantas SET activo = 0 WHERE id_planta = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plantas SET activo = 1 WHERE id_planta = :id");
        return $stmt->execute([':id' => $id]);
    }
}