<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Tareas - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .badge-estatus { font-size: 0.8rem; }
        .assignment-detail-label { font-weight: 600; color: var(--text-secondary); }
        .text-pre-wrap { white-space: pre-wrap; }
        .card-header h6 { font-size: 0.95rem; }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'tareas';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Asignar Tareas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Asignación de Tareas</h1>
                    <p style="color: var(--text-secondary);">Registra tareas, asígnalas a trabajadores y registra el consumo de insumos.</p>
                </div>
                <button class="btn btn-primary" id="btnAssignTask">
                    <i class="fas fa-plus"></i> Asignar Tarea
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Trabajador</th>
                                    <th>Tarea</th>
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

    <!-- ============ MODAL: ASIGNAR TAREA ============ -->
    <?php modal_form(['id' => 'assignTaskModal', 'title' => 'Asignar Tarea', 'formId' => 'assignTaskForm', 'size' => 'modal-lg', 'hasHiddenId' => true, 'saveText' => 'Guardar Asignación']); ?>
        <input type="hidden" name="id_asignacion">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Nombre de la tarea *</label>
                <input type="text" class="form-control" name="nombre_tarea" required placeholder="Ej: Regar plantas, Podar rosales" maxlength="50">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Trabajador *</label>
                <select class="form-select" name="id_usuario" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($trabajadores as $t): ?>
                    <?php
                        $displayName = trim(($t['nombre_trabajador'] ?? '') . ' ' . ($t['apellido_trabajador'] ?? ''));
                        if ($displayName === '') {
                            $displayName = $t['nombre_usuario'] ?? '—';
                        }
                    ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($displayName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Lote *</label>
                <select class="form-select" name="id_lote" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($lotes as $l): ?>
                    <option value="<?= $l['id'] ?>" data-planta="<?= htmlspecialchars($l['planta_nombre'] ?? '') ?>">
                        #<?= $l['id'] ?> - <?= htmlspecialchars($l['planta_nombre'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2" placeholder="Detalles adicionales de la tarea (opcional)" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha de Asignación</label>
            <input type="date" class="form-control" name="fecha_asignacion">
        </div>

        <div class="card border-success mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-success bg-opacity-10 text-success">
                <h6 class="mb-0"><i class="fas fa-boxes"></i> Consumo de Insumos</h6>
                <small class="text-muted">(opcional)</small>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-bordered mb-2">
                    <thead>
                        <tr>
                            <th style="width:35%">Insumo</th>
                            <th style="width:15%">Stock</th>
                            <th style="width:25%">Cantidad</th>
                            <th style="width:25%"></th>
                        </tr>
                    </thead>
                    <tbody id="consumptionsBody">
                        <!-- rows added by JS -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-success btn-add-row" id="btnAddConsumptionRow">
                    <i class="fas fa-plus"></i> Agregar Insumo
                </button>
            </div>
        </div>

        <div class="card border-primary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-primary bg-opacity-10 text-primary">
                <h6 class="mb-0"><i class="fas fa-wrench"></i> Uso de Herramientas</h6>
                <small class="text-muted">(opcional)</small>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-bordered mb-2">
                    <thead>
                        <tr>
                            <th style="width:35%">Herramienta</th>
                            <th style="width:15%">Disponibles</th>
                            <th style="width:25%">Cantidad</th>
                            <th style="width:25%"></th>
                        </tr>
                    </thead>
                    <tbody id="toolsBody">
                        <!-- rows added by JS -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary btn-add-row" id="btnAddToolRow">
                    <i class="fas fa-plus"></i> Agregar Herramienta
                </button>
            </div>
        </div>
    <?php modal_form_end('assignTaskForm'); ?>

    <!-- ============ MODAL: COMPLETAR ASIGNACIÓN ============ -->
    <?php modal_form(['id' => 'completeAssignModal', 'title' => 'Completar Asignación', 'formId' => 'completeAssignForm', 'size' => 'modal-lg', 'hasHiddenId' => true, 'hiddenId' => 'completeAssignId', 'saveText' => 'Completar', 'saveClass' => 'success']); ?>
        <p>¿Registrar la finalización de esta tarea?</p>
        <div class="mb-3">
            <label class="form-label">Fecha de Cumplimiento</label>
            <input type="date" class="form-control" name="fecha_cumplimiento">
        </div>
        <div class="mb-3">
            <label class="form-label">Horas Dedicadas</label>
            <input type="number" class="form-control" name="horas_dedicadas" step="0.25" min="0" max="999.99" placeholder="ej: 2.5">
        </div>
        <div class="card border-primary">
            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-primary bg-opacity-10 text-primary">
                <h6 class="mb-0"><i class="fas fa-wrench"></i> Estado de Herramientas</h6>
                <small class="text-muted">Post-uso</small>
            </div>
            <div class="card-body p-2">
                <p class="text-muted small mb-2">Indica el estado de cada herramienta después de su uso.</p>
                <div id="completeToolsContainer">
                    <!-- filled by JS -->
                </div>
            </div>
        </div>
    <?php modal_form_end('completeAssignForm'); ?>

    <!-- ============ MODAL: DETALLE DE ASIGNACIÓN ============ -->
    <?php modal_detail_start(['id' => 'detailAssignModal', 'title' => 'Detalle de Asignación', 'size' => 'modal-lg', 'bodyId' => 'detailAssignBody']); ?>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script>
        window.TASK_DATA = {
            tasksUrl: '<?= BASE_URL ?>tareas',
            insumos: <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>,
            herramientas: <?= json_encode($herramientas ?? [], JSON_UNESCAPED_UNICODE) ?>,
            hoy: '<?= date('Y-m-d') ?>'
        };
    </script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/tareas.js?v=2"></script>
</body>
</html>
