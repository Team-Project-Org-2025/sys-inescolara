<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'roles';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Roles'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Roles</h1>
                    <p style="color: var(--text-secondary);">Administración de roles del sistema.</p>
                </div>
                <button class="btn btn-primary" id="btnAddRole">
                    <i class="fas fa-plus"></i> Nuevo Rol
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rolesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Rol</th>
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

    <!-- Add Role Modal -->
    <?php modal_form(['id' => 'addRoleModal', 'title' => 'Agregar Rol', 'formId' => 'addRoleForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Rol</label>
            <input type="text" class="form-control" name="nombre_rol" required placeholder="Ej: Supervisor, Vendedor" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion_rol" rows="2" placeholder="Descripción opcional del rol" maxlength="500"></textarea>
        </div>
    <?php modal_form_end('addRoleForm'); ?>

    <!-- Edit Role Modal -->
    <?php modal_form(['id' => 'editRoleModal', 'title' => 'Editar Rol', 'formId' => 'editRoleForm', 'hasHiddenId' => true, 'hiddenId' => 'editRoleId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Rol</label>
            <input type="text" class="form-control" name="nombre_rol" id="editRoleName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion_rol" id="editRoleDesc" rows="2" maxlength="500"></textarea>
        </div>
    <?php modal_form_end('editRoleForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/roles.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/roles.js') ?>"></script>
</body>
</html>
