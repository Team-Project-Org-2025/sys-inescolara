<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotes - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .batch-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .2s;
            border: 2px solid #e5e7eb;
        }
        .batch-thumb:hover { transform: scale(1.1); }
        .batch-thumb-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 1.5rem;
            border: 2px dashed #d1d5db;
        }
        .img-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            border: 2px solid var(--color-primary);
        }
        #lightboxImg { max-height: 80vh; object-fit: contain; }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'batches';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Lotes'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Lotes</h1>
                    <p style="color: var(--text-secondary);">Administración de lotes de plantas en el vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddBatch">
                    <i class="fas fa-plus"></i> Nuevo Lote
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="batchesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Planta</th>
                                    <th>Especie</th>
                                    <th>Fecha Siembra</th>
                                    <th>Cant. Inicial</th>
                                    <th>Cant. Actual</th>
                                    <th>Estado</th>
                                    <th>Origen</th>
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

    <!-- Add Batch Modal -->
    <div class="modal fade" id="addBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addBatchForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Lote</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Planta</label>
                            <select class="form-select" name="id_planta" required>
                                <option value="">Seleccione una planta...</option>
                                <?php foreach ($plants as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_comun']) ?><?= $p['especie_nombre'] ? ' (' . htmlspecialchars($p['especie_nombre']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Siembra</label>
                            <input type="date" class="form-control" name="fecha_siembra" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Cantidad Inicial</label>
                                <input type="number" class="form-control" name="cantidad_inicial" min="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Cantidad Actual</label>
                                <input type="number" class="form-control" name="cantidad_actual" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Vivo">Vivo</option>
                                    <option value="Crecimiento">Crecimiento</option>
                                    <option value="Floración">Floración</option>
                                    <option value="Cosechado">Cosechado</option>
                                    <option value="Muerto">Muerto</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Origen</label>
                                <select class="form-select" name="origen" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Siembra">Siembra</option>
                                    <option value="Ampliación">Ampliación</option>
                                    <option value="Donación">Donación</option>
                                    <option value="Compra">Compra</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" name="observacion" rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen del Lote</label>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="addBatchImage">
                            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
                            <div class="mt-2">
                                <img id="addBatchPreview" class="img-preview">
                            </div>
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

    <!-- Edit Batch Modal -->
    <div class="modal fade" id="editBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editBatchForm">
                    <input type="hidden" name="id" id="editBatchId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Lote</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Planta</label>
                            <select class="form-select" name="id_planta" id="editBatchPlant" required>
                                <option value="">Seleccione una planta...</option>
                                <?php foreach ($plants as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_comun']) ?><?= $p['especie_nombre'] ? ' (' . htmlspecialchars($p['especie_nombre']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Siembra</label>
                            <input type="date" class="form-control" name="fecha_siembra" id="editBatchDate" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Cantidad Inicial</label>
                                <input type="number" class="form-control" name="cantidad_inicial" id="editBatchQtyInit" min="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Cantidad Actual</label>
                                <input type="number" class="form-control" name="cantidad_actual" id="editBatchQtyCurr" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" id="editBatchStatus" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Vivo">Vivo</option>
                                    <option value="Crecimiento">Crecimiento</option>
                                    <option value="Floración">Floración</option>
                                    <option value="Cosechado">Cosechado</option>
                                    <option value="Muerto">Muerto</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Origen</label>
                                <select class="form-select" name="origen" id="editBatchOrigen" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Siembra">Siembra</option>
                                    <option value="Ampliación">Ampliación</option>
                                    <option value="Donación">Donación</option>
                                    <option value="Compra">Compra</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" name="observacion" id="editBatchObs" rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen del Lote</label>
                            <div id="editImageCurrent" class="mb-2" style="display:none;">
                                <img src="" alt="Imagen actual" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--color-primary);">
                            </div>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="editBatchImage">
                            <small class="text-muted">Dejar vacío para mantener la imagen actual.</small>
                            <div class="mt-2">
                                <img id="editBatchPreview" class="img-preview">
                            </div>
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

    <!-- Lightbox Modal -->
    <div class="modal fade" id="imageLightbox" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index:10;"></button>
                    <img id="lightboxImg" src="" alt="Imagen de lote" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/batches.js"></script>
</body>
</html>
