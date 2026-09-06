<?php

namespace SysInescolara\models;

use SysInescolara\core\Database;
use PDO;

class Reports extends Database
{
    private array $modules = [];
    private array $schemaCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = $this->buildModules();
    }

    private function buildModules(): array
    {
        return [
            'plantas'          => ['id' => 'plantas', 'nombre' => 'Plantas', 'icono' => 'fa-seedling', 'has_chart' => true],
            'lotes'            => ['id' => 'lotes', 'nombre' => 'Lotes', 'icono' => 'fa-boxes', 'has_chart' => true],
            'insumos'          => ['id' => 'insumos', 'nombre' => 'Insumos', 'icono' => 'fa-flask', 'has_chart' => true],
            'proveedores'      => ['id' => 'proveedores', 'nombre' => 'Proveedores', 'icono' => 'fa-truck', 'has_chart' => true],
            'clientes'         => ['id' => 'clientes', 'nombre' => 'Clientes', 'icono' => 'fa-users', 'has_chart' => false],
            'trabajadores'     => ['id' => 'trabajadores', 'nombre' => 'Trabajadores', 'icono' => 'fa-user-hard-hat', 'has_chart' => true],
            'tareas'           => ['id' => 'tareas', 'nombre' => 'Tareas', 'icono' => 'fa-tasks', 'has_chart' => true],
            'ventas'           => ['id' => 'ventas', 'nombre' => 'Ventas', 'icono' => 'fa-shopping-cart', 'has_chart' => true],
            'compras'          => ['id' => 'compras', 'nombre' => 'Compras', 'icono' => 'fa-file-invoice', 'has_chart' => true],
            'herramientas'     => ['id' => 'herramientas', 'nombre' => 'Herramientas', 'icono' => 'fa-wrench', 'has_chart' => true],
            'especies'         => ['id' => 'especies', 'nombre' => 'Especies', 'icono' => 'fa-leaf', 'has_chart' => true],
            'inventario'       => ['id' => 'inventario', 'nombre' => 'Inventario', 'icono' => 'fa-chart-pie', 'has_chart' => true],
            'recoleccion'      => ['id' => 'recoleccion', 'nombre' => 'Recolección', 'icono' => 'fa-hand-holding-heart', 'has_chart' => true],
            'ubicaciones'      => ['id' => 'ubicaciones', 'nombre' => 'Ubicaciones', 'icono' => 'fa-map-marker-alt', 'has_chart' => false],
            'unidades_medida'  => ['id' => 'unidades_medida', 'nombre' => 'Unidades Medida', 'icono' => 'fa-ruler', 'has_chart' => false],
            'cuentas_pagar'    => ['id' => 'cuentas_pagar', 'nombre' => 'Cuentas x Pagar', 'icono' => 'fa-money-bill-wave', 'has_chart' => true],
            'precios'          => ['id' => 'precios', 'nombre' => 'Precios', 'icono' => 'fa-tag', 'has_chart' => true],
            'cuentas_cobrar'   => ['id' => 'cuentas_cobrar', 'nombre' => 'Cuentas x Cobrar', 'icono' => 'fa-hand-holding-usd', 'has_chart' => true],
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

    private function sanitizeOp(string $op): string
    {
        return in_array($op, ['=', '>', '<', '>=', '<=', '!='], true) ? $op : '>=';
    }

    private function pName(string $field): string
    {
        return ':' . str_replace(['.', '-', ' '], '_', $field);
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
        } catch (\Throwable $e) {
            return $this->schemaCache[$key] = false;
        }
    }

    // ---------------------------------------------------------------------
    // Utilidades para definición de filtros
    // ---------------------------------------------------------------------

    private function optActivo(): array
    {
        return [
            ['value' => '', 'label' => 'Todos'],
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
        ];
    }

    private function defActivo(string $column): array
    {
        return ['field' => 'activo', 'label' => 'Estado', 'type' => 'select', 'column' => $column, 'options' => $this->optActivo()];
    }

    private function defText(string $field, string $label, string $column): array
    {
        return ['field' => $field, 'label' => $label, 'type' => 'text', 'column' => $column];
    }

    private function defNumber(string $field, string $label, string $column): array
    {
        return ['field' => $field, 'label' => $label, 'type' => 'number', 'column' => $column];
    }

    private function defDateRange(string $field, string $label, string $column): array
    {
        return ['field' => $field, 'label' => $label, 'type' => 'date-range', 'column' => $column];
    }

    private function defSelect(string $field, string $label, string $column, array $options): array
    {
        return ['field' => $field, 'label' => $label, 'type' => 'select', 'column' => $column, 'options' => $options];
    }

    private function selectFromQuery(string $field, string $label, string $sql, string $valueCol, string $textCol, string $column = ''): array
    {
        $options = $this->fetchOptions($sql, $valueCol, $textCol);
        return ['field' => $field, 'label' => $label, 'type' => 'select', 'column' => $column ?: $field, 'options' => $options];
    }

    private function fetchOptions(string $sql, string $valueCol, string $textCol): array
    {
        $options = [['value' => '', 'label' => 'Todos']];
        try {
            $stmt = $this->db()->query($sql);
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                $options[] = ['value' => $row[$valueCol], 'label' => $row[$textCol]];
            }
        } catch (\Throwable $e) {
        }
        return $options;
    }

    private function distinctOptions(string $sql, string $col): array
    {
        $options = [['value' => '', 'label' => 'Todos']];
        try {
            $stmt = $this->db()->query($sql);
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] as $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                $options[] = ['value' => $v, 'label' => $v];
            }
        } catch (\Throwable $e) {
        }
        return $options;
    }

    // ---------------------------------------------------------------------
    // Definiciones de filtros por módulo
    // ---------------------------------------------------------------------

    public function getModuleFilters(string $module): array
    {
        $method = 'filters' . $this->camelize($module);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return [$this->defActivo('activo')];
    }

    private function camelize(string $module): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
    }

    private function filtersPlantas(): array
    {
        return [
            $this->defActivo('p.activo'),
            $this->defSelect('id_especie', 'Especie', 'p.id_especie',
                $this->fetchOptions(
                    "SELECT id_especie AS value, nombre_especie AS label FROM especie WHERE activo = 1 ORDER BY nombre_especie ASC",
                    'value', 'label'
                )),
            $this->defText('nombre_comun', 'Nombre común', 'p.nombre_comun'),
            $this->defText('nombre_tecnico', 'Nombre técnico', 'p.nombre_tecnico'),
            ['field' => 'stock_lotes_min', 'label' => 'Stock en lotes (mín.)', 'type' => 'number', 'column' => 'stock_lotes', 'manual' => true],
            ['field' => 'stock_lotes_max', 'label' => 'Stock en lotes (máx.)', 'type' => 'number', 'column' => 'stock_lotes', 'manual' => true],
        ];
    }

    private function filtersLotes(): array
    {
        $fkEstado = $this->hasColumn('lote', 'id_estado');
        $fkCat = $this->hasColumn('lote', 'id_categoria');
        $fkOrigen = $this->hasColumn('lote', 'id_origen');

        $estadoOpts = $fkEstado
            ? $this->fetchOptions(
                "SELECT e.id_estado AS value, e.nombre AS label FROM estado e INNER JOIN lote l ON e.id_estado = l.id_estado GROUP BY e.id_estado, e.nombre ORDER BY e.nombre ASC",
                'value', 'label'
            )
            : $this->distinctOptions("SELECT DISTINCT estado FROM lote WHERE estado IS NOT NULL AND estado != '' ORDER BY estado ASC", 'estado');

        $catOpts = $fkCat
            ? $this->fetchOptions(
                "SELECT c.id_categoria AS value, c.nombre AS label FROM categoria c INNER JOIN lote l ON c.id_categoria = l.id_categoria GROUP BY c.id_categoria, c.nombre ORDER BY c.nombre ASC",
                'value', 'label'
            )
            : $this->distinctOptions("SELECT DISTINCT categoria FROM lote WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC", 'categoria');

        $origenOpts = $fkOrigen
            ? $this->fetchOptions(
                "SELECT o.id_origen AS value, o.nombre AS label FROM origen o INNER JOIN lote l ON o.id_origen = l.id_origen GROUP BY o.id_origen, o.nombre ORDER BY o.nombre ASC",
                'value', 'label'
            )
            : $this->distinctOptions("SELECT DISTINCT origen FROM lote WHERE origen IS NOT NULL AND origen != '' ORDER BY origen ASC", 'origen');

        return [
            $this->defActivo('l.activo'),
            $this->defSelect('estado_lote', 'Estado del Lote', $fkEstado ? 'l.id_estado' : 'l.estado', $estadoOpts),
            $this->defSelect('categoria', 'Categoría', $fkCat ? 'l.id_categoria' : 'l.categoria', $catOpts),
            $this->defSelect('origen', 'Origen', $fkOrigen ? 'l.id_origen' : 'l.origen', $origenOpts),
            $this->selectFromQuery('id_planta', 'Planta',
                "SELECT id_planta AS value, nombre_comun AS label FROM plantas WHERE activo = 1 ORDER BY nombre_comun ASC", 'value', 'label', 'l.id_planta'),
            $this->selectFromQuery('id_ubicacion', 'Ubicación',
                "SELECT id_ubicacion AS value, nombre_ubicacion AS label FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC", 'value', 'label', 'l.id_ubicacion'),
            $this->defDateRange('fecha_siembra', 'Fecha Siembra', 'l.fecha_siembra'),
            $this->defNumber('cantidad_inicial', 'Cantidad inicial', 'l.cantidad_inicial'),
            $this->defNumber('cantidad_actual', 'Cantidad actual', 'l.cantidad_actual'),
            $this->defNumber('costo_unitario', 'Costo unitario', 'l.costo_unitario'),
        ];
    }

    private function filtersInsumos(): array
    {
        return [
            $this->defActivo('i.activo'),
            $this->defSelect('categoria', 'Categoría', 'i.categoria',
                $this->distinctOptions("SELECT DISTINCT categoria FROM insumo WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC", 'categoria')),
            $this->selectFromQuery('id_unidad_medida', 'Unidad Medida',
                "SELECT id_unidad_medida AS value, nombre_unidad_medida AS label FROM unidad_medida WHERE activo = 1 ORDER BY nombre_unidad_medida ASC", 'value', 'label', 'i.id_unidad_medida'),
            $this->defText('nombre_insumo', 'Nombre', 'i.nombre_insumo'),
            $this->defNumber('stock_actual', 'Stock actual', 'i.stock_actual'),
            $this->defNumber('costo_unitario_actual', 'Costo unitario', 'i.costo_unitario_actual'),
        ];
    }

    private function filtersProveedores(): array
    {
        return [
            $this->defActivo('p.activo'),
            $this->defText('nombre_proveedor', 'Nombre', 'p.nombre_proveedor'),
            $this->defText('rif_proveedor', 'RIF', 'p.rif_proveedor'),
            $this->defText('contacto_vendedor', 'Contacto', 'p.contacto_vendedor'),
            $this->defText('telefono_proveedor', 'Teléfono', 'p.telefono_proveedor'),
            ['field' => 'con_compras', 'label' => 'Con compras', 'type' => 'select', 'column' => '', 'manual' => true,
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'si', 'label' => 'Con compras'],
                    ['value' => 'no', 'label' => 'Sin compras'],
                ]],
            ['field' => 'numero_compras', 'label' => 'N° compras (mín.)', 'type' => 'number', 'column' => 'numero_compras', 'manual' => true],
            ['field' => 'total_compras_min', 'label' => 'Total comprado (mín.)', 'type' => 'number', 'column' => 'total_compras', 'manual' => true],
            ['field' => 'total_compras_max', 'label' => 'Total comprado (máx.)', 'type' => 'number', 'column' => 'total_compras', 'manual' => true],
        ];
    }

    private function filtersClientes(): array
    {
        return [
            $this->defActivo('c.activo'),
            $this->defSelect('tipo_cedula_cliente', 'Tipo C.I.', 'c.tipo_cedula_cliente',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'V', 'label' => 'V'],
                    ['value' => 'E', 'label' => 'E'],
                    ['value' => 'J', 'label' => 'J'],
                    ['value' => 'G', 'label' => 'G'],
                    ['value' => 'P', 'label' => 'P'],
                ]),
            $this->defText('nombre_cliente', 'Nombre', 'c.nombre_cliente'),
            $this->defText('apellido_cliente', 'Apellido', 'c.apellido_cliente'),
            $this->defText('cedula_cliente', 'Cédula', 'c.cedula_cliente'),
            $this->defText('contacto_cliente', 'Contacto', 'c.contacto_cliente'),
            ['field' => 'con_ventas', 'label' => 'Con ventas', 'type' => 'select', 'column' => '', 'manual' => true,
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'si', 'label' => 'Con ventas'],
                    ['value' => 'no', 'label' => 'Sin ventas'],
                ]],
            ['field' => 'numero_ventas', 'label' => 'N° ventas (mín.)', 'type' => 'number', 'column' => 'numero_ventas', 'manual' => true],
            ['field' => 'total_compras_min', 'label' => 'Monto comprado (mín.)', 'type' => 'number', 'column' => 'total_compras', 'manual' => true],
            ['field' => 'total_compras_max', 'label' => 'Monto comprado (máx.)', 'type' => 'number', 'column' => 'total_compras', 'manual' => true],
        ];
    }

    private function filtersTrabajadores(): array
    {
        return [
            $this->defActivo('tr.activo'),
            $this->defSelect('cargo', 'Cargo', 'tr.cargo',
                $this->distinctOptions("SELECT DISTINCT cargo FROM `SysInescolara-Seguridad`.`usuarios` WHERE cargo IS NOT NULL AND cargo != '' ORDER BY cargo ASC", 'cargo')),
            $this->defText('nombre_trabajador', 'Nombre', 'tr.nombre_trabajador'),
            $this->defText('apellido_trabajador', 'Apellido', 'tr.apellido_trabajador'),
            $this->defText('cedula_trabajador', 'Cédula', 'tr.cedula_trabajador'),
            $this->defText('telefono_trabajador', 'Teléfono', 'tr.telefono_trabajador'),
            ['field' => 'tareas_estatus', 'label' => 'Con tareas en estatus', 'type' => 'select', 'column' => '', 'manual' => true,
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]],
            ['field' => 'fecha_asignacion', 'label' => 'Tareas asignadas entre', 'type' => 'date-range', 'column' => 'at.fecha_asignacion', 'manual' => true],
            ['field' => 'numero_tareas', 'label' => 'N° tareas (mín.)', 'type' => 'number', 'column' => 'numero_tareas', 'manual' => true],
            ['field' => 'tareas_pendientes', 'label' => 'Tareas pendientes (mín.)', 'type' => 'number', 'column' => 'tareas_pendientes', 'manual' => true],
        ];
    }

    private function filtersTareas(): array
    {
        return [
            $this->defSelect('estatus_tarea', 'Estatus', 'a.estatus_tarea',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]),
            $this->selectFromQuery('id_usuario', 'Trabajador',
                "SELECT id_usuario AS value, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS label FROM `SysInescolara-Seguridad`.`usuarios` WHERE nombre_trabajador IS NOT NULL AND nombre_trabajador != '' ORDER BY nombre_trabajador ASC", 'value', 'label', 'a.id_usuario'),
            $this->selectFromQuery('id_tarea', 'Tarea',
                "SELECT id_tarea AS value, nombre_tarea AS label FROM tareas WHERE activo = 1 ORDER BY nombre_tarea ASC", 'value', 'label', 'a.id_tarea'),
            $this->selectFromQuery('id_lote', 'Lote',
                "SELECT l.id_lote AS value, CONCAT('Lote #', l.id_lote, ' - ', p.nombre_comun) AS label FROM lote l LEFT JOIN plantas p ON l.id_planta = p.id_planta WHERE l.activo = 1 ORDER BY l.id_lote DESC", 'value', 'label', 'a.id_lote'),
            $this->defDateRange('fecha_asignacion', 'Fecha Asignación', 'a.fecha_asignacion'),
            $this->defDateRange('fecha_cumplimiento', 'Fecha Cumplimiento', 'a.fecha_cumplimiento'),
            $this->defNumber('horas_dedicadas', 'Horas dedicadas', 'a.horas_dedicadas'),
        ];
    }

    private function filtersVentas(): array
    {
        return [
            $this->defText('referencia', 'Referencia', 'v.referencia'),
            $this->defSelect('estado', 'Estado', 'v.estado',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]),
            $this->defSelect('tipo_venta', 'Tipo Venta', 'v.tipo_venta',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'contado', 'label' => 'Contado'],
                    ['value' => 'credito', 'label' => 'Crédito'],
                ]),
            $this->selectFromQuery('id_cliente', 'Cliente',
                "SELECT id_cliente AS value, CONCAT(nombre_cliente, ' ', apellido_cliente) AS label FROM cliente WHERE activo = 1 ORDER BY nombre_cliente ASC", 'value', 'label', 'v.id_cliente'),
            $this->selectFromQuery('id_usuario', 'Vendedor',
                "SELECT id_usuario AS value, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS label FROM `SysInescolara-Seguridad`.`usuarios` WHERE nombre_trabajador IS NOT NULL AND nombre_trabajador != '' ORDER BY nombre_trabajador ASC", 'value', 'label', 'v.id_usuario'),
            $this->defDateRange('fecha_venta', 'Fecha Venta', 'v.fecha_venta'),
            $this->defDateRange('fecha_vencimiento', 'Fecha Vencimiento', 'v.fecha_vencimiento'),
            ['field' => 'total_min', 'label' => 'Total mayor o menor a', 'type' => 'number', 'column' => 'total', 'manual' => true],
            ['field' => 'items_min', 'label' => 'N° de items (mín.)', 'type' => 'number', 'column' => 'items', 'manual' => true],
        ];
    }

    private function filtersCompras(): array
    {
        return [
            $this->defSelect('estado', 'Estado', 'c.estado',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'recibida', 'label' => 'Recibida'],
                    ['value' => 'pagada', 'label' => 'Pagada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ]),
            $this->selectFromQuery('id_proveedor', 'Proveedor',
                "SELECT id_proveedor AS value, nombre_proveedor AS label FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC", 'value', 'label', 'c.id_proveedor'),
            $this->defSelect('tipo_comprobante', 'Tipo comprobante', 'c.tipo_comprobante',
                $this->distinctOptions("SELECT DISTINCT tipo_comprobante FROM compra WHERE tipo_comprobante IS NOT NULL AND tipo_comprobante != '' ORDER BY tipo_comprobante ASC", 'tipo_comprobante')),
            $this->defText('numero_comprobante', 'N° comprobante', 'c.numero_comprobante'),
            $this->defDateRange('fecha_compra', 'Fecha Compra', 'c.fecha_compra'),
            $this->defNumber('total', 'Total', 'c.total'),
            $this->defNumber('subtotal', 'Subtotal', 'c.subtotal'),
            $this->defNumber('iva', 'IVA', 'c.iva'),
        ];
    }

    private function filtersHerramientas(): array
    {
        return [
            $this->defActivo('h.activo'),
            $this->defText('nombre_herramienta', 'Nombre', 'h.nombre_herramienta'),
            $this->defSelect('tipo', 'Tipo', 'h.tipo',
                $this->distinctOptions("SELECT DISTINCT tipo FROM herramienta WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo ASC", 'tipo')),
            $this->defSelect('estado', 'Estado', 'h.estado',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'disponible', 'label' => 'Disponible'],
                    ['value' => 'en_uso', 'label' => 'En uso'],
                    ['value' => 'requiere_mantenimiento', 'label' => 'Requiere mantenimiento'],
                    ['value' => 'danada', 'label' => 'Dañada'],
                    ['value' => 'baja', 'label' => 'Baja'],
                ]),
            $this->defNumber('cantidad', 'Cantidad', 'h.cantidad'),
            $this->defDateRange('fecha_adquisicion', 'Fecha Adquisición', 'h.fecha_adquisicion'),
            $this->defDateRange('fecha_ultimo_mantenimiento', 'Últ. Mantenimiento', 'h.fecha_ultimo_mantenimiento'),
        ];
    }

    private function filtersEspecies(): array
    {
        return [
            $this->defActivo('e.activo'),
            $this->defText('nombre_especie', 'Nombre', 'e.nombre_especie'),
            ['field' => 'total_plantas', 'label' => 'N° plantas', 'type' => 'number', 'column' => 'total_plantas', 'manual' => true],
        ];
    }

    private function filtersInventario(): array
    {
        $fkEstado = $this->hasColumn('lote', 'id_estado');
        $fkCat = $this->hasColumn('lote', 'id_categoria');
        $estadoOpts = $fkEstado
            ? $this->fetchOptions(
                "SELECT e.id_estado AS value, e.nombre AS label FROM estado e INNER JOIN lote l ON e.id_estado = l.id_estado GROUP BY e.id_estado, e.nombre ORDER BY e.nombre ASC",
                'value', 'label'
            )
            : $this->distinctOptions("SELECT DISTINCT estado FROM lote WHERE estado IS NOT NULL AND estado != '' ORDER BY estado ASC", 'estado');
        $catOpts = $fkCat
            ? $this->fetchOptions(
                "SELECT c.id_categoria AS value, c.nombre AS label FROM categoria c INNER JOIN lote l ON c.id_categoria = l.id_categoria GROUP BY c.id_categoria, c.nombre ORDER BY c.nombre ASC",
                'value', 'label'
            )
            : $this->distinctOptions("SELECT DISTINCT categoria FROM lote WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC", 'categoria');

        return [
            ['field' => 'nivel_stock', 'label' => 'Nivel de Stock', 'type' => 'select', 'column' => '', 'manual' => true,
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'sin_stock', 'label' => 'Sin stock'],
                    ['value' => 'bajo', 'label' => 'Bajo'],
                    ['value' => 'medio', 'label' => 'Medio'],
                    ['value' => 'alto', 'label' => 'Alto'],
                ]],
            $this->selectFromQuery('id_planta', 'Planta',
                "SELECT id_planta AS value, nombre_comun AS label FROM plantas WHERE activo = 1 ORDER BY nombre_comun ASC", 'value', 'label', 'l.id_planta'),
            $this->selectFromQuery('id_ubicacion', 'Ubicación',
                "SELECT id_ubicacion AS value, nombre_ubicacion AS label FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC", 'value', 'label', 'l.id_ubicacion'),
            $this->defSelect('estado', 'Estado del Lote', $fkEstado ? 'l.id_estado' : 'l.estado', $estadoOpts),
            $this->defSelect('categoria', 'Categoría', $fkCat ? 'l.id_categoria' : 'l.categoria', $catOpts),
            $this->defNumber('cantidad_actual', 'Cantidad actual', 'l.cantidad_actual'),
        ];
    }

    private function filtersRecoleccion(): array
    {
        return [
            $this->defSelect('estatus', 'Estatus', 'r.estatus',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'Pendiente', 'label' => 'Pendiente'],
                    ['value' => 'Realizada', 'label' => 'Realizada'],
                ]),
            $this->selectFromQuery('id_usuario', 'Trabajador',
                "SELECT id_usuario AS value, CONCAT(nombre_trabajador, ' ', apellido_trabajador) AS label FROM `SysInescolara-Seguridad`.`usuarios` WHERE nombre_trabajador IS NOT NULL AND nombre_trabajador != '' ORDER BY nombre_trabajador ASC", 'value', 'label', 'r.id_usuario'),
            $this->selectFromQuery('id_ubicacion', 'Ubicación',
                "SELECT id_ubicacion AS value, nombre_ubicacion AS label FROM ubicacion WHERE activo = 1 ORDER BY nombre_ubicacion ASC", 'value', 'label', 'r.id_ubicacion'),
            $this->defDateRange('fecha_asignacion', 'Fecha Asignación', 'r.fecha_asignacion'),
            $this->defDateRange('fecha_recoleccion', 'Fecha Recolección', 'r.fecha_recoleccion'),
        ];
    }

    private function filtersUbicaciones(): array
    {
        return [
            $this->defActivo('u.activo'),
            $this->defText('nombre_ubicacion', 'Nombre', 'u.nombre_ubicacion'),
            $this->defText('zona', 'Zona', 'u.zona'),
            $this->defText('descripcion', 'Descripción', 'u.descripcion'),
        ];
    }

    private function filtersUnidadesMedida(): array
    {
        return [
            $this->defActivo('u.activo'),
            $this->defText('nombre_unidad_medida', 'Nombre', 'u.nombre_unidad_medida'),
            $this->defText('simbolo', 'Símbolo', 'u.simbolo'),
        ];
    }

    private function filtersCuentasPagar(): array
    {
        return [
            $this->defSelect('estado', 'Estado', 'cp.estado',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'parcial', 'label' => 'Parcial'],
                    ['value' => 'pagada', 'label' => 'Pagada'],
                ]),
            $this->selectFromQuery('id_proveedor', 'Proveedor',
                "SELECT id_proveedor AS value, nombre_proveedor AS label FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC", 'value', 'label', 'c.id_proveedor'),
            $this->defNumber('monto_total', 'Monto total', 'cp.monto_total'),
            $this->defNumber('saldo_pendiente', 'Saldo pendiente', 'cp.saldo_pendiente'),
            $this->defDateRange('fecha_vencimiento', 'Fecha Vencimiento', 'cp.fecha_vencimiento'),
            $this->defDateRange('fecha_compra', 'Fecha Compra', 'c.fecha_compra'),
        ];
    }

    private function filtersPrecios(): array
    {
        return [
            $this->selectFromQuery('id_lote', 'Lote',
                "SELECT l.id_lote AS value, CONCAT('Lote #', l.id_lote, ' - ', p.nombre_comun) AS label FROM lote l LEFT JOIN plantas p ON l.id_planta = p.id_planta WHERE l.activo = 1 ORDER BY l.id_lote DESC", 'value', 'label', 'cp.id_lote'),
            $this->defSelect('vigente', 'Vigente', 'cp.vigente',
                [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => '1', 'label' => 'Vigente'],
                    ['value' => '0', 'label' => 'No vigente'],
                ]),
            $this->defDateRange('fecha_calculo', 'Fecha Cálculo', 'cp.fecha_calculo'),
            $this->defNumber('precio_final_sugerido', 'Precio final', 'cp.precio_final_sugerido'),
            $this->defNumber('costo_mano_obra', 'Costo mano de obra', 'cp.costo_mano_obra'),
            $this->defNumber('costo_total_insumo', 'Costo insumos', 'cp.costo_total_insumo'),
            $this->defNumber('porcentaje_ganancia', '% Ganancia', 'cp.porcentaje_ganancia'),
        ];
    }

    private function filtersCuentasCobrar(): array
    {
        return [
            ['field' => 'estado_cuenta', 'label' => 'Estado', 'type' => 'select', 'column' => '', 'manual' => true,
                'options' => [
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'vigente', 'label' => 'Vigente'],
                    ['value' => 'vencido', 'label' => 'Vencido'],
                    ['value' => 'pagado', 'label' => 'Pagado'],
                ]],
            $this->selectFromQuery('id_cliente', 'Cliente',
                "SELECT id_cliente AS value, CONCAT(nombre_cliente, ' ', apellido_cliente) AS label FROM cliente WHERE activo = 1 ORDER BY nombre_cliente ASC", 'value', 'label', 'v.id_cliente'),
            $this->defDateRange('fecha_venta', 'Fecha Venta', 'v.fecha_venta'),
            $this->defDateRange('fecha_vencimiento', 'Fecha Vencimiento', 'v.fecha_vencimiento'),
            ['field' => 'monto_total_min', 'label' => 'Monto total (mín.)', 'type' => 'number', 'column' => 'monto_total', 'manual' => true],
            ['field' => 'monto_total_max', 'label' => 'Monto total (máx.)', 'type' => 'number', 'column' => 'monto_total', 'manual' => true],
            ['field' => 'saldo_pendiente_min', 'label' => 'Saldo pendiente (mín.)', 'type' => 'number', 'column' => 'saldo_pendiente', 'manual' => true],
            ['field' => 'saldo_pendiente_max', 'label' => 'Saldo pendiente (máx.)', 'type' => 'number', 'column' => 'saldo_pendiente', 'manual' => true],
        ];
    }

    // ---------------------------------------------------------------------
    // Construcción de condiciones WHERE
    // ---------------------------------------------------------------------

    private function buildWhere(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function applyDefs(array $filters, array $defs, array &$conds, array &$params): void
    {
        foreach ($defs as $def) {
            if (!empty($def['manual'])) {
                continue;
            }
            $field = $def['field'];
            $type = $def['type'] ?? 'text';
            $column = $def['column'] ?? $field;

            if (empty($column)) {
                continue;
            }

            if ($type === 'text') {
                if ($this->fVal($filters[$field] ?? null)) {
                    $p = $this->pName($field);
                    $conds[] = "$column LIKE $p";
                    $params[$p] = '%' . $filters[$field] . '%';
                }
                continue;
            }

            if ($type === 'select' || $type === 'boolean') {
                if ($this->fVal($filters[$field] ?? null)) {
                    $p = $this->pName($field);
                    $conds[] = "$column = $p";
                    $params[$p] = $filters[$field];
                }
                continue;
            }

            if ($type === 'date-range') {
                if ($this->fVal($filters[$field . '_desde'] ?? null)) {
                    $conds[] = "$column >= :{$field}_desde";
                    $params[":{$field}_desde"] = $filters[$field . '_desde'];
                }
                if ($this->fVal($filters[$field . '_hasta'] ?? null)) {
                    $conds[] = "$column < :{$field}_hasta + INTERVAL 1 DAY";
                    $params[":{$field}_hasta"] = $filters[$field . '_hasta'];
                }
                continue;
            }

            if ($type === 'number') {
                if ($this->fVal($filters[$field] ?? null)) {
                    $op = $this->sanitizeOp($filters[$field . '_op'] ?? '>=');
                    $p = $this->pName($field);
                    $conds[] = "$column $op $p";
                    $params[$p] = (float) $filters[$field];
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Reportes
    // ---------------------------------------------------------------------

    public function getReportData(string $module, array $filters): array
    {
        $method = 'report' . $this->camelize($module);
        if (method_exists($this, $method)) {
            return $this->$method($filters);
        }
        return ['columns' => [], 'rows' => [], 'chart' => null];
    }

    private function reportPlantas(array $filters): array
    {
        $defs = $this->filtersPlantas();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        if ($this->fVal($filters['stock_lotes_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['stock_lotes_min_op'] ?? '>=');
            $conds[] = "(SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta) $op :stock_lotes_min";
            $params[':stock_lotes_min'] = (float) $filters['stock_lotes_min'];
        }
        if ($this->fVal($filters['stock_lotes_max'] ?? null)) {
            $op = $this->sanitizeOp($filters['stock_lotes_max_op'] ?? '<=');
            $conds[] = "(SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta) $op :stock_lotes_max";
            $params[':stock_lotes_max'] = (float) $filters['stock_lotes_max'];
        }
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT p.id_planta, p.nombre_comun, p.nombre_tecnico,
                           e.nombre_especie AS especie,
                           (SELECT COALESCE(SUM(l2.cantidad_actual), 0) FROM lote l2 WHERE l2.id_planta = p.id_planta) AS stock_lotes
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
                $chartMap[$esp] = ($chartMap[$esp] ?? 0) + (int) $r['stock_lotes'];
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
        $fkEstado = $this->hasColumn('lote', 'id_estado');
        $fkCat = $this->hasColumn('lote', 'id_categoria');
        $fkOrigen = $this->hasColumn('lote', 'id_origen');

        $defs = $this->filtersLotes();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        $joinEstado = $fkEstado ? 'LEFT JOIN estado es ON l.id_estado = es.id_estado' : '';
        $joinCategoria = $fkCat ? 'LEFT JOIN categoria ca ON l.id_categoria = ca.id_categoria' : '';
        $joinOrigen = $fkOrigen ? 'LEFT JOIN origen o ON l.id_origen = o.id_origen' : '';
        $estadoSel = $fkEstado ? 'es.nombre AS estado' : 'l.estado';
        $categoriaSel = $fkCat ? 'ca.nombre AS categoria' : 'l.categoria';
        $origenSel = $fkOrigen ? 'o.nombre AS origen' : 'l.origen';

        try {
            $sql = "SELECT l.id_lote, p.nombre_comun AS planta, e.nombre_especie AS especie,
                           u.nombre_ubicacion AS ubicacion, l.cantidad_inicial, l.cantidad_actual,
                           $estadoSel, $categoriaSel, $origenSel, l.fecha_siembra
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta AND p.activo = 1
                    LEFT JOIN especie e ON p.id_especie = e.id_especie AND e.activo = 1
                    LEFT JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion AND u.activo = 1
                    $joinEstado
                    $joinCategoria
                    $joinOrigen
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
                'columns' => ['ID', 'Planta', 'Especie', 'Ubicación', 'Cant. Inicial', 'Cant. Actual', 'Estado', 'Categoría', 'Origen', 'Fecha Siembra'],
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
        $defs = $this->filtersInsumos();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

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
        $defs = $this->filtersProveedores();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        if ($this->fVal($filters['con_compras'] ?? null)) {
            $flag = $filters['con_compras'] === 'si';
            $conds[] = ($flag ? 'EXISTS' : 'NOT EXISTS') . " (SELECT 1 FROM compra cc WHERE cc.id_proveedor = p.id_proveedor)";
        }
        if ($this->fVal($filters['numero_compras'] ?? null)) {
            $op = $this->sanitizeOp($filters['numero_compras_op'] ?? '>=');
            $conds[] = "(SELECT COUNT(*) FROM compra cc WHERE cc.id_proveedor = p.id_proveedor) $op :numero_compras";
            $params[':numero_compras'] = (float) $filters['numero_compras'];
        }
        if ($this->fVal($filters['total_compras_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_compras_min_op'] ?? '>=');
            $conds[] = "(SELECT COALESCE(SUM(cc.total), 0) FROM compra cc WHERE cc.id_proveedor = p.id_proveedor) $op :total_compras_min";
            $params[':total_compras_min'] = (float) $filters['total_compras_min'];
        }
        if ($this->fVal($filters['total_compras_max'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_compras_max_op'] ?? '<=');
            $conds[] = "(SELECT COALESCE(SUM(cc.total), 0) FROM compra cc WHERE cc.id_proveedor = p.id_proveedor) $op :total_compras_max";
            $params[':total_compras_max'] = (float) $filters['total_compras_max'];
        }
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT p.id_proveedor, p.nombre_proveedor, p.rif_proveedor, p.contacto_vendedor, p.telefono_proveedor,
                           (SELECT COUNT(*) FROM compra cc WHERE cc.id_proveedor = p.id_proveedor) AS numero_compras,
                           (SELECT COALESCE(SUM(cc.total), 0) FROM compra cc WHERE cc.id_proveedor = p.id_proveedor) AS total_compras
                    FROM proveedores p
                    $where
                    ORDER BY p.nombre_proveedor ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $chartMap[$r['nombre_proveedor'] ?? 'Sin nombre'] = (float) ($r['total_compras'] ?? 0);
            }
            arsort($chartMap);

            return [
                'columns' => ['ID', 'Nombre', 'RIF', 'Contacto', 'Teléfono', 'N° Compras', 'Total Comprado'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_slice(array_keys($chartMap), 0, 10),
                    'values' => array_slice(array_values($chartMap), 0, 10),
                    'label' => 'Total comprado x Proveedor (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportProveedores: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportClientes(array $filters): array
    {
        $defs = $this->filtersClientes();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        if ($this->fVal($filters['con_ventas'] ?? null)) {
            $flag = $filters['con_ventas'] === 'si';
            $conds[] = ($flag ? 'EXISTS' : 'NOT EXISTS') . " (SELECT 1 FROM venta vv WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada')";
        }
        if ($this->fVal($filters['numero_ventas'] ?? null)) {
            $op = $this->sanitizeOp($filters['numero_ventas_op'] ?? '>=');
            $conds[] = "(SELECT COUNT(*) FROM venta vv WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada') $op :numero_ventas";
            $params[':numero_ventas'] = (float) $filters['numero_ventas'];
        }
        if ($this->fVal($filters['total_compras_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_compras_min_op'] ?? '>=');
            $conds[] = "(SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) FROM venta vv JOIN detalle_venta dv ON dv.id_venta = vv.id_venta WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada') $op :total_compras_min";
            $params[':total_compras_min'] = (float) $filters['total_compras_min'];
        }
        if ($this->fVal($filters['total_compras_max'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_compras_max_op'] ?? '<=');
            $conds[] = "(SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) FROM venta vv JOIN detalle_venta dv ON dv.id_venta = vv.id_venta WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada') $op :total_compras_max";
            $params[':total_compras_max'] = (float) $filters['total_compras_max'];
        }
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT c.id_cliente, c.tipo_cedula_cliente, c.cedula_cliente,
                           CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente, c.contacto_cliente,
                           (SELECT COUNT(*) FROM venta vv WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada') AS numero_ventas,
                           (SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) FROM venta vv JOIN detalle_venta dv ON dv.id_venta = vv.id_venta WHERE vv.id_cliente = c.id_cliente AND vv.estado != 'cancelada') AS total_compras
                    FROM cliente c
                    $where
                    ORDER BY nombre_cliente ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $chartMap[$r['nombre_cliente'] ?? 'Sin nombre'] = (float) ($r['total_compras'] ?? 0);
            }
            arsort($chartMap);

            return [
                'columns' => ['ID', 'Tipo C.I.', 'C.I.', 'Nombre', 'Contacto', 'N° Ventas', 'Total Comprado'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => array_slice(array_keys($chartMap), 0, 10),
                    'values' => array_slice(array_values($chartMap), 0, 10),
                    'label' => 'Total comprado x Cliente (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportClientes: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportTrabajadores(array $filters): array
    {
        $defs = $this->filtersTrabajadores();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        // Subquery base de tareas para filtros EXISTS
        $taskCond = 'at.id_usuario = tr.id_usuario';
        if ($this->fVal($filters['tareas_estatus'] ?? null)) {
            $taskCond .= " AND at.estatus_tarea = :tareas_estatus";
            $params[':tareas_estatus'] = $filters['tareas_estatus'];
        }
        if ($this->fVal($filters['fecha_asignacion_desde'] ?? null)) {
            $taskCond .= " AND at.fecha_asignacion >= :task_desde";
            $params[':task_desde'] = $filters['fecha_asignacion_desde'];
        }
        if ($this->fVal($filters['fecha_asignacion_hasta'] ?? null)) {
            $taskCond .= " AND at.fecha_asignacion < :task_hasta + INTERVAL 1 DAY";
            $params[':task_hasta'] = $filters['fecha_asignacion_hasta'];
        }

        if ($this->fVal($filters['tareas_estatus'] ?? null) || $this->fVal($filters['fecha_asignacion_desde'] ?? null) || $this->fVal($filters['fecha_asignacion_hasta'] ?? null)) {
            $conds[] = "EXISTS (SELECT 1 FROM asignar_tarea at WHERE $taskCond)";
        }

        if ($this->fVal($filters['numero_tareas'] ?? null)) {
            $op = $this->sanitizeOp($filters['numero_tareas_op'] ?? '>=');
            $conds[] = "(SELECT COUNT(*) FROM asignar_tarea at WHERE at.id_usuario = tr.id_usuario) $op :numero_tareas";
            $params[':numero_tareas'] = (float) $filters['numero_tareas'];
        }
        if ($this->fVal($filters['tareas_pendientes'] ?? null)) {
            $op = $this->sanitizeOp($filters['tareas_pendientes_op'] ?? '>=');
            $conds[] = "(SELECT COUNT(*) FROM asignar_tarea at WHERE at.id_usuario = tr.id_usuario AND at.estatus_tarea = 'pendiente') $op :tareas_pendientes";
            $params[':tareas_pendientes'] = (float) $filters['tareas_pendientes'];
        }
        $where = $this->buildWhere($conds);

        // Recalcular subquery para columna "tareas en rango" sin los params de filtro
        $rangeCond = 'at.id_usuario = tr.id_usuario';
        if ($this->fVal($filters['fecha_asignacion_desde'] ?? null)) {
            $rangeCond .= " AND at.fecha_asignacion >= '" . $filters['fecha_asignacion_desde'] . "'";
        }
        if ($this->fVal($filters['fecha_asignacion_hasta'] ?? null)) {
            $rangeCond .= " AND at.fecha_asignacion < '" . $filters['fecha_asignacion_hasta'] . "' + INTERVAL 1 DAY";
        }
        if ($this->fVal($filters['tareas_estatus'] ?? null)) {
            $rangeCond .= " AND at.estatus_tarea = '" . addslashes($filters['tareas_estatus']) . "'";
        }

        try {
            $sql = "SELECT tr.id_usuario, tr.nombre_trabajador, tr.apellido_trabajador, tr.cedula_trabajador,
                           tr.telefono_trabajador, tr.cargo,
                           (SELECT COUNT(*) FROM asignar_tarea at WHERE at.id_usuario = tr.id_usuario) AS numero_tareas,
                           (SELECT COUNT(*) FROM asignar_tarea at WHERE at.id_usuario = tr.id_usuario AND at.estatus_tarea = 'pendiente') AS tareas_pendientes
                           " . ($this->fVal($filters['fecha_asignacion_desde'] ?? null) || $this->fVal($filters['fecha_asignacion_hasta'] ?? null) ? ", (SELECT COUNT(*) FROM asignar_tarea at WHERE $rangeCond) AS tareas_en_rango" : '') . "
                    FROM `SysInescolara-Seguridad`.`usuarios` tr
                    $where
                    ORDER BY tr.nombre_trabajador ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $cargo = $r['cargo'] ?? 'Sin cargo';
                $chartMap[$cargo] = ($chartMap[$cargo] ?? 0) + 1;
            }

            $columns = ['ID', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Cargo', 'N° Tareas', 'Tareas Pendientes'];
            if ($this->fVal($filters['fecha_asignacion_desde'] ?? null) || $this->fVal($filters['fecha_asignacion_hasta'] ?? null)) {
                $columns[] = 'Tareas en Rango';
            }

            return [
                'columns' => $columns,
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
        $defs = $this->filtersTareas();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT a.id_asignacion, t.nombre_tarea AS tarea,
                           CONCAT(tr.nombre_trabajador, ' ', tr.apellido_trabajador) AS trabajador,
                           CONCAT('#', l.id_lote, ' ', COALESCE(p.nombre_comun, '')) AS lote,
                           a.fecha_asignacion, a.fecha_cumplimiento, a.estatus_tarea, a.horas_dedicadas
                    FROM asignar_tarea a
                    LEFT JOIN tareas t ON a.id_tarea = t.id_tarea AND t.activo = 1
                    LEFT JOIN `SysInescolara-Seguridad`.`usuarios` tr ON a.id_usuario = tr.id_usuario
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
                'columns' => ['ID', 'Tarea', 'Trabajador', 'Lote', 'Fecha Asignación', 'Fecha Cumplimiento', 'Estatus', 'Horas'],
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
        $defs = $this->filtersVentas();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        if (!($this->fVal($filters['estado'] ?? null))) {
            $conds[] = "v.estado != 'cancelada'";
        }

        if ($this->fVal($filters['total_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_min_op'] ?? '>=');
            $conds[] = "(SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta) $op :total_min";
            $params[':total_min'] = (float) $filters['total_min'];
        }
        if ($this->fVal($filters['items_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['items_min_op'] ?? '>=');
            $conds[] = "(SELECT COUNT(*) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta) $op :items_min";
            $params[':items_min'] = (float) $filters['items_min'];
        }
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT v.id_venta, v.referencia, CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS cliente,
                           CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS vendedor,
                           v.tipo_venta, v.estado,
                           (SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta) AS total,
                           (SELECT COUNT(*) FROM detalle_venta dv WHERE dv.id_venta = v.id_venta) AS items,
                           v.fecha_venta
                    FROM venta v
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente AND c.activo = 1
                    LEFT JOIN `SysInescolara-Seguridad`.`usuarios` t ON v.id_usuario = t.id_usuario
                    $where
                    ORDER BY v.fecha_venta DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $mes = date('Y-m', strtotime($r['fecha_venta']));
                $chartMap[$mes] = ($chartMap[$mes] ?? 0) + (float) $r['total'];
            }

            return [
                'columns' => ['ID', 'Referencia', 'Cliente', 'Vendedor', 'Tipo', 'Estado', 'Total', 'Items', 'Fecha'],
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
        $defs = $this->filtersCompras();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);

        if (!($this->fVal($filters['estado'] ?? null))) {
            $conds[] = "c.estado != 'cancelada'";
        }
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT c.id_compra, p.nombre_proveedor AS proveedor, c.fecha_compra,
                           c.tipo_comprobante, c.numero_comprobante, c.subtotal, c.iva, c.total, c.estado
                    FROM compra c
                    LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                    $where
                    ORDER BY c.fecha_compra DESC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartMap = [];
            foreach ($rows as $r) {
                $prov = $r['proveedor'] ?? 'Sin proveedor';
                $chartMap[$prov] = ($chartMap[$prov] ?? 0) + (float) $r['total'];
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
        $defs = $this->filtersHerramientas();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT h.id_herramienta, h.nombre_herramienta, h.tipo, h.estado, h.cantidad,
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
                'columns' => ['ID', 'Nombre', 'Tipo', 'Estado', 'Cant.', 'Fecha Adq.', 'Últ. Mantenimiento'],
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
        $defs = $this->filtersEspecies();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        $having = '';
        if ($this->fVal($filters['total_plantas'] ?? null)) {
            $op = $this->sanitizeOp($filters['total_plantas_op'] ?? '>=');
            $having = " HAVING COUNT(p.id_planta) $op :total_plantas";
            $params[':total_plantas'] = (float) $filters['total_plantas'];
        }

        try {
            $sql = "SELECT e.id_especie, e.nombre_especie, e.descripcion,
                           COUNT(p.id_planta) AS total_plantas
                    FROM especie e
                    LEFT JOIN plantas p ON p.id_especie = e.id_especie AND p.activo = 1
                    $where
                    GROUP BY e.id_especie, e.nombre_especie, e.descripcion
                    $having
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
        $fkEstado = $this->hasColumn('lote', 'id_estado');
        $fkCat = $this->hasColumn('lote', 'id_categoria');

        $defs = $this->filtersInventario();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        $having = '';
        if ($this->fVal($filters['nivel_stock'] ?? null)) {
            $having = ' HAVING nivel_stock = :nivel_stock';
            $map = ['sin_stock' => 'Sin stock', 'bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'];
            $params[':nivel_stock'] = $map[$filters['nivel_stock']] ?? $filters['nivel_stock'];
        }

        try {
            $sql = "SELECT
                        CASE
                            WHEN l.cantidad_actual <= 0 THEN 'Sin stock'
                            WHEN l.cantidad_actual < 20 THEN 'Bajo'
                            WHEN l.cantidad_actual < 50 THEN 'Medio'
                            ELSE 'Alto'
                        END AS nivel_stock,
                        COUNT(*) AS total_lotes,
                        SUM(l.cantidad_actual) AS total_plantas,
                        SUM(l.cantidad_inicial) AS total_inicial
                    FROM lote l
                    $where
                    GROUP BY nivel_stock
                    $having
                    ORDER BY FIELD(nivel_stock, 'Alto', 'Medio', 'Bajo', 'Sin stock')";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['Nivel de Stock', 'Total Lotes', 'Total Plantas', 'Total Inicial'],
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
        $defs = $this->filtersRecoleccion();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT r.id_recoleccion,
                           CONCAT(t.nombre_trabajador, ' ', t.apellido_trabajador) AS trabajador,
                           u.nombre_ubicacion AS ubicacion,
                           r.fecha_asignacion, r.fecha_recoleccion, r.estatus,
                           (SELECT COUNT(*) FROM recoleccion_semillas_detalle d WHERE d.id_recoleccion = r.id_recoleccion) AS total_detalles
                    FROM recoleccion_semillas r
                    LEFT JOIN `SysInescolara-Seguridad`.`usuarios` t ON r.id_usuario = t.id_usuario
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
        $defs = $this->filtersUbicaciones();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT u.id_ubicacion, u.nombre_ubicacion, u.zona, u.descripcion,
                           (SELECT COUNT(*) FROM lote l WHERE l.id_ubicacion = u.id_ubicacion) AS total_lotes
                    FROM ubicacion u
                    $where
                    ORDER BY u.nombre_ubicacion ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Zona', 'Descripción', 'N° Lotes'],
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
        $defs = $this->filtersUnidadesMedida();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT u.id_unidad_medida, u.nombre_unidad_medida, u.simbolo,
                           (SELECT COUNT(*) FROM insumo i WHERE i.id_unidad_medida = u.id_unidad_medida) AS total_insumos
                    FROM unidad_medida u
                    $where
                    ORDER BY u.nombre_unidad_medida ASC";
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'columns' => ['ID', 'Nombre', 'Símbolo', 'N° Insumos'],
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
        $defs = $this->filtersCuentasPagar();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

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
                $chartMap[$est] = ($chartMap[$est] ?? 0) + (float) $r['saldo_pendiente'];
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
        $defs = $this->filtersPrecios();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $where = $this->buildWhere($conds);

        try {
            $sql = "SELECT l.id_lote, p.nombre_comun AS planta, l.id_planta, l.cantidad_actual,
                           l.costo_unitario, l.porcentaje_ganancia,
                           COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) AS costo_total_insumos,
                           ROUND(l.costo_unitario + COALESCE(SUM(ri.costo_unitario * ri.cantidad), 0) +
                                 (l.costo_unitario * l.porcentaje_ganancia / 100), 2) AS precio_final
                    FROM lote l
                    LEFT JOIN plantas p ON l.id_planta = p.id_planta
                    LEFT JOIN registro_insumo ri ON l.id_lote = ri.id_lote
                    WHERE l.activo = 1
                    GROUP BY l.id_lote, p.nombre_comun, l.costo_unitario, l.porcentaje_ganancia, l.cantidad_actual
                    ORDER BY p.nombre_comun ASC";
            $stmt = $this->db()->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $chartLabels = array_map(fn($r) => ($r['planta'] ?? 'Planta') . ' (Lote #' . $r['id_lote'] . ')', $rows);
            $chartValues = array_map('floatval', array_column($rows, 'precio_final'));

            return [
                'columns' => ['Lote', 'Planta', 'Stock', 'Costo Unitario', 'Costo Insumos', '% Ganancia', 'Precio Final'],
                'rows' => $rows,
                'chart' => [
                    'type' => 'bar',
                    'labels' => $chartLabels,
                    'values' => $chartValues,
                    'label' => 'Precio Final (Bs.)',
                ],
            ];
        } catch (\Throwable $e) {
            error_log('Error reportPrecios: ' . $e->getMessage());
            return ['columns' => [], 'rows' => [], 'chart' => null];
        }
    }

    private function reportCuentasCobrar(array $filters): array
    {
        $defs = $this->filtersCuentasCobrar();
        $conds = [];
        $params = [];
        $this->applyDefs($filters, $defs, $conds, $params);
        $conds[] = "v.estado != 'cancelada'";
        $where = $this->buildWhere($conds);

        $having = [];
        if ($this->fVal($filters['estado_cuenta'] ?? null)) {
            $having[] = 'estado_cuenta = :estado_cuenta';
            $params[':estado_cuenta'] = $filters['estado_cuenta'];
        }
        if ($this->fVal($filters['monto_total_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['monto_total_min_op'] ?? '>=');
            $having[] = "monto_total $op :monto_total_min";
            $params[':monto_total_min'] = (float) $filters['monto_total_min'];
        }
        if ($this->fVal($filters['monto_total_max'] ?? null)) {
            $op = $this->sanitizeOp($filters['monto_total_max_op'] ?? '<=');
            $having[] = "monto_total $op :monto_total_max";
            $params[':monto_total_max'] = (float) $filters['monto_total_max'];
        }
        if ($this->fVal($filters['saldo_pendiente_min'] ?? null)) {
            $op = $this->sanitizeOp($filters['saldo_pendiente_min_op'] ?? '>=');
            $having[] = "saldo_pendiente $op :saldo_pendiente_min";
            $params[':saldo_pendiente_min'] = (float) $filters['saldo_pendiente_min'];
        }
        if ($this->fVal($filters['saldo_pendiente_max'] ?? null)) {
            $op = $this->sanitizeOp($filters['saldo_pendiente_max_op'] ?? '<=');
            $having[] = "saldo_pendiente $op :saldo_pendiente_max";
            $params[':saldo_pendiente_max'] = (float) $filters['saldo_pendiente_max'];
        }
        $havingSql = $having ? ' HAVING ' . implode(' AND ', $having) : '';

        try {
            $sql = "SELECT
                        v.id_venta, v.referencia, CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_cliente,
                        v.fecha_venta, v.fecha_vencimiento,
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
                    LEFT JOIN (SELECT id_venta, SUM(monto) AS total_pagado FROM pago_venta WHERE estado_pago != 'rechazado' GROUP BY id_venta) pag ON v.id_venta = pag.id_venta
                    $where
                    $havingSql
                    ORDER BY v.fecha_vencimiento ASC";
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
