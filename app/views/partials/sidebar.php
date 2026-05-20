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

        <?php if (hasPermiso('INVENTARIO_VIEW') || hasPermiso('PLANTAS_VIEW') || hasPermiso('PROVEEDORES_VIEW') || hasPermiso('TRABAJADORES_VIEW') || hasPermiso('INSUMOS_VIEW') || hasPermiso('CLIENTES_VIEW')): ?>
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
                            <path d="M2 22h20"></path>
                            <path d="M12 22V11"></path>
                            <path d="M12 14c2.5-2.5 6-3 7.5-1.5s1 5-1.5 7.5"></path>
                            <path d="M12 11c-2.5-2.5-6-3-7.5-1.5s-1 5 1.5 7.5"></path>
                        </svg>
                        <span>Plantas</span>
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
                        <span>Insumos</span>
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

        <?php if (hasPermiso('ASISTENTE_ACCESS')): ?>
        <div class="sidebar-section">
            <span class="sidebar-section-title">Herramientas</span>
            <ul class="sidebar-menu">
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
