<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Role;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_roles'   => get_roles(),
                'POST_add_ajax'   => add_ajax(),
                'POST_edit_ajax'  => edit_ajax(),
                'POST_delete_ajax' => delete_ajax(),
                default           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/roles.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de roles no encontrada.';
        return;
    }
    require $view;
}

function get_roles(): void { checkModuleAuth(); roles_getRolesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('roles:crear'); roles_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('roles:editar'); roles_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('roles:eliminar'); roles_handleDelete(); }

function roles_handleAddEdit(string $mode): void
{
    $model = new Role();
    $nombreRol = trim((string)($_POST['nombre_rol'] ?? ''));
    if ($nombreRol === '') throw new \Exception('El nombre del rol es requerido.');

    $descripcion = trim((string)($_POST['descripcion_rol'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    if ($mode === 'add') {
        if ($model->existsByName($nombreRol)) {
            throw new \Exception('Ya existe un rol con ese nombre.');
        }
        $model->add($nombreRol, $descripcion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'roles', $newId, null, compact('nombreRol', 'descripcion'));
        jsonResponse(['success' => true, 'message' => 'Rol creado correctamente', 'role' => ['id' => $newId, 'nombre_rol' => $nombreRol]]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if ($id === 1) throw new \Exception('No se puede editar el rol de Administrador.');
    if (!$model->exists($id)) throw new \Exception('No existe el rol.');

    if ($model->existsByName($nombreRol, $id)) {
        throw new \Exception('Ya existe otro rol con ese nombre.');
    }

    $oldData = $model->getById($id);
    $model->update($id, $nombreRol, $descripcion);
    AuditLog::record('UPDATE', 'roles', $id, $oldData, compact('nombreRol', 'descripcion'));
    jsonResponse(['success' => true, 'message' => 'Rol actualizado correctamente', 'role' => ['id' => $id]]);
}

function roles_handleDelete(): void
{
    $model = new Role();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if ($id <= 2) throw new \Exception('No se pueden eliminar los roles por defecto.');
    if (!$model->exists($id)) throw new \Exception('No existe el rol.');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'roles', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Rol desactivado correctamente', 'roleId' => $id]);
}

function roles_getRolesAjax(): void
{
    $model = new Role();
    $roles = $model->getAll();
    jsonResponse(['success' => true, 'roles' => $roles, 'count' => count($roles)]);
}
