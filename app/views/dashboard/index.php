<?php
/**
 * Vista: Dashboard principal
 * Variables esperadas: $stats (array), $recentActivity (array), $lowStock (array), $user (array)
 */
?>
<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
        <div class="kpi-content">
            <span class="kpi-value"><?= number_format($stats['totalStock'] ?? 0) ?></span>
            <span class="kpi-label">Plantas en Stock</span>
        </div>
        <div class="kpi-trend kpi-trend-up">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            +12% este mes
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="kpi-content">
            <span class="kpi-value">Bs. <?= number_format($stats['ventasMes'] ?? 0, 2) ?></span>
            <span class="kpi-label">Ventas del Mes</span>
        </div>
        <div class="kpi-trend kpi-trend-up">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            +8% vs mes anterior
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
            </svg>
        </div>
        <div class="kpi-content">
            <span class="kpi-value"><?= number_format($stats['lotesActivos'] ?? 0) ?></span>
            <span class="kpi-label">Lotes Activos</span>
        </div>
        <div class="kpi-badge">
            <?= $stats['lotesPendientes'] ?? 0 ?> pendientes de validar
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="kpi-content">
            <span class="kpi-value"><?= number_format($stats['cuadrillasActivas'] ?? 0) ?></span>
            <span class="kpi-label">Cuadrillas Activas</span>
        </div>
        <div class="kpi-badge">
            <?= $stats['tareasHoy'] ?? 0 ?> tareas hoy
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Recent Activity -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">Actividad Reciente</h3>
            <a href="/dashboard/actividad" class="card-link">Ver todo</a>
        </div>
        <div class="card-body">
            <ul class="activity-list" id="activityList">
                <?php if (!empty($recentActivity)): ?>
                    <?php foreach ($recentActivity as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon activity-icon-<?= htmlspecialchars($activity['tipo'] ?? 'default') ?>">
                            <?php if (($activity['tipo'] ?? '') === 'venta'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <?php elseif (($activity['tipo'] ?? '') === 'ingreso'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text"><?= htmlspecialchars($activity['descripcion'] ?? '') ?></p>
                            <span class="activity-time"><?= htmlspecialchars($activity['tiempo'] ?? '') ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="activity-empty">No hay actividad reciente</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">
                <svg class="card-title-icon text-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Stock Bajo
            </h3>
            <a href="/dashboard/inventario?stock=bajo" class="card-link">Ver todo</a>
        </div>
        <div class="card-body">
            <ul class="stock-list" id="lowStockList">
                <?php if (!empty($lowStock)): ?>
                    <?php foreach ($lowStock as $item): ?>
                    <li class="stock-item">
                        <div class="stock-info">
                            <span class="stock-name"><?= htmlspecialchars($item['nombre'] ?? '') ?></span>
                            <span class="stock-lot">Lote: <?= htmlspecialchars($item['lote'] ?? '') ?></span>
                        </div>
                        <div class="stock-quantity stock-quantity-low">
                            <?= number_format($item['cantidad'] ?? 0) ?> uds.
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="stock-empty">No hay alertas de stock bajo</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">Acciones Rápidas</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="/dashboard/ventas" class="quick-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Nueva Venta
                </a>
                <a href="/dashboard/inventario/nuevo" class="quick-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Agregar Lote
                </a>
                <a href="/dashboard/despachos/nuevo" class="quick-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    Nuevo Despacho
                </a>
                <a href="/dashboard/reportes" class="quick-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Generar Reporte
                </a>
            </div>
        </div>
    </div>
    
    <!-- AI Assistant Mini -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">
                <svg class="card-title-icon text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                    <path d="M8.5 8.5v.01"></path>
                    <path d="M16 15.5v.01"></path>
                    <path d="M12 12v.01"></path>
                </svg>
                Asistente IA
            </h3>
            <a href="/dashboard/asistente" class="card-link">Abrir chat</a>
        </div>
        <div class="card-body">
            <div class="ai-mini-chat">
                <p class="ai-mini-intro">Haz preguntas sobre inventario, ventas o el estado del vivero:</p>
                <div class="ai-suggestions">
                    <button class="ai-suggestion-btn" data-question="¿Cuál es el stock actual?">
                        Stock actual
                    </button>
                    <button class="ai-suggestion-btn" data-question="¿Cuáles son las plantas más vendidas?">
                        Más vendidas
                    </button>
                    <button class="ai-suggestion-btn" data-question="¿Qué lotes necesitan atención?">
                        Lotes urgentes
                    </button>
                </div>
                <form class="ai-mini-form" id="aiMiniForm">
                    <input type="text" class="ai-mini-input" placeholder="Escribe tu pregunta..." id="aiMiniInput">
                    <button type="submit" class="ai-mini-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
