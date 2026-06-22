<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'inventario';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Inventario'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="mb-4">
                <h1>Inventario General</h1>
                <p style="color: var(--text-secondary);">Vista consolidada del inventario del vivero.</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label for="filterTipo" class="form-label mb-0 me-2">Filtrar por tipo:</label>
                            <select id="filterTipo" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="">Todos</option>
                                <option value="Planta">Plantas</option>
                                <option value="Insumo">Insumos</option>
                                <option value="Herramienta">Herramientas</option>
                                <option value="Lote">Lotes</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="consolidatedTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nombre</th>
                                    <th>Stock Actual</th>
                                    <th>Unidad</th>
                                    <th>Ubicación</th>
                                    <th>Precio Unitario</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/inventario.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/dashboard/inventario.js') ?>"></script>
    <?= $scripts_links ?>
</body>
</html>
