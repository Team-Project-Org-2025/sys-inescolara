<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ornatos - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'ornatos';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Ornatos'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Ornatos</h1>
                    <p style="color: var(--text-secondary);">Administra las solicitudes de diseño, paisajismo y embellecimiento vegetal.</p>
                </div>
                <button class="btn btn-primary" id="btnNuevoOrnato">
                    <i class="fas fa-plus"></i> Nuevo Ornato
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaOrnatos" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Ubicación</th>
                                    <th>Monto Total</th>
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

    <!-- Modal de Ornato (Agregar / Editar) -->
    <div class="modal fade" id="modalOrnato" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formOrnato">
                    <input type="hidden" name="id" id="inputId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tituloModal">Agregar Ornato</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Cabecera -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente *</label>
                                <select class="form-select" name="id_cliente" id="inputCliente" required>
                                    <option value="">Seleccione un cliente...</option>
                                    <?php if (isset($clientes)): foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_cliente']) ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tipo de ornato *</label>
                                <select class="form-select" name="tipo_ornato" id="inputTipo" required>
                                    <option value="Venta">Venta</option>
                                    <option value="Donacion">Donación</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" name="fecha" id="inputFecha" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ubicación</label>
                                <input type="text" class="form-control" name="ubicacion" id="inputUbicacion" placeholder="Ej: Jardín frontal, Área de recepción" maxlength="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto Total</label>
                                <input type="text" class="form-control" id="inputMontoTotal" readonly style="font-weight:bold;font-size:1.1rem;" value="0.00">
                                <input type="hidden" name="monto_total" id="inputMontoTotalHidden" value="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="inputDescripcion" rows="2" placeholder="Detalles del servicio de ornato..." maxlength="500"></textarea>
                        </div>

                        <hr>

                        <!-- Detalle: Plantas Asignadas -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Plantas Asignadas</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" id="btnAgregarPlanta">
                                <i class="fas fa-plus"></i> Agregar planta
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="tablaDetalle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:200px;">Lote</th>
                                        <th style="width:100px;">Cantidad</th>
                                        <th style="width:130px;">Precio Unit.</th>
                                        <th style="width:130px;">Subtotal</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoDetalle">
                                    <!-- filas insertadas por JS -->
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">TOTAL</td>
                                        <td id="totalDetalle">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template oculto para fila de detalle -->
    <template id="templateFilaDetalle">
        <tr class="fila-detalle">
            <td>
                <select class="form-select form-select-sm select-lote" required>
                    <option value="">Seleccione lote...</option>
                    <?php if (isset($lotes)): foreach ($lotes as $l): ?>
                    <option value="<?= $l['id'] ?>" data-planta="<?= htmlspecialchars($l['planta_nombre'] ?? '') ?>" data-especie="<?= htmlspecialchars($l['especie_nombre'] ?? '') ?>" data-precio="<?= htmlspecialchars($l['precio_unitario'] ?? '') ?>">
                        #<?= $l['id'] ?> - <?= htmlspecialchars($l['planta_nombre'] ?? '') ?> (<?= htmlspecialchars($l['especie_nombre'] ?? '') ?>) - Disp: <?= (int)($l['cantidad_actual'] ?? 0) ?>
                    </option>
                    <?php endforeach; endif; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm input-cantidad" min="1" required value="1">
            </td>
            <td>
                <input type="number" step="0.01" class="form-control form-control-sm input-precio" min="0" required value="0.00">
            </td>
            <td>
                <span class="span-subtotal">$0.00</span>
                <input type="hidden" class="input-subtotal-hidden" value="0.00">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-fila" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    </template>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/ornatos.js"></script>
</body>
</html>
