<?php
/**
 * Vista: Página de inicio
 * Variables esperadas: $featuredPlants (array de plantas destacadas)
 */
?>
<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Cultivamos el Futuro Verde de Lara</h1>
        <p class="hero-subtitle">
            El Vivero Institucional de INECOLARA produce plantas nativas y ornamentales 
            para la reforestación y embellecimiento del Estado Lara.
        </p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>catalogo" class="btn btn-primary btn-lg">Ver Catálogo</a>
            <a href="<?= BASE_URL ?>contacto" class="btn btn-outline btn-lg">Contáctanos</a>
        </div>
    </div>
</section>

<!-- Servicios Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Nuestros Servicios</h2>
            <p class="section-subtitle">Soluciones integrales para proyectos ambientales y paisajísticos</p>
        </div>
        
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon service-icon-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h3 class="service-title">Plantas Ornamentales</h3>
                <p class="service-desc">
                    Amplia variedad de plantas ornamentales para jardines, parques y espacios públicos. 
                    Flores, arbustos y árboles de sombra.
                </p>
                <a href="<?= BASE_URL ?>servicios#ornamentales" class="service-link">Conocer más</a>
            </div>
            
            <div class="service-card">
                <div class="service-icon service-icon-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 18a5 5 0 0 0-10 0"></path>
                        <line x1="12" y1="9" x2="12" y2="2"></line>
                        <line x1="4.22" y1="10.22" x2="5.64" y2="11.64"></line>
                        <line x1="1" y1="18" x2="3" y2="18"></line>
                        <line x1="21" y1="18" x2="23" y2="18"></line>
                        <line x1="18.36" y1="11.64" x2="19.78" y2="10.22"></line>
                        <line x1="23" y1="22" x2="1" y2="22"></line>
                        <polyline points="8 6 12 2 16 6"></polyline>
                    </svg>
                </div>
                <h3 class="service-title">Reforestación</h3>
                <p class="service-desc">
                    Producción de especies nativas para programas de reforestación y recuperación 
                    de áreas degradadas en el Estado Lara.
                </p>
                <a href="<?= BASE_URL ?>servicios#reforestacion" class="service-link">Conocer más</a>
            </div>
            
            <div class="service-card">
                <div class="service-icon service-icon-accent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18"></path>
                        <path d="M9 8h1"></path>
                        <path d="M9 12h1"></path>
                        <path d="M9 16h1"></path>
                        <path d="M14 8h1"></path>
                        <path d="M14 12h1"></path>
                        <path d="M14 16h1"></path>
                        <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path>
                    </svg>
                </div>
                <h3 class="service-title">Proyectos Institucionales</h3>
                <p class="service-desc">
                    Asesoría y suministro de plantas para proyectos de instituciones públicas, 
                    escuelas, hospitales y comunidades organizadas.
                </p>
                <a href="<?= BASE_URL ?>servicios#proyectos" class="service-link">Conocer más</a>
            </div>
        </div>
    </div>
</section>

<!-- Plantas Destacadas -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Plantas Destacadas</h2>
            <p class="section-subtitle">Algunas de nuestras especies más solicitadas</p>
        </div>
        
        <div class="plants-grid" id="featuredPlants">
            <!-- Se llena dinámicamente con JavaScript o PHP -->
        </div>
        
        <div class="section-footer">
            <a href="<?= BASE_URL ?>catalogo" class="btn btn-primary">Ver Catálogo Completo</a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="section stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">50,000+</span>
                <span class="stat-label">Plantas Producidas Anualmente</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">120+</span>
                <span class="stat-label">Especies Disponibles</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">15</span>
                <span class="stat-label">Años de Experiencia</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">200+</span>
                <span class="stat-label">Instituciones Atendidas</span>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h2 class="section-title">Sobre el Vivero</h2>
                <p>
                    El Vivero Institucional de INECOLARA es un espacio dedicado a la producción 
                    de plantas nativas y ornamentales, comprometido con la conservación del 
                    medio ambiente y el desarrollo sustentable del Estado Lara.
                </p>
                <p>
                    Contamos con personal técnico especializado y las instalaciones necesarias 
                    para garantizar plantas de alta calidad, adaptadas a las condiciones 
                    climáticas de nuestra región.
                </p>
                <ul class="about-features">
                    <li>
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Personal técnico especializado
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Especies nativas certificadas
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Asesoría técnica gratuita
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Precios accesibles para instituciones
                    </li>
                </ul>
                <a href="<?= BASE_URL ?>nosotros" class="btn btn-primary">Conocer Más</a>
            </div>
            <div class="about-image">
                <img src="<?= BASE_URL ?>public/images/vivero-about.jpg" alt="Vivero INECOLARA" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">¿Tienes un Proyecto en Mente?</h2>
            <p class="cta-text">
                Contáctanos para asesorarte en la selección de plantas ideales para tu proyecto 
                de reforestación, jardinería o paisajismo.
            </p>
            <div class="cta-actions">
                <a href="<?= BASE_URL ?>contacto" class="btn btn-white btn-lg">Solicitar Asesoría</a>
                <a href="tel:+582511234567" class="btn btn-outline-white btn-lg">Llamar Ahora</a>
            </div>
        </div>
    </div>
</section>
