<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'employees';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="dashboard-page-title">Empleados</h1>
            </div>
            
            <div class="dashboard-header-right">
                <div class="dashboard-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Buscar...">
                </div>
                
                <button class="header-icon-btn" aria-label="Notificaciones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge"></span>
                </button>
                
                <div class="sidebar-user" style="padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="sidebar-user-avatar" style="width: 36px; height: 36px; background-color: #e5a835; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1a1f2e; overflow: hidden; flex-shrink: 0;">
                        <?php
                        $headerAvatar = $_SESSION['user_avatar'] ?? null;
                        $headerName = $_SESSION['user_nombre'] ?? 'U';
                        if ($headerAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($headerAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($headerName, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.875rem;font-weight:500;color:#374151;white-space:nowrap;"><?= htmlspecialchars($headerName) ?></span>
                </div>
            </div>
        </header>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Empleados</h1>
                    <p style="color: var(--text-secondary);">Registro y control de empleados de la empresa.</p>
                </div>
                <button class="btn btn-primary" id="btnAddEmployee">
                    <i class="fas fa-plus"></i> Nuevo Empleado
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="employeesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Cédula</th>
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

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addEmployeeForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Empleado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre_trabajador" required placeholder="Ej: Luis">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" name="apellido_trabajador" placeholder="Ej: Rodríguez">
                            <small class="text-muted">Opcional</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cédula</label>
                            <input type="text" class="form-control" name="cedula_trabajador" placeholder="Ej: V-12345678">
                            <small class="text-muted">Opcional</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono_trabajador" placeholder="Ej: 0412-9876543">
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

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editEmployeeForm">
                    <input type="hidden" name="id" id="editEmployeeId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Empleado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre_trabajador" id="editEmployeeName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" name="apellido_trabajador" id="editEmployeeApellido" placeholder="Opcional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cédula</label>
                            <input type="text" class="form-control" name="cedula_trabajador" id="editEmployeeCedula" placeholder="Opcional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono_trabajador" id="editEmployeeTelefono" placeholder="Opcional">
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

    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/employees.js"></script>
</body>
</html>
