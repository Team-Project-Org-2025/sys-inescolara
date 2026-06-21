<?php
include_once __DIR__ . '/../common/links.php';
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
        .stat-card { border-radius: 0.75rem; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
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

            <!-- Stats Cards -->
            <div class="row g-3 mb-4" id="statsContainer">
                <div class="col-6 col-md-3">
                    <div class="card stat-card shadow-sm p-3 text-center">
                        <div class="text-muted small">Por Cobrar</div>
                        <div class="h3 mb-0 fw-bold" id="statPorCobrar">0,00</div>
                        <div class="small text-muted" id="statCuentas">0 cuentas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card shadow-sm p-3 text-center" style="border-left: 4px solid #dc3545;">
                        <div class="text-muted small">Vencido</div>
                        <div class="h3 mb-0 fw-bold text-danger" id="statVencidoMonto">0,00</div>
                        <div class="small text-danger" id="statVencidas">0 cuentas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card shadow-sm p-3 text-center" style="border-left: 4px solid #0d6efd;">
                        <div class="text-muted small">Vigente</div>
                        <div class="h3 mb-0 fw-bold text-primary" id="statVigenteMonto">0,00</div>
                        <div class="small text-primary" id="statVigentes">0 cuentas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card stat-card shadow-sm p-3 text-center" style="border-left: 4px solid #198754;">
                        <div class="text-muted small">Cobrado este Mes</div>
                        <div class="h3 mb-0 fw-bold text-success" id="statCobradoMes">0,00</div>
                        <div class="small text-success" id="statPagadas">0 cuentas pagadas</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filterEstado">
                                <option value="">Todos</option>
                                <option value="vigente">Vigente</option>
                                <option value="vencido">Vencido</option>
                                <option value="pagado">Pagado</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button class="btn btn-outline-secondary" id="btnFilter">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">Los montos se calculan automáticamente desde los detalles de venta y pagos registrados.</small>
                        </div>
                    </div>
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
                                    <th>Contacto</th>
                                    <th>Fecha Venta</th>
                                    <th>Vencimiento</th>
                                    <th>Monto Total</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
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
    <div class="modal fade" id="detailModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Venta a Crédito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2 text-muted">Cargando detalle...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="paymentForm">
                    <input type="hidden" name="id_venta" id="payIdVenta">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
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
                                <input type="text" class="form-control" name="referencia" id="payReferencia" maxlength="50" pattern="[0-9]{6}" inputmode="numeric">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_pago" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cobrado por <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_trabajador" required>
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/cuentas-cobrar.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/dashboard/cuentas-cobrar.js') ?>"></script>
    <?= $scripts_links ?>
</body>
</html>
