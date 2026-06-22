<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herramientas - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'herramientas';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Herramientas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Herramientas</h1>
                    <p style="color: var(--text-secondary);">Registro y control de inventario de herramientas del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddTool">
                    <i class="fas fa-plus"></i> Nueva Herramienta
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="herramientasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Fecha Adquisición</th>
                                    <th>Último Mantenimiento</th>
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

    <!-- Add Herramienta Modal -->
    <?php modal_form(['id' => 'addToolModal', 'title' => 'Agregar Herramienta', 'formId' => 'addToolForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Herramienta</label>
            <input type="text" class="form-control" name="nombre_herramienta" required placeholder="Ej: Pala, Rastrillo, Tijeras de podar" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <input type="text" class="form-control" name="tipo" placeholder="Ej: Manual, Eléctrica, Mecánica" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="disponible">Disponible</option>
                <option value="en_uso">En Uso</option>
                <option value="mantenimiento">En Mantenimiento</option>
                <option value="danada">Dañada</option>
                <option value="baja">De Baja</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha de Adquisición</label>
                <input type="date" class="form-control" name="fecha_adquisicion">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Último Mantenimiento</label>
                <input type="date" class="form-control" name="fecha_ultimo_mantenimiento">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" name="observacion" rows="2" placeholder="Notas adicionales..." maxlength="500"></textarea>
        </div>
    <?php modal_form_end('addToolForm'); ?>

    <!-- Edit Herramienta Modal -->
    <?php modal_form(['id' => 'editToolModal', 'title' => 'Editar Herramienta', 'formId' => 'editToolForm', 'hasHiddenId' => true, 'hiddenId' => 'editToolId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de la Herramienta</label>
            <input type="text" class="form-control" name="nombre_herramienta" id="editToolName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <input type="text" class="form-control" name="tipo" id="editToolType" placeholder="Ej: Manual, Eléctrica, Mecánica" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado" id="editToolStatus">
                <option value="disponible">Disponible</option>
                <option value="en_uso">En Uso</option>
                <option value="mantenimiento">En Mantenimiento</option>
                <option value="danada">Dañada</option>
                <option value="baja">De Baja</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha de Adquisición</label>
                <input type="date" class="form-control" name="fecha_adquisicion" id="editToolAcqDate">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Último Mantenimiento</label>
                <input type="date" class="form-control" name="fecha_ultimo_mantenimiento" id="editToolMaintDate">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" name="observacion" id="editToolObs" rows="2" placeholder="Notas adicionales..." maxlength="500"></textarea>
        </div>
    <?php modal_form_end('editToolForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/herramientas.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/herramientas.js') ?>"></script>
</body>
</html>
