<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

final class CrudConfigs
{
    public static function especies(): array
    {
        return [
            'path' => 'dashboard/especies',
            'table' => 'especiesTable',
            'add' => '#btnAddSpecies',
            'addModal' => '#addSpeciesModal',
            'addForm' => '#addSpeciesForm',
            'editModal' => '#editSpeciesModal',
            'editForm' => '#editSpeciesForm',
            'fields' => ['nombre_especie' => 'text', 'descripcion' => 'text'],
            'editFields' => ['nombre_especie' => 'text', 'descripcion' => 'text'],
        ];
    }

    public static function ubicaciones(): array
    {
        return [
            'path' => 'dashboard/ubicaciones',
            'table' => 'ubicacionesTable',
            'add' => '#btnAddLocation',
            'addModal' => '#addLocationModal',
            'addForm' => '#addLocationForm',
            'editModal' => '#editLocationModal',
            'editForm' => '#editLocationForm',
            'fields' => ['nombre_ubicacion' => 'text', 'descripcion' => 'text', 'tipo' => 'select'],
            'editFields' => ['nombre_ubicacion' => 'text', 'descripcion' => 'text', 'tipo' => 'select'],
        ];
    }

    public static function clientes(): array
    {
        return [
            'path' => 'dashboard/clientes',
            'table' => 'clientesTable',
            'add' => '#btnAddClient',
            'addModal' => '#addClientModal',
            'addForm' => '#addClientForm',
            'editModal' => '#editClientModal',
            'editForm' => '#editClientForm',
            'fields' => [
                'tipo_cedula_cliente' => 'select',
                'cedula_cliente' => 'text',
                'nombre_cliente' => 'text',
                'apellido_cliente' => 'text',
                'contacto_cliente' => 'text',
            ],
            'editFields' => [
                'nombre_cliente' => 'text',
                'apellido_cliente' => 'text',
                'contacto_cliente' => 'text',
            ],
        ];
    }

    public static function proveedores(): array
    {
        return [
            'path' => 'dashboard/proveedores',
            'table' => 'proveedoresTable',
            'add' => '#btnAddSupplier',
            'addModal' => '#addSupplierModal',
            'addForm' => '#addSupplierForm',
            'editModal' => '#editSupplierModal',
            'editForm' => '#editSupplierForm',
            'fields' => [
                'nombre_proveedor' => 'text',
                'rif_tipo' => 'select',
                'rif_numero' => 'text',
                'contacto_vendedor' => 'text',
                'telefono_proveedor' => 'text',
            ],
            'editFields' => [
                'nombre_proveedor' => 'text',
                'contacto_vendedor' => 'text',
                'telefono_proveedor' => 'text',
            ],
        ];
    }

    public static function unidades(): array
    {
        return [
            'path' => 'dashboard/unidades-medida',
            'table' => 'unidadesMedidaTable',
            'add' => '#btnAddUnit',
            'addModal' => '#addUnitModal',
            'addForm' => '#addUnitForm',
            'editModal' => '#editUnitModal',
            'editForm' => '#editUnitForm',
            'fields' => ['nombre' => 'text'],
            'editFields' => ['nombre' => 'text'],
        ];
    }

    public static function herramientas(): array
    {
        return [
            'path' => 'dashboard/herramientas',
            'table' => 'herramientasTable',
            'add' => '#btnAddTool',
            'addModal' => '#addToolModal',
            'addForm' => '#addToolForm',
            'editModal' => '#editToolModal',
            'editForm' => '#editToolForm',
            'fields' => [
                'nombre_herramienta' => 'text',
                'cantidad' => 'text',
                'estado' => 'select',
                'fecha_ultimo_mantenimiento' => 'date',
                'observacion' => 'text',
            ],
            'editFields' => [
                'nombre_herramienta' => 'text',
                'cantidad' => 'text',
                'estado' => 'select',
                'observacion' => 'text',
            ],
        ];
    }

    public static function insumos(): array
    {
        return [
            'path' => 'dashboard/insumos',
            'table' => 'insumosTable',
            'add' => '#btnAddSupply',
            'addModal' => '#addSupplyModal',
            'addForm' => '#addSupplyForm',
            'editModal' => '#editSupplyModal',
            'editForm' => '#editSupplyForm',
            'fields' => [
                'nombre_insumo' => 'text',
                'id_unidad_medida' => 'select_value',
                'categoria' => 'text',
                'stock_actual' => 'text',
                'costo_unitario_actual' => 'text',
            ],
            'editFields' => [
                'nombre_insumo' => 'text',
                'categoria' => 'text',
                'stock_actual' => 'text',
                'costo_unitario_actual' => 'text',
            ],
        ];
    }

    public static function plantas(): array
    {
        return [
            'path' => 'dashboard/plantas',
            'table' => 'plantasTable',
            'add' => '#btnAddPlant',
            'addModal' => '#addPlantModal',
            'addForm' => '#addPlantForm',
            'editModal' => '#editPlantModal',
            'editForm' => '#editPlantForm',
            'fields' => [
                'nombre_comun' => 'text',
                'nombre_tecnico' => 'text',
                'especie_id' => 'select_value',
            ],
            'editFields' => [
                'nombre_comun' => 'text',
                'nombre_tecnico' => 'text',
            ],
        ];
    }

    public static function lotes(): array
    {
        return [
            'path' => 'dashboard/lotes',
            'table' => 'lotesTable',
            'add' => '#btnAddBatch',
            'addModal' => '#addBatchModal',
            'addForm' => '#addBatchForm',
            'editModal' => '#editBatchModal',
            'editForm' => '#editBatchForm',
            'fields' => [
                'id_planta' => 'select_value',
                'id_ubicacion' => 'select_value',
                'fecha_siembra' => 'date',
                'cantidad_inicial' => 'text',
                'cantidad_actual' => 'text',
                'costo_unitario' => 'text',
                'porcentaje_ganancia' => 'text',
                'id_estado' => 'select',
                'id_categoria' => 'select',
                'id_origen' => 'select',
                'observacion' => 'text',
            ],
            'editFields' => [
                'cantidad_actual' => 'text',
                'observacion' => 'text',
            ],
        ];
    }

    public static function roles(): array
    {
        return [
            'path' => 'dashboard/roles',
            'table' => 'rolesTable',
            'add' => '#btnAddRole',
            'addModal' => '#addRoleModal',
            'addForm' => '#addRoleForm',
            'editModal' => '#editRoleModal',
            'editForm' => '#editRoleForm',
            'fields' => ['nombre_rol' => 'text', 'descripcion_rol' => 'text'],
            'editFields' => ['nombre_rol' => 'text', 'descripcion_rol' => 'text'],
        ];
    }
}
