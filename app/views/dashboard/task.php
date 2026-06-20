<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Tareas - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .consumptions-grid { margin-top: 1rem; }
        .consumptions-grid table { margin-bottom: 0.5rem; }
        .consumptions-grid .btn-add-row { margin-bottom: 0.5rem; }
        .badge-estatus { font-size: 0.8rem; }
        .assignment-detail-label { font-weight: 600; color: var(--text-secondary); }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'tasks';
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
                                    <th>#</th>
                                    <th>Trabajador</th>
                                    <th>Tarea</th>
                                    <th>Lote</th>
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
    <div class="modal fade" id="assignTaskModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="assignTaskForm">
                    <input type="hidden" name="id_asignacion">
                    <div class="modal-header">
                        <h5 class="modal-title">Asignar Tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la tarea *</label>
                                <input type="text" class="form-control" name="nombre_tarea" required placeholder="Ej: Regar plantas, Podar rosales">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Trabajador *</label>
                                <select class="form-select" name="id_trabajador" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($trabajadores as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_trabajador'] . ' ' . ($t['apellido_trabajador'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
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
                            <textarea class="form-control" name="descripcion" rows="2" placeholder="Detalles adicionales de la tarea (opcional)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Asignación</label>
                            <input type="date" class="form-control" name="fecha_asignacion">
                        </div>

                        <hr>
                        <h6><i class="fas fa-boxes"></i> Consumo de Insumos <small class="text-muted">(opcional)</small></h6>

                        <div class="consumptions-grid">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Insumo</th>
                                        <th style="width:12%">Stock</th>
                                        <th style="width:18%">Cantidad</th>
                                        <th style="width:15%">Costo Unit.</th>
                                        <th style="width:15%">Fecha</th>
                                        <th style="width:12%"></th>
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

                        <hr>
                        <h6><i class="fas fa-wrench"></i> Uso de Herramientas <small class="text-muted">(opcional)</small></h6>

                        <div class="tools-grid">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:40%">Herramienta</th>
                                        <th style="width:20%">Fecha</th>
                                        <th>Observación</th>
                                        <th style="width:10%"></th>
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: COMPLETAR ASIGNACIÓN ============ -->
    <div class="modal fade" id="completeAssignModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="completeAssignForm">
                    <input type="hidden" name="id" id="completeAssignId">
                    <div class="modal-header">
                        <h5 class="modal-title">Completar Asignación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Registrar la finalización de esta tarea?</p>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Cumplimiento</label>
                            <input type="date" class="form-control" name="fecha_cumplimiento">
                        </div>
                        <hr>
                        <h6><i class="fas fa-wrench"></i> Estado de Herramientas</h6>
                        <p class="text-muted small">Indica el estado de cada herramienta después de su uso.</p>
                        <div id="completeToolsContainer">
                            <!-- filled by JS -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Completar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: DETALLE DE ASIGNACIÓN ============ -->
    <div class="modal fade" id="detailAssignModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailAssignBody">
                    <!-- filled by JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script>
        window.TASK_DATA = {
            tasksUrl: '<?= BASE_URL ?>tasks',
            insumos: <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>,
            herramientas: <?= json_encode($herramientas ?? [], JSON_UNESCAPED_UNICODE) ?>,
            hoy: '<?= date('Y-m-d') ?>'
        };
    </script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/task.js"></script>
</body>
</html>
