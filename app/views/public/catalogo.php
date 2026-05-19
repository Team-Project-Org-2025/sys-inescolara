<?php
/**
 * Vista: Catálogo de plantas
 */
?>
<section class="catalog-hero">
    <div class="container">
        <span class="home-kicker">Catálogo institucional</span>
        <h1>Especies disponibles para reforestación y ornamentación</h1>
        <p>Explora una selección ordenada de plantas producidas en el vivero.</p>
    </div>
</section>

<section class="home-section">
    <div class="container">
        <div class="catalog-toolbar">
            <input type="text" id="catalogSearch" placeholder="Buscar planta..." class="form-input">
            <div class="catalog-toolbar-actions">
                <button class="btn btn-outline" onclick="setFilter('type','all')">Todas</button>
                <button class="btn btn-outline" onclick="setFilter('type','Ornamental')">Ornamentales</button>
                <button class="btn btn-outline" onclick="setFilter('type','Forestal')">Forestales</button>
            </div>
        </div>

        <div class="plants-grid" id="catalogGrid"></div>

        <div class="empty-state hidden" id="emptyState">
            <h3>No se encontraron plantas</h3>
            <p>Prueba con otros filtros o términos de búsqueda.</p>
        </div>
    </div>
</section>

<div class="modal" id="plantModal">
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-content modal-lg">
        <button class="modal-close" id="modalClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="modal-body" id="plantModalBody"></div>
    </div>
</div>
