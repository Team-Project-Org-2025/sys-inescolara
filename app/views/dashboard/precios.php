<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precios - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .editable-field {
            cursor: pointer;
            border-bottom: 1px dashed var(--color-primary);
            padding: 2px 6px;
            border-radius: 4px;
            transition: background .2s;
        }
        .editable-field:hover {
            background: rgba(var(--bs-primary-rgb), 0.08);
        }
        .inline-input {
            width: 100px;
            text-align: end;
            font-weight: 600;
        }
        .price-highlight {
            background: linear-gradient(135deg, #fef3e2, #fde9b0);
            border: 1px solid #f5c85c;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .price-highlight .value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #c48f2a;
        }
    </style>
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
                    <span>Precios por Lote</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="preciosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Planta</th>
                                    <th class="text-center">Cant. Actual</th>
                                    <th class="text-end">Costo Unitario</th>
                                    <th class="text-center">% Ganancia</th>
                                    <th class="text-end">Total Insumos</th>
                                    <th class="text-end">Precio Final</th>
                                    <th class="text-center" style="width:140px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Editar Precio -->
    <div class="modal fade" id="editarPrecioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Precio del Lote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editLoteId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="editLoteLabel">—</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Costo Unitario ($)</label>
                            <input type="number" class="form-control" id="editCostoUnitario" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">% Ganancia</label>
                            <input type="number" class="form-control" id="editPorcentajeGanancia" step="0.01" min="0" max="100" placeholder="30">
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Total Insumos</label>
                            <div class="fw-semibold" id="editTotalInsumos">$0.00</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Ganancia ($)</label>
                            <div class="fw-semibold" id="editGananciaMonto">$0.00</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Precio Final</label>
                            <div class="price-highlight">
                                <div class="value" id="editPrecioFinal">$0.00</div>
                            </div>
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

    <!-- Modal Detalle de Insumos -->
    <?php modal_detail_start(['id' => 'detalleInsumosModal', 'title' => 'Detalle de Insumos', 'size' => 'modal-lg']); ?>
        <div class="mb-3">
            <label class="form-label fw-semibold" id="detalleLoteLabel">—</label>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="detalleInsumosTable">
                <thead class="table-light">
                    <tr>
                        <th>Insumo</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Costo Unitario</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Origen</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="detalleInsumosBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Cargando...</td>
                    </tr>
                </tbody>
                <tfoot id="detalleInsumosFoot">
                    <tr>
                        <th colspan="3" class="text-end">Total Insumos:</th>
                        <th class="text-end" id="detalleTotalInsumos">$0.00</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/precios.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/precios.js') ?>"></script>
</body>
</html>
