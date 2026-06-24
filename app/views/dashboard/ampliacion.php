<?php
$permisos = \SysInescolara\helpers\Auth::permisos();
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ampliación de Especies - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'ampliacion';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Ampliación de Especies'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Ampliación de Especies</h1>
                    <p style="color: var(--text-secondary);">Registro de intercambios de plantas: salida y entrada sin venta.</p>
                </div>
                <?php if (\SysInescolara\helpers\Auth::hasModuleAccess('ampliacion', 'crear')): ?>
                <button class="btn btn-primary" id="btnAddAmpliacion">
                    <i class="fas fa-exchange-alt"></i> Nueva Ampliación
                </button>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ampliacionTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Items Salida</th>
                                    <th>Items Entrada</th>
                                    <th>Gestor</th>
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

    <!-- Modal Registrar Ampliación -->
    <?php modal_form(['id' => 'ampliacionModal', 'title' => 'Nueva Ampliación de Especies', 'formId' => 'ampliacionForm', 'size' => 'modal-xl modal-dialog-centered']); ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="id_cliente">Cliente</label>
                <select class="form-select" name="id_cliente" id="id_cliente">
                    <option value="">Seleccione (opcional)</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>">
                            <?= htmlspecialchars($c['nombre_cliente'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="id_trabajador_gestor">Gestor <span class="text-danger">*</span></label>
                <select class="form-select" name="id_trabajador_gestor" id="id_trabajador_gestor" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($trabajadores as $t): ?>
                        <option value="<?= (int)$t['id'] ?>">
                            <?= htmlspecialchars(($t['nombre_trabajador'] ?? '') . ' ' . ($t['apellido_trabajador'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="fecha_movimiento">Fecha <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="fecha_movimiento" id="fecha_movimiento" required>
            </div>
            <div class="col-md-12">
                <label class="form-label" for="observacion">Observación</label>
                <textarea class="form-control" name="observacion" id="observacion" rows="2" maxlength="500"></textarea>
            </div>
        </div>

        <hr class="my-4">

        <div class="border rounded-3 p-3 mb-3" style="border-color: #dc3545 !important; border-width: 2px !important;">
            <h5 class="mb-3 text-danger fw-bold"><i class="fas fa-arrow-right"></i> Plantas que salen</h5>
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="salidaTable">
                    <thead class="table-light">
                        <tr>
                            <th>Lote</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="salidaTableBody"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="btnAddSalidaRow">
                <i class="fas fa-plus"></i> Agregar salida
            </button>
        </div>

        <div class="border rounded-3 p-3 mb-3" style="border-color: #198754 !important; border-width: 2px !important;">
            <h5 class="mb-3 text-success fw-bold"><i class="fas fa-arrow-left"></i> Plantas que entran</h5>
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="entradaTable">
                    <thead class="table-light">
                        <tr>
                            <th>Planta</th>
                            <th>Ubicación</th>
                            <th>Cantidad</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="entradaTableBody"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success mt-2" id="btnAddEntradaRow">
                <i class="fas fa-plus"></i> Agregar entrada
            </button>
        </div>
    <?php modal_form_end('ampliacionForm'); ?>

    <!-- Modal Detalle -->
    <?php modal_detail_start(['id' => 'detalleModal', 'title' => 'Detalle de Ampliación', 'size' => 'modal-lg', 'bodyId' => 'detalleModalBody']); ?>
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Cargando...</p>
        </div>
    <?php modal_detail_end(); ?>

    <!-- Templates -->
    <template id="salidaRowTemplate">
        <tr>
            <td>
                <select class="form-select form-select-sm salida-lote" required>
                    <option value="">Seleccione lote</option>
                </select>
            </td>
            <td class="text-center salida-stock-display">—</td>
            <td>
                <input type="number" min="1" class="form-control form-control-sm salida-cantidad" placeholder="0" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    </template>

    <template id="entradaRowTemplate">
        <tr>
            <td>
                <div class="entrada-planta-wrapper">
                    <select class="form-select entrada-planta" required>
                        <option value="">Seleccione planta</option>
                    </select>
                    <div class="entrada-nueva-planta mt-2 p-2 bg-light rounded d-none">
                        <input type="text" class="form-control mb-1 entrada-nueva-nombre" placeholder="Nombre común *" maxlength="50">
                        <input type="text" class="form-control mb-1 entrada-nueva-tecnico" placeholder="Nombre científico" maxlength="50">
                        <select class="form-select entrada-nueva-especie">
                            <option value="">Especie *</option>
                        </select>
                    </div>
                </div>
            </td>
            <td>
                <select class="form-select entrada-ubicacion" required>
                    <option value="">Seleccione ubicación</option>
                </select>
            </td>
            <td>
                <input type="number" min="1" class="form-control entrada-cantidad" placeholder="0" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    </template>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/ampliacion.js?v=<?= time() ?>"></script>
</body>
</html>
