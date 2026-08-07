<?php

include_once __DIR__ . '/../common/links.php';

$userName = \SysInescolara\helpers\Auth::name();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SYSINECOLARA</title>
    <?= $css_links ?>

<style>
.welcome-banner {
  background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 35%, #e5a835 100%);
  border-radius: var(--radius-xl);
  padding: var(--space-5) var(--space-6);
  margin-bottom: var(--space-5);
  position: relative;
  overflow: hidden;
}
.welcome-content h1 {
  font-size: 1.35rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 0.15rem;
}
.welcome-content > p {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.8);
  margin: 0 0 var(--space-3);
}
.time-filter-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: var(--space-3);
}
.time-preset {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.9);
  padding: 0.25rem 0.7rem;
  border-radius: var(--radius-full);
  font-size: 0.72rem;
  cursor: pointer;
  transition: all 0.15s;
  font-weight: 500;
  user-select: none;
}
.time-preset:hover {
  background: rgba(255,255,255,0.22);
  border-color: rgba(255,255,255,0.3);
}
.time-preset.active {
  background: rgba(255,255,255,0.95);
  border-color: #fff;
  color: #1b5e20;
}
.time-preset.active:hover {
  background: #fff;
}
.time-custom-wrap {
  display: none;
  align-items: center;
  gap: 0.35rem;
}
.time-custom-wrap.show {
  display: flex;
}
.time-custom-wrap input {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: var(--radius-md);
  padding: 0.25rem 0.5rem;
  color: #fff;
  font-size: 0.72rem;
  outline: none;
  width: 125px;
}
.time-custom-wrap input:focus {
  background: rgba(255,255,255,0.22);
  border-color: rgba(255,255,255,0.4);
}
.time-custom-wrap input::-webkit-calendar-picker-indicator {
  filter: invert(1);
  opacity: 0.5;
  cursor: pointer;
}
.time-custom-apply {
  background: rgba(255,255,255,0.95);
  border: none;
  color: #1b5e20;
  padding: 0.25rem 0.7rem;
  border-radius: var(--radius-full);
  font-size: 0.72rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.15s;
  user-select: none;
  line-height: 1.3;
}
.time-custom-apply:hover {
  background: #fff;
  transform: scale(1.03);
}
.module-select {
  max-width: 300px;
}
.module-select select {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: var(--radius-md);
  padding: 0.4rem 2rem 0.4rem 0.75rem;
  color: #fff;
  font-size: 0.85rem;
  width: 100%;
  outline: none;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='rgba(255,255,255,0.7)' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.6rem center;
  transition: all 0.2s;
}
.module-select select option {
  background: #fff;
  color: #1b5e20;
}
.module-select select:hover {
  background: rgba(255,255,255,0.22);
  border-color: rgba(255,255,255,0.35);
}
.module-select select:focus {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.5);
}
#summaryBar {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-1) 0;
  font-size: 0.78rem;
  color: var(--text-muted);
  border-bottom: 1px solid var(--color-gray-100);
  margin-bottom: var(--space-2);
}
#summaryBar .summary-count {
  font-weight: 600;
  color: var(--text-secondary);
}
#summaryBar .summary-ts {
  margin-left: auto;
  font-size: 0.7rem;
}
.report-chart-container {
  min-height: 380px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}
#reportChart {
  max-height: 360px;
  width: 100% !important;
}
#reportsTable thead th {
  background-color: var(--color-secondary);
  color: var(--text-light);
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  padding: 0.55rem 0.75rem;
  border-bottom: none;
  white-space: nowrap;
}
#reportsTable thead tr:first-child th:first-child {
  border-radius: 0.4rem 0 0 0;
}
#reportsTable thead tr:first-child th:last-child {
  border-radius: 0 0.4rem 0 0;
}
#reportsTable tbody td {
  padding: 0.4rem 0.75rem;
  vertical-align: middle;
  font-size: 0.825rem;
  border-bottom: 1px solid var(--color-gray-100);
}
#reportsTable tbody tr:nth-child(even) {
  background-color: rgba(46, 125, 50, 0.02);
}
#reportsTable tbody tr:hover {
  background-color: rgba(229, 168, 53, 0.06);
}
#reportsTable {
  border-collapse: separate;
  border-spacing: 0;
}

.report-filters-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.6rem;
}
.report-filters-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.filter-chip {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: var(--bg-primary);
  border: 1px solid var(--color-gray-200);
  border-radius: 0.5rem;
  padding: 0.4rem 0.5rem 0.4rem 0.6rem;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.filter-chip.has-value {
  border-color: rgba(46, 125, 50, 0.45);
  background: rgba(46, 125, 50, 0.04);
}
.filter-chip-label {
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  white-space: nowrap;
}
.filter-chip-control {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.filter-chip-control select,
.filter-chip-control input {
  font-size: 0.75rem;
  padding: 0.2rem 0.4rem;
  border-radius: 0.4rem;
  border: 1px solid var(--color-gray-200);
  color: var(--text-secondary);
  background: #fff;
}
.filter-chip-control input[type="date"],
.filter-chip-control input[type="text"] { width: 120px; }
.filter-chip-control input[type="number"] { width: 90px; }
.filter-chip-control .op-select { width: auto; min-width: 52px; }
.filter-chip-remove {
  border: none;
  background: rgba(220, 38, 38, 0.08);
  color: #dc2626;
  width: 20px;
  height: 20px;
  line-height: 1;
  border-radius: 50%;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
}
.filter-chip-remove:hover {
  background: rgba(220, 38, 38, 0.2);
}
.report-add-filter {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.report-add-filter select {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.4rem;
  border: 1px dashed var(--color-gray-300);
  background: var(--bg-primary);
  color: var(--text-secondary);
  cursor: pointer;
  min-width: 190px;
}
.report-filter-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.report-filter-actions .btn {
  font-size: 0.72rem;
  padding: 0.25rem 0.6rem;
  border-radius: 0.4rem;
}
.report-filters-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.4rem;
}
@media (max-width: 768px) {
  .welcome-banner { padding: var(--space-3); }
  #summaryBar { flex-wrap: wrap; }
  .time-custom-wrap input { width: 100px; }
}
</style>
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

            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1><i class="fas fa-chart-bar" style="margin-right:0.4rem;"></i> Reportes del Sistema</h1>
                    <p>Selecciona un módulo y un período para consultar.</p>

                    <!--
                    <div class="time-filter-bar" id="timeFilterBar">
                        <span class="time-preset active" data-period="all">Todo</span>
                        <span class="time-preset" data-period="today">Hoy</span>
                        <span class="time-preset" data-period="7d">7 días</span>
                        <span class="time-preset" data-period="30d">30 días</span>
                        <span class="time-preset" data-period="year">Este año</span>
                        <span class="time-preset" data-period="custom">Personalizado</span>
                        <span class="time-custom-wrap" id="timeCustomWrap">
                            <input type="date" id="timeDesde">
                            <span style="color:rgba(255,255,255,0.6);font-size:0.7rem;">→</span>
                            <input type="date" id="timeHasta">
                            <button id="timeCustomApply" class="time-custom-apply">Aplicar</button>
                        </span>
                    </div>
                    -->

                    <div class="module-select">
                        <select id="reportModuleSelect">
                            <option value="">— Seleccionar módulo —</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="dashboard-card" id="dataCard">
                <div class="dashboard-card-header" style="padding:var(--space-3) var(--space-4);">
                    <h3 class="dashboard-card-title" id="reportTitle" style="font-size:0.9rem;"><i class="fas fa-table" style="color:var(--color-secondary);"></i> Datos</h3>
                    <div class="d-flex gap-1 align-items-center d-none" id="reportActions">
                        <button id="btnRefresh" class="btn btn-sm" style="border:1px solid var(--color-gray-200);border-radius:0.4rem;padding:0.25rem 0.5rem;font-size:0.75rem;background:var(--bg-primary);color:var(--text-secondary);" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button id="btnPdf" class="btn btn-sm" style="border:1px solid rgba(220,38,38,0.2);border-radius:0.4rem;padding:0.25rem 0.5rem;font-size:0.75rem;background:rgba(220,38,38,0.06);color:#dc2626;" title="PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button id="btnCsv" class="btn btn-sm" style="border:1px solid rgba(46,125,50,0.2);border-radius:0.4rem;padding:0.25rem 0.5rem;font-size:0.75rem;background:rgba(46,125,50,0.06);color:var(--color-secondary);" title="CSV">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    </div>
                </div>
                <div class="dashboard-card-body" style="padding:var(--space-3) var(--space-4);">
                    <div class="report-filters-wrap d-none" id="filtersWrap">
                        <div class="report-filters-bar" id="filtersBar"></div>
                        <div class="report-filters-footer">
                            <div class="report-add-filter">
                                <select id="addFilterSelect" title="Agregar filtro">
                                    <option value="">+ Agregar filtro</option>
                                </select>
                            </div>
                            <div class="report-filter-actions">
                                <button id="btnClearFilters" class="btn btn-sm" title="Limpiar filtros">
                                    <i class="fas fa-broom" style="margin-right:0.25rem;"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="summaryBar" class="d-none">
                        <span class="summary-count" id="summaryCount">0 registros</span>
                        <span class="summary-ts" id="summaryTimestamp"></span>
                    </div>
                    <div id="tableContainer">
                        <table id="reportsTable" class="table w-100">
                            <thead><tr></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="reportPlaceholder" class="text-center py-4">
                        <i class="fas fa-chart-bar mb-2 d-block" style="font-size:2.5rem;color:var(--text-muted);opacity:0.3;"></i>
                        <h6 style="color:var(--text-secondary);font-weight:500;">Selecciona un módulo</h6>
                        <p style="color:var(--text-muted);font-size:0.85rem;margin:0;">Los datos se cargarán automáticamente.</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card d-none" id="chartCard">
                <div class="dashboard-card-header" style="padding:var(--space-3) var(--space-4);">
                    <h3 class="dashboard-card-title" id="chartTitle" style="font-size:0.9rem;"><i class="fas fa-chart-pie" style="color:var(--color-primary);"></i> Gráfico</h3>
                    <select id="chartTypeSelector" class="form-select form-select-sm" style="width:auto;border-radius:0.4rem;font-size:0.75rem;padding:0.2rem 0.5rem;">
                        <option value="bar">Barras</option>
                        <option value="line">Líneas</option>
                        <option value="pie">Pastel</option>
                        <option value="doughnut">Donut</option>
                    </select>
                </div>
                <div class="dashboard-card-body report-chart-container" style="padding:var(--space-3);">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

        </div>
    </main>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/reports.js?v=20260803"></script>
</body>
</html>
