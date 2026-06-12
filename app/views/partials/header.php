<?php
/**
 * Header para páginas públicas
 */
?>
<header class="site-header">
    <div class="container">
        <a href="<?= BASE_URL ?>" class="site-logo">
            <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA">
            <div class="site-logo-text">
                <span>INECOLARA</span>
                <span>Vivero Institucional</span>
            </div>
        </a>

        <nav class="site-nav">
            <a href="<?= BASE_URL ?>" class="<?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Inicio</a>
            <a href="<?= BASE_URL ?>catalogo" class="<?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">Catálogo</a>
            <a href="<?= BASE_URL ?>servicios" class="<?= ($currentPage ?? '') === 'servicios' ? 'active' : '' ?>">Servicios</a>
            <a href="<?= BASE_URL ?>nosotros" class="<?= ($currentPage ?? '') === 'nosotros' ? 'active' : '' ?>">Nosotros</a>
            <a href="<?= BASE_URL ?>contacto" class="<?= ($currentPage ?? '') === 'contacto' ? 'active' : '' ?>">Contacto</a>
            <a href="<?= BASE_URL ?>login" class="btn btn-primary">Acceso Personal</a>
        </nav>

        <a href="<?= BASE_URL ?>login" class="btn btn-primary mobile-login-btn">Acceso Personal</a>
    </div>
</header>

<!-- Bottom Navbar (solo visible en mobile) -->
<nav class="bottom-nav" aria-label="Navegación principal">
    <a href="<?= BASE_URL ?>" class="<?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Inicio</span>
    </a>
    <a href="<?= BASE_URL ?>catalogo" class="<?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Catálogo</span>
    </a>
    <a href="<?= BASE_URL ?>servicios" class="<?= ($currentPage ?? '') === 'servicios' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
        </svg>
        <span>Servicios</span>
    </a>
    <a href="<?= BASE_URL ?>contacto" class="<?= ($currentPage ?? '') === 'contacto' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        <span>Contacto</span>
    </a>
    <a href="<?= BASE_URL ?>nosotros">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span>Nosotros</span>
    </a>
</nav>
