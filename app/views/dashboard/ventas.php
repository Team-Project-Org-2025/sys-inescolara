<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - INECOLARA</title>
    <?= $css_links ?>
    <style>
        #resultadosLotes {
            max-height: 160px;
            overflow-y: auto;
        }
        #ventaModal .modal-dialog {
            margin-left: 300px;
            margin-right: auto;
        }
        @media (min-width: 1200px) {
            #ventaModal .modal-xl {
                max-width: 1320px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'ventas';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Ventas'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-3">
                            <i class="fas fa-receipt fa-2x text-primary mb-2"></i>
                            <h6 class="text-muted small mb-1">Total Ventas</h6>
                            <h4 class="mb-0" id="totalVentas">0</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-3">
                            <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                            <h6 class="text-muted small mb-1">Total Ingresos</h6>
                            <h4 class="mb-0 text-success" id="totalIngresos">Bs. 0,00</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-3">
                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                            <h6 class="text-muted small mb-1">Pendientes</h6>
                            <h4 class="mb-0 text-warning" id="totalPendientes">0</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-3">
                            <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                            <h6 class="text-muted small mb-1">Completadas</h6>
                            <h4 class="mb-0 text-info" id="totalCompletadas">0</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Ventas</h1>
                    <p style="color: var(--text-secondary);">Registro de ventas y facturación POS.</p>
                </div>
                <?php if (\SysInescolara\helpers\Auth::hasModuleAccess('ventas', 'crear')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ventaModal">
                    <i class="fas fa-plus"></i> Nueva Venta
                </button>
                <?php endif; ?>
            </div>

            <!-- Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ventasTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Referencia</th>
                                    <th>Cliente</th>
                                    <th>C.I.</th>
                                    <th>Vendedor</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal: Nueva Venta -->
    <?php modal_form(['id' => 'ventaModal', 'title' => 'Registrar Nueva Venta', 'formId' => 'ventaForm', 'size' => 'modal-xl', 'saveText' => 'Guardar Venta']); ?>
        <div class="row g-3">
            <div class="col-lg-6">

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small mb-0">Cliente</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="buscarClienteInput" placeholder="Buscar por C.I., nombre o apellido..." autocomplete="off">
                        <input type="hidden" name="id_cliente" id="idClienteHidden">
                        <div id="clienteSearchResults" class="dropdown-menu w-100"></div>
                    </div>
                    <div id="clienteSeleccionado" class="d-none mt-1">
                        <span class="badge bg-success" id="clienteSeleccionadoTexto"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" id="limpiarCliente" title="Cambiar cliente">&times;</button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small mb-0">Vendedor</label>
                    <select class="form-select" name="id_trabajador" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($trabajadores as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_trabajador'] . ' ' . ($t['apellido_trabajador'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5 col-sm-4">
                    <label class="form-label small mb-0">Tipo</label>
                    <select class="form-select" name="tipo_venta" id="tipoVenta">
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="col-7 col-sm-7">
                    <label class="form-label small mb-0">Fecha</label>
                    <input type="text" class="form-control" name="fecha_venta" id="fechaVenta" readonly>
                </div>
            </div>

            <label class="form-label small fw-semibold mb-1">Buscar Planta</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="buscarLote" placeholder="Escriba nombre de la planta..." minlength="2" maxlength="100">
            </div>
            <div id="resultadosLotes" class="list-group mb-2" style="display:none;"></div>

            <div id="productosContainer"></div>
            <div id="sinProductos" class="alert alert-info text-center py-2 mb-3">
                <i class="fas fa-info-circle me-1"></i>Busque y seleccione plantas para agregar a la venta
            </div>

            <div class="mb-2">
                <label class="form-label small fw-semibold mb-1">Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="2" placeholder="Opcional" maxlength="500"></textarea>
            </div>
            </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3 px-3" style="background:#f8f9fa;border-radius:0.5rem;">
                    <h6 class="fw-bold text-center mb-3 pb-2 border-bottom">Resumen de Venta</h6>

                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal (sin IVA):</span>
                        <strong id="resumenSubtotal">Bs. 0,00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>IVA (16%):</span>
                        <strong id="resumenIva">Bs. 0,00</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold fs-6">Total a Pagar:</span>
                        <strong class="fs-4 text-primary" id="resumenTotal">Bs. 0,00</strong>
                    </div>

                    <div>
                        <h6 class="fw-bold mb-2 d-flex align-items-center gap-1">
                            <i class="fas fa-credit-card"></i> Pago
                        </h6>

                            <div id="pagosContainer">
                                <div class="pago-row mb-2 pb-2 border-bottom">
                                    <div class="row g-1 align-items-center">
                                        <div class="col-5">
                                            <select class="form-select form-select-sm metodo-pago">
                                                 <option value="efectivo">Efectivo</option>
                                                 <option value="transferencia">Transferencia</option>
                                                 <option value="pago_movil">Pago Móvil</option>
                                                 <option value="punto">Punto</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="text" class="form-control form-control-sm monto-pago" placeholder="Monto" inputmode="decimal">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" class="form-control form-control-sm ref-pago" placeholder="Ref." maxlength="100">
                                        </div>
                                        <div class="col-1 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger quitar-pago py-0 px-1" style="display:none;"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-1 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="pagarCompleto">
                                    <i class="fas fa-check-circle me-1"></i>Pagar completo
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="agregarPago">
                                    <i class="fas fa-plus me-1"></i>Dividir pago
                                </button>
                            </div>

                            <div class="d-flex justify-content-between pt-2 border-top mb-1">
                                <span class="fw-bold">Total Pagado:</span>
                                <strong class="text-success" id="totalPagado">Bs. 0,00</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-danger" id="saldoPendienteRow">
                                <span>Pendiente:</span>
                                <strong id="saldoPendiente">Bs. 0,00</strong>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </div>
    <?php modal_form_end('ventaForm'); ?>

    <!-- Modal: Ver Detalle -->
    <?php modal_detail_start(['id' => 'detalleModal', 'title' => 'Detalle de Venta', 'size' => 'modal-lg modal-dialog-scrollable', 'bodyId' => 'detalleContenido']); ?>
        <a href="#" class="btn btn-primary" id="btnDescargarPdf">
            <i class="fas fa-file-pdf me-1"></i> Descargar Comprobante
        </a>
    <?php modal_detail_end(); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/ventas.js"></script>
</body>
</html>
