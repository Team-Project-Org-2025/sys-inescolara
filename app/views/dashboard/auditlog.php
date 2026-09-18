<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'auditlog';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Bitácora'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1>Bitácora del Sistema</h1>
                    <p style="color: var(--text-secondary);">Registro de todas las acciones realizadas por los usuarios.</p>
                </div>
                <button class="btn btn-outline-success" id="btnExportCsv">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form id="auditlogFilters" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha desde</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_desde" id="fecha_desde">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha hasta</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_hasta" id="fecha_hasta">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Usuario</label>
                            <select class="form-select form-select-sm" name="id_usuario" id="id_usuario">
                                <option value="">Todos</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>"><?= htmlspecialchars($u['nombre_usuario']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Acción</label>
                            <select class="form-select form-select-sm" name="accion" id="accion">
                                <option value="">Todas</option>
                                <?php foreach ($actions as $a): ?>
                                <option value="<?= $a ?>"><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Tabla</label>
                            <select class="form-select form-select-sm" name="tabla_afectada" id="tabla_afectada">
                                <option value="">Todas</option>
                                <?php foreach ($tables as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearFilters">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="auditlogTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width:180px">Fecha</th>
                                    <th style="width:140px">Usuario</th>
                                    <th style="width:130px">Acción</th>
                                    <th style="width:140px">Tabla</th>
                                    <th style="width:80px">ID Reg.</th>
                                    <th style="width:60px" data-orderable="false">Detalle</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Detail Modal -->
    <?php modal_detail_start(['id' => 'detailModal', 'title' => 'Detalle del Cambio', 'size' => 'modal-lg modal-dialog-centered modal-dialog-scrollable', 'bodyId' => 'detailModalBody']); ?>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/auditlog.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/auditlog.js') ?>"></script>
</body>
</html>
