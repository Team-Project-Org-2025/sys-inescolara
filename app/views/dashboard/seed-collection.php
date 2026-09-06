<?php
$permisos = \SysInescolara\helpers\Auth::permisos();
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recolección de Semillas - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'seed-collection';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Recolección de Semillas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Recolección de Semillas</h1>
                    <p style="color: var(--text-secondary);">Asignación y control de tareas de recolección de semillas en campo.</p>
                </div>
                <?php if (\SysInescolara\helpers\Auth::hasModuleAccess('seed_collection', 'crear')): ?>
                <button class="btn btn-primary" id="btnAddRecoleccion">
                    <i class="fas fa-plus"></i> Registrar Recolección
                </button>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="recoleccionTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Trabajador</th>
                                    <th>Sitio de Recolección</th>
                                    <th>Fecha Asignación</th>
                                    <th>Estatus</th>
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

    <!-- Modal Registrar/Editar Recolección -->
    <?php modal_form(['id' => 'recoleccionModal', 'title' => 'Registrar Recolección', 'formId' => 'recoleccionForm', 'size' => 'modal-lg', 'hasHiddenId' => true, 'titleId' => 'recoleccionModalTitle', 'hiddenId' => 'recoleccionId', 'submitId' => 'recoleccionSubmitBtn']); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="id_usuario">Trabajador <span class="text-danger">*</span></label>
                <select class="form-select" name="id_usuario" id="id_usuario" required>
                    <option value="">Seleccione un trabajador</option>
                    <?php foreach ($trabajadores as $t): ?>
                        <option value="<?= (int)$t['id'] ?>">
                            <?= htmlspecialchars(($t['nombre_trabajador'] ?? '') . ' ' . ($t['apellido_trabajador'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="id_ubicacion">Sitio de Recolección <span class="text-danger">*</span></label>
                <div class="input-group">
                    <select class="form-select" name="id_ubicacion" id="id_ubicacion" required>
                        <option value="">Seleccione una ubicación</option>
                        <?php foreach ($ubicaciones as $u): ?>
                            <option value="<?= (int)$u['id'] ?>">
                                <?= htmlspecialchars($u['nombre_ubicacion'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-success" type="button" id="btnAddUbicacionQuick" title="Agregar nueva ubicación">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="fecha_asignacion">Fecha de Asignación <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="fecha_asignacion" id="fecha_asignacion" required>
            </div>
            <div class="col-md-12">
                <label class="form-label" for="observacion">Observación</label>
                <textarea class="form-control" name="observacion" id="observacion" rows="2" maxlength="500"></textarea>
            </div>
        </div>
    <?php modal_form_end('recoleccionForm'); ?>

    <!-- Modal Quick-Add Ubicación -->
    <?php modal_form(['id' => 'ubicacionQuickModal', 'title' => 'Nueva Ubicación', 'formId' => 'ubicacionQuickForm', 'saveText' => 'Guardar Ubicación', 'saveClass' => 'success']); ?>
        <div class="mb-3">
            <label class="form-label" for="quick_nombre_ubicacion">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre_ubicacion" id="quick_nombre_ubicacion" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label" for="quick_descripcion">Descripción</label>
            <textarea class="form-control" name="descripcion" id="quick_descripcion" rows="2" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <input type="hidden" name="tipo" value="Externo">
            <input type="text" class="form-control" value="Externo" disabled>
        </div>
    <?php modal_form_end('ubicacionQuickForm'); ?>

    <!-- Modal Completar Recolección -->
    <?php modal_form(['id' => 'completarModal', 'title' => 'Completar Recolección', 'formId' => 'completarForm', 'hasHiddenId' => true, 'hiddenId' => 'completarId', 'saveText' => 'Completar', 'saveClass' => 'success']); ?>
        <p>¿Confirmas que esta recolección ha sido realizada?</p>
        <div class="mb-3">
            <label class="form-label" for="fecha_recoleccion">Fecha de Recolección <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="fecha_recoleccion" id="fecha_recoleccion" required>
        </div>
    <?php modal_form_end('completarForm'); ?>

    <!-- Modal Registrar Insumos (múltiples semillas) -->
    <?php modal_form(['id' => 'insumoModal', 'title' => 'Registrar Semillas Recolectadas', 'formId' => 'insumoForm', 'size' => 'modal-lg', 'hasHiddenId' => true, 'hiddenId' => 'insumoRecoleccionId', 'saveText' => 'Registrar Semillas']); ?>
        <p style="color: var(--text-secondary);">Agrega los tipos de semillas recolectadas. Cada tipo se registrará como un insumo.</p>
        <div class="table-responsive">
            <table class="table table-bordered" id="insumosTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:28%;">Planta de origen</th>
                        <th style="width:28%;">Nombre de la Semilla</th>
                        <th style="width:24%;">Cantidad</th>
                        <th style="width:auto;"></th>
                    </tr>
                </thead>
                <tbody id="insumosTableBody">
                    <!-- filas se agregan dinámicamente -->
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnAddInsumoRow">
            <i class="fas fa-plus"></i> Agregar otra semilla
        </button>
    <?php modal_form_end('insumoForm'); ?>

    <!-- Template oculto para fila de insumo -->
    <template id="insumoRowTemplate">
        <tr>
            <td>
                <select class="form-select form-select-sm insumo-planta">
                    <option value="">Seleccione</option>
                    <?php foreach ($plantas as $p): ?>
                        <option value="<?= htmlspecialchars($p['nombre_comun'] ?? $p['nombre_tecnico'] ?? '') ?>">
                            <?= htmlspecialchars($p['nombre_comun'] ?? $p['nombre_tecnico'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm insumo-nombre" placeholder="Ej: Semillas de Araguaney" required maxlength="50">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm insumo-cantidad" placeholder="0.00" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-insumo-row" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    </template>

    <!-- Modal Detalle de Recolección -->
    <?php modal_detail_start(['id' => 'detailModal', 'title' => 'Detalle de Recolección', 'size' => 'modal-lg', 'bodyId' => 'detailModalBody']); ?>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/seed-collection.js?v=<?= time() ?>"></script>
</body>
</html>
