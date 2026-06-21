<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'suppliers';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Proveedores'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Proveedores</h1>
                    <p style="color: var(--text-secondary);">Registro y control de proveedores del vivero.</p>
                </div>
                <button class="btn btn-primary" id="btnAddSupplier">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="suppliersTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre del Proveedor</th>
                                    <th>RIF</th>
                                    <th>Contacto</th>
                                    <th>Teléfono</th>
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

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addSupplierForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Proveedor</label>
                            <input type="text" class="form-control" name="nombre_proveedor" required placeholder="Ej: Viveros del Valle C.A." maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RIF</label>
                            <div class="d-flex gap-2">
                                <select class="form-control" name="rif_tipo" id="addRifTipo" style="max-width: 60px; flex-shrink: 0;">
                                    <option value="">—</option>
                                    <option value="J">J</option>
                                    <option value="G">G</option>
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="P">P</option>
                                </select>
                                <input type="text" class="form-control" name="rif_numero" id="addRifNumero" placeholder="12345678-9" style="font-family: monospace;" maxlength="9">
                                <input type="hidden" name="rif_proveedor" id="addRifProveedor">
                            </div>
                            <small class="text-muted">Opcional</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del Contacto (Vendedor)</label>
                            <input type="text" class="form-control" name="contacto_vendedor" placeholder="Ej: María García" maxlength="50">
                            <small class="text-muted">Opcional</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono_proveedor" placeholder="Ej: 0412-7654321" maxlength="11">
                            <small class="text-muted">Opcional</small>
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

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editSupplierForm">
                    <input type="hidden" name="id" id="editSupplierId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Proveedor</label>
                            <input type="text" class="form-control" name="nombre_proveedor" id="editSupplierName" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RIF</label>
                            <div class="d-flex gap-2">
                                <select class="form-control" name="rif_tipo" id="editRifTipo" style="max-width: 60px; flex-shrink: 0;">
                                    <option value="">—</option>
                                    <option value="J">J</option>
                                    <option value="G">G</option>
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="P">P</option>
                                </select>
                                <input type="text" class="form-control" name="rif_numero" id="editRifNumero" placeholder="12345678-9" style="font-family: monospace;" maxlength="9">
                                <input type="hidden" name="rif_proveedor" id="editRifProveedor">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contacto (Vendedor)</label>
                            <input type="text" class="form-control" name="contacto_vendedor" id="editSupplierContacto" placeholder="Opcional" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono_proveedor" id="editSupplierTelefono" placeholder="Opcional" maxlength="11">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/suppliers.js"></script>
</body>
</html>
