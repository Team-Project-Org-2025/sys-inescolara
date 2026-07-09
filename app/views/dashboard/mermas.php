<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mermas - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'mermas';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Mermas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Mermas y Bajas Definitivas</h1>
                    <p style="color: var(--text-secondary);">Registro formal de pérdidas de ejemplares desde cuarentena por plaga, daño mecánico, factores climáticos u otros.</p>
                </div>
                <button class="btn btn-warning" id="btnAddMerma">
                    <i class="fas fa-plus"></i> Registrar Merma
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="mermasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Cuarentena</th>
                                    <th>Planta</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View Merma Modal -->
            <?php modal_detail_start(['id' => 'viewMermaModal', 'title' => 'Detalles de la Merma']); ?>
                <div id="viewMermaContent"></div>
            <?php modal_detail_end(); ?>
        </div>
    </main>

    <!-- Add Merma Modal -->
    <?php modal_form(['id' => 'addMermaModal', 'title' => 'Registrar Merma desde Cuarentena', 'formId' => 'addMermaForm', 'saveText' => 'Registrar Merma', 'saveClass' => 'warning']); ?>
        <div class="mb-3">
            <label class="form-label">Registro de Cuarentena</label>
            <select class="form-select" name="id_trazabilidad" id="mermaQuarantine" required>
                <option value="">Seleccione una cuarentena...</option>
            </select>
            <div class="form-text">Solo se muestran cuarentenas con ejemplares disponibles.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Cantidad de ejemplares perdidos</label>
            <input type="number" class="form-control" name="cantidad" id="mermaCantidad" min="1" required placeholder="Ej: 5">
            <div class="form-text" id="quarantineStockInfo"></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Motivo</label>
            <select class="form-select" name="motivo" required>
                <option value="">Seleccione un motivo...</option>
                <option value="plaga">Plaga</option>
                <option value="enfermedad">Enfermedad</option>
                <option value="daño_mecanico">Daño Mecánico</option>
                <option value="factor_climatico">Factor Climático</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha de la merma</label>
            <input type="date" class="form-control" name="fecha_merma" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción (opcional)</label>
            <textarea class="form-control" name="descripcion" rows="3" placeholder="Detalles adicionales sobre la pérdida..." maxlength="500"></textarea>
        </div>
        <div class="alert alert-info mb-0" id="impactoPreview" style="display:none;">
            Impacto económico estimado: <strong id="impactoValue">$0.00</strong>
        </div>
    <?php modal_form_end('addMermaForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/mermas.js"></script>
</body>
</html>
