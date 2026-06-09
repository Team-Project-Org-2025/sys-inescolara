<?php

function hasPermiso(string $codigo): bool
{
    $permisos = $_SESSION['user_permisos'] ?? [];
    return in_array($codigo, $permisos, true);
}

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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <span class="sidebar-section-title">Principal</span>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>dashboard" class="sidebar-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Inicio</span>
                    </a>
                </li>
            </ul>
        </div>

        <?php if (hasPermiso('PLANTAS_VIEW') || hasPermiso('UBICACIONES_VIEW') || hasPermiso('AMPLIACION_VIEW') || hasPermiso('RECOLECCION_VIEW') || hasPermiso('ORNATOS_VIEW') || hasPermiso('TRAZABILIDAD_VIEW') || hasPermiso('MERMAS_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Producción</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('PLANTAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/species" class="sidebar-link <?= ($currentPage ?? '') === 'species' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="6" y1="3" x2="6" y2="15"></line>
                            <circle cx="18" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                            <path d="M18 9a9 9 0 0 1-9 9"></path>
                        </svg>
                        <span>Especies</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/plants" class="sidebar-link <?= ($currentPage ?? '') === 'plants' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22v-9"></path>
                            <path d="M12 13c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 10c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                        </svg>
                        <span>Plantas</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/batches" class="sidebar-link <?= ($currentPage ?? '') === 'batches' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5"></polygon>
                            <line x1="12" y1="22" x2="12" y2="15.5"></line>
                            <polyline points="2 8.5 12 15.5 22 8.5"></polyline>
                        </svg>
                        <span>Lotes</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('UBICACIONES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/locations" class="sidebar-link <?= ($currentPage ?? '') === 'locations' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Ubicación</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('AMPLIACION_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/ampliacion" class="sidebar-link <?= ($currentPage ?? '') === 'ampliacion' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        <span>Ampliación</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('RECOLECCION_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/seed-collection" class="sidebar-link <?= ($currentPage ?? '') === 'seed-collection' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 18a5 5 0 0 0-10 0"></path>
                            <line x1="12" y1="9" x2="12" y2="2"></line>
                            <line x1="4.22" y1="10.22" x2="5.64" y2="11.64"></line>
                            <line x1="1" y1="18" x2="3" y2="18"></line>
                            <line x1="21" y1="18" x2="23" y2="18"></line>
                            <line x1="18.36" y1="11.64" x2="19.78" y2="10.22"></line>
                            <polyline points="8 21 12 17 16 21"></polyline>
                        </svg>
                        <span>Recolección</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('ORNATOS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/ornatos" class="sidebar-link <?= ($currentPage ?? '') === 'ornatos' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L9.5 9.5L2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5L12 2z"></path>
                        </svg>
                        <span>Ornatos</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('TRAZABILIDAD_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/trazabilidad" class="sidebar-link <?= ($currentPage ?? '') === 'trazabilidad' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>Monitoreo</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('MERMAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/mermas" class="sidebar-link <?= ($currentPage ?? '') === 'mermas' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <span>Mermas</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('INVENTARIO_VIEW') || hasPermiso('INSUMOS_VIEW') || hasPermiso('HERRAMIENTAS_VIEW') || hasPermiso('UNIDADES_MEDIDA_VIEW') || hasPermiso('PROVEEDORES_VIEW') || hasPermiso('COMPRAS_VIEW') || hasPermiso('CUENTAS_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Inventario y Suministros</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('INVENTARIO_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/inventario" class="sidebar-link <?= ($currentPage ?? '') === 'inventario' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span>Inventario</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('INSUMOS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/supplies" class="sidebar-link <?= ($currentPage ?? '') === 'supplies' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                        </svg>
                        <span>Insumos</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('HERRAMIENTAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/tools" class="sidebar-link <?= ($currentPage ?? '') === 'tools' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                        <span>Herramientas</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('UNIDADES_MEDIDA_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/unit-measures" class="sidebar-link <?= ($currentPage ?? '') === 'unit-measures' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        <span>U. Medida</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('PROVEEDORES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/suppliers" class="sidebar-link <?= ($currentPage ?? '') === 'suppliers' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                        <span>Proveedores</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('COMPRAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/compras" class="sidebar-link <?= ($currentPage ?? '') === 'compras' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span>Compras</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('CUENTAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/cuentas-pagar" class="sidebar-link <?= ($currentPage ?? '') === 'cuentas-pagar' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <polyline points="19 12 12 19 5 12"></polyline>
                        </svg>
                        <span>Cuentas x Pagar</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('PRECIOS_VIEW') || hasPermiso('CLIENTES_VIEW') || hasPermiso('VENTAS_ACCESS') || hasPermiso('CUENTAS_COBRAR_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Comercial</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('PRECIOS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/prices" class="sidebar-link <?= ($currentPage ?? '') === 'prices' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        <span>Precios</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('CLIENTES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/clients" class="sidebar-link <?= ($currentPage ?? '') === 'clients' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Clientes</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('VENTAS_ACCESS')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/ventas" class="sidebar-link <?= ($currentPage ?? '') === 'ventas' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span>Ventas</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('CUENTAS_COBRAR_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/cuentas_cobrar" class="sidebar-link <?= ($currentPage ?? '') === 'cuentas-cobrar' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <polyline points="5 12 12 5 19 12"></polyline>
                        </svg>
                        <span>Cuentas x Cobrar</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('TRABAJADORES_VIEW') || hasPermiso('TAREAS_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Personal</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('TRABAJADORES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/employees" class="sidebar-link <?= ($currentPage ?? '') === 'employees' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('TAREAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/tasks" class="sidebar-link <?= ($currentPage ?? '') === 'tasks' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <span>Tareas</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('ASISTENTE_ACCESS') || hasPermiso('DASHBOARD_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Herramientas</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('ASISTENTE_ACCESS')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/asistente" class="sidebar-link <?= ($currentPage ?? '') === 'asistente' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                        <span>Asistente IA</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('DASHBOARD_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/reports" class="sidebar-link <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>Reportes</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (hasPermiso('USUARIOS_MANAGE')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Configuración</span>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>dashboard/usuarios" class="sidebar-link <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/roles" class="sidebar-link <?= ($currentPage ?? '') === 'roles' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>Roles</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/auditlog" class="sidebar-link <?= ($currentPage ?? '') === 'auditlog' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            <line x1="8" y1="7" x2="16" y2="7"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                        <span>Bitácora</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/backups" class="sidebar-link <?= ($currentPage ?? '') === 'backups' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Respaldos</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>login/logout" class="sidebar-link logout-link">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
