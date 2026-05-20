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
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="dashboard-page-title">Plantas</h1>
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
                            <input type="text" class="form-control" name="nombre_comun" required placeholder="Ej: Rosa, Cactus, Suculenta">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Técnico (Científico)</label>
                            <input type="text" class="form-control" name="nombre_tecnico" placeholder="Ej: Rosa gallica, Echinocactus grusonii">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie / Grupo Familiar</label>
                            <select class="form-select" name="especie_id">
                                <option value="">Sin especie</option>
                                <?php foreach ($species as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_comun']) ?></option>
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
                            <input type="text" class="form-control" name="nombre_comun" id="editPlantName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Técnico</label>
                            <input type="text" class="form-control" name="nombre_tecnico" id="editPlantTecnico">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie / Grupo Familiar</label>
                            <select class="form-select" name="especie_id" id="editPlantSpecies">
                                <option value="">Sin especie</option>
                                <?php foreach ($species as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_comun']) ?></option>
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

    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/plants.js"></script>
</body>
</html>
