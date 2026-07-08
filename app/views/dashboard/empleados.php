<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'empleados';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Empleados'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Empleados</h1>
                    <p style="color: var(--text-secondary);">Registro y control de empleados de la empresa.</p>
                </div>
                <button class="btn btn-primary" id="btnAddEmployee">
                    <i class="fas fa-plus"></i> Nuevo Empleado
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="empleadosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Cédula</th>
                                    <th>Cargo</th>
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

    <!-- Add Employee Modal -->
    <?php modal_form(['id' => 'addEmployeeModal', 'title' => 'Agregar Empleado', 'formId' => 'addEmployeeForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre_trabajador" required placeholder="Ej: Luis" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" class="form-control" name="apellido_trabajador" placeholder="Ej: Rodríguez" maxlength="50">
            <small class="text-muted">Opcional</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Cédula</label>
            <input type="text" class="form-control" name="cedula_trabajador" placeholder="Ej: V-12345678" maxlength="10" minlength="6">
            <small class="text-muted">Opcional</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" name="telefono_trabajador" placeholder="Ej: 0412-9876543" maxlength="11">
        </div>
        <div class="mb-3">
            <label class="form-label">Cargo</label>
            <select class="form-select" name="cargo">
                <option value="">Seleccione...</option>
                <?php foreach ($cargoOptions as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" name="activo" id="addEmployeeActivo" value="1" checked>
            <label class="form-check-label" for="addEmployeeActivo">Activo</label>
        </div>
    <?php modal_form_end('addEmployeeForm'); ?>

    <!-- Edit Employee Modal -->
    <?php modal_form(['id' => 'editEmployeeModal', 'title' => 'Editar Empleado', 'formId' => 'editEmployeeForm', 'hasHiddenId' => true, 'hiddenId' => 'editEmployeeId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre_trabajador" id="editEmployeeName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" class="form-control" name="apellido_trabajador" id="editEmployeeApellido" placeholder="Opcional" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Cédula</label>
            <input type="text" class="form-control" name="cedula_trabajador" id="editEmployeeCedula" placeholder="Opcional" maxlength="10">
        </div>
        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" name="telefono_trabajador" id="editEmployeeTelefono" placeholder="Opcional" maxlength="11">
        </div>
        <div class="mb-3">
            <label class="form-label">Cargo</label>
            <select class="form-select" name="cargo" id="editEmployeeCargo">
                <option value="">Seleccione...</option>
                <?php foreach ($cargoOptions as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" name="activo" id="editEmployeeActivo" value="1">
            <label class="form-check-label" for="editEmployeeActivo">Activo</label>
        </div>
    <?php modal_form_end('editEmployeeForm'); ?>

    <!-- Modal Detalle Empleado -->
    <?php modal_detail_start(['id' => 'detailEmployeeModal', 'title' => 'Detalle del Empleado', 'size' => '', 'bodyId' => 'detailEmployeeBody']); ?>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/empleados.js"></script>
</body>
</html>
