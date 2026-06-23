<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'clientes';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Clientes'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Clientes</h1>
                    <p style="color: var(--text-secondary);">Registro y control de clientes del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddClient">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="clientesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Contacto</th>
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

    <!-- Add Client Modal -->
    <?php modal_form(['id' => 'addClientModal', 'title' => 'Agregar Cliente', 'formId' => 'addClientForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Cliente</label>
            <input type="text" class="form-control" name="nombre_cliente" required placeholder="Ej: Juan Pérez" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Contacto</label>
            <input type="text" class="form-control" name="contacto_cliente" placeholder="Ej: 0412-1234567" maxlength="11">
            <small class="text-muted">Opcional</small>
        </div>
    <?php modal_form_end('addClientForm'); ?>

    <!-- Edit Client Modal -->
    <?php modal_form(['id' => 'editClientModal', 'title' => 'Editar Cliente', 'formId' => 'editClientForm', 'hasHiddenId' => true, 'hiddenId' => 'editClientId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Cliente</label>
            <input type="text" class="form-control" name="nombre_cliente" id="editClientName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Contacto</label>
            <input type="text" class="form-control" name="contacto_cliente" id="editClientContacto" placeholder="Opcional" maxlength="11">
        </div>
    <?php modal_form_end('editClientForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/clientes.js"></script>
</body>
</html>
