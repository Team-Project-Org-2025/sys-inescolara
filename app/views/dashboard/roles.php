<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';

$modulos = [
    'Dashboard' => ['DASHBOARD_VIEW' => 'Ver'],
    'Inventario' => ['INVENTARIO_VIEW' => 'Ver'],
    'Ventas' => ['VENTAS_ACCESS' => 'Acceder', 'VENTAS_CREATE' => 'Crear', 'VENTAS_EDIT' => 'Editar', 'VENTAS_DELETE' => 'Anular', 'VENTAS_PDF' => 'PDF'],
    'Plantas' => ['PLANTAS_VIEW' => 'Ver', 'PLANTAS_CREATE' => 'Crear', 'PLANTAS_EDIT' => 'Editar', 'PLANTAS_DELETE' => 'Eliminar'],
    'Proveedores' => ['PROVEEDORES_VIEW' => 'Ver', 'PROVEEDORES_CREATE' => 'Crear', 'PROVEEDORES_EDIT' => 'Editar', 'PROVEEDORES_DELETE' => 'Eliminar'],
    'Insumos' => ['INSUMOS_VIEW' => 'Ver', 'INSUMOS_CREATE' => 'Crear', 'INSUMOS_EDIT' => 'Editar', 'INSUMOS_DELETE' => 'Eliminar'],
    'Trabajadores' => ['TRABAJADORES_VIEW' => 'Ver', 'TRABAJADORES_CREATE' => 'Crear', 'TRABAJADORES_EDIT' => 'Editar', 'TRABAJADORES_DELETE' => 'Eliminar'],
    'Clientes' => ['CLIENTES_VIEW' => 'Ver', 'CLIENTES_CREATE' => 'Crear', 'CLIENTES_EDIT' => 'Editar', 'CLIENTES_DELETE' => 'Eliminar'],
    'Tareas' => ['TAREAS_VIEW' => 'Ver', 'TAREAS_CREATE' => 'Crear', 'TAREAS_EDIT' => 'Editar', 'TAREAS_DELETE' => 'Eliminar'],
    'Ubicaciones' => ['UBICACIONES_VIEW' => 'Ver', 'UBICACIONES_CREATE' => 'Crear', 'UBICACIONES_EDIT' => 'Editar', 'UBICACIONES_DELETE' => 'Eliminar'],
    'Ornatos' => ['ORNATOS_VIEW' => 'Ver', 'ORNATOS_CREATE' => 'Crear', 'ORNATOS_EDIT' => 'Editar', 'ORNATOS_DELETE' => 'Eliminar'],
    'Asistente IA' => ['ASISTENTE_ACCESS' => 'Acceder'],
    'Sistema' => ['USUARIOS_MANAGE' => 'Gestionar (usuarios, bitácora, respaldos)'],
];

$codigoToId = [];
foreach ($allPermisos as $p) {
    $codigoToId[$p['codigo_permiso']] = $p['id_permiso'];
}

function renderPermisosChecklist(array $modulos, array $codigoToId): void
{
    foreach ($modulos as $nombreModulo => $acciones):
        $showActions = [];
        foreach ($acciones as $codigo => $etiqueta) {
            if (isset($codigoToId[$codigo])) {
                $showActions[] = ['codigo' => $codigo, 'etiqueta' => $etiqueta, 'id' => $codigoToId[$codigo]];
            }
        }
        if (empty($showActions)) continue;
    ?>
    <div style="margin-bottom:10px;">
        <div style="font-size:0.8rem;font-weight:600;color:var(--text-primary);margin-bottom:4px;"><?= htmlspecialchars($nombreModulo) ?></div>
        <div style="display:flex;flex-wrap:wrap;gap:4px 12px;">
            <?php foreach ($showActions as $a): ?>
            <label style="display:flex;align-items:center;gap:4px;font-size:0.8rem;cursor:pointer;">
                <input type="checkbox" name="permisos[]" value="<?= $a['id'] ?>">
                <?= htmlspecialchars($a['etiqueta']) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    endforeach;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'roles';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Roles y Permisos'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Roles</h1>
                    <p style="color: var(--text-secondary);">Administración de roles y asignación de permisos del sistema.</p>
                </div>
                <button class="btn btn-primary" id="btnAddRole">
                    <i class="fas fa-plus"></i> Nuevo Rol
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rolesTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Rol</th>
                                    <th>Descripción</th>
                                    <th>Permisos</th>
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

    <!-- Add Role Modal -->
    <?php modal_form(['id' => 'addRoleModal', 'title' => 'Agregar Rol', 'formId' => 'addRoleForm']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Rol</label>
            <input type="text" class="form-control" name="nombre_rol" required placeholder="Ej: Supervisor, Vendedor" maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion_rol" rows="2" placeholder="Descripción opcional del rol" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Módulos y acciones permitidas</label>
            <div style="padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:300px;overflow-y:auto;">
                <?php renderPermisosChecklist($modulos, $codigoToId); ?>
            </div>
            <small class="text-muted">Selecciona los permisos que tendrá este rol.</small>
        </div>
    <?php modal_form_end('addRoleForm'); ?>

    <!-- Edit Role Modal -->
    <?php modal_form(['id' => 'editRoleModal', 'title' => 'Editar Rol', 'formId' => 'editRoleForm', 'hasHiddenId' => true, 'hiddenId' => 'editRoleId', 'saveText' => 'Actualizar']); ?>
        <div class="mb-3">
            <label class="form-label">Nombre del Rol</label>
            <input type="text" class="form-control" name="nombre_rol" id="editRoleName" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion_rol" id="editRoleDesc" rows="2" maxlength="500"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Módulos y acciones permitidas</label>
            <div style="padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:300px;overflow-y:auto;">
                <?php renderPermisosChecklist($modulos, $codigoToId); ?>
            </div>
            <small class="text-muted">Selecciona los permisos que tendrá este rol.</small>
        </div>
    <?php modal_form_end('editRoleForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/roles.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/roles.js') ?>"></script>
</body>
</html>
