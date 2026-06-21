<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantas - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .plant-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform .2s;
            border: 2px solid #e5e7eb;
        }
        .plant-thumb:hover { transform: scale(1.1); }
        .plant-thumb-placeholder {
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
    $currentPage = 'plants';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Plantas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Catálogo de Plantas</h1>
                    <p style="color: var(--text-secondary);">Registro individual de plantas del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddPlant">
                    <i class="fas fa-plus"></i> Nueva Planta
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="plantsTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre Común</th>
                                    <th>Nombre Técnico</th>
                                    <th>Especie</th>
                                    <th>Stock en Lotes</th>
                                    <th>Precio Vigente</th>
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

    <!-- Add Plant Modal -->
    <div class="modal fade" id="addPlantModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addPlantForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Planta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre Común</label>
                            <input type="text" class="form-control" name="nombre_comun" required placeholder="Ej: Rosa, Cactus, Suculenta" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Técnico (Científico)</label>
                            <input type="text" class="form-control" name="nombre_tecnico" placeholder="Ej: Rosa gallica, Echinocactus grusonii" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie / Grupo Familiar</label>
                            <select class="form-select" name="especie_id">
                                <option value="">Sin especie</option>
                                <?php foreach ($species as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_especie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Opcional. Selecciona la especie a la que pertenece.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen</label>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="addPlantImage">
                            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
                            <div class="mt-2">
                                <img id="addPlantPreview" class="img-preview">
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

    <!-- Edit Plant Modal -->
    <div class="modal fade" id="editPlantModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editPlantForm">
                    <input type="hidden" name="id" id="editPlantId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Planta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre Común</label>
                            <input type="text" class="form-control" name="nombre_comun" id="editPlantName" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Técnico</label>
                            <input type="text" class="form-control" name="nombre_tecnico" id="editPlantTecnico" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie / Grupo Familiar</label>
                            <select class="form-select" name="especie_id" id="editPlantSpecies">
                                <option value="">Sin especie</option>
                                <?php foreach ($species as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_especie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen</label>
                            <div id="editImageCurrent" class="mb-2" style="display:none;">
                                <img src="" alt="Imagen actual" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--color-primary);">
                            </div>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="editPlantImage">
                            <small class="text-muted">Dejar vacío para mantener la imagen actual.</small>
                            <div class="mt-2">
                                <img id="editPlantPreview" class="img-preview">
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
                    <img id="lightboxImg" src="" alt="Imagen de planta" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/plants.js"></script>
</body>
</html>
