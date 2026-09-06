<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Cobrar - INECOLARA</title>
    <?= $css_links ?>
    <style>
        .badge-estado { font-size: 0.8rem; padding: 0.35em 0.65em; }
        .badge-vigente { background-color: #0d6efd; color: #fff; }
        .badge-vencido { background-color: #dc3545; color: #fff; }
        .badge-pagado { background-color: #198754; color: #fff; }
    </style>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'cuentas-cobrar';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Cuentas por Cobrar'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Cuentas por Cobrar</h1>
                    <p style="color: var(--text-secondary);">Control de ventas a crédito y cobranzas.</p>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="cuentasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Referencia</th>
                                    <th>Cliente</th>
                                    <th>C.I.</th>
                                    <th>Fecha</th>
                                    <th>Monto Total</th>
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
    <?php modal_detail_start(['id' => 'detailModal', 'title' => 'Detalle de Venta a Crédito', 'size' => 'modal-lg', 'bodyId' => 'detailModalBody']); ?>
        <div class="text-center py-4">
            <div class="spinner-border" role="status"></div>
            <p class="mt-2 text-muted">Cargando detalle...</p>
        </div>
    <?php modal_detail_end(); ?>

    <!-- Payment Modal -->
    <?php modal_form(['id' => 'paymentModal', 'title' => 'Registrar Pago', 'formId' => 'paymentForm', 'hasHiddenId' => true, 'hiddenId' => 'payIdVenta', 'saveText' => 'Registrar Pago', 'saveClass' => 'success']); ?>
        <div class="alert alert-info" id="payInfo"></div>
        <div class="mb-3">
            <label class="form-label">Monto <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="monto" step="0.01" min="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
            <select class="form-select" name="metodo" id="payMetodo" required>
                <option value="">Seleccione...</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="punto">Punto de Venta</option>
                <option value="pago_movil">Pago Móvil</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div id="payReferenceGroup" style="display:none;">
            <div class="mb-3">
                <label class="form-label">Banco</label>
                <select class="form-select" name="banco" id="payBanco">
                    <option value="">Seleccione...</option>
                    <option value="banesco">Banesco</option>
                    <option value="mercantil">Mercantil</option>
                    <option value="provincial">Provincial</option>
                    <option value="venezuela">Banco de Venezuela</option>
                    <option value="exterior">Banco Exterior</option>
                    <option value="nacional">Banco Nacional de Crédito</option>
                    <option value="occidental">Banco Occidental de Descuento</option>
                    <option value="caroni">Banco Caroní</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Referencia (6 dígitos)</label>
                <input type="text" class="form-control" name="referencia" id="payReferencia" maxlength="6" pattern="[0-9]{6}" inputmode="numeric">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="fecha_pago" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cobrado por <span class="text-danger">*</span></label>
            <select class="form-select" name="id_usuario" required>
                <option value="">Seleccione...</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre_trabajador'] . ' ' . $e['apellido_trabajador']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones" rows="2" maxlength="500"></textarea>
        </div>
    <?php modal_form_end('paymentForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/cuentas-cobrar.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/dashboard/cuentas-cobrar.js') ?>"></script>
    <?= $scripts_links ?>
</body>
</html>
