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
    $currentPage = 'precios';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Precios'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Precios Registrados</span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" id="btnNuevoPrecio">
                            <i class="fas fa-plus"></i> Nuevo Precio
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="preciosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Planta</th>
                                    <th>Precio Sugerido</th>
                                    <th>Vigencia</th>
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

    <!-- Modal Nuevo/Editar Precio -->
    <div class="modal fade" id="precioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="precioModalTitle">Nuevo Cálculo de Precio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId" value="0">

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Lote *</label>
                            <select class="form-select" id="selLote">
                                <option value="">Seleccione un lote...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="txtCategoria" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cant. Plantas</label>
                            <input type="text" class="form-control" id="txtCantidad" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Precio Base ($) *</label>
                            <input type="number" class="form-control" id="precioPlantaBase" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 fw-semibold">Insumos</label>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarInsumo">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered" id="insumosDetalleTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Insumo</th>
                                    <th class="text-end" style="width:150px">Monto ($)</th>
                                    <th style="width:50px"></th>
                                </tr>
                            </thead>
                            <tbody id="insumosDetalleBody">
                                <tr id="noInsumosRow">
                                    <td colspan="3" class="text-center text-muted">No hay insumos agregados</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-end">Total Insumos:</th>
                                    <th class="text-end" id="totalInsumosLabel">$0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">% Ganancia *</label>
                            <input type="number" class="form-control" id="porcentajeGanancia" step="0.01" min="0" value="30" placeholder="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio Final Sugerido ($)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="precioFinalSugerido" step="0.01" min="0" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="btnRecalcular" title="Recalcular">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <small class="text-muted">(Base + Insumos) × (1 + %/100)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarPrecio">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Selector de Insumo (small modal) -->
    <div class="modal fade" id="insumoSelectorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Agregar Insumo</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Insumo</label>
                        <select class="form-select" id="selInsumo">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Costo Unitario ($)</label>
                        <input type="text" class="form-control" id="insumoCostoUnitario" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" class="form-control" id="insumoCantidad" step="0.01" min="0" placeholder="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Monto Total ($)</label>
                        <input type="text" class="form-control" id="insumoMontoTotal" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarInsumo">Agregar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/precios.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/precios.js') ?>"></script>
</body>
</html>
