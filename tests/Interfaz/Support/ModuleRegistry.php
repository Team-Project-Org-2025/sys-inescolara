<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

final class ModuleRegistry
{
    public static function modules(): array
    {
        return [
            ['name' => 'Dashboard', 'path' => 'dashboard', 'heading' => 'Bienvenido', 'table' => null, 'group' => 'Sistema', 'coverage' => 'lectura'],
            ['name' => 'Plantas', 'path' => 'dashboard/plantas', 'heading' => 'Catálogo de Plantas', 'table' => 'plantasTable', 'group' => 'Catálogo', 'coverage' => 'crud'],
            ['name' => 'Especies', 'path' => 'dashboard/especies', 'heading' => 'Especies', 'table' => 'especiesTable', 'group' => 'Catálogo', 'coverage' => 'crud'],
            ['name' => 'Ubicaciones', 'path' => 'dashboard/ubicaciones', 'heading' => 'Ubicaciones', 'table' => 'ubicacionesTable', 'group' => 'Catálogo', 'coverage' => 'crud'],
            ['name' => 'Inventario', 'path' => 'dashboard/inventario', 'heading' => 'Inventario General', 'table' => 'consolidatedTable', 'group' => 'Inventario', 'coverage' => 'lectura'],
            ['name' => 'Lotes', 'path' => 'dashboard/lotes', 'heading' => 'Lotes', 'table' => 'lotesTable', 'group' => 'Inventario', 'coverage' => 'crud'],
            ['name' => 'Trazabilidad', 'path' => 'dashboard/trazabilidad', 'heading' => 'Trazabilidad', 'table' => 'trazabilidadTable', 'group' => 'Inventario', 'coverage' => 'crud'],
            ['name' => 'Insumos', 'path' => 'dashboard/insumos', 'heading' => 'Insumo', 'table' => 'insumosTable', 'group' => 'Inventario', 'coverage' => 'crud'],
            ['name' => 'Herramientas', 'path' => 'dashboard/herramientas', 'heading' => 'Herramientas', 'table' => 'herramientasTable', 'group' => 'Inventario', 'coverage' => 'crud'],
            ['name' => 'Unidades de medida', 'path' => 'dashboard/unidades-medida', 'heading' => 'Unidades de Medida', 'table' => 'unidadesMedidaTable', 'group' => 'Inventario', 'coverage' => 'crud'],
            ['name' => 'Mermas', 'path' => 'dashboard/mermas', 'heading' => 'Mermas', 'table' => 'mermasTable', 'group' => 'Inventario', 'coverage' => 'flujo'],
            ['name' => 'Ventas', 'path' => 'dashboard/ventas', 'heading' => 'Ventas', 'table' => 'ventasTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Precios', 'path' => 'dashboard/precios', 'heading' => null, 'body' => 'Precio', 'table' => 'preciosTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Clientes', 'path' => 'dashboard/clientes', 'heading' => 'Clientes', 'table' => 'clientesTable', 'group' => 'Comercial', 'coverage' => 'crud'],
            ['name' => 'Cuentas por cobrar', 'path' => 'dashboard/cuentas_cobrar', 'heading' => 'Cuentas por Cobrar', 'table' => 'cuentasTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Cuentas por pagar', 'path' => 'dashboard/cuentas-pagar', 'heading' => 'Cuentas por Pagar', 'table' => 'cuentasTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Compras', 'path' => 'dashboard/compras', 'heading' => 'Compras', 'table' => 'comprasTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Ornatos', 'path' => 'dashboard/ornatos', 'heading' => 'Ornatos', 'table' => 'tablaOrnatos', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Ampliación', 'path' => 'dashboard/ampliacion', 'heading' => 'Ampliación', 'table' => 'ampliacionTable', 'group' => 'Comercial', 'coverage' => 'flujo'],
            ['name' => 'Proveedores', 'path' => 'dashboard/proveedores', 'heading' => 'Proveedores', 'table' => 'proveedoresTable', 'group' => 'Comercial', 'coverage' => 'crud'],
            ['name' => 'Tareas', 'path' => 'dashboard/tareas', 'finalPath' => '/tareas', 'heading' => 'Tareas', 'table' => 'assignmentsTable', 'group' => 'Operaciones', 'coverage' => 'flujo'],
            ['name' => 'Recolección de semillas', 'path' => 'dashboard/seed-collection', 'heading' => 'Recolección', 'table' => 'recoleccionTable', 'group' => 'Operaciones', 'coverage' => 'flujo'],
            ['name' => 'Asistente IA', 'path' => 'dashboard/asistente', 'heading' => null, 'body' => 'Asistente', 'table' => null, 'group' => 'Sistema', 'coverage' => 'lectura'],
            ['name' => 'Reportes', 'path' => 'dashboard/reports', 'heading' => 'Reportes', 'table' => null, 'group' => 'Sistema', 'coverage' => 'lectura'],
            ['name' => 'Usuarios', 'path' => 'dashboard/usuarios', 'heading' => 'Usuarios', 'table' => 'usuariosTable', 'group' => 'Sistema', 'coverage' => 'crud'],
            ['name' => 'Roles', 'path' => 'dashboard/roles', 'heading' => 'Roles', 'table' => 'rolesTable', 'group' => 'Sistema', 'coverage' => 'crud'],
            ['name' => 'AuditLog', 'path' => 'dashboard/auditlog', 'heading' => 'Bitácora', 'table' => 'auditlogTable', 'group' => 'Sistema', 'coverage' => 'lectura'],
            ['name' => 'Backups', 'path' => 'dashboard/backups', 'heading' => 'Respaldos', 'table' => 'backupsTable', 'group' => 'Sistema', 'coverage' => 'flujo'],
            ['name' => 'Perfil', 'path' => 'dashboard/perfil', 'heading' => null, 'body' => 'Foto de perfil', 'table' => null, 'group' => 'Sistema', 'coverage' => 'flujo'],
            ['name' => 'Notificaciones', 'path' => 'dashboard', 'heading' => null, 'body' => 'Notificaciones', 'table' => null, 'group' => 'Sistema', 'coverage' => 'flujo'],
            ['name' => 'Recuperar contraseña', 'path' => 'recuperar-password', 'heading' => null, 'body' => 'Recuperar', 'table' => null, 'group' => 'Autenticación', 'coverage' => 'flujo'],
        ];
    }
}

