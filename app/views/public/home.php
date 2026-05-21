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
                <a href="<?= BASE_URL ?>login" class="btn btn-outline btn-lg">Acceso personal</a>
            </div>
        </div>
        <div class="home-hero-card">
            <div class="home-stat">
                <strong>0+</strong>
                <span>Especies disponibles</span>
            </div>
            <div class="home-stat">
                <strong>0+</strong>
                <span>Plantas al año</span>
            </div>
            <div class="home-stat">
                <strong>0+</strong>
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
            <article class="service-card">
                <h3>Ornamentales</h3>
                <p>Plantas decorativas para espacios públicos y privados.</p>
            </article>
            <article class="service-card">
                <h3>Forestales</h3>
                <p>Especies nativas para reforestación y recuperación de áreas.</p>
            </article>
            <article class="service-card">
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
            <div class="about-content">
                <h2 class="section-title">Sobre el vivero</h2>
                <p>
                    El vivero institucional de INECOLARA impulsa la producción de especies
                    para proyectos ambientales, educativos y comunitarios en el estado Lara.
                </p>
                <ul class="about-features">
                    <li><span class="about-feature-icon"></span><span>Producción controlada</span></li>
                    <li><span class="about-feature-icon"></span><span>Especies nativas</span></li>
                    <li><span class="about-feature-icon"></span><span>Asesoría técnica</span></li>
                    <li><span class="about-feature-icon"></span><span>Atención institucional</span></li>
                </ul>
            </div>
            <div class="about-image about-image-placeholder">
                <div>
                    <h3>INECOLARA</h3>
                    <p>Instituto de Ecosocialismo del Estado Lara</p>
                </div>
            </div>
        </div>
    </div>
</section>
