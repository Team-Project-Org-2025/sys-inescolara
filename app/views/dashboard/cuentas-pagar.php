<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Pagar - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'cuentas-pagar';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Cuentas por Pagar'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Cuentas por Pagar</h1>
                    <p style="color: var(--text-secondary);">Control de cuentas pendientes con proveedores.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="cuentasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Compra</th>
                                    <th>Monto</th>
                                    <th>Saldo Pendiente</th>
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

    <!-- Detail Modal -->
    <?php modal_detail_start(['id' => 'detalleModal', 'title' => 'Detalle de Cuenta', 'size' => 'modal-lg modal-dialog-centered', 'bodyId' => 'detalleBody']); ?>
    <?php modal_detail_end(); ?>

    <!-- Pago Modal -->
    <?php modal_form(['id' => 'pagoModal', 'title' => 'Registrar Pago', 'formId' => 'pagoForm', 'hasHiddenId' => true, 'hiddenId' => 'pagoIdCuenta', 'saveText' => 'Registrar Pago']); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Monto *</label>
                <input type="number" class="form-control" name="monto" id="pagoMonto" step="0.01" min="0.01" required>
                <div class="form-text">Saldo pendiente: <strong id="pagoSaldoInfo">0,00</strong></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha *</label>
                <input type="date" class="form-control" name="fecha_pago" id="pagoFecha" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de Pago</label>
                <select class="form-select" name="tipo_pago" id="pagoTipo">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Depósito">Depósito</option>
                    <option value="Pago Móvil">Pago Móvil</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Referencia</label>
                <input type="text" class="form-control" name="referencia" id="pagoReferencia" placeholder="Opcional" maxlength="50">
            </div>
            <div class="col-12">
                <label class="form-label">Observación</label>
                <textarea class="form-control" name="observacion" id="pagoObservacion" rows="2" placeholder="Opcional" maxlength="500"></textarea>
            </div>
        </div>
    <?php modal_form_end('pagoForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/cuentas-pagar.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/dashboard/cuentas-pagar.js') ?>"></script>
</body>
</html>
