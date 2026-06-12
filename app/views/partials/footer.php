<?php

/**
 * Footer para páginas públicas
 */
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="footer-logo-img" style="height:48px;margin-bottom:1rem">
                <p>
                    Promoviendo la conservación ambiental y el desarrollo sustentable a través de la producción de plantas nativas y ornamentales.
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook" title="Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                    <a href="#" aria-label="Instagram" title="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="#" aria-label="Twitter" title="Twitter">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-column">
                <h4>Enlaces Rápidos</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>">Inicio</a></li>
                    <li><a href="<?= BASE_URL ?>catalogo">Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>servicios">Servicios</a></li>
                    <li><a href="<?= BASE_URL ?>nosotros">Nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>contacto">Contacto</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h4>Servicios</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>servicios#ornamentales">Plantas Ornamentales</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#reforestacion">Reforestación</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#proyectos">Proyectos Institucionales</a></li>
                    <li><a href="<?= BASE_URL ?>servicios#asesoria">Asesoría Técnica</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h4>Contacto</h4>
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
