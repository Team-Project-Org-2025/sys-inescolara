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
                <?php if (in_array('TRAZABILIDAD_CREATE', $permisos)): ?>
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
                                    <th>Fecha de Cuarentena</th>
                                    <th>Observación</th>
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
                <label class="form-label" for="estado_salud">Estado de Salud <span class="text-danger">*</span></label>
                <select class="form-select" name="estado_salud" id="estado_salud" required>
                    <option value="">Seleccione un estado</option>
                    <option value="Sano">Sano</option>
                    <option value="Sospechoso">Sospechoso</option>
                    <option value="Enfermo">Enfermo</option>
                    <option value="Plaga">Plaga</option>
                    <option value="Cuarentena">Cuarentena</option>
                    <option value="Bajo observación">Bajo observación</option>
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

    <script>
        window.userPermisos = <?= json_encode($permisos) ?>;
    </script>
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/trazabilidad.js?v=<?= time() ?>"></script>
</body>
</html>
