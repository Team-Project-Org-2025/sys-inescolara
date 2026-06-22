<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades de Medida - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'unidades-medida';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Unidades de Medida'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Unidades de Medida</h1>
                    <p style="color: var(--text-secondary);">Catálogo de unidades de medida utilizadas en insumos.</p>
                </div>
                <button class="btn btn-primary" id="btnAddUnit">
                    <i class="fas fa-plus"></i> Nueva Unidad
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="unidadesMedidaTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
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

    <!-- Add Unit Modal -->
    <?php modal_form(['id' => 'addUnitModal', 'title' => 'Nueva Unidad de Medida', 'formId' => 'addUnitForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre" required maxlength="50" placeholder="Ej: Kilogramo">
        </div>
    <?php modal_form_end('addUnitForm'); ?>

    <!-- Edit Unit Modal -->
    <?php modal_form(['id' => 'editUnitModal', 'title' => 'Editar Unidad de Medida', 'formId' => 'editUnitForm', 'hasHiddenId' => true, 'hiddenId' => 'editUnitId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre" id="editUnitName" required maxlength="50">
        </div>
    <?php modal_form_end('editUnitForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/unidades-medida.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/unidades-medida.js') ?>"></script>
</body>
</html>
