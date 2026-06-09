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
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Inicio</span>
                    </a>
                </li>
            </ul>
        </div>

        <?php if (hasPermiso('INVENTARIO_VIEW') || hasPermiso('PLANTAS_VIEW') || hasPermiso('PROVEEDORES_VIEW') || hasPermiso('TRABAJADORES_VIEW') || hasPermiso('INSUMOS_VIEW') || hasPermiso('CLIENTES_VIEW') || hasPermiso('TAREAS_VIEW') || hasPermiso('HERRAMIENTAS_VIEW') || hasPermiso('PRECIOS_VIEW') || hasPermiso('RECOLECCION_VIEW') || hasPermiso('TRAZABILIDAD_VIEW') || hasPermiso('MERMAS_VIEW')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Gestión</span>
            <ul class="sidebar-menu">
                <?php if (hasPermiso('INVENTARIO_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/inventario" class="sidebar-link <?= ($currentPage ?? '') === 'inventario' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span>Inventario</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('PLANTAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/plants" class="sidebar-link <?= ($currentPage ?? '') === 'plants' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22V11"></path>
                            <path d="M12 14c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 11c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                        </svg>
                        <span>Plantas</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?= BASE_URL ?>dashboard/batches" class="sidebar-link <?= ($currentPage ?? '') === 'batches' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 22h20"></path>
                            <path d="M12 22V11"></path>
                            <path d="M12 14c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 11c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                        </svg>
                        <span>Lotes</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/species" class="sidebar-link <?= ($currentPage ?? '') === 'species' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a8 8 0 0 1 8 8c0 3-2 5-4 7l-4 5-4-5c-2-2-4-4-4-7a8 8 0 0 1 8-8z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Especies</span>
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
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span>Compras</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('TRABAJADORES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/employees" class="sidebar-link <?= ($currentPage ?? '') === 'employees' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('INSUMOS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/supplies" class="sidebar-link <?= ($currentPage ?? '') === 'supplies' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                            <rect x="1" y="3" width="22" height="5"></rect>
                            <line x1="10" y1="12" x2="14" y2="12"></line>
                        </svg>
                        <span>Insumo</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('UNIDADES_MEDIDA_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/unit-measures" class="sidebar-link <?= ($currentPage ?? '') === 'unit-measures' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="2" x2="12" y2="22"></line>
                            <polyline points="17 7 12 2 7 7"></polyline>
                            <polyline points="17 17 12 22 7 17"></polyline>
                        </svg>
                        <span>U. Medida</span>
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
                <?php if (hasPermiso('PRECIOS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/prices" class="sidebar-link <?= ($currentPage ?? '') === 'prices' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>Precios</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('CLIENTES_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/clients" class="sidebar-link <?= ($currentPage ?? '') === 'clients' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Clientes</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermiso('TAREAS_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/tasks" class="sidebar-link <?= ($currentPage ?? '') === 'tasks' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                            <path d="M8.5 8.5v.01"></path>
                            <path d="M16 15.5v.01"></path>
                            <path d="M12 12v.01"></path>
                            <path d="M11 17v.01"></path>
                            <path d="M7 14v.01"></path>
                        </svg>
                        <span>Tareas</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('RECOLECCION_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/seed-collection" class="sidebar-link <?= ($currentPage ?? '') === 'seed-collection' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22V11"></path>
                            <path d="M12 14c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 11c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                            <circle cx="9" cy="9" r="2"></circle>
                        </svg>
                        <span>Recolección</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('TRAZABILIDAD_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/trazabilidad" class="sidebar-link <?= ($currentPage ?? '') === 'trazabilidad' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22V11"></path>
                            <path d="M12 14c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 11c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                            <path d="M9 12h6"></path>
                            <path d="M12 9v6"></path>
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

        <?php if (hasPermiso('VENTAS_ACCESS')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Operaciones</span>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>dashboard/ventas" class="sidebar-link <?= ($currentPage ?? '') === 'ventas' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span>Venta</span>
                    </a>
                </li>
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
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                            <path d="M8.5 8.5v.01"></path>
                            <path d="M16 15.5v.01"></path>
                            <path d="M12 12v.01"></path>
                            <path d="M11 17v.01"></path>
                            <path d="M7 14v.01"></path>
                        </svg>
                        <span>Asistente IA</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermiso('DASHBOARD_VIEW')): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/reports" class="sidebar-link <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
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
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/roles" class="sidebar-link <?= ($currentPage ?? '') === 'roles' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Roles</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/auditlog" class="sidebar-link <?= ($currentPage ?? '') === 'auditlog' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Bitácora</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/backups" class="sidebar-link <?= ($currentPage ?? '') === 'backups' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
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
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>