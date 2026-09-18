<?php
include_once __DIR__ . '/../common/links.php';
include_once __DIR__ . '/../common/modal.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permisos - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'permisos';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Permisos'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Gestión de Permisos</h1>
                    <p style="color: var(--text-secondary);">Asigna los módulos y acciones permitidas a cada rol del sistema.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="permisosTable" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Rol</th>
                                    <th>Descripción</th>
                                    <th>Permisos Asignados</th>
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

    <!-- Role Permissions Modal -->
    <?php modal_form(['id' => 'rolePermisosModal', 'title' => 'Permisos del Rol', 'formId' => 'rolePermisosForm', 'hasHiddenId' => true, 'hiddenId' => 'permisosRoleId', 'hiddenIdName' => 'id_rol', 'size' => 'modal-lg', 'saveText' => 'Guardar Permisos']); ?>
        <p class="mb-3" style="color: var(--text-secondary);">
            Rol: <strong id="permisosRoleName" style="color: var(--text-primary);"></strong>
        </p>
        <div class="mb-2 d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" id="btnCheckAll">
                <i class="fas fa-check-square"></i> Seleccionar todo
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnUncheckAll">
                <i class="fas fa-square"></i> Quitar todo
            </button>
        </div>
        <div style="padding:8px 12px;border:1px solid var(--color-gray-200);border-radius:var(--radius-md);background:var(--bg-secondary);max-height:400px;overflow-y:auto;">
            <div style="position:sticky;top:0;z-index:1;background:var(--bg-secondary);padding:6px 2px 4px;margin:0 -12px;padding-left:12px;padding-right:12px;border-bottom:2px solid var(--color-gray-200);">
                <div style="display:flex;gap:16px 24px;font-size:0.78rem;">
                    <span style="min-width:120px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.03em;">Módulo</span>
                    <?php foreach ($acciones as $accion): ?>
                        <span style="min-width:60px;text-align:center;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.03em;"><?= ucfirst($accion['nombre_permiso']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php foreach ($modulos as $modulo):
                $idModulo = $modulo['id_modulo'];
                $nombreModulo = $modulo['nombre_modulo'];
            ?>
            <div style="display:flex;align-items:center;gap:16px 24px;padding:5px 2px;font-size:0.8rem;border-bottom:1px solid var(--color-gray-100);">
                <span style="min-width:120px;font-weight:500;color:var(--text-primary);" title="<?= htmlspecialchars($modulo['descripcion_modulo'] ?? '') ?>"><?= htmlspecialchars($nombreModulo) ?></span>
                <?php foreach ($acciones as $accion):
                    $value = $idModulo . ':' . $accion['id_permiso'];
                ?>
                <label style="display:flex;align-items:center;justify-content:center;min-width:60px;gap:3px;cursor:pointer;">
                    <input type="checkbox" name="permisos[]" value="<?= $value ?>">
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <small class="text-muted">Los cambios aplican a todos los usuarios con este rol y toman efecto en su próxima recarga de permisos (máx. 5 minutos).</small>
    <?php modal_form_end('rolePermisosForm'); ?>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/permisos.js?v=<?= filemtime(ROOT_PATH . 'public/assets/js/dashboard/permisos.js') ?>"></script>
</body>
</html>
