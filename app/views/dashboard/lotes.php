<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotes - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .lote-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .2s;
            border: 2px solid #e5e7eb;
        }
.lote-thumb:hover { transform: scale(1.1); }
.lote-thumb-placeholder {
            width: 80px;
            height: 80px;
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
    $currentPage = 'lotes';
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
                        <table id="lotesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Planta</th>
                                    <th>Especie</th>
                                    <th>Ubicación</th>
                                    <th>Cant. Actual</th>
                                    <th>Precio</th>
                                    <th>% Ganancia</th>
                                    <th>Estado</th>
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

    <!-- Add Lote Modal -->
    <?php modal_form(['id' => 'addBatchModal', 'title' => 'Agregar Lote', 'formId' => 'addBatchForm']); ?>
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
            <label class="form-label">Ubicación</label>
            <select class="form-select" name="id_ubicacion" required>
                <option value="">Seleccione una ubicación...</option>
                <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nombre_ubicacion']) ?></option>
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
                <input type="number" class="form-control" name="cantidad_inicial" id="addBatchQtyInit" min="1" required>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Cantidad Actual</label>
                <input type="number" class="form-control" name="cantidad_actual" id="addBatchQtyCurr" min="0" required readonly>
            </div>
        </div>
        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label">Costo Unitario</label>
                <input type="number" class="form-control" name="costo_unitario" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Porcentaje de Ganancia (%)</label>
                <input type="number" class="form-control" name="porcentaje_ganancia" step="0.01" min="0" max="100" value="30" placeholder="30">
            </div>
        </div>
        <div class="row">
            <div class="col-4 mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="id_estado" id="addBatchEstado" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($estados as $e): ?>
                    <option value="<?= $e['id'] ?>"<?= $e['id'] === $estadoVivoId ? ' selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 mb-3">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="id_categoria">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 mb-3">
                <label class="form-label">Origen</label>
                <select class="form-select" name="id_origen" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($origenes as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" name="observacion" rows="2" placeholder="Opcional" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagen del Lote</label>
            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="addBatchImage">
            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
            <div class="mt-2">
                <img id="addBatchPreview" class="img-preview">
            </div>
        </div>
    <?php modal_form_end('addBatchForm'); ?>

    <!-- Edit Lote Modal -->
    <?php modal_form(['id' => 'editBatchModal', 'title' => 'Editar Lote', 'formId' => 'editBatchForm', 'hasHiddenId' => true, 'hiddenId' => 'editBatchId', 'saveText' => 'Actualizar']); ?>
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
            <label class="form-label">Ubicación</label>
            <select class="form-select" name="id_ubicacion" id="editBatchLocation" required>
                <option value="">Seleccione una ubicación...</option>
                <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nombre_ubicacion']) ?></option>
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
                <label class="form-label">Costo Unitario</label>
                <input type="number" class="form-control" name="costo_unitario" id="editBatchCostoUnitario" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Porcentaje de Ganancia (%)</label>
                <input type="number" class="form-control" name="porcentaje_ganancia" id="editBatchPorcentajeGanancia" step="0.01" min="0" max="100" value="30" placeholder="30">
            </div>
        </div>
        <div class="row">
            <div class="col-4 mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="id_estado" id="editBatchEstado" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($estados as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 mb-3">
                <label class="form-label">Categoría</label>
                <select class="form-select" name="id_categoria" id="editBatchCategoria">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 mb-3">
                <label class="form-label">Origen</label>
                <select class="form-select" name="id_origen" id="editBatchOrigen" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($origenes as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" name="observacion" id="editBatchObs" rows="2" placeholder="Opcional" maxlength="500"></textarea>
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
    <?php modal_form_end('editBatchForm'); ?>

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

    <!-- View Batch Detail Modal -->
    <?php modal_detail_start(['id' => 'viewBatchModal', 'title' => 'Detalle de Lote', 'size' => 'modal-lg']); ?>
        <div class="text-center mb-4">
            <img id="viewBatchImage" src="" alt="Imagen de lote" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;">
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Planta</label>
                <p class="fs-5 fw-medium" id="viewBatchPlanta">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Especie</label>
                <p class="fs-5 fw-medium" id="viewBatchEspecie">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Ubicación</label>
                <p class="fs-5 fw-medium" id="viewBatchUbicacion">—</p>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small text-uppercase">Fecha de Siembra</label>
                <p class="fs-5 fw-medium" id="viewBatchFecha">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Cantidad Inicial</label>
                <p class="fs-5 fw-medium" id="viewBatchCantInicial">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Cantidad Actual</label>
                <p class="fs-5 fw-medium" id="viewBatchCantActual">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Estado</label>
                <p class="fs-5 fw-medium" id="viewBatchEstado">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Costo Unitario</label>
                <p class="fs-5 fw-medium" id="viewBatchCostoUnitario">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">% Ganancia</label>
                <p class="fs-5 fw-medium" id="viewBatchPorcentajeGanancia">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Precio Final</label>
                <p class="fs-5 fw-medium text-success fw-bold" id="viewBatchPrecioFinal">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Categoría</label>
                <p class="fs-5 fw-medium" id="viewBatchCategoria">—</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted small text-uppercase">Origen</label>
                <p class="fs-5 fw-medium" id="viewBatchOrigen">—</p>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold text-muted small text-uppercase">Observación</label>
                <p class="fs-5 fw-medium" id="viewBatchObs">—</p>
            </div>
        </div>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/lotes.js"></script>
</body>
</html>
