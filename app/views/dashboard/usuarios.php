<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'usuarios';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Usuarios'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Usuarios</h1>
                    <p style="color: var(--text-secondary);">Módulo para administrar los usuarios del sistema.</p>
                </div>
                <button class="btn btn-primary" id="btnAddUser">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre de Usuario</th>
                                    <th>Correo Electrónico</th>
                                    <th>Rol</th>
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

    <!-- Modals fuera de main-content para evitar conflictos con Bootstrap 5.3 -->
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" name="nombre_usuario" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo_electronico" placeholder="usuario@correo.com">
                            <small class="text-muted">Opcional, necesario para recuperación de contraseña.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol_id" id="addUserRole">
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 permisos-checklist" id="addPermisosChecklist" style="display:none;">
                            <label class="form-label">Módulos permitidos</label>
                            <div style="display:flex;flex-direction:column;gap:6px;padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:240px;overflow-y:auto;">
                                <?php foreach ($allPermisos as $perm): ?>
                                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="permisos[]" value="<?= $perm['id_permiso'] ?>">
                                    <?= htmlspecialchars($perm['descripcion_permiso']) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Selecciona los módulos a los que este usuario tendrá acceso.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto de perfil</label>
                            <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editUserForm">
                    <input type="hidden" name="id" id="editUserIdHidden">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" name="nombre_usuario" id="editUserName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo_electronico" id="editUserEmail" placeholder="usuario@correo.com">
                            <small class="text-muted">Opcional, necesario para recuperación de contraseña.</small>
                        </div>
                        <div class="mb-3" id="currentPasswordGroup" style="display:none;">
                            <label class="form-label">Tu Contraseña Actual</label>
                            <input type="password" class="form-control" name="current_password" id="editCurrentPassword" placeholder="Ingresa tu contraseña actual para autorizar el cambio">
                            <small class="text-muted" id="currentPasswordHelp">Debes ingresar tu propia contraseña para realizar este cambio.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña (Dejar en blanco para no cambiar)</label>
                            <input type="password" class="form-control" name="password" id="editUserPassword" placeholder="Nueva contraseña">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol_id" id="editUserRole">
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="editUserRoleNote" style="display:none;">El rol del superusuario no se puede modificar.</small>
                        </div>
                        <div class="mb-3 permisos-checklist" id="editPermisosChecklist" style="display:none;">
                            <label class="form-label">Módulos permitidos</label>
                            <div style="display:flex;flex-direction:column;gap:6px;padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:240px;overflow-y:auto;">
                                <?php foreach ($allPermisos as $perm): ?>
                                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="permisos[]" value="<?= $perm['id_permiso'] ?>">
                                    <?= htmlspecialchars($perm['descripcion_permiso']) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Selecciona los módulos a los que este usuario tendrá acceso.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto de perfil</label>
                            <div id="editAvatarPreview" class="mb-2" style="display:none;">
                                <img src="" alt="Avatar actual" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary);">
                            </div>
                            <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
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
    <input type="hidden" id="currentUserId" value="<?= (int)($_SESSION['user_id'] ?? 0) ?>">
    <input type="hidden" id="currentUserRole" value="<?= (int)($_SESSION['user_rol_id'] ?? 0) ?>">
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/usuarios.js"></script>
</body>
</html>
