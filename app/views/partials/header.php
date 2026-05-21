<?php
/**
 * Header para páginas públicas - Versión Tarjeta con Barra Superior Verde Oscuro #2e7d32
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

        <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Abrir menú">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav class="site-nav">
            <a href="<?= BASE_URL ?>" class="<?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Inicio</a>
            <a href="<?= BASE_URL ?>catalogo" class="<?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">Catálogo</a>
            <a href="<?= BASE_URL ?>servicios" class="<?= ($currentPage ?? '') === 'servicios' ? 'active' : '' ?>">Servicios</a>
            <a href="<?= BASE_URL ?>nosotros" class="<?= ($currentPage ?? '') === 'nosotros' ? 'active' : '' ?>">Nosotros</a>
            <a href="<?= BASE_URL ?>contacto" class="<?= ($currentPage ?? '') === 'contacto' ? 'active' : '' ?>">Contacto</a>
            <a href="<?= BASE_URL ?>login" class="btn btn-primary">Acceso Personal</a>
        </nav>
    </div>

    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <button class="close-menu-btn" onclick="toggleMobileMenu()" aria-label="Cerrar menú">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <nav>
                <a href="<?= BASE_URL ?>" class="<?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Inicio</a>
                <a href="<?= BASE_URL ?>catalogo" class="<?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">Catálogo</a>
                <a href="<?= BASE_URL ?>servicios" class="<?= ($currentPage ?? '') === 'servicios' ? 'active' : '' ?>">Servicios</a>
                <a href="<?= BASE_URL ?>nosotros" class="<?= ($currentPage ?? '') === 'nosotros' ? 'active' : '' ?>">Nosotros</a>
                <a href="<?= BASE_URL ?>contacto" class="<?= ($currentPage ?? '') === 'contacto' ? 'active' : '' ?>">Contacto</a>
                <a href="<?= BASE_URL ?>login" class="btn btn-primary">Acceso Personal</a>
            </nav>
        </div>
    </div>
</header>