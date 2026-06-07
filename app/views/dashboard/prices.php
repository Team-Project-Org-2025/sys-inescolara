<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cálculo de Precios - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'prices';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Precios'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Cálculo de Precios por Planta</h1>
                    <p style="color: var(--text-secondary);">
                        Seleccione una planta y su categoría para calcular el precio unitario sumando todos los lotes.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Planta *</label>
                            <select class="form-select" id="calcPlanta">
                                <option value="">Seleccione...</option>
                                <?php if (empty($plants)): ?>
                                <option value="" disabled>No hay plantas disponibles</option>
                                <?php else: ?>
                                <?php foreach ($plants as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_comun']) ?></option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" id="calcCategoria">
                                <option value="">Todas</option>
                                <option value="germinado">Germinado</option>
                                <option value="en_crecimiento">En Crecimiento</option>
                                <option value="para_cosechar">Para Cosechar</option>
                                <option value="maduro">Maduro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">% Ganancia</label>
                            <input type="number" class="form-control" id="calcGanancia" step="0.01" min="0" value="30">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" id="btnCalcularPlanta">
                                <i class="fas fa-calculator"></i> Calcular
                            </button>
                        </div>
                    </div>

                    <div id="calcResultContainer" class="d-none">
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="alert alert-secondary py-2 mb-0 text-center">
                                    <small class="d-block text-muted">Total Costos</small>
                                    <strong id="calcTotalInsumos" class="fs-5">Bs 0,00</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-secondary py-2 mb-0 text-center">
                                    <small class="d-block text-muted">Total Plantas</small>
                                    <strong id="calcTotalPlantas" class="fs-5">0</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-info py-2 mb-0 text-center">
                                    <small class="d-block text-muted">Costo por Planta</small>
                                    <strong id="calcCostoPlanta" class="fs-5">Bs 0,00</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-success py-2 mb-0 text-center">
                                    <small class="d-block text-muted">Precio Sugerido</small>
                                    <strong id="calcPrecioSugerido" class="fs-5">Bs 0,00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>¿Cómo se calcula?</strong><br>
                            1. Se suman los costos (mano de obra + insumos + agua) de <strong>todos los lotes</strong> de la planta con la categoría seleccionada.<br>
                            2. Se suman las cantidades de plantas de esos lotes.<br>
                            3. <strong>Costo por Planta</strong> = Total Costos &divide; Total Plantas.<br>
                            4. <strong>Precio Sugerido</strong> = Costo por Planta &times; (1 + %Ganancia/100).
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th># Lote</th>
                                        <th>Categoría</th>
                                        <th>Cant. Actual</th>
                                        <th class="text-end">Mano de Obra</th>
                                        <th class="text-end">Insumos</th>
                                        <th class="text-end">Agua</th>
                                        <th class="text-end">Costo Total</th>
                                    </tr>
                                </thead>
                                <tbody id="calcLotesBody"></tbody>
                            </table>
                        </div>

                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-success" id="btnGuardarPlanta">
                                <i class="fas fa-save"></i> Guardar Precio Unitario para Todos los Lotes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Precios Registrados</span>
                    <button class="btn btn-outline-secondary btn-sm" onclick="if(typeof pricesTable !== 'undefined') pricesTable.ajax.reload(null, false)">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pricesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Planta</th>
                                    <th>Costo Insumos</th>
                                    <th>Ganancia</th>
                                    <th>Precio Sugerido</th>
                                    <th>Fecha</th>
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

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/prices.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/prices.js') ?>"></script>
</body>
</html>
