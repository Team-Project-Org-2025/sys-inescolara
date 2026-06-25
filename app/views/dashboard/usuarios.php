<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
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
                        <table id="usuariosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre de Usuario</th>
                                    <th>Correo Electrónico</th>
                                    <th>Rol</th>
                                    <th>Trabajador</th>
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

<?php
function renderPermisosChecklist(array $allPermisos): void
{
    $modulos = $allPermisos['modulos'] ?? [];
    $acciones = $allPermisos['acciones'] ?? [];
    foreach ($modulos as $modulo):
        $idModulo = $modulo['id_modulo'];
        $nombreModulo = $modulo['nombre_modulo'];
    ?>
    <div style="margin-bottom:10px;">
        <div style="font-size:0.8rem;font-weight:600;color:var(--text-primary);margin-bottom:4px;"><?= htmlspecialchars($nombreModulo) ?></div>
        <div style="display:flex;flex-wrap:wrap;gap:4px 12px;">
            <?php foreach ($acciones as $accion):
                $value = $idModulo . ':' . $accion['id_permiso'];
                $label = ucfirst($accion['nombre_permiso']);
            ?>
            <label style="display:flex;align-items:center;gap:4px;font-size:0.8rem;cursor:pointer;">
                <input type="checkbox" name="permisos[]" value="<?= $value ?>">
                <?= htmlspecialchars($label) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    endforeach;
}
?>

    <!-- Modals fuera de main-content para evitar conflictos con Bootstrap 5.3 -->
    
    <!-- Add User Modal -->
    <?php modal_form(['id' => 'addUserModal', 'title' => 'Agregar Usuario', 'formId' => 'addUserForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de Usuario</label>
            <input type="text" class="form-control" name="nombre_usuario" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control" name="correo_electronico" placeholder="usuario@correo.com" maxlength="254">
            <small class="text-muted">Opcional, necesario para recuperación de contraseña.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="password" required maxlength="30">
        </div>
        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" name="rol_id" id="addUserRole">
                <?php foreach ($roles as $rol): ?>
                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Vinculado a trabajador</label>
            <select class="form-select" name="id_trabajador_ref">
                <option value="">— Sin vincular —</option>
                <?php foreach ($trabajadores as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_trabajador'] . ' ' . ($t['apellido_trabajador'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Opcional. Vincula este usuario a un trabajador para notificaciones de tareas.</small>
        </div>
        <div class="mb-3 permisos-checklist" id="addPermisosChecklist" style="display:none;">
            <label class="form-label">Módulos y acciones permitidas</label>
            <div style="padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:300px;overflow-y:auto;">
                <?php renderPermisosChecklist($allPermisos); ?>
            </div>
            <small class="text-muted">Selecciona los módulos y acciones a los que este usuario tendrá acceso.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Foto de perfil</label>
            <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
        </div>
    <?php modal_form_end('addUserForm'); ?>

    <!-- Edit User Modal -->
    <?php modal_form(['id' => 'editUserModal', 'title' => 'Editar Usuario', 'formId' => 'editUserForm', 'hasHiddenId' => true, 'hiddenId' => 'editUserIdHidden', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre de Usuario</label>
            <input type="text" class="form-control" name="nombre_usuario" id="editUserName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control" name="correo_electronico" id="editUserEmail" placeholder="usuario@correo.com" maxlength="254">
            <small class="text-muted">Opcional, necesario para recuperación de contraseña.</small>
        </div>
        <div class="mb-3" id="currentPasswordGroup" style="display:none;">
            <label class="form-label">Tu Contraseña Actual</label>
            <input type="password" class="form-control" name="current_password" id="editCurrentPassword" placeholder="Ingresa tu contraseña actual para autorizar el cambio" maxlength="30">
            <small class="text-muted" id="currentPasswordHelp">Debes ingresar tu propia contraseña para realizar este cambio.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva Contraseña (Dejar en blanco para no cambiar)</label>
            <input type="password" class="form-control" name="password" id="editUserPassword" placeholder="Nueva contraseña" maxlength="30">
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
        <div class="mb-3">
            <label class="form-label">Vinculado a trabajador</label>
            <select class="form-select" name="id_trabajador_ref" id="editTrabajadorRef">
                <option value="">— Sin vincular —</option>
                <?php foreach ($trabajadores as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_trabajador'] . ' ' . ($t['apellido_trabajador'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Opcional. Vincula este usuario a un trabajador para notificaciones de tareas.</small>
        </div>
        <div class="mb-3 permisos-checklist" id="editPermisosChecklist" style="display:none;">
            <label class="form-label">Módulos y acciones permitidas</label>
            <div style="padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:300px;overflow-y:auto;">
                <?php renderPermisosChecklist($allPermisos); ?>
            </div>
            <small class="text-muted">Selecciona los módulos y acciones a los que este usuario tendrá acceso.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Foto de perfil</label>
            <div id="editAvatarPreview" class="mb-2" style="display:none;">
                <img src="" alt="Avatar actual" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary);">
            </div>
            <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="text-muted">Formatos: jpg, png, gif, webp. Máx 5MB.</small>
        </div>
    <?php modal_form_end('editUserForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <input type="hidden" id="currentUserId" value="<?= \SysInescolara\helpers\Auth::id() ?>">
    <input type="hidden" id="currentUserRole" value="<?= \SysInescolara\helpers\Auth::roleId() ?>">
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/usuarios.js"></script>
</body>
</html>