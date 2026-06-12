<?php
/**
 * Vista: Página de inicio
 */
?>
<section class="home-hero">
    <div class="container home-hero-grid">
        <div class="home-hero-copy">
            <span class="home-kicker">Vivero Institucional INECOLARA</span>
            <h1>Propagamos vida para el Estado Lara</h1>
            <p>
                Producción, control y distribución de especies ornamentales y forestales
                con enfoque ambiental y social.
            </p>
            <div class="home-actions">
                <a href="<?= BASE_URL ?>catalogo" class="btn btn-primary btn-lg">Explorar catálogo</a>
                <a href="<?= BASE_URL ?>contacto" class="btn btn-outline btn-lg">Contáctanos</a>
            </div>
        </div>
        <div class="home-hero-card">
            <div class="home-stat">
                <strong>120+</strong>
                <span>Especies disponibles</span>
            </div>
            <div class="home-stat">
                <strong>50K+</strong>
                <span>Plantas al año</span>
            </div>
            <div class="home-stat">
                <strong>200+</strong>
                <span>Instituciones atendidas</span>
            </div>
        </div>
    </div>
</section>

<section class="home-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Nuestros servicios</h2>
            <p class="section-subtitle">Producción y suministro para programas ambientales y proyectos institucionales</p>
        </div>
        <div class="services-grid">
            <article class="service-card reveal">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                        <path d="M7 10h10"></path>
                        <path d="M7 14h6"></path>
                    </svg>
                </div>
                <h3>Ornamentales</h3>
                <p>Plantas decorativas para espacios públicos y privados.</p>
            </article>
            <article class="service-card reveal">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h3>Forestales</h3>
                <p>Especies nativas para reforestación y recuperación de áreas.</p>
            </article>
            <article class="service-card reveal">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3>Asesoría</h3>
                <p>Acompañamiento técnico para proyectos y mantenimiento.</p>
            </article>
        </div>
    </div>
</section>

<section class="home-section home-alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Plantas destacadas</h2>
            <p class="section-subtitle">Una selección de especies disponibles en el vivero</p>
        </div>
        <div class="plants-grid" id="featuredPlants"></div>
        <div class="section-footer">
            <a href="<?= BASE_URL ?>catalogo" class="btn btn-primary">Ver catálogo completo</a>
        </div>
    </div>
</section>

<section class="home-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content reveal">
                <h2 class="section-title">Sobre el vivero</h2>
                <p>
                    El vivero institucional de INECOLARA impulsa la producción de especies
                    para proyectos ambientales, educativos y comunitarios en el estado Lara.
                </p>
                <ul class="about-features">
                    <li>
                        <span class="about-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span>Producción controlada</span>
                    </li>
                    <li>
                        <span class="about-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span>Especies nativas</span>
                    </li>
                    <li>
                        <span class="about-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span>Asesoría técnica</span>
                    </li>
                    <li>
                        <span class="about-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span>Atención institucional</span>
                    </li>
                </ul>
            </div>
            <div class="about-image reveal">
                <img class="about-image-img" src="https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=450&fit=crop" alt="Vivero INECOLARA" loading="lazy">
                <div class="about-image-overlay">
                    <h3>INECOLARA</h3>
                    <p>Instituto de Ecosocialismo del Estado Lara</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="home-section home-alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Galería de plantas y paisajes</h2>
            <p class="section-subtitle">Especies producidas en nuestro vivero y paisajes del estado Lara</p>
        </div>
        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1476842384041-a57a4f124e2e?w=800&h=600&fit=crop" alt="Paisaje venezolano" loading="lazy">
                <div class="gallery-caption"><span>Paisajes del Estado Lara</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1598880940371-c756e015fea1?w=400&h=300&fit=crop" alt="Heliconia Caribaea" loading="lazy">
                <div class="gallery-caption"><span>Heliconia Caribaea</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1545241047-6083a3684587?w=400&h=300&fit=crop" alt="Palma Areca" loading="lazy">
                <div class="gallery-caption"><span>Palma Areca</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=400&h=300&fit=crop" alt="Ceiba" loading="lazy">
                <div class="gallery-caption"><span>Ceiba pentandra</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1567331711402-509c12c41959?w=400&h=300&fit=crop" alt="Anturio Rojo" loading="lazy">
                <div class="gallery-caption"><span>Anturio Rojo</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1509423350716-97f9360b4e09?w=400&h=300&fit=crop" alt="Planta de Jade" loading="lazy">
                <div class="gallery-caption"><span>Planta de Jade</span></div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1518495973542-4542c06a5843?w=400&h=300&fit=crop" alt="Apamate" loading="lazy">
                <div class="gallery-caption"><span>Apamate (Tabebuia rosea)</span></div>
            </div>
        </div>
    </div>
</section>
