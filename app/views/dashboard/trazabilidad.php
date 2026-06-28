<?php
$permisos = \SysInescolara\helpers\Auth::permisos();
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo de Ejemplares - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'trazabilidad';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Monitoreo de Ejemplares'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Trazabilidad</h1>
                    <p style="color: var(--text-secondary);">Control fitosanitario, cuarentena y monitoreo de ejemplares por lote.</p>
                </div>
                <?php if (\SysInescolara\helpers\Auth::hasModuleAccess('trazabilidad', 'crear')): ?>
                <button class="btn btn-primary" id="btnAddTrazabilidad">
                    <i class="fas fa-plus"></i> Registrar Cuarentena
                </button>
                <?php endif; ?>
            </div>

                    <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="trazabilidadTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Planta</th>
                                    <th>Cantidad en Cuarentena</th>
                                    <th>Estado de Salud</th>
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

    <!-- Modal Registrar/Editar Cuarentena -->
    <?php modal_form(['id' => 'trazabilidadModal', 'title' => 'Registrar Cuarentena', 'formId' => 'trazabilidadForm', 'size' => 'modal-lg', 'hasHiddenId' => true, 'titleId' => 'trazabilidadModalTitle', 'hiddenId' => 'trazabilidadId', 'submitId' => 'trazabilidadSubmitBtn']); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="id_lote">Lote <span class="text-danger">*</span></label>
                <select class="form-select" name="id_lote" id="id_lote" required>
                    <option value="">Seleccione un lote</option>
                </select>
                <div class="form-text" id="loteInfo"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cantidad">Cantidad a Apartar <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="cantidad" id="cantidad" min="1" step="1" required>
                <div class="form-text">Cantidad de ejemplares que pasarán a cuarentena.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="id_estado">Estado de Salud <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_estado" id="id_estado" required>
                        <option value="">Seleccione un estado</option>
                        <?php foreach ($estados as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="fecha_registro">Fecha de Cuarentena <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="fecha_registro" id="fecha_registro" required>
            </div>
            <div class="col-md-12">
                <label class="form-label" for="observacion">Observación</label>
                <textarea class="form-control" name="observacion" id="observacion" rows="2" placeholder="Motivo de la cuarentena, síntomas observados, etc." maxlength="500"></textarea>
            </div>
        </div>
    <?php modal_form_end('trazabilidadForm'); ?>

    <!-- Modal Editar Estado (quick) -->
    <?php modal_form(['id' => 'editEstadoModal', 'title' => 'Cambiar Estado de Salud', 'formId' => 'editEstadoForm', 'hasHiddenId' => true, 'hiddenId' => 'editEstadoId', 'submitId' => 'editEstadoSubmitBtn', 'saveText' => 'Cambiar Estado']); ?>
        <div class="mb-3">
            <label class="form-label" for="editEstadoSelect">Nuevo Estado <span class="text-danger">*</span></label>
            <select class="form-select" name="id_estado" id="editEstadoSelect" required>
                <option value="">Seleccione...</option>
                <?php foreach ($estados as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="alert alert-info mb-0" id="editEstadoAlert" style="display:none;">
            <i class="fas fa-info-circle"></i> <span id="editEstadoAlertMsg"></span>
        </div>
    <?php modal_form_end('editEstadoForm'); ?>

    <!-- Modal Ver Detalle -->
    <?php modal_detail_start(['id' => 'viewTrazabilidadModal', 'title' => 'Detalle de Trazabilidad']); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Lote</label>
                <p class="fs-5 fw-medium" id="viewTrazaLote">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Planta</label>
                <p class="fs-5 fw-medium" id="viewTrazaPlanta">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Cantidad en Cuarentena</label>
                <p class="fs-5 fw-medium" id="viewTrazaCantidad">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Estado de Salud</label>
                <p class="fs-5 fw-medium" id="viewTrazaEstado">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Fecha de Cuarentena</label>
                <p class="fs-5 fw-medium" id="viewTrazaFecha">—</p>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-semibold text-muted small text-uppercase">Observación</label>
                <p class="fs-5 fw-medium" id="viewTrazaObs">—</p>
            </div>
        </div>
    <?php modal_detail_end(); ?>

    <script>
        window.userPermisos = <?= json_encode($permisos) ?>;
        window.estadoVivoId = <?= (int)$estadoVivoId ?>;
        window.isAdmin = <?= \SysInescolara\helpers\Auth::isAdmin() ? 'true' : 'false' ?>;
    </script>
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/trazabilidad.js?v=<?= time() ?>"></script>
</body>
</html>
