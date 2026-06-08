<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - INECOLARA</title>
        <?= $css_links ?>
    <style>
        #itemsTable td { vertical-align: middle; padding: 0.25rem 0.35rem !important; }
        #itemsTable th { vertical-align: middle; padding: 0.4rem 0.35rem !important; white-space: nowrap; }
        #itemsTable .form-select-sm, #itemsTable .form-control-sm { font-size: .8rem; }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'compras';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Compras'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Compras</h1>
                    <p style="color: var(--text-secondary);">Registro de órdenes de compra a proveedores.</p>
                </div>
                <button class="btn btn-primary" id="btnAddCompra">
                    <i class="fas fa-plus"></i> Nueva Compra
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="comprasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Comprobante</th>
                                    <th>Subtotal</th>
                                    <th>IVA</th>
                                    <th>Total</th>
                                    <th>Items</th>
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

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="compraModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="compraForm">
                    <input type="hidden" name="id" id="compraId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="compraModalTitle">Nueva Compra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Proveedor *</label>
                                <select class="form-select" name="id_proveedor" id="frmProveedor" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($proveedores as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_proveedor']) ?> (<?= htmlspecialchars($p['rif_proveedor']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" name="fecha_compra" id="frmFecha" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo Comprobante</label>
                                <select class="form-select" name="tipo_comprobante" id="frmTipoComprobante">
                                    <option value="Factura">Factura</option>
                                    <option value="Boleta">Boleta</option>
                                    <option value="Recibo">Recibo</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° Comprobante</label>
                                <input type="text" class="form-control" name="numero_comprobante" id="frmNumComprobante" placeholder="Opcional">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación</label>
                                <textarea class="form-control" name="observacion" id="frmObservacion" rows="2" placeholder="Opcional"></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">Items de la Compra</label>
                            <button type="button" class="btn btn-sm btn-outline-success" id="btnAddItem">
                                <i class="fas fa-plus"></i> Agregar Item
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:130px">Tipo</th>
                                        <th>Item</th>
                                        <th class="text-end" style="width:100px">Cantidad</th>
                                        <th class="text-end" style="width:110px">Costo Unit.</th>
                                        <th class="text-end" style="width:110px">Subtotal</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end">Subtotal (Items):</td>
                                        <td class="text-end" id="itemsTotal">Bs 0,00</td>
                                        <td></td>
                                    </tr>
                                    <tr class="fw-bold table-active">
                                        <td colspan="4" class="text-end">Total:</td>
                                        <td class="text-end" id="itemsTotalFinal">Bs 0,00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <input type="hidden" name="subtotal" id="frmSubtotal" value="0">
                    <input type="hidden" name="total" id="frmTotal" value="0">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Compra</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script>
    window.UBICACIONES = <?= json_encode(array_map(function($u) {
        return ['id' => $u['id'], 'nombre' => $u['nombre_ubicacion']];
    }, $ubicaciones)) ?>;
    </script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/compras.js"></script>
</body>
</html>
