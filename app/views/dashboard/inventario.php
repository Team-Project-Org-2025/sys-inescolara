<?php
/**
 * Vista: Inventario / Lotes
 * Variables esperadas: $lotes (array), $filtros (array), $estadisticas (array)
 */
?>
<!-- Page Actions -->
<div class="page-actions">
    <div class="page-actions-left">
        <div class="search-box">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" class="search-input" placeholder="Buscar por nombre, código o lote..." id="searchLotes">
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn <?= ($filtros['estado'] ?? '') === '' ? 'active' : '' ?>" data-filter="todos">
                Todos
            </button>
            <button class="filter-btn <?= ($filtros['estado'] ?? '') === 'disponible' ? 'active' : '' ?>" data-filter="disponible">
                Disponibles
            </button>
            <button class="filter-btn <?= ($filtros['estado'] ?? '') === 'agotado' ? 'active' : '' ?>" data-filter="agotado">
                Agotados
            </button>
            <button class="filter-btn <?= ($filtros['estado'] ?? '') === 'bajo' ? 'active' : '' ?>" data-filter="bajo">
                Stock Bajo
            </button>
        </div>
    </div>
    
    <div class="page-actions-right">
        <button class="btn btn-outline" id="exportBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Exportar
        </button>
        <a href="/dashboard/inventario/nuevo" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo Lote
        </a>
    </div>
</div>

<!-- Stats Summary -->
<div class="stats-summary">
    <div class="stat-item">
        <span class="stat-value"><?= number_format($estadisticas['totalLotes'] ?? 0) ?></span>
        <span class="stat-label">Total Lotes</span>
    </div>
    <div class="stat-item">
        <span class="stat-value"><?= number_format($estadisticas['totalPlantas'] ?? 0) ?></span>
        <span class="stat-label">Total Plantas</span>
    </div>
    <div class="stat-item">
        <span class="stat-value text-success"><?= number_format($estadisticas['disponibles'] ?? 0) ?></span>
        <span class="stat-label">Disponibles</span>
    </div>
    <div class="stat-item">
        <span class="stat-value text-warning"><?= number_format($estadisticas['stockBajo'] ?? 0) ?></span>
        <span class="stat-label">Stock Bajo</span>
    </div>
    <div class="stat-item">
        <span class="stat-value text-error"><?= number_format($estadisticas['agotados'] ?? 0) ?></span>
        <span class="stat-label">Agotados</span>
    </div>
</div>

<!-- Inventory Table -->
<div class="table-container">
    <table class="data-table" id="inventoryTable">
        <thead>
            <tr>
                <th class="sortable" data-sort="codigo">
                    Código
                    <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 15l5 5 5-5"></path>
                        <path d="M7 9l5-5 5 5"></path>
                    </svg>
                </th>
                <th class="sortable" data-sort="planta">Planta</th>
                <th class="sortable" data-sort="lote">Lote</th>
                <th class="sortable" data-sort="cantidad">Cantidad</th>
                <th class="sortable" data-sort="ubicacion">Ubicación</th>
                <th class="sortable" data-sort="fecha">Fecha Ingreso</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="inventoryBody">
            <!-- Se llena dinámicamente con JavaScript o PHP -->
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="table-footer">
    <div class="table-info">
        Mostrando <span id="showingFrom">1</span>-<span id="showingTo">10</span> de <span id="totalItems">0</span> registros
    </div>
    <div class="pagination" id="tablePagination">
        <!-- Se llena dinámicamente -->
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-backdrop" id="editModalBackdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Lote</h3>
            <button class="modal-close" id="editModalClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form id="editLoteForm" class="modal-form">
            <input type="hidden" name="id" id="editLoteId">
            
            <div class="form-group">
                <label class="form-label" for="editCantidad">Cantidad</label>
                <input type="number" class="form-input" id="editCantidad" name="cantidad" min="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="editUbicacion">Ubicación</label>
                <input type="text" class="form-input" id="editUbicacion" name="ubicacion" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="editEstado">Estado</label>
                <select class="form-select" id="editEstado" name="estado">
                    <option value="disponible">Disponible</option>
                    <option value="reservado">Reservado</option>
                    <option value="agotado">Agotado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="editNotas">Notas</label>
                <textarea class="form-textarea" id="editNotas" name="notas" rows="3"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="cancelEdit">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-backdrop" id="deleteModalBackdrop"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Confirmar Eliminación</h3>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar el lote <strong id="deleteLoteName"></strong>?</p>
            <p class="text-muted">Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="cancelDelete">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
        </div>
    </div>
</div>
