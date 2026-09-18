<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Permiso;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_roles'            => get_roles(),
                'GET_get_role_permissions' => get_role_permissions(),
                'POST_save_ajax'           => save_ajax(),
                default                    => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    header('Location: ' . BASE_URL . 'dashboard/permisos');
    exit();
}

function get_roles(): void { checkModuleAuth(); checkPermisoOrFail('permisos:ver'); permisos_getRolesAjax(); }
function get_role_permissions(): void { checkModuleAuth(); checkPermisoOrFail('permisos:ver'); permisos_getRolePermissionsAjax(); }
function save_ajax(): void { checkModuleAuth(); checkPermisoOrFail('permisos:editar'); permisos_handleSave(); }

function permisos_getRolesAjax(): void
{
    $model = new Permiso();
    $roles = $model->getRoles();
    jsonResponse(['success' => true, 'roles' => $roles, 'count' => count($roles)]);
}

function permisos_getRolePermissionsAjax(): void
{
    $rolId = (int)($_GET['id'] ?? 0);
    if ($rolId <= 0) throw new \Exception('ID de rol inválido');

    $model = new Permiso();
    $permisos = $model->getPermisosRol($rolId);
    jsonResponse(['success' => true, 'permisos' => $permisos]);
}

function permisos_handleSave(): void
{
    $rolId = (int)($_POST['id_rol'] ?? 0);
    if ($rolId <= 0) throw new \Exception('ID de rol inválido');
    if ($rolId === 1) throw new \Exception('No se pueden modificar los permisos del rol Administrador.');

    $permisoIds = [];
    if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
        foreach ($_POST['permisos'] as $val) {
            $parts = explode(':', (string)$val);
            if (count($parts) === 2) {
                $permisoIds[] = ['id_modulo' => (int)$parts[0], 'id_permiso' => (int)$parts[1]];
            }
        }
    }

    $model = new Permiso();
    $model->setPermisosRol($rolId, $permisoIds);

    jsonResponse([
        'success' => true,
        'message' => 'Permisos del rol actualizados correctamente',
        'roleId' => $rolId,
        'permisos' => $model->getPermisosRol($rolId),
    ]);
}
