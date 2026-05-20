<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batches - INECOLARA</title>
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
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="dashboard-page-title">Batches</h1>
            </div>
            <div class="dashboard-header-right">
                <div class="dashboard-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Buscar...">
                </div>
                <button class="header-icon-btn" aria-label="Notificaciones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge"></span>
                </button>
                <div class="sidebar-user" style="padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="sidebar-user-avatar" style="width: 36px; height: 36px; background-color: #e5a835; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1a1f2e; overflow: hidden; flex-shrink: 0;">
                        <?php
                        $headerAvatar = $_SESSION['user_avatar'] ?? null;
                        $headerName = $_SESSION['user_nombre'] ?? 'U';
                        if ($headerAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($headerAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($headerName, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.875rem;font-weight:500;color:#374151;white-space:nowrap;"><?= htmlspecialchars($headerName) ?></span>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Batch Management</h1>
                    <p style="color: var(--text-secondary);">Administration of plant batches in the nursery.</p>
                </div>
                <button class="btn btn-primary" id="btnAddBatch">
                    <i class="fas fa-plus"></i> New Batch
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="batchesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Plant</th>
                                    <th>Species</th>
                                    <th>Planting Date</th>
                                    <th>Initial Qty</th>
                                    <th>Current Qty</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Actions</th>
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
                        <h5 class="modal-title">Add Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Plant</label>
                            <select class="form-select" name="id_planta" required>
                                <option value="">Select a plant...</option>
                                <?php foreach ($plants as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_comun']) ?><?= $p['especie_nombre'] ? ' (' . htmlspecialchars($p['especie_nombre']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Planting Date</label>
                            <input type="date" class="form-control" name="fecha_siembra" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Initial Quantity</label>
                                <input type="number" class="form-control" name="cantidad_inicial" min="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Current Quantity</label>
                                <input type="number" class="form-control" name="cantidad_actual" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="estado" required>
                                    <option value="">Select...</option>
                                    <option value="Alive">Alive</option>
                                    <option value="Growing">Growing</option>
                                    <option value="Flowering">Flowering</option>
                                    <option value="Harvested">Harvested</option>
                                    <option value="Dead">Dead</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="ubicacion" placeholder="E.g: Greenhouse A" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Image</label>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="addBatchImage">
                            <small class="text-muted">Formats: jpg, png, gif, webp. Max 5MB.</small>
                            <div class="mt-2">
                                <img id="addBatchPreview" class="img-preview">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
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
                        <h5 class="modal-title">Edit Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Plant</label>
                            <select class="form-select" name="id_planta" id="editBatchPlant" required>
                                <option value="">Select a plant...</option>
                                <?php foreach ($plants as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_comun']) ?><?= $p['especie_nombre'] ? ' (' . htmlspecialchars($p['especie_nombre']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Planting Date</label>
                            <input type="date" class="form-control" name="fecha_siembra" id="editBatchDate" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Initial Quantity</label>
                                <input type="number" class="form-control" name="cantidad_inicial" id="editBatchQtyInit" min="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Current Quantity</label>
                                <input type="number" class="form-control" name="cantidad_actual" id="editBatchQtyCurr" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="estado" id="editBatchStatus" required>
                                    <option value="">Select...</option>
                                    <option value="Alive">Alive</option>
                                    <option value="Growing">Growing</option>
                                    <option value="Flowering">Flowering</option>
                                    <option value="Harvested">Harvested</option>
                                    <option value="Dead">Dead</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="ubicacion" id="editBatchLocation" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Image</label>
                            <div id="editImageCurrent" class="mb-2" style="display:none;">
                                <img src="" alt="Current image" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--color-primary);">
                            </div>
                            <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" id="editBatchImage">
                            <small class="text-muted">Leave empty to keep current image.</small>
                            <div class="mt-2">
                                <img id="editBatchPreview" class="img-preview">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
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
                    <img id="lightboxImg" src="" alt="Batch image" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>

    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/batches.js"></script>
</body>
</html>
