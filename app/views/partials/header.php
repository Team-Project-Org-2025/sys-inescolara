<?php
/**
 * Header para páginas públicas
 */
?>
<header class="header">
    <div class="container">
        <nav class="nav">
            <a href="/" class="logo">
                <img src="/public/images/logo.png" alt="INECOLARA" class="logo-img">
                <div class="logo-text">
                    <span class="logo-title">INECOLARA</span>
                    <span class="logo-subtitle">Vivero Institucional</span>
                </div>
            </a>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menú">
                <span class="hamburger"></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="/" class="nav-link <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Inicio</a></li>
                <li><a href="/catalogo" class="nav-link <?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">Catálogo</a></li>
                <li><a href="/servicios" class="nav-link <?= ($currentPage ?? '') === 'servicios' ? 'active' : '' ?>">Servicios</a></li>
                <li><a href="/nosotros" class="nav-link <?= ($currentPage ?? '') === 'nosotros' ? 'active' : '' ?>">Nosotros</a></li>
                <li><a href="/contacto" class="nav-link <?= ($currentPage ?? '') === 'contacto' ? 'active' : '' ?>">Contacto</a></li>
                <li><a href="/login" class="btn btn-primary">Acceso Personal</a></li>
            </ul>
        </nav>
    </div>
</header>
