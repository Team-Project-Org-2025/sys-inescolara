<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respaldos - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'backups';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Respaldos'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Respaldos de Base de Datos</h1>
                    <p style="color: var(--text-secondary);">Administra los respaldos de las bases de datos del sistema. Puedes crear, descargar, restaurar y eliminar respaldos.</p>
                </div>
                <div>
                    <button id="createBackupBtn" class="btn btn-success">
                        <i class="fas fa-database"></i> Crear Respaldo Completo
                    </button>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-4">
                            <h5 class="card-title text-muted">Base de Datos de Datos</h5>
                            <p class="h3 mb-0" id="dbMainName"><?= htmlspecialchars(getenv('DB_NAME') ?: 'sysinescolara') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-4">
                            <h5 class="card-title text-muted">Base de Datos de Seguridad</h5>
                            <p class="h3 mb-0" id="dbSecName"><?= htmlspecialchars(getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : 'SysInescolara-Seguridad') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="backupsTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Base de Datos</th>
                                    <th>Fecha</th>
                                    <th>Tamaño</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Restaurar Respaldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>¿Estás seguro de que deseas restaurar este respaldo?</strong></p>
                    <p class="text-danger mb-0"><i class="fas fa-ban"></i> Esta acción <strong>sobrescribirá</strong> los datos actuales de la base de datos. No se puede deshacer.</p>
                    <hr>
                    <p class="mb-0">Archivo: <strong id="restoreFileName"></strong></p>
                    <p class="mb-0">Base de Datos: <strong id="restoreDbName"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirmRestoreBtn"><i class="fas fa-undo"></i> Restaurar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/backups.js"></script>
</body>
</html>
