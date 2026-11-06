<?php

declare(strict_types=1);

namespace Tests\Aceptacion\Support;

/**
 * Catálogo de módulos usados por la matriz de aceptación.
 *
 * Cada entrada asocia un requisito funcional (RF-xx) de la matriz de
 * trazabilidad con la pantalla real que lo materializa para el usuario.
 */
final class ModuleCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'RF-01' => [
                'rf' => 'RF-01',
                'module' => 'Plantas',
                'path' => '/dashboard/plantas',
                'heading' => 'Catálogo de Plantas',
                'table' => 'plantasTable',
            ],
            'RF-02' => [
                'rf' => 'RF-02',
                'module' => 'Especies',
                'path' => '/dashboard/especies',
                'heading' => 'Gestión de Especies',
                'table' => 'especiesTable',
            ],
            'RF-03' => [
                'rf' => 'RF-03',
                'module' => 'Ubicaciones',
                'path' => '/dashboard/ubicaciones',
                'heading' => 'Gestión de Ubicaciones',
                'table' => 'ubicacionesTable',
            ],
            'RF-04' => [
                'rf' => 'RF-04',
                'module' => 'Inventario',
                'path' => '/dashboard/inventario',
                'heading' => 'Inventario General',
                'table' => 'consolidatedTable',
            ],
            'RF-05' => [
                'rf' => 'RF-05',
                'module' => 'Lotes',
                'path' => '/dashboard/lotes',
                'heading' => 'Gestión de Lotes',
                'table' => 'lotesTable',
            ],
            'RF-06' => [
                'rf' => 'RF-06',
                'module' => 'Trazabilidad',
                'path' => '/dashboard/trazabilidad',
                'heading' => 'Gestión de Trazabilidad',
                'table' => 'trazabilidadTable',
            ],
            'RF-07' => [
                'rf' => 'RF-07',
                'module' => 'Insumos',
                'path' => '/dashboard/insumos',
                'heading' => 'Gestión de Insumo',
                'table' => 'insumosTable',
            ],
            'RF-08' => [
                'rf' => 'RF-08',
                'module' => 'Herramientas',
                'path' => '/dashboard/herramientas',
                'heading' => 'Gestión de Herramientas',
                'table' => 'herramientasTable',
            ],
            'RF-09' => [
                'rf' => 'RF-09',
                'module' => 'Unidades de medida',
                'path' => '/dashboard/unidades-medida',
                'heading' => 'Unidades de Medida',
                'table' => 'unidadesMedidaTable',
            ],
            'RF-10' => [
                'rf' => 'RF-10',
                'module' => 'Mermas',
                'path' => '/dashboard/mermas',
                'heading' => 'Mermas y Bajas Definitivas',
                'table' => 'mermasTable',
            ],
            'RF-11' => [
                'rf' => 'RF-11',
                'module' => 'Registro de insumos (soporte)',
                'path' => '/dashboard/precios',
                'heading' => null,
                'body' => 'Total Insumos',
                'table' => 'preciosTable',
            ],
            'RF-12' => [
                'rf' => 'RF-12',
                'module' => 'Uso de herramientas (soporte)',
                'path' => '/dashboard/tareas',
                'heading' => 'Asignación de Tareas',
                'special' => 'assignment-tools',
                'table' => 'assignmentsTable',
            ],
            'RF-13' => [
                'rf' => 'RF-13',
                'module' => 'Ventas',
                'path' => '/dashboard/ventas',
                'heading' => 'Gestión de Ventas',
                'table' => 'ventasTable',
            ],
            'RF-14' => [
                'rf' => 'RF-14',
                'module' => 'Precios',
                'path' => '/dashboard/precios',
                'heading' => null,
                'body' => 'Precios por Lote',
                'table' => 'preciosTable',
            ],
            'RF-15' => [
                'rf' => 'RF-15',
                'module' => 'Clientes',
                'path' => '/dashboard/clientes',
                'heading' => 'Gestión de Clientes',
                'table' => 'clientesTable',
            ],
            'RF-16' => [
                'rf' => 'RF-16',
                'module' => 'Cuentas por cobrar',
                'path' => '/dashboard/cuentas_cobrar',
                'heading' => 'Cuentas por Cobrar',
                'table' => 'cuentasTable',
            ],
            'RF-17' => [
                'rf' => 'RF-17',
                'module' => 'Cuentas por pagar',
                'path' => '/dashboard/cuentas-pagar',
                'heading' => 'Cuentas por Pagar',
                'table' => 'cuentasTable',
            ],
            'RF-18' => [
                'rf' => 'RF-18',
                'module' => 'Compras',
                'path' => '/dashboard/compras',
                'heading' => 'Gestión de Compras',
                'table' => 'comprasTable',
            ],
            'RF-19' => [
                'rf' => 'RF-19',
                'module' => 'Ornatos',
                'path' => '/dashboard/ornatos',
                'heading' => 'Gestión de Ornatos',
                'table' => 'tablaOrnatos',
            ],
            'RF-20' => [
                'rf' => 'RF-20',
                'module' => 'Ampliación',
                'path' => '/dashboard/ampliacion',
                'heading' => 'Ampliación de Especies',
                'table' => 'ampliacionTable',
            ],
            'RF-21' => [
                'rf' => 'RF-21',
                'module' => 'Proveedores',
                'path' => '/dashboard/proveedores',
                'heading' => 'Gestión de Proveedores',
                'table' => 'proveedoresTable',
            ],
            'RF-22' => [
                'rf' => 'RF-22',
                'module' => 'Tareas',
                'path' => '/dashboard/tareas',
                'heading' => 'Asignación de Tareas',
                'table' => 'assignmentsTable',
            ],
            'RF-23' => [
                'rf' => 'RF-23',
                'module' => 'Recolección de semillas',
                'path' => '/dashboard/seed-collection',
                'heading' => 'Gestión de Recolección de Semillas',
                'table' => 'recoleccionTable',
            ],
            'RF-24' => [
                'rf' => 'RF-24',
                'module' => 'Reportes',
                'path' => '/dashboard/reports',
                'heading' => null,
                'body' => 'Reportes',
            ],
            'RF-25' => [
                'rf' => 'RF-25',
                'module' => 'Dashboard',
                'path' => '/dashboard',
                'heading' => 'Bienvenido a SYSINECOLARA',
            ],
            'RF-26' => [
                'rf' => 'RF-26',
                'module' => 'Usuarios',
                'path' => '/dashboard/usuarios',
                'heading' => 'Gestión de Usuarios',
                'table' => 'usuariosTable',
            ],
            'RF-27' => [
                'rf' => 'RF-27',
                'module' => 'Roles',
                'path' => '/dashboard/roles',
                'heading' => 'Gestión de Roles',
                'table' => 'rolesTable',
            ],
            'RF-28' => [
                'rf' => 'RF-28',
                'module' => 'Auditoría',
                'path' => '/dashboard/auditlog',
                'heading' => 'Bitácora del Sistema',
                'table' => 'auditlogTable',
            ],
            'RF-29' => [
                'rf' => 'RF-29',
                'module' => 'Respaldos',
                'path' => '/dashboard/backups',
                'heading' => 'Respaldos de Base de Datos',
                'table' => 'backupsTable',
            ],
            'RF-30' => [
                'rf' => 'RF-30',
                'module' => 'Notificaciones (soporte)',
                'path' => '/dashboard',
                'heading' => 'Bienvenido a SYSINECOLARA',
                'special' => 'notifications',
            ],
            'RF-31' => [
                'rf' => 'RF-31',
                'module' => 'Recuperación de contraseña (soporte)',
                'path' => '/recuperar-password',
                'heading' => null,
                'body' => 'Recuperar',
            ],
        ];
    }
}
