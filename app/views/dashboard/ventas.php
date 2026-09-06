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
        .badge-tipo-planta {
            background-color: #d1e7dd;
            color: #0f5132;
            font-size: 0.6rem;
        }
        .badge-tipo-insumo {
            background-color: #cff4fc;
            color: #055160;
            font-size: 0.6rem;
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
                                        <th>Referencia</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
                                        <th>Fecha</th>
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
    <div class="modal fade" id="ventaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="ventaForm">
            <div class="modal-header">
              <h5 class="modal-title">Registrar Nueva Venta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

            <!-- Step Indicator -->
            <div class="d-flex align-items-center justify-content-center mb-4" style="gap:0">
              <div class="d-flex align-items-center gap-2">
                <div class="step-circle d-flex align-items-center justify-content-center fw-semibold" id="step1Circle" style="width:32px;height:32px;border-radius:50%;font-size:14px;background:#e5a835;color:#fff">1</div>
                <span class="step-label fw-semibold" id="step1Label" style="font-size:13px;color:#424242">Datos de Venta</span>
              </div>
              <div class="step-connector" style="width:80px;height:2px;background:#e0e0e0;margin:0 12px;flex-shrink:0"></div>
              <div class="d-flex align-items-center gap-2">
                <div class="step-circle d-flex align-items-center justify-content-center fw-semibold" id="step2Circle" style="width:32px;height:32px;border-radius:50%;font-size:14px;background:#e0e0e0;color:#9e9e9e">2</div>
                <span class="step-label" id="step2Label" style="font-size:13px;color:#9e9e9e">Resumen y Pago</span>
              </div>
            </div>

            <!-- ========== STEP 1 ========== -->
            <div id="step1Panel">

              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Cliente</label>
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
                  <label class="form-label small fw-semibold">Vendedor</label>
                  <select class="form-select" name="id_usuario" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($trabajadores as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_trabajador'] . ' ' . ($t['apellido_trabajador'] ?? '')) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Tipo de Venta</label>
                  <select class="form-select" name="tipo_venta" id="tipoVenta">
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                  </select>
                </div>
              </div>

              <hr class="my-3">

              <label class="form-label small fw-semibold mb-1">Producto</label>
              <select class="form-select" id="productoSelect">
                <option value="">Seleccione un producto...</option>
              </select>

              <div id="productosContainer"></div>
              <div id="sinProductos" class="alert alert-info text-center py-2 mb-3">
                <i class="fas fa-info-circle me-1"></i>Seleccione productos del listado para agregar a la venta
              </div>

              <div>
                <label class="form-label small fw-semibold mb-1">Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="2" placeholder="Opcional" maxlength="500"></textarea>
              </div>
            </div>

            <!-- ========== STEP 2 ========== -->
            <div id="step2Panel" class="d-none">

              <div class="card border-0" style="background:#f8f9fa;border-radius:0.75rem;">
                <div class="card-body p-4">
                  <h6 class="fw-bold text-center mb-4 pb-3" style="border-bottom:1px solid #e9ecef;color:#424242;">
                    <i class="fas fa-receipt me-2"></i>Resumen de Venta
                  </h6>

                  <div class="d-flex justify-content-between mb-2" style="color:#6c757d;">
                    <span>Subtotal (sin IVA):</span>
                    <strong style="color:#424242" id="resumenSubtotal">Bs. 0,00</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-2" style="color:#6c757d;">
                    <span>IVA (16%):</span>
                    <strong style="color:#424242" id="resumenIva">Bs. 0,00</strong>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-6" style="color:#424242;">Total a Pagar:</span>
                    <strong class="fs-4" style="color:#e5a835;" id="resumenTotal">Bs. 0,00</strong>
                  </div>

                  <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:#424242;">
                    <i class="fas fa-credit-card"></i> Método de Pago
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
                        <div class="col-2 ref-col">
                          <input type="text" class="form-control form-control-sm ref-pago" placeholder="Ref." maxlength="100">
                        </div>
                        <div class="col-1 text-end">
                          <button type="button" class="btn btn-sm btn-outline-danger quitar-pago py-0 px-1" style="display:none;"><i class="fas fa-times"></i></button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex gap-1 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="pagarCompleto">
                      <i class="fas fa-check-circle me-1"></i>Pagar completo
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="agregarPago">
                      <i class="fas fa-plus me-1"></i>Dividir pago
                    </button>
                  </div>

                  <div class="d-flex justify-content-between pt-3 border-top mb-1">
                    <span class="fw-bold" style="color:#424242;">Total Pagado:</span>
                    <strong class="text-success" id="totalPagado">Bs. 0,00</strong>
                  </div>
                  <div class="d-flex justify-content-between small text-danger" id="saldoPendienteRow">
                    <span>Pendiente:</span>
                    <strong id="saldoPendiente">Bs. 0,00</strong>
                  </div>
                </div>
              </div>
            </div>

            </div> <!-- /modal-body -->
            <div class="modal-footer d-flex justify-content-between">
              <div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="btnAnterior"><i class="fas fa-arrow-left me-1"></i>Anterior</button>
                <button type="button" class="btn btn-primary" id="btnSiguiente">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                <button type="submit" class="btn btn-primary d-none" id="btnGuardarVenta"><i class="fas fa-save me-1"></i>Guardar Venta</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

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
