<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insumo - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'insumos'; 
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Insumo'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Insumo</h1>
                    <p style="color: var(--text-secondary);">Registro y control de inventario de materiales, tierras y agroquímicos.</p>
                </div>
                <button class="btn btn-primary" id="btnAddSupply">
                    <i class="fas fa-plus"></i> Nuevo Insumo
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="insumosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre del Insumo</th>
                                    <th>Categoría</th>
                                    <th>U. Medida</th>
                                    <th>Stock Actual</th>
                                    <th>Costo Unitario (Actual)</th>
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

    <!-- Add Supply Modal -->
    <?php modal_form(['id' => 'addSupplyModal', 'title' => 'Agregar Insumo', 'formId' => 'addSupplyForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Insumo</label>
            <input type="text" class="form-control" name="nombre_insumo" required placeholder="Ej: Fertilizante NPK, Bolsa de polietileno" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Unidad de Medida</label>
            <select class="form-select" name="id_unidad_medida" required>
                <option value="">Seleccione...</option>
                <?php if (isset($unidades)): foreach ($unidades as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_unidad_medida']) ?> (<?= htmlspecialchars($u['simbolo']) ?>)</option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Categoría</label>
            <input type="text" class="form-control" name="categoria" placeholder="Ej: Fertilizantes, Herramientas, Empaques" maxlength="50">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Stock Inicial</label>
                <input type="number" step="0.01" class="form-control" name="stock_actual" required value="0.00">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Costo Unitario Actual</label>
                <input type="number" step="0.01" class="form-control" name="costo_unitario_actual" required placeholder="0.00">
            </div>
        </div>
    <?php modal_form_end('addSupplyForm'); ?>

    <!-- Edit Supply Modal -->
    <?php modal_form(['id' => 'editSupplyModal', 'title' => 'Editar Insumo', 'formId' => 'editSupplyForm', 'hasHiddenId' => true, 'hiddenId' => 'editSupplyId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Insumo</label>
            <input type="text" class="form-control" name="nombre_insumo" id="editSupplyName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Unidad de Medida</label>
            <select class="form-select" name="id_unidad_medida" id="editSupplyUnit" required>
                <option value="">Seleccione...</option>
                <?php if (isset($unidades)): foreach ($unidades as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_unidad_medida']) ?> (<?= htmlspecialchars($u['simbolo']) ?>)</option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Categoría</label>
            <input type="text" class="form-control" name="categoria" id="editSupplyCat" placeholder="Ej: Fertilizantes, Herramientas, Empaques" maxlength="50">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Stock Actual</label>
                <input type="number" step="0.01" class="form-control" name="stock_actual" id="editSupplyStock" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Costo Unitario Actual</label>
                <input type="number" step="0.01" class="form-control" name="costo_unitario_actual" id="editSupplyCost" required>
            </div>
        </div>
    <?php modal_form_end('editSupplyForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/insumos.js"></script>
</body>
</html>