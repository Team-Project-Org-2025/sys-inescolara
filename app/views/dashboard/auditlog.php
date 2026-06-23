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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Bitácora del Sistema</h1>
                    <p style="color: var(--text-secondary);">Registro de todas las acciones realizadas por los usuarios.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                            <table id="auditlogTable" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th style="width:160px">Fecha</th>
                                        <th style="width:140px">Usuario</th>
                                        <th style="width:130px">Acción</th>
                                        <th style="width:140px">Tabla</th>
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
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/auditlog.js"></script>
</body>
</html>
