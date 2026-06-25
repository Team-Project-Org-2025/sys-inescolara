<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Reports extends Database
{
    private array $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = $this->buildModules();
    }

    private function buildModules(): array
    {
        return [
            'plantas' => ['id' => 'plantas', 'nombre' => 'Plantas', 'icono' => 'fa-seedling', 'has_chart' => true],
            'lotes' => ['id' => 'lotes', 'nombre' => 'Lotes', 'icono' => 'fa-boxes', 'has_chart' => true],
            'insumos' => ['id' => 'insumos', 'nombre' => 'Insumos', 'icono' => 'fa-flask', 'has_chart' => true],
            'proveedores' => ['id' => 'proveedores', 'nombre' => 'Proveedores', 'icono' => 'fa-truck', 'has_chart' => true],
            'clientes' => ['id' => 'clientes', 'nombre' => 'Clientes', 'icono' => 'fa-users', 'has_chart' => false],
            'trabajadores' => ['id' => 'trabajadores', 'nombre' => 'Trabajadores', 'icono' => 'fa-user-hard-hat', 'has_chart' => true],
            'tareas' => ['id' => 'tareas', 'nombre' => 'Tareas', 'icono' => 'fa-tasks', 'has_chart' => true],
            'ventas' => ['id' => 'ventas', 'nombre' => 'Ventas', 'icono' => 'fa-shopping-cart', 'has_chart' => true],
            'compras' => ['id' => 'compras', 'nombre' => 'Compras', 'icono' => 'fa-file-invoice', 'has_chart' => true],
            'herramientas' => ['id' => 'herramientas', 'nombre' => 'Herramientas', 'icono' => 'fa-wrench', 'has_chart' => true],
            'especies' => ['id' => 'especies', 'nombre' => 'Especies', 'icono' => 'fa-leaf', 'has_chart' => true],
            'inventario' => ['id' => 'inventario', 'nombre' => 'Inventario', 'icono' => 'fa-chart-pie', 'has_chart' => true],
            'recoleccion' => ['id' => 'recoleccion', 'nombre' => 'Recolección', 'icono' => 'fa-hand-holding-heart', 'has_chart' => true],
            'ubicaciones' => ['id' => 'ubicaciones', 'nombre' => 'Ubicaciones', 'icono' => 'fa-map-marker-alt', 'has_chart' => false],
            'unidades_medida' => ['id' => 'unidades_medida', 'nombre' => 'Unidades Medida', 'icono' => 'fa-ruler', 'has_chart' => false],
            'cuentas_pagar' => ['id' => 'cuentas_pagar', 'nombre' => 'Cuentas x Pagar', 'icono' => 'fa-money-bill-wave', 'has_chart' => true],
            'precios' => ['id' => 'precios', 'nombre' => 'Precios', 'icono' => 'fa-tag', 'has_chart' => true],
            'cuentas_cobrar' => ['id' => 'cuentas_cobrar', 'nombre' => 'Cuentas x Cobrar', 'icono' => 'fa-hand-holding-usd', 'has_chart' => true],
        ];
    }

    public function getModules(): array
    {
        return array_values($this->modules);
    }

    private function fVal(mixed $val): bool
    {
        return isset($val) && $val !== '';
    }

    public function getModuleFilters(string $module): array
    {
        $method = 'filters' . ucfirst($module);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return [$this->filterActivo()];
    }

    private function filterActivo(): array
    {
        return [
            'field' => 'activo',
            'label' => 'Estado',
            'type' => 'select',
            'options' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => '1', 'label' => 'Activo'],
                ['value' => '0', 'label' => 'Inactivo'],
            ],
        ];
    }

    private function filterDateRange(string $field, string $label): array
    {
        return ['field' => $field, 'label' => $label, 'type' => 'date-range'];
    }

    private function filterSelectFromQuery(string $field, string $label, string $sql, string $valueCol, string $textCol, string $prependLabel = 'Todos'): array
    {
        try {
            $stmt = $this->db()->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            $rows = [];
        }
        $options = [['value' => '', 'label' => $prependLabel]];
        foreach ($rows as $row) {
            $options[] = ['value' => $row[$valueCol], 'label' => $row[$textCol]];
        }
        return ['field' => $field, 'label' => $label, 'type' => 'select', 'options' => $options];
    }

    private function filtersPlantas(): array
    {
        return [
            $this->filterActivo(),
            $this->filterSelectFromQuery('id_especie', 'Especie',
                "SELECT id_especie, nombre_especie FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC",
                'id_especie', 'nombre_especie'),
        ];
    }

    private function filtersLotes(): array
    {
        $estadoOptions = [['value' => '', 'label' => 'Todos']];
        try {
            $stmt = $this->db()->query("SELECT DISTINCT estado FROM lote WHERE activo = 1 ORDER BY estado ASC");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $e) {
                $estadoOptions[] = ['value' => $e, 'label' => $e];
            }
        } catch (\Throwable $e) {}

        $catOptions = [['value' => '', 'label' => 'Todas']];
        try {
            $stmt = $this->db()->query("SELECT DISTINCT categoria FROM lote WHERE activo = 1 AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $c) {
                $catOptions[] = ['value' => $c, 'label' => $c];
            }
        } catch (\Throwable $e) {}

        return [
            $this->filterActivo(),
            ['field' => 'estado_lote', 'label' => 'Estado del Lote', 'type' => 'select', 'options' => $estadoOptions],
            ['field' => 'categoria', 'label' => 'Categoría', 'type' => 'select', 'options' => $catOptions],
            $this->filterSelectFromQuery('id_ubicacion', 'Ubicación',
                "SELECT id_ubicacion, nombre_ubicacion FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC",
                'id_ubicacion', 'nombre_ubicacion'),
            $this->filterSelectFromQuery('id_planta', 'Planta',
                "SELECT id_planta, nombre_comun FROM plantas WHERE activo = 1 ORDER BY nombre_comun ASC",
                'id_planta', 'nombre_comun'),
            $this->filterDateRange('fecha_siembra', 'Fecha Siembra'),
        ];
    }

    private function filtersInsumos(): array
    {
        $catOptions = [['value' => '', 'label' => 'Todas']];
        try {
            $stmt = $this->db()->query("SELECT DISTINCT categoria FROM insumo WHERE activo = 1 AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $c) {
                $catOptions[] = ['value' => $c, 'label' => $c];
            }
        } catch (\Throwable $e) {}

        return [
            $this->filterActivo(),
            ['field' => 'categoria', 'label' => 'Categoría', 'type' => 'select', 'options' => $catOptions],
            $this->filterSelectFromQuery('id_unidad_medida', 'Unidad Medida',
                "SELECT id_unidad_medida, nombre_unidad_medida FROM unidad_medida WHERE activo = 1 ORDER BY nombre_unidad_medida ASC",
                'id_unidad_medida', 'nombre_unidad_medida'),
            ['field' => 'stock_min', 'label' => 'Stock Mínimo', 'type' => 'number'],
            ['field' => 'stock_max', 'label' => 'Stock Máximo', 'type' => 'number'],
        ];
    }

    private function filtersProveedores(): array
    {
        return [$this->filterActivo()];
    }

    private function filtersClientes(): array
    {
        return [$this->filterActivo()];
    }

    private function filtersTrabajadores(): array
    {
        $cargoOptions = [['value' => '', 'label' => 'Todos']];
        try {
            $stmt = $this->db()->query("SELECT DISTINCT cargo FROM trabajadores WHERE activo = 1 AND cargo IS NOT NULL AND cargo != '' ORDER BY cargo ASC");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $c) {
                $cargoOptions[] = ['value' => $c, 'label' => $c];
            }
        } catch (\Throwable $e) {}

        return [
            $this->filterActivo(),
            ['field' => 'cargo', 'label' => 'Cargo', 'type' => 'select', 'options' => $cargoOptions],
        ];
    }

    private function filtersTareas(): array
    {
        return [
            ['field' => 'estatus_tarea', 'label' => 'Estatus', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]],
            $this->filterSelectFromQuery('id_trabajador', 'Trabajador',
                "SELECT id_trabajador, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS nombre_completo FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC",
                'id_trabajador', 'nombre_completo'),
            $this->filterDateRange('fecha_asignacion', 'Fecha Asignación'),
        ];
    }

    private function filtersVentas(): array
    {
        return [
            ['field' => 'estado', 'label' => 'Estado', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]],
            ['field' => 'tipo_venta', 'label' => 'Tipo Venta', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'contado', 'label' => 'Contado'],
                    ['value' => 'credito', 'label' => 'Crédito'],
                ]],
            $this->filterSelectFromQuery('id_trabajador', 'Vendedor',
                "SELECT id_trabajador, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS nombre_completo FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC",
                'id_trabajador', 'nombre_completo'),
            $this->filterDateRange('fecha_venta', 'Fecha Venta'),
        ];
    }

    private function filtersCompras(): array
    {
        return [
            ['field' => 'estado', 'label' => 'Estado', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'recibida', 'label' => 'Recibida'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]],
            $this->filterSelectFromQuery('id_proveedor', 'Proveedor',
                "SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC",
                'id_proveedor', 'nombre_proveedor'),
            $this->filterDateRange('fecha_compra', 'Fecha Compra'),
        ];
    }

    private function filtersHerramientas(): array
    {
        $tipoOptions = [['value' => '', 'label' => 'Todos']];
        try {
            $stmt = $this->db()->query("SELECT DISTINCT tipo FROM herramienta WHERE activo = 1 AND tipo IS NOT NULL AND tipo != '' ORDER BY tipo ASC");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $t) {
                $tipoOptions[] = ['value' => $t, 'label' => $t];
            }
        } catch (\Throwable $e) {}

        return [
            $this->filterActivo(),
            ['field' => 'tipo', 'label' => 'Tipo', 'type' => 'select', 'options' => $tipoOptions],
            ['field' => 'estado_herramienta', 'label' => 'Estado', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'disponible', 'label' => 'Disponible'],
                    ['value' => 'en_uso', 'label' => 'En uso'],
                    ['value' => 'dañado', 'label' => 'Dañado'],
                ]],
        ];
    }

    private function filtersEspecies(): array
    {
        return [$this->filterActivo()];
    }

    private function filtersInventario(): array
    {
        return [
            ['field' => 'nivel_stock', 'label' => 'Nivel de Stock', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'sin_stock', 'label' => 'Sin stock'],
                    ['value' => 'bajo', 'label' => 'Bajo'],
                    ['value' => 'medio', 'label' => 'Medio'],
                    ['value' => 'alto', 'label' => 'Alto'],
                ]],
        ];
    }

    private function filtersRecoleccion(): array
    {
        return [
            ['field' => 'estatus', 'label' => 'Estatus', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'Pendiente', 'label' => 'Pendiente'],
                    ['value' => 'Realizada', 'label' => 'Realizada'],
                ]],
            $this->filterSelectFromQuery('id_trabajador', 'Trabajador',
                "SELECT id_trabajador, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS nombre_completo FROM trabajadores WHERE activo = 1 ORDER BY nombre_trabajador ASC",
                'id_trabajador', 'nombre_completo'),
            $this->filterDateRange('fecha_asignacion', 'Fecha Asignación'),
        ];
    }

    private function filtersUbicaciones(): array
    {
        return [$this->filterActivo()];
    }

    private function filtersUnidadesMedida(): array
    {
        return [$this->filterActivo()];
    }

    private function filtersCuentasPagar(): array
    {
        return [
            ['field' => 'estado', 'label' => 'Estado', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'parcial', 'label' => 'Parcial'],
                    ['value' => 'pagada', 'label' => 'Pagada'],
                ]],
            $this->filterSelectFromQuery('id_proveedor', 'Proveedor',
                "SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC",
                'id_proveedor', 'nombre_proveedor'),
        ];
    }

    private function filtersPrecios(): array
    {
        return [
            $this->filterSelectFromQuery('id_lote', 'Lote',
                "SELECT id_lote, CONCAT(id_lote, ' - ', p.nombre_comun) AS info FROM lote LEFT JOIN plantas p ON lote.id_planta = p.id_planta WHERE lote.activo = 1 ORDER BY lote.id_lote DESC",
                'id_lote', 'info'),
        ];
    }

    private function filtersCuentasCobrar(): array
    {
        return [
            ['field' => 'estado_cuenta', 'label' => 'Estado', 'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'vigente', 'label' => 'Vigente'],
                    ['value' => 'vencido', 'label' => 'Vencido'],
                    ['value' => 'pagado', 'label' => 'Pagado'],
                ]],
            $this->filterDateRange('fecha_venta', 'Fecha Venta'),
        ];
    }

    public function getReportData(string $module, array $filters): array
    {
        $method = 'report' . ucfirst($module);
        if (method_exists($this, $method)) {
            return $this->$method($filters);
        }
        return ['columns' => [], 'rows' => [], 'chart' => null];
    }

    private function buildWhere(array $conditions, array &$params): string
    {
        if (empty($conditions)) return '';
        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function aCond(array &$conds, array &$params, array $filters, string $field, string $column, ?string $alias = null): void
    {
        if ($this->fVal($filters[$field] ?? null)) {
            $pname = ':' . str_replace('.', '_', $column);
            $conds[] = ($alias ? "$alias." : '') . "$column = $pname";
            $params[$pname] = $filters[$field];
        }
    }

    private function aActivo(array &$conds, array &$params, array $filters, string $alias = ''): void
    {
        $prefix = $alias ? "$alias." : '';
        if ($this->fVal($filters['activo'] ?? null)) {
            $conds[] = $prefix . 'activo = :activo';
            $params[':activo'] = (int)$filters['activo'];
        } else {
            $conds[] = $prefix . 'activo = 1';
        }
    }

    private function aDateRange(array &$conds, array &$params, array $filters, string $field, string $column): void
    {
        if ($this->fVal($filters[$field . '_desde'] ?? null)) {
            $conds[] = "$column >= :{$field}_desde";
            $params[":{$field}_desde"] = $filters[$field . '_desde'];
        }
        if ($this->fVal($filters[$field . '_hasta'] ?? null)) {
            $conds[] = "$column < :{$field}_hasta + INTERVAL 1 DAY";
            $params[":{$field}_hasta"] = $filters[$field . '_hasta'];
        }
    }

    private function reportPlantas(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters, 'p');
        $this->aCond($conds, $params, $filters, 'id_especie', 'id_especie', 'p');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT p.id_planta, p.nombre_comun, p.nombre_tecnico,
                           e.nombre_especie AS especie,
                           COALESCE((SELECT SUM(l2.cantidad_actual) FROM lote l2 WHERE l2.id_planta = p.id_planta AND l2.activo = 1), 0) AS stock_lotes
                    FROM plantas p
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    $where
                    ORDER BY p.nombre_comun ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $esp = $r['especie'] ?? 'Sin especie';
                $chartMap[$esp] = ($chartMap[$esp] ?? 0) + (int)$r['stock_lotes'];
            }

            return [
                'columns' => ['ID', 'Nombre Común', 'Nombre Técnico', 'Especie', 'Stock en Lotes'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Stock x Especie',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportPlantas: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportLotes(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters, 'l');
        $this->aCond($conds, $params, $filters, 'estado_lote', 'estado', 'l');
        $this->aCond($conds, $params, $filters, 'categoria', 'categoria', 'l');
        $this->aCond($conds, $params, $filters, 'id_ubicacion', 'id_ubicacion', 'l');
        $this->aCond($conds, $params, $filters, 'id_planta', 'id_planta', 'l');
        $this->aDateRange($conds, $params, $filters, 'fecha_siembra', 'l.fecha_siembra');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT l.id_lote, p.nombre_comun AS planta, e.nombre_especie AS especie,
                           u.nombre_ubicacion AS ubicacion, l.cantidad_inicial, l.cantidad_actual,
                           l.estado, l.categoria, l.fecha_siembra
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    $where
                    ORDER BY l.fecha_siembra DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estado'] ?? 'Sin estado';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Planta', 'Especie', 'Ubicación', 'Cant. Inicial', 'Cant. Actual', 'Estado', 'Categoría', 'Fecha Siembra'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'doughnut',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Lotes x Estado',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportLotes: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportInsumos(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters, 'i');
        $this->aCond($conds, $params, $filters, 'categoria', 'categoria', 'i');
        $this->aCond($conds, $params, $filters, 'id_unidad_medida', 'id_unidad_medida', 'i');
        if ($this->fVal($filters['stock_min'] ?? null)) {
            $conds[] = 'i.stock_actual >= :stock_min';
            $params[':stock_min'] = (float)$filters['stock_min'];
        }
        if ($this->fVal($filters['stock_max'] ?? null)) {
            $conds[] = 'i.stock_actual <= :stock_max';
            $params[':stock_max'] = (float)$filters['stock_max'];
        }
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT i.id_insumo, i.nombre_insumo, i.categoria, i.stock_actual,
                           u.nombre_unidad_medida AS unidad, i.costo_unitario_actual
                    FROM insumo i
                    LEFT JOIN unidad_medida u ON i.id_unidad_medida = u.id_unidad_medida AND u.activo = 1
                    $where
                    ORDER BY i.nombre_insumo ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Categoría', 'Stock Actual', 'Unidad', 'Costo Unit.'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_column($rows, 'nombre_insumo'),
                    'values' => array_map('floatval', array_column($rows, 'stock_actual')),
                    'label' => 'Stock Actual',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportInsumos: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportProveedores(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters);
        $where = $this->buildWhere($conds, $params);

        try {
            $stmt = $this->db()->prepare("SELECT id_proveedor, nombre_proveedor, rif_proveedor, contacto_vendedor, telefono_proveedor FROM proveedores $where ORDER BY nombre_proveedor ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $status = ($r['id_proveedor'] ? 'Activo' : 'Inactivo');
                $chartMap['Activos'] = ($chartMap['Activos'] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Nombre', 'RIF', 'Contacto', 'Teléfono'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'polarArea',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Proveedores',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportProveedores: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportClientes(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters);
        $where = $this->buildWhere($conds, $params);

        try {
            $stmt = $this->db()->prepare("SELECT id_cliente, CONCAT(nombre_cliente, ' ', apellido_cliente) AS nombre_cliente, tipo_cedula_cliente, cedula_cliente, contacto_cliente FROM cliente $where ORDER BY nombre_cliente ASC, apellido_cliente ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'C.I.', 'Contacto'],
                'rows' => $rows,
                'chart' => null,
            ];
        } catch (\Throwable $e) {
            error_log('Error reportClientes: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportTrabajadores(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters);
        $this->aCond($conds, $params, $filters, 'cargo', 'cargo');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT id_trabajador, nombre_trabajador, apellido_trabajador, cedula_trabajador, telefono_trabajador, cargo
                    FROM trabajadores $where ORDER BY nombre_trabajador ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $cargo = $r['cargo'] ?? 'Sin cargo';
                $chartMap[$cargo] = ($chartMap[$cargo] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Cargo'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'pie',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Trabajadores x Cargo',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportTrabajadores: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportTareas(array $filters): array
    {
        $conds = []; $params = [];
        $this->aCond($conds, $params, $filters, 'estatus_tarea', 'estatus_tarea', 'a');
        $this->aCond($conds, $params, $filters, 'id_trabajador', 'id_trabajador', 'a');
        $this->aDateRange($conds, $params, $filters, 'fecha_asignacion', 'a.fecha_asignacion');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT a.id_asignacion, t.nombre_tarea AS tarea,
                           CONCAT(tr.nombre_trabajador, ' ', tr.apellido_trabajador) AS trabajador,
                           l.id_lote AS lote_id, p.nombre_comun AS lote_planta,
                           a.fecha_asignacion, a.fecha_cumplimiento, a.estatus_tarea
                    FROM asignar_tarea a
                    LEFT JOIN tareas t ON a.id_tarea = t.id_tarea AND t.activo = 1
                    LEFT JOIN trabajadores tr ON a.id_trabajador = tr.id_trabajador AND tr.activo = 1
                    LEFT JOIN lote l ON a.id_lote = l.id_lote AND l.activo = 1
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    $where
                    ORDER BY a.fecha_asignacion DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estatus_tarea'] ?? 'Sin estado';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Tarea', 'Trabajador', 'Lote', 'Fecha Asignación', 'Fecha Cumplimiento', 'Estatus'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Tareas x Estatus',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportTareas: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportVentas(array $filters): array
    {
        $conds = []; $params = [];
        $conds[] = 'v.activo = 1';
        $this->aCond($conds, $params, $filters, 'estado', 'estado', 'v');
        $this->aCond($conds, $params, $filters, 'tipo_venta', 'tipo_venta', 'v');
        $this->aCond($conds, $params, $filters, 'id_trabajador', 'id_trabajador', 'v');
        $this->aDateRange($conds, $params, $filters, 'fecha_venta', 'v.fecha_venta');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT v.id_venta, v.referencia, CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS cliente,
                           CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS vendedor,
                           v.tipo_venta, v.estado,
                           COALESCE((SELECT SUM(dv.cantidad * dv.precio_unitario) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta), 0) AS total,
                           v.fecha_venta
                    FROM venta v
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente AND c.activo = 1
                    LEFT JOIN trabajadores t ON v.id_trabajador = t.id_trabajador AND t.activo = 1
                    $where
                    ORDER BY v.fecha_venta DESC LIMIT 500";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $mes = date('Y-m', strtotime($r['fecha_venta']));
                $chartMap[$mes] = ($chartMap[$mes] ?? 0) + (float)$r['total'];
            }

            return [
                'columns' => ['ID', 'Referencia', 'Cliente', 'Vendedor', 'Tipo', 'Estado', 'Total', 'Fecha'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'line',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Ventas x Mes (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportVentas: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportCompras(array $filters): array
    {
        $conds = []; $params = [];
        $conds[] = 'c.activo = 1';
        $this->aCond($conds, $params, $filters, 'estado', 'estado', 'c');
        $this->aCond($conds, $params, $filters, 'id_proveedor', 'id_proveedor', 'c');
        $this->aDateRange($conds, $params, $filters, 'fecha_compra', 'c.fecha_compra');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT c.id_compra, p.nombre_proveedor AS proveedor, c.fecha_compra,
                           c.tipo_comprobante, c.numero_comprobante, c.subtotal, c.iva, c.total, c.estado
                    FROM compra c
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    $where
                    ORDER BY c.fecha_compra DESC LIMIT 500";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $prov = $r['proveedor'] ?? 'Sin proveedor';
                $chartMap[$prov] = ($chartMap[$prov] ?? 0) + (float)$r['total'];
            }

            return [
                'columns' => ['ID', 'Proveedor', 'Fecha', 'Comprobante', 'N°', 'Subtotal', 'IVA', 'Total', 'Estado'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Total x Proveedor (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportCompras: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportHerramientas(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters, 'h');
        $this->aCond($conds, $params, $filters, 'tipo', 'tipo', 'h');
        $this->aCond($conds, $params, $filters, 'estado_herramienta', 'estado', 'h');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT h.id_herramienta, h.nombre_herramienta, h.tipo, h.estado,
                           h.fecha_adquisicion, h.fecha_ultimo_mantenimiento
                    FROM herramienta h
                    $where
                    ORDER BY h.nombre_herramienta ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estado'] ?? 'Sin estado';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Nombre', 'Tipo', 'Estado', 'Fecha Adq.', 'Últ. Mantenimiento'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'polarArea',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Herramientas x Estado',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportHerramientas: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportEspecies(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters, 'e');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT e.id_especie, e.nombre_especie, e.descripcion,
                           COUNT(p.id_planta) AS total_plantas
                    FROM especie e
                    LEFT JOIN plantas p ON p.id_especie = e.id_especie AND p.activo = 1
                    $where
                    GROUP BY e.id_especie, e.nombre_especie, e.descripcion
                    ORDER BY total_plantas DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Descripción', 'Total Plantas'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_column($rows, 'nombre_especie'),
                    'values' => array_map('intval', array_column($rows, 'total_plantas')),
                    'label' => 'Plantas x Especie',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportEspecies: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportInventario(array $filters): array
    {
        try {
            $nivelFilter = $filters['nivel_stock'] ?? '';
            $having = '';
            $params = [];
            if ($this->fVal($nivelFilter)) {
                $having = ' HAVING nivel_stock = :nivel_stock';
                $map = ['sin_stock' => 'Sin stock', 'bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];
                $params[':nivel_stock'] = $map[$nivelFilter] ?? $nivelFilter;
            }

            $sql = "SELECT
                        CASE
                            WHEN l.cantidad_actual <= 0 THEN 'Sin stock'
                            WHEN l.cantidad_actual < 20 THEN 'Bajo'
                            WHEN l.cantidad_actual < 50 THEN 'Medio'
                            ELSE 'Alto'
                        END AS nivel_stock,
                        COUNT(*) AS total_lotes,
                        SUM(l.cantidad_actual) AS total_plantas
                    FROM lote l
                    WHERE l.activo = 1
                    GROUP BY nivel_stock
                    $having
                    ORDER BY FIELD(nivel_stock, 'Alto', 'Medio', 'Bajo', 'Sin stock')";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['Nivel de Stock', 'Total Lotes', 'Total Plantas'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'doughnut',
                    'labels' => array_column($rows, 'nivel_stock'),
                    'values' => array_map('intval', array_column($rows, 'total_lotes')),
                    'label' => 'Distribución de Stock',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportInventario: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportRecoleccion(array $filters): array
    {
        $conds = []; $params = [];
        $conds[] = 'r.activo = 1';
        $this->aCond($conds, $params, $filters, 'estatus', 'estatus', 'r');
        $this->aCond($conds, $params, $filters, 'id_trabajador', 'id_trabajador', 'r');
        $this->aDateRange($conds, $params, $filters, 'fecha_asignacion', 'r.fecha_asignacion');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT r.id_recoleccion,
                           CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS trabajador,
                           u.nombre_ubicacion AS ubicacion,
                           r.fecha_asignacion, r.fecha_recoleccion, r.estatus,
                           (SELECT COUNT(*) FROM recoleccion_semillas_detalle d WHERE d.id_recoleccion = r.id_recoleccion) AS total_detalles
                    FROM recoleccion_semillas r
                    LEFT JOIN trabajadores t ON r.id_trabajador = t.id_trabajador
                    LEFT JOIN ubicacion u ON r.id_ubicacion = u.id_ubicacion
                    $where
                    ORDER BY r.fecha_asignacion DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estatus'] ?? 'Pendiente';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + 1;
            }

            return [
                'columns' => ['ID', 'Trabajador', 'Ubicación', 'Fecha Asig.', 'Fecha Recol.', 'Estatus', 'Detalles'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'pie',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Recolecciones x Estatus',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportRecoleccion: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportUbicaciones(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters);
        $where = $this->buildWhere($conds, $params);

        try {
            $stmt = $this->db()->prepare("SELECT id_ubicacion, nombre_ubicacion, descripcion, zona FROM ubicacion $where ORDER BY nombre_ubicacion ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Descripción', 'Zona'],
                'rows' => $rows,
                'chart' => null,
            ];
        } catch (\Throwable $e) {
            error_log('Error reportUbicaciones: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportUnidadesMedida(array $filters): array
    {
        $conds = []; $params = [];
        $this->aActivo($conds, $params, $filters);
        $where = $this->buildWhere($conds, $params);

        try {
            $stmt = $this->db()->prepare("SELECT id_unidad_medida, nombre_unidad_medida, simbolo FROM unidad_medida $where ORDER BY nombre_unidad_medida ASC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Símbolo'],
                'rows' => $rows,
                'chart' => null,
            ];
        } catch (\Throwable $e) {
            error_log('Error reportUnidadesMedida: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportCuentasPagar(array $filters): array
    {
        $conds = []; $params = [];
        $conds[] = 'cp.activo = 1';
        $this->aCond($conds, $params, $filters, 'estado', 'estado', 'cp');
        $this->aCond($conds, $params, $filters, 'id_proveedor', 'c.id_proveedor');
        $where = $this->buildWhere($conds, $params);

        try {
            $sql = "SELECT cp.id_cuenta_pagar, p.nombre_proveedor AS proveedor, cp.monto_total,
                           cp.saldo_pendiente, cp.fecha_vencimiento, cp.estado,
                           c.fecha_compra
                    FROM cuentas_pagar cp
                    JOIN compra c ON cp.id_compra = c.id_compra
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    $where
                    ORDER BY cp.created_at DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estado'] ?? 'pendiente';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + (float)$r['saldo_pendiente'];
            }

            return [
                'columns' => ['ID', 'Proveedor', 'Monto Total', 'Saldo Pendiente', 'Fecha Venc.', 'Estado', 'Fecha Compra'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'doughnut',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Saldo x Estado (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportCuentasPagar: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportPrecios(array $filters): array
    {
        $conds = []; $params = [];
        $this->aCond($conds, $params, $filters, 'id_lote', 'l.id_lote');

        try {
            $sql = "SELECT c.id_calculo, p.nombre_comun AS planta, l.id_lote, l.cantidad_actual,
                           c.costo_mano_obra, c.costo_total_insumo, c.porcentaje_ganancia,
                           c.precio_final_sugerido, c.fecha_calculo
                    FROM calculo_precio c
                    LEFT JOIN lote l ON c.id_lote = l.id_lote
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta";
            if (!empty($conds)) {
                $sql .= ' WHERE ' . implode(' AND ', $conds);
            }
            $sql .= " ORDER BY c.fecha_calculo DESC";

            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartLabels = array_map(fn($r) => $r['planta'] . ' (Lote #' . $r['id_lote'] . ')', $rows);
            $chartValues = array_map('floatval', array_column($rows, 'precio_final_sugerido'));

            return [
                'columns' => ['ID', 'Planta', 'Lote', 'Stock', 'Mano Obra', 'Costo Insumos', '% Ganancia', 'Precio Final', 'Fecha Cálculo'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => $chartLabels,
                    'values' => $chartValues,
                    'label' => 'Precio Final Sugerido (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportPrecios: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportCuentasCobrar(array $filters): array
    {
        $conds = []; $params = [];
        $conds[] = "v.activo = 1 AND v.tipo_venta = 'credito'";
        $this->aDateRange($conds, $params, $filters, 'fecha_venta', 'v.fecha_venta');

        $having = '';
        if ($this->fVal($filters['estado_cuenta'] ?? null)) {
            $having = ' HAVING estado_cuenta = :estado_cuenta';
            $params[':estado_cuenta'] = $filters['estado_cuenta'];
        }

        try {
            $sql = "SELECT
                        v.id_venta, v.referencia, CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente, v.fecha_venta, v.fecha_vencimiento,
                        COALESCE(det.monto_total, 0) AS monto_total,
                        COALESCE(pag.total_pagado, 0) AS total_pagado,
                        ROUND(COALESCE(det.monto_total, 0) - COALESCE(pag.total_pagado, 0), 2) AS saldo_pendiente,
                        CASE
                            WHEN COALESCE(pag.total_pagado, 0) >= COALESCE(det.monto_total, 0) THEN 'pagado'
                            WHEN v.fecha_vencimiento IS NOT NULL AND v.fecha_vencimiento < CURDATE() THEN 'vencido'
                            ELSE 'vigente'
                        END AS estado_cuenta
                    FROM venta v
                    INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                    LEFT JOIN (SELECT id_venta, SUM(cantidad * precio_unitario) AS monto_total FROM detalle_venta GROUP BY id_venta) det ON v.id_venta = det.id_venta
                    LEFT JOIN (SELECT id_venta, SUM(monto) AS total_pagado FROM pago_venta WHERE estado_pago != 'rechazado' GROUP BY id_venta) pag ON v.id_venta = pag.id_venta";
            if (!empty($conds)) {
                $sql .= ' WHERE ' . implode(' AND ', $conds);
            }
            $sql .= $having . " ORDER BY v.fecha_vencimiento ASC";

            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $est = $r['estado_cuenta'] ?? 'vigente';
                $chartMap[$est] = ($chartMap[$est] ?? 0) + 1;
            }

            return [
                'columns' => ['ID Venta', 'Referencia', 'Cliente', 'Fecha Venta', 'Vencimiento', 'Total', 'Pagado', 'Saldo', 'Estado'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'pie',
                    'labels' => array_keys($chartMap),
                    'values' => array_values($chartMap),
                    'label' => 'Cuentas x Cobrar x Estado',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportCuentasCobrar: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }
}
