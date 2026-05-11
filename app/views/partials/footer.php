<?php

/**
 * Footer para páginas públicas
 */
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-logo">
                    <img src="<?= BASE_URL ?>public/images/logo.png" alt="INECOLARA" class="footer-logo-img">
                    <div>
                        <span class="footer-logo-title">INECOLARA</span>
                        <span class="footer-logo-subtitle">Instituto de Ecosocialismo del Estado Lara</span>
                    </div>
                </div>
                <p class="footer-desc">
                    Promoviendo la conservación ambiental y el desarrollo sustentable a través de la producción de plantas nativas y ornamentales.
                </p>
            </div>

            <div class="footer-col">
                <h4 class="footer-title">Enlaces Rápidos</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>">Inicio</a></li>
                    <li><a href="<?= BASE_URL ?>catalogo">Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>servicios">Servicios</a></li>
                    <li><a href="<?= BASE_URL ?>nosotros">Nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>contacto">Contacto</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-title">Servicios</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>servicios#ornamentales">Plantas Ornamentales</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#reforestacion">Reforestación</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#proyectos">Proyectos Institucionales</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#asesoria">Asesoría Técnica</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-title">Contacto</h4>
                <ul class="footer-contact">
                    <li>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Barquisimeto, Estado Lara, Venezuela</span>
                    </li>
                    <li>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>(0251) 123-4567</span>
                    </li>
                    <li>
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>vivero@inecolara.gob.ve</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> INECOLARA - Instituto de Ecosocialismo del Estado Lara. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>