<?php

function hasPermiso(string $codigo): bool
{
    return \SysInescolara\helpers\Auth::hasPermiso($codigo);
}

$current = $currentPage ?? '';

$isPlanta = in_array($current, ['plants', 'species', 'locations']);
$isActivos = in_array($current, ['inventario', 'batches', 'trazabilidad', 'supplies', 'tools', 'unit-measures', 'mermas']);
$isVenta = in_array($current, ['ventas', 'prices', 'clientes', 'cuentas-cobrar', 'cuentas-pagar', 'compras']);
$isServicios = in_array($current, ['ornatos', 'ampliacion', 'suppliers']);
$isTarea = in_array($current, ['tasks', 'employees', 'seed-collection']);
$isConfiguracion = in_array($current, ['usuarios', 'roles', 'auditlog', 'backups']);

$showInventario = hasPermiso('PLANTAS_VIEW') || hasPermiso('UBICACIONES_VIEW')
    || hasPermiso('INVENTARIO_VIEW') || hasPermiso('TRAZABILIDAD_VIEW')
    || hasPermiso('INSUMOS_VIEW') || hasPermiso('HERRAMIENTAS_VIEW')
    || hasPermiso('UNIDADES_MEDIDA_VIEW') || hasPermiso('MERMAS_VIEW');

$showComercial = hasPermiso('VENTAS_ACCESS') || hasPermiso('PRECIOS_VIEW')
    || hasPermiso('CLIENTES_VIEW') || hasPermiso('CUENTAS_COBRAR_VIEW')
    || hasPermiso('CUENTAS_VIEW') || hasPermiso('COMPRAS_VIEW')
    || hasPermiso('ORNATOS_VIEW') || hasPermiso('AMPLIACION_VIEW')
    || hasPermiso('PROVEEDORES_VIEW');

$showOperaciones = hasPermiso('TRABAJADORES_VIEW') || hasPermiso('TAREAS_VIEW')
    || hasPermiso('RECOLECCION_VIEW');

$showHerramientas = hasPermiso('ASISTENTE_ACCESS') || hasPermiso('DASHBOARD_VIEW');

$showSistema = hasPermiso('USUARIOS_MANAGE');

?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>dashboard" class="sidebar-logo">
            <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="sidebar-logo-img">
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">SYSINECOLARA</span>
            </div>
        </a>
        <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">

        <a href="<?= BASE_URL ?>dashboard" class="nav-link <?= $current === 'dashboard' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Inicio</span>
        </a>

        <hr class="sidebar-divider">

        <?php if ($showInventario): ?>
        <div class="sidebar-section-label">INVENTARIO</div>

        <?php if (hasPermiso('PLANTAS_VIEW') || hasPermiso('UBICACIONES_VIEW')): ?>
        <div class="nav-group">
            <button class="nav-group-btn <?= $isPlanta ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"></path><path d="M12 13c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path><path d="M12 10c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path></svg>
                <span>Gestionar Planta</span>
                <svg class="chevron <?= $isPlanta ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isPlanta ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <?php if (hasPermiso('PLANTAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/plants" class="nav-link <?= $current === 'plants' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"></path><path d="M12 13c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path><path d="M12 10c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path></svg>
                            <span>Administrar planta</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('PLANTAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/species" class="nav-link <?= $current === 'species' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"></line><circle cx="18" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><path d="M18 9a9 9 0 0 1-9 9"></path></svg>
                            <span>Administrar especie</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('UBICACIONES_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/locations" class="nav-link <?= $current === 'locations' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Administrar ubicacion</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('INVENTARIO_VIEW') || hasPermiso('PLANTAS_VIEW') || hasPermiso('TRAZABILIDAD_VIEW') || hasPermiso('INSUMOS_VIEW') || hasPermiso('HERRAMIENTAS_VIEW') || hasPermiso('UNIDADES_MEDIDA_VIEW') || hasPermiso('MERMAS_VIEW')): ?>
        <div class="nav-group">
            <button class="nav-group-btn <?= $isActivos ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <span>Gestionar Activos</span>
                <svg class="chevron <?= $isActivos ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isActivos ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <?php if (hasPermiso('INVENTARIO_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/inventario" class="nav-link <?= $current === 'inventario' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            <span>Inventario</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('PLANTAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/batches" class="nav-link <?= $current === 'batches' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5"></polygon><line x1="12" y1="22" x2="12" y2="15.5"></line><polyline points="2 8.5 12 15.5 22 8.5"></polyline></svg>
                            <span>Lote</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('TRAZABILIDAD_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/trazabilidad" class="nav-link <?= $current === 'trazabilidad' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>Trazabilidad</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('INSUMOS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/supplies" class="nav-link <?= $current === 'supplies' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
                            <span>Insumos</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('HERRAMIENTAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/tools" class="nav-link <?= $current === 'tools' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                            <span>Herramientas</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('UNIDADES_MEDIDA_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/unit-measures" class="nav-link <?= $current === 'unit-measures' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                            <span>Unidad de medida</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('MERMAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/mermas" class="nav-link <?= $current === 'mermas' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            <span>Mermas</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showComercial): ?>
        <div class="sidebar-section-label">COMERCIAL</div>

        <?php if (hasPermiso('VENTAS_ACCESS') || hasPermiso('PRECIOS_VIEW') || hasPermiso('CLIENTES_VIEW') || hasPermiso('CUENTAS_COBRAR_VIEW') || hasPermiso('CUENTAS_VIEW') || hasPermiso('COMPRAS_VIEW')): ?>
        <div class="nav-group">
            <button class="nav-group-btn <?= $isVenta ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span>Gestionar Venta</span>
                <svg class="chevron <?= $isVenta ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isVenta ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <?php if (hasPermiso('VENTAS_ACCESS')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/ventas" class="nav-link <?= $current === 'ventas' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            <span>Procesar venta</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('PRECIOS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/prices" class="nav-link <?= $current === 'prices' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                            <span>Gestionar precio</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('CLIENTES_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/clientes" class="nav-link <?= $current === 'clientes' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Gestionar cliente</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('CUENTAS_COBRAR_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/cuentas_cobrar" class="nav-link <?= $current === 'cuentas-cobrar' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                            <span>Cuenta por cobrar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('CUENTAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/cuentas-pagar" class="nav-link <?= $current === 'cuentas-pagar' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            <span>Cuentas por pagar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('COMPRAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/compras" class="nav-link <?= $current === 'compras' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            <span>Gestionar compra</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('ORNATOS_VIEW') || hasPermiso('AMPLIACION_VIEW') || hasPermiso('PROVEEDORES_VIEW')): ?>
        <div class="nav-group">
            <button class="nav-group-btn <?= $isServicios ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L9.5 9.5L2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5L12 2z"></path></svg>
                <span>Gestionar Servicios</span>
                <svg class="chevron <?= $isServicios ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isServicios ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <?php if (hasPermiso('ORNATOS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/ornatos" class="nav-link <?= $current === 'ornatos' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L9.5 9.5L2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5L12 2z"></path></svg>
                            <span>Gestionar ornatos</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('AMPLIACION_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/ampliacion" class="nav-link <?= $current === 'ampliacion' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            <span>Gestionar ampliacion</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('PROVEEDORES_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/suppliers" class="nav-link <?= $current === 'suppliers' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            <span>Gestionar proveedor</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showOperaciones): ?>
        <div class="sidebar-section-label">OPERACIONES</div>

        <?php if (hasPermiso('TRABAJADORES_VIEW') || hasPermiso('TAREAS_VIEW') || hasPermiso('RECOLECCION_VIEW')): ?>
        <div class="nav-group">
            <button class="nav-group-btn <?= $isTarea ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                <span>Gestionar Tarea</span>
                <svg class="chevron <?= $isTarea ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isTarea ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <?php if (hasPermiso('TAREAS_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/tasks" class="nav-link <?= $current === 'tasks' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            <span>Asignar tarea</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('TRABAJADORES_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/employees" class="nav-link <?= $current === 'employees' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                            <span>Gestionar Empleados</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermiso('RECOLECCION_VIEW')): ?>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/seed-collection" class="nav-link <?= $current === 'seed-collection' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M17 18a5 5 0 0 0-10 0"></path><line x1="12" y1="9" x2="12" y2="2"></line><line x1="4.22" y1="10.22" x2="5.64" y2="11.64"></line><line x1="1" y1="18" x2="3" y2="18"></line><line x1="21" y1="18" x2="23" y2="18"></line><line x1="18.36" y1="11.64" x2="19.78" y2="10.22"></line><polyline points="8 21 12 17 16 21"></polyline></svg>
                            <span>Gestionar recoleccion</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showHerramientas): ?>
        <div class="sidebar-section-label">HERRAMIENTAS</div>

        <?php if (hasPermiso('ASISTENTE_ACCESS')): ?>
        <a href="<?= BASE_URL ?>dashboard/asistente" class="nav-link <?= $current === 'asistente' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            <span>Asistente IA</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermiso('DASHBOARD_VIEW')): ?>
        <a href="<?= BASE_URL ?>dashboard/reports" class="nav-link <?= $current === 'reports' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            <span>Reportes</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showSistema): ?>
        <div class="sidebar-section-label">SISTEMA</div>

        <div class="nav-group">
            <button class="nav-group-btn <?= $isConfiguracion ? 'open-bg' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <span>Gestionar Configuracion</span>
                <svg class="chevron <?= $isConfiguracion ? 'rotate' : '' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="submenu-wrapper <?= $isConfiguracion ? 'show' : '' ?>">
                <ul class="submenu-inner">
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/usuarios" class="nav-link <?= $current === 'usuarios' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            <span>Usuario</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/roles" class="nav-link <?= $current === 'roles' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>Roles</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/auditlog" class="nav-link <?= $current === 'auditlog' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><line x1="8" y1="7" x2="16" y2="7"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                            <span>Bitacora</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>dashboard/backups" class="nav-link <?= $current === 'backups' ? 'active' : '' ?>">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Respaldo</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>login/logout" class="logout-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
