<?php

$reportTypes = [
    'plants_by_species' => 'Plantas por Especie',
    'lots_by_status' => 'Lotes por Estado',
    'inventory_summary' => 'Resumen de Inventario',
    'supply_stock' => 'Stock de Insumos',
    'recent_sales' => 'Ventas Recientes',
];

include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SYSINECOLARA</title>
    
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'reports';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="dashboard-page-title">Reportes</h1>
            </div>
            
            <div class="dashboard-header-right">
                <div class="sidebar-user" style="padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="sidebar-user-avatar" style="width: 36px; height: 36px; background-color: #e5a835; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1a1f2e; overflow: hidden; flex-shrink: 0;">
                        <?php
                        $headerAvatar = $_SESSION['user_avatar'] ?? null;
                        $headerName = $_SESSION['user_nombre'] ?? 'U';
                        if ($headerAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($headerAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($headerName, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.875rem;font-weight:500;color:#374151;white-space:nowrap;"><?= htmlspecialchars($headerName) ?></span>
                </div>
            </div>
        </header>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p style="color:var(--text-secondary);margin:0;">Selecciona un reporte para visualizar los datos.</p>
                <div class="d-flex gap-2 align-items-center">
                    <label for="reportType" class="form-label mb-0" style="font-weight:500;white-space:nowrap;">Tipo de reporte:</label>
                    <select id="reportType" class="form-select" style="width:auto;">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($reportTypes as $value => $label): ?>
                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="reportsTable" class="table table-striped table-hover w-100" style="display:none;">
                    </table>
                    <div id="reportPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-bar mb-3 d-block" style="font-size:3rem;color:var(--text-muted);opacity:0.4;"></i>
                        <h5 style="color:var(--text-secondary);">Selecciona un tipo de reporte</h5>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Los datos se cargarán automáticamente al seleccionar.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/reports.js"></script>
</body>
</html>
