<?php
include_once __DIR__ . '/../common/links.php';
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
    $currentPage = 'tools';
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
                        <table id="toolsTable" class="table table-striped table-hover w-100">
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

    <!-- Add Tool Modal -->
    <div class="modal fade" id="addToolModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addToolForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Herramienta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Herramienta</label>
                            <input type="text" class="form-control" name="nombre_herramienta" required placeholder="Ej: Pala, Rastrillo, Tijeras de podar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <input type="text" class="form-control" name="tipo" placeholder="Ej: Manual, Eléctrica, Mecánica">
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
                            <textarea class="form-control" name="observacion" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Tool Modal -->
    <div class="modal fade" id="editToolModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editToolForm">
                    <input type="hidden" name="id" id="editToolId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Herramienta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Herramienta</label>
                            <input type="text" class="form-control" name="nombre_herramienta" id="editToolName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <input type="text" class="form-control" name="tipo" id="editToolType" placeholder="Ej: Manual, Eléctrica, Mecánica">
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
                            <textarea class="form-control" name="observacion" id="editToolObs" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/tools.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/tools.js') ?>"></script>
</body>
</html>
