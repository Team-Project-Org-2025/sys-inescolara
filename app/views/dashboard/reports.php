<?php

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
        <?php $title = 'Reportes'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="reportModule" class="form-label fw-medium">Módulo</label>
                            <select id="reportModule" class="form-select">
                                <option value="">Cargando módulos...</option>
                            </select>
                        </div>
                        <div class="col" id="filtersContainer">
                            <div class="text-muted small py-2">Selecciona un módulo para ver los filtros disponibles.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="reportHeader" class="d-flex justify-content-between align-items-center mb-3 d-none">
                        <h5 class="mb-0" id="reportTitle">Reporte</h5>
                        <div class="d-flex gap-2">
                            <button id="btnRefresh" class="btn btn-outline-secondary btn-sm" title="Actualizar">
                                <i class="fas fa-sync-alt"></i> Refrescar
                            </button>
                            <button id="btnPdf" class="btn btn-outline-danger btn-sm" title="Exportar PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button id="btnCsv" class="btn btn-outline-success btn-sm" title="Exportar CSV">
                                <i class="fas fa-file-excel"></i> CSV
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-8" id="tableContainer">
                            <table id="reportsTable" class="table table-striped table-hover w-100">
                                <thead><tr></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="col-lg-4" id="chartContainer">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center py-2">
                                    <h6 class="card-title mb-0" id="chartTitle">Gráfico</h6>
                                    <select id="chartTypeSelector" class="form-select form-select-sm" style="width:auto;">
                                        <option value="bar">Barras</option>
                                        <option value="line">Líneas</option>
                                        <option value="pie">Pastel</option>
                                        <option value="doughnut">Donut</option>
                                        <option value="polarArea">Área Polar</option>
                                    </select>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                                        <canvas id="reportChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="reportPlaceholder" class="text-center py-5">
                        <i class="fas fa-chart-bar mb-3 d-block" style="font-size:3rem;color:var(--text-muted);opacity:0.4;"></i>
                        <h5 style="color:var(--text-secondary);">Selecciona un módulo de reporte</h5>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Los datos se cargarán automáticamente al seleccionar un módulo.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/reports.js"></script>
</body>
</html>
