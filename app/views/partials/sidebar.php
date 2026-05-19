<?php

?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>dashboard" class="sidebar-logo">
            <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="sidebar-logo-img">
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">INECOLARA</span>
                <span class="sidebar-logo-subtitle">Ecosocialismo</span>
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

        <div class="sidebar-section">
            <span class="sidebar-section-title">Gestión</span>
            <ul class="sidebar-menu">
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
                <!--                 <li>
                    <a href="<?= BASE_URL ?>dashboard/lotes" class="sidebar-link <?= ($currentPage ?? '') === 'lotes' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                        <span>Lotes</span>
                    </a>
                </li> -->
                <!--                 <li>
                    <a href="<?= BASE_URL ?>dashboard/plantas" class="sidebar-link <?= ($currentPage ?? '') === 'plantas' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 20h10"></path>
                            <path d="M12 20v-6"></path>
                            <path d="M9 6.5V3.75A1.75 1.75 0 0 1 10.75 2h2.5A1.75 1.75 0 0 1 15 3.75V6.5"></path>
                            <path d="M12 6.5a6 6 0 0 0-6 6v1.5h12v-1.5a6 6 0 0 0-6-6z"></path>
                        </svg>
                        <span>Plantas</span>
                    </a>
                </li> -->
            </ul>
        </div>

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
                <!--                 <li>
                    <a href="<?= BASE_URL ?>dashboard/despachos" class="sidebar-link <?= ($currentPage ?? '') === 'despachos' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                        <span>Despachos</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard/cuadrillas" class="sidebar-link <?= ($currentPage ?? '') === 'cuadrillas' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Cuadrillas</span>
                    </a>
                </li>
            </ul>
        </div>
         -->
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
                        <!--                <li>
                    <a href="<?= BASE_URL ?>dashboard/reportes" class="sidebar-link <?= ($currentPage ?? '') === 'reportes' ? 'active' : '' ?>">
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Reportes</span>
                    </a>
                </li> -->
                    </ul>
                </div>

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
                            <a href="<?= BASE_URL ?>dashboard/plants" class="sidebar-link <?= ($currentPage ?? '') === 'plants' ? 'active' : '' ?>">
                                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>Plantas</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="sidebar-link <?= ($currentPage ?? '') === 'configuracion' ? 'active' : '' ?>">
                                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                <span>Configuración</span>
                            </a>
                        </li>
                    </ul>
                </div>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>logout" class="sidebar-link logout-link">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>