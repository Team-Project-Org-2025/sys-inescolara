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
                    <h1>Cálculo de Precios</h1>
                    <p style="color: var(--text-secondary);">Gestión de costos y precios de venta por lote de producción.</p>
                </div>
                <button class="btn btn-primary" id="btnAddPrice">
                    <i class="fas fa-plus"></i> Nuevo Cálculo
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pricesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Planta</th>
                                    <th>Costo MO</th>
                                    <th>Costo Insumos</th>
                                    <th>Ganancia</th>
                                    <th>Precio Sugerido</th>
                                    <th>Vigente</th>
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

    <!-- Add Price Modal -->
    <div class="modal fade" id="addPriceModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addPriceForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Cálculo de Precio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Lote <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_lote" required>
                                <option value="">Seleccione un lote</option>
                                <?php foreach ($batches as $b): $hasPrice = in_array($b['id'], $batchIdsWithPrices); ?>
                                <option value="<?= $b['id'] ?>" data-planta="<?= htmlspecialchars($b['planta_nombre']) ?>" data-cantidad="<?= $b['cantidad_actual'] ?>" data-exists="<?= $hasPrice ? '1' : '0' ?>">
                                    #<?= $b['id'] ?> - <?= htmlspecialchars($b['planta_nombre']) ?> (<?= $b['cantidad_actual'] ?> disp.)<?= $hasPrice ? ' [Ya tiene precio]' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="addBatchPriceWarning" class="form-text text-danger d-none">
                                <i class="fas fa-exclamation-triangle"></i> Este lote ya tiene un cálculo. Puedes editarlo o eliminarlo desde la tabla.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Costo Mano de Obra (Bs)</label>
                                <input type="number" class="form-control" name="costo_mano_obra" step="0.01" min="0" value="0" id="addCostoManoObra">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Costo Total Insumos (Bs)</label>
                                <input type="number" class="form-control" name="costo_total_insumo" step="0.01" min="0" value="0" id="addCostoInsumo">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">% Ganancia</label>
                                <input type="number" class="form-control" name="porcentaje_ganancia" step="0.01" min="0" value="30" id="addPorcentajeGanancia">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de Cálculo</label>
                                <input type="date" class="form-control" name="fecha_calculo" id="addFechaCalculo">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="vigente" value="1" id="addVigente">
                                    <label class="form-check-label" for="addVigente">
                                        Marcar como precio vigente
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Final Sugerido (Bs) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="precio_final_sugerido" required step="0.01" min="0.01" id="addPrecioSugerido">
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-calculator"></i>
                            <strong>Fórmula:</strong>
                            Precio Unitario = (Costo MO + Costo Insumos) &times; (1 + %Ganancia/100) &divide; Cantidad del Lote
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cálculo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Price Modal -->
    <div class="modal fade" id="editPriceModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editPriceForm">
                    <input type="hidden" name="id" id="editPriceId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Cálculo de Precio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Lote <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_lote" id="editIdLote" required>
                                <option value="">Seleccione un lote</option>
                                <?php foreach ($batches as $b): $hasPrice = in_array($b['id'], $batchIdsWithPrices); ?>
                                <option value="<?= $b['id'] ?>" data-planta="<?= htmlspecialchars($b['planta_nombre']) ?>" data-cantidad="<?= $b['cantidad_actual'] ?>" data-exists="<?= $hasPrice ? '1' : '0' ?>">
                                    #<?= $b['id'] ?> - <?= htmlspecialchars($b['planta_nombre']) ?> (<?= $b['cantidad_actual'] ?> disp.)<?= $hasPrice ? ' [Ya tiene precio]' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="editBatchPriceWarning" class="form-text text-danger d-none">
                                <i class="fas fa-exclamation-triangle"></i> El lote seleccionado ya tiene otro cálculo de precio.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Costo Mano de Obra (Bs)</label>
                                <input type="number" class="form-control" name="costo_mano_obra" id="editCostoManoObra" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Costo Total Insumos (Bs)</label>
                                <input type="number" class="form-control" name="costo_total_insumo" id="editCostoInsumo" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">% Ganancia</label>
                                <input type="number" class="form-control" name="porcentaje_ganancia" id="editPorcentajeGanancia" step="0.01" min="0" value="30">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de Cálculo</label>
                                <input type="date" class="form-control" name="fecha_calculo" id="editFechaCalculo">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="vigente" value="1" id="editVigente">
                                    <label class="form-check-label" for="editVigente">
                                        Marcar como precio vigente
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Final Sugerido (Bs) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="precio_final_sugerido" id="editPrecioSugerido" required step="0.01" min="0.01">
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-calculator"></i>
                            <strong>Fórmula:</strong>
                            Precio Unitario = (Costo MO + Costo Insumos) &times; (1 + %Ganancia/100) &divide; Cantidad del Lote
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Cálculo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/prices.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/prices.js') ?>"></script>
</body>
</html>
