<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Especies - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'especies';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Especies'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Especies</h1>
                    <p style="color: var(--text-secondary);">Catálogo de especies de plantas del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddSpecies">
                    <i class="fas fa-plus"></i> Nueva Especie
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="especiesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre de la Especie</th>
                                    <th>Descripción</th>
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

    <!-- Add Especie Modal -->
    <?php modal_form(['id' => 'addSpeciesModal', 'title' => 'Agregar Especie', 'formId' => 'addSpeciesForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Especie</label>
            <input type="text" class="form-control" name="nombre_especie" required placeholder="Ej: Rosal, Girasol, Cactus" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2" placeholder="Ej: Arbusto perenne con flores de colores variados" maxlength="500"></textarea>
            <small class="text-muted">Opcional</small>
        </div>
    <?php modal_form_end('addSpeciesForm'); ?>

    <!-- Edit Especie Modal -->
    <?php modal_form(['id' => 'editSpeciesModal', 'title' => 'Editar Especie', 'formId' => 'editSpeciesForm', 'hasHiddenId' => true, 'hiddenId' => 'editSpeciesId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Especie</label>
            <input type="text" class="form-control" name="nombre_especie" id="editSpeciesName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="editSpeciesDescripcion" rows="2" placeholder="Opcional" maxlength="500"></textarea>
        </div>
    <?php modal_form_end('editSpeciesForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/especies.js"></script>
</body>
</html>
