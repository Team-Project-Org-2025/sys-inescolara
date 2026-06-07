<?php
$permisos = $_SESSION['user_permisos'] ?? [];
include_once __DIR__ . '/../common/links.php';
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
                <?php if (in_array('RECOLECCION_CREATE', $permisos)): ?>
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
                                    <th>Fecha Recolección</th>
                                    <th>Estatus</th>
                                    <th>Insumo</th>
                                    <th>Cantidad</th>
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
    <div class="modal fade" id="recoleccionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recoleccionModalTitle">Registrar Recolección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="recoleccionForm">
                    <input type="hidden" name="id" id="recoleccionId" value="0">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="id_trabajador">Trabajador <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_trabajador" id="id_trabajador" required>
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
                                <textarea class="form-control" name="observacion" id="observacion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="recoleccionSubmitBtn">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Quick-Add Ubicación -->
    <div class="modal fade" id="ubicacionQuickModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Ubicación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="ubicacionQuickForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="quick_nombre_ubicacion">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre_ubicacion" id="quick_nombre_ubicacion" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="quick_descripcion">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="quick_descripcion" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="quick_zona">Zona</label>
                            <input type="text" class="form-control" name="zona" id="quick_zona">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Ubicación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Completar Recolección -->
    <div class="modal fade" id="completarModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Completar Recolección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="completarForm">
                    <input type="hidden" name="id" id="completarId" value="0">
                    <div class="modal-body">
                        <p>¿Confirmas que esta recolección ha sido realizada?</p>
                        <div class="mb-3">
                            <label class="form-label" for="fecha_recoleccion">Fecha de Recolección <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_recoleccion" id="fecha_recoleccion" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="completarSubmitBtn">
                            <i class="fas fa-check"></i> Completar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Insumo (Semillas recolectadas) -->
    <div class="modal fade" id="insumoModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Semillas como Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="insumoForm">
                    <input type="hidden" name="id" id="insumoRecoleccionId" value="0">
                    <div class="modal-body">
                        <p style="color: var(--text-secondary);">Registra las semillas recolectadas en el inventario de insumos.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="id_planta_insumo">Planta de origen <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_planta_insumo">
                                    <option value="">Seleccione una planta</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="nombre_insumo">Nombre de la Semilla <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre_insumo" id="nombre_insumo" placeholder="Ej: Semillas de Araguaney" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="insumo_id_unidad_medida">Unidad de Medida <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_unidad_medida" id="insumo_id_unidad_medida" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($unidades as $un): ?>
                                        <option value="<?= (int)$un['id'] ?>">
                                            <?= htmlspecialchars($un['nombre_unidad_medida'] ?? '') ?> (<?= htmlspecialchars($un['simbolo'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cantidad">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="cantidad" id="cantidad" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-seedling"></i> Registrar Insumo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/seed-collection.js"></script>
</body>
</html>
