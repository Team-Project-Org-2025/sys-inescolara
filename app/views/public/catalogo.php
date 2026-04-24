<?php
/**
 * Vista: Catálogo de plantas
 * Variables esperadas: $plantas (array), $categorias (array), $filtros (array actual)
 */
?>
<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">Catálogo de Plantas</h1>
        <p class="page-subtitle">Explora nuestra variedad de especies disponibles</p>
    </div>
</section>

<!-- Catalog Section -->
<section class="section">
    <div class="container">
        <div class="catalog-layout">
            <!-- Filters Sidebar -->
            <aside class="catalog-filters">
                <div class="filters-header">
                    <h3 class="filters-title">Filtros</h3>
                    <button class="filters-clear" id="clearFilters">Limpiar</button>
                </div>
                
                <form id="filtersForm" class="filters-form">
                    <!-- Search -->
                    <div class="filter-group">
                        <label class="filter-label">Buscar</label>
                        <div class="search-input-wrapper">
                            <input type="text" name="buscar" id="searchInput" class="filter-input" placeholder="Nombre de planta...">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Category -->
                    <div class="filter-group">
                        <label class="filter-label">Categoría</label>
                        <select name="categoria" id="categoryFilter" class="filter-select">
                            <option value="">Todas las categorías</option>
                            <option value="ornamental">Ornamentales</option>
                            <option value="frutal">Frutales</option>
                            <option value="forestal">Forestales</option>
                            <option value="medicinal">Medicinales</option>
                            <option value="palma">Palmas</option>
                        </select>
                    </div>
                    
                    <!-- Care Level -->
                    <div class="filter-group">
                        <label class="filter-label">Nivel de Cuidado</label>
                        <div class="filter-checkboxes">
                            <label class="checkbox-label">
                                <input type="checkbox" name="cuidado[]" value="bajo">
                                <span class="checkbox-custom"></span>
                                Bajo
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="cuidado[]" value="medio">
                                <span class="checkbox-custom"></span>
                                Medio
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="cuidado[]" value="alto">
                                <span class="checkbox-custom"></span>
                                Alto
                            </label>
                        </div>
                    </div>
                    
                    <!-- Light -->
                    <div class="filter-group">
                        <label class="filter-label">Luz</label>
                        <div class="filter-checkboxes">
                            <label class="checkbox-label">
                                <input type="checkbox" name="luz[]" value="sol">
                                <span class="checkbox-custom"></span>
                                Sol directo
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="luz[]" value="sombra-parcial">
                                <span class="checkbox-custom"></span>
                                Sombra parcial
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="luz[]" value="sombra">
                                <span class="checkbox-custom"></span>
                                Sombra
                            </label>
                        </div>
                    </div>
                    
                    <!-- Availability -->
                    <div class="filter-group">
                        <label class="filter-label">Disponibilidad</label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="disponible" value="1" id="availableOnly">
                            <span class="checkbox-custom"></span>
                            Solo disponibles
                        </label>
                    </div>
                </form>
            </aside>
            
            <!-- Catalog Grid -->
            <div class="catalog-main">
                <div class="catalog-header">
                    <p class="catalog-results">
                        Mostrando <span id="resultsCount">0</span> plantas
                    </p>
                    <div class="catalog-sort">
                        <label for="sortSelect">Ordenar por:</label>
                        <select id="sortSelect" class="sort-select">
                            <option value="nombre">Nombre A-Z</option>
                            <option value="-nombre">Nombre Z-A</option>
                            <option value="precio">Precio: Menor a Mayor</option>
                            <option value="-precio">Precio: Mayor a Menor</option>
                        </select>
                    </div>
                </div>
                
                <div class="plants-grid" id="plantsGrid">
                    <!-- Se llena dinámicamente con JavaScript -->
                </div>
                
                <!-- Empty State -->
                <div class="empty-state hidden" id="emptyState">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <h3 class="empty-title">No se encontraron plantas</h3>
                    <p class="empty-text">Intenta ajustar los filtros de búsqueda</p>
                    <button class="btn btn-primary" id="resetFilters">Limpiar Filtros</button>
                </div>
                
                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <!-- Se llena dinámicamente con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Plant Detail Modal -->
<div class="modal" id="plantModal">
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-content modal-lg">
        <button class="modal-close" id="modalClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="modal-body" id="plantModalBody">
            <!-- Se llena dinámicamente con JavaScript -->
        </div>
    </div>
</div>
