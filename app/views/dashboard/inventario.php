<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'inventario';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Inventario'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Inventario General</h1>
                    <p style="color: var(--text-secondary);">Vista consolidada del inventario, movimientos y ajustes.</p>
                </div>
                <?php if (isset($showAdjustBtn) && $showAdjustBtn): ?>
                <button class="btn btn-warning" id="btnNewAdjustment">
                    <i class="fas fa-sliders-h"></i> Nuevo Ajuste
                </button>
                <?php endif; ?>
            </div>

            <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="consolidated-tab" data-bs-toggle="tab" data-bs-target="#consolidated" type="button" role="tab">
                        <i class="fas fa-boxes"></i> Consolidado
                    </button>
                </li>
                <?php if (isset($showAdjustBtn) && $showAdjustBtn): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="adjustments-tab" data-bs-toggle="tab" data-bs-target="#adjustments" type="button" role="tab">
                        <i class="fas fa-sliders-h"></i> Ajustes
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="inventoryTabsContent">
                <!-- Consolidado -->
                <div class="tab-pane fade show active" id="consolidated" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="consolidatedTable" class="table table-striped table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Nombre</th>
                                            <th>Stock Actual</th>
                                            <th>Unidad</th>
                                            <th>Ubicación</th>
                                            <th>Precio Unitario</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ajustes -->
                <?php if (isset($showAdjustBtn) && $showAdjustBtn): ?>
                <div class="tab-pane fade" id="adjustments" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="adjustmentsTable" class="table table-striped table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Insumo</th>
                                            <th>Trabajador</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Motivo</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Ajuste Modal -->
    <?php if (isset($showAdjustBtn) && $showAdjustBtn): ?>
    <?php modal_form(['id' => 'adjustmentModal', 'title' => 'Nuevo Ajuste de Inventario', 'formId' => 'adjustmentForm', 'saveText' => 'Registrar Ajuste', 'saveClass' => 'warning']); ?>
        <div class="mb-3">
            <label class="form-label">Insumo</label>
            <select class="form-select" name="id_insumo" required>
                <option value="">Seleccione...</option>
                <?php foreach ($supplies as $s): ?>
                <option value="<?= $s['id_insumo'] ?>"><?= htmlspecialchars($s['nombre_insumo']) ?> (Stock: <?= $s['stock_actual'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Trabajador</label>
            <select class="form-select" name="id_trabajador" required>
                <option value="">Seleccione...</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre_trabajador'] . ' ' . $e['apellido_trabajador']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Tipo de Ajuste</label>
                <select class="form-select" name="tipo_ajuste" required>
                    <option value="">Seleccione...</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control" name="cantidad" step="0.01" min="0.01" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Motivo</label>
            <textarea class="form-control" name="motivo" rows="2" maxlength="500" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha del Ajuste</label>
            <input type="date" class="form-control" name="fecha_ajuste" required>
        </div>
    <?php modal_form_end('adjustmentForm'); ?>
    <?php endif; ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/inventario.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/dashboard/inventario.js') ?>"></script>
    <?= $scripts_links ?>
</body>
</html>
