<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubicaciones - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'ubicaciones';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Ubicaciones'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Ubicaciones</h1>
                    <p style="color: var(--text-secondary);">Distribución geográfica, áreas de producción, lotes y secciones del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddLocation">
                    <i class="fas fa-plus"></i> Nueva Ubicación
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ubicacionesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Zona</th>
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

    <!-- Add Ubicacion Modal -->
    <?php modal_form(['id' => 'addLocationModal', 'title' => 'Agregar Ubicación', 'formId' => 'addLocationForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Ubicación</label>
            <input type="text" class="form-control" name="nombre_ubicacion" required placeholder="Ej: Invernadero Principal A, Sector de Sombreado 2..." maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2" placeholder="Opcional" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Zona</label>
            <input type="text" class="form-control" name="zona" placeholder="Ej: Producción, Cuarentena, Venta" maxlength="50">
        </div>
    <?php modal_form_end('addLocationForm'); ?>

    <!-- Edit Ubicacion Modal -->
    <?php modal_form(['id' => 'editLocationModal', 'title' => 'Editar Ubicación', 'formId' => 'editLocationForm', 'hasHiddenId' => true, 'hiddenId' => 'editLocationId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Ubicación</label>
            <input type="text" class="form-control" name="nombre_ubicacion" id="editLocationName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="editLocationDesc" rows="2" placeholder="Opcional" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Zona</label>
            <input type="text" class="form-control" name="zona" id="editLocationZona" placeholder="Ej: Producción, Cuarentena, Venta" maxlength="50">
        </div>
    <?php modal_form_end('editLocationForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/ubicaciones.js"></script>
</body>
</html>
