<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\User;
use SysInescolara\models\AuditLog;
use SysInescolara\models\Empleado;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_users'   => users_getUsersAjax(),
                'POST_add_ajax'   => users_handleAddEdit('add'),
                'POST_edit_ajax'  => users_handleAddEdit('edit'),
                'POST_delete_ajax' => users_handleDelete(),
                default           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $model = new User();
    $roles = $model->getRoles();
    $allPermisos = $model->getAllPermissions();
    $employeeModel = new Empleado();
    $trabajadores = $employeeModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/usuarios.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de usuarios no encontrada.';
        return;
    }
    require $view;
}

function get_users(): void { checkModuleAuth(); users_getUsersAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('USUARIOS_MANAGE'); users_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('USUARIOS_MANAGE'); users_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('USUARIOS_MANAGE'); users_handleDelete(); }

function users_validateUserData(array $data, string $mode): void
{
    $errors = [];
    if (empty($data['nombre_usuario'])) $errors[] = 'El nombre de usuario es requerido';
    if ($mode === 'add' && empty($data['password'])) $errors[] = 'La contraseña es requerida';
    if (!empty($data['password'])) {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/', $data['password'])) {
            $errors[] = 'La contraseña debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos';
        }
    }
    if (empty($data['rol_id'])) $errors[] = 'El rol es requerido';
    $email = trim((string)($data['correo_electronico'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido.';
    }
    if (!empty($errors)) throw new \Exception(implode(', ', $errors));
}

function users_handleAddEdit(string $mode): void
{
    $model = new User();
    users_validateUserData($_POST, $mode);

    $id = (int)($_POST['id'] ?? 0);
    $nombreUsuario = trim((string)($_POST['nombre_usuario'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $rolId = (int)($_POST['rol_id'] ?? 1);
    $correoElectronico = trim((string)($_POST['correo_electronico'] ?? ''));
    if ($correoElectronico === '') $correoElectronico = null;
    $idTrabajadorRef = !empty($_POST['id_trabajador_ref']) ? (int)$_POST['id_trabajador_ref'] : null;
    if ($id === 1) $rolId = 1;

    $isChangingPassword = ($mode === 'edit' && $password !== '');
    $isOwnAccount = \SysInescolara\helpers\Auth::check() && \SysInescolara\helpers\Auth::id() === $id;
    $isAdmin = \SysInescolara\helpers\Auth::isAdmin();
    if ($isChangingPassword && ($isOwnAccount || $isAdmin)) {
        $currentPassword = $_POST['current_password'] ?? '';
        if ($currentPassword === '') {
            throw new \Exception('Debes ingresar tu contraseña actual para realizar este cambio.');
        }
        $verifyUserId = $isOwnAccount ? $id : \SysInescolara\helpers\Auth::id();
        if (!$model->verifyPassword($verifyUserId, $currentPassword)) {
            throw new \Exception('La contraseña actual no es correcta.');
        }
    }

    $avatar = null;
    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/avatars');
        $result = $uploader->upload($_FILES['avatar'], 'avatar');
        if (!$result['success']) throw new \Exception(implode(', ', $result['errors']));
        $avatar = $result['data']['url'];
    }

    $permisoIds = isset($_POST['permisos']) ? array_map('intval', (array)$_POST['permisos']) : [];

    if ($mode === 'add') {
        if ($model->userExists(null, $nombreUsuario)) {
            throw new \Exception('El nombre de usuario ya está registrado');
        }
        $model->add($nombreUsuario, $password, $rolId, $correoElectronico, $avatar, $idTrabajadorRef);
        $newId = $model->getLastInsertId() ?? 0;
        if ($rolId !== 1) $model->setUserPermissions($newId, $permisoIds);
        AuditLog::record('CREATE', 'usuarios', $newId, null, compact('nombreUsuario', 'correoElectronico', 'rolId'));
        jsonResponse([
            'success' => true, 'message' => 'Usuario agregado',
            'user' => ['id' => $newId, 'nombre_usuario' => $nombreUsuario, 'correo_electronico' => $correoElectronico, 'rol_id' => $rolId, 'id_trabajador_ref' => $idTrabajadorRef, 'avatar' => $avatar, 'permisos' => $rolId !== 1 ? $model->getUserPermissions($newId) : []],
        ]);
    }

    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    if ($avatar === null) {
        $avatar = $oldData['avatar'] ?? null;
    } elseif (!empty($oldData['avatar'])) {
        (new \SysInescolara\helpers\ImageUploader())->delete($oldData['avatar']);
    }

    $model->update($id, $nombreUsuario, $rolId, $correoElectronico, $password !== '' ? $password : null, $avatar, $idTrabajadorRef);
    if ($rolId !== 1) {
        $model->setUserPermissions($id, $permisoIds);
    } else {
        $model->setUserPermissions($id, []);
    }

    AuditLog::record('UPDATE', 'usuarios', $id, $oldData, compact('nombreUsuario', 'correoElectronico', 'rolId'));

    if (\SysInescolara\helpers\Auth::check() && \SysInescolara\helpers\Auth::id() === $id) {
        \SysInescolara\helpers\Auth::setField('user_nombre', $nombreUsuario);
        \SysInescolara\helpers\Auth::setField('user_avatar', $avatar);
    }

    jsonResponse([
        'success' => true, 'message' => 'Usuario actualizado',
        'user' => ['id' => $id, 'nombre_usuario' => $nombreUsuario, 'correo_electronico' => $correoElectronico, 'rol_id' => $rolId, 'id_trabajador_ref' => $idTrabajadorRef, 'avatar' => $avatar, 'permisos' => $rolId !== 1 ? $model->getUserPermissions($id) : []],
    ]);
}

function users_handleDelete(): void
{
    $model = new User();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if ($id === 1) {
        jsonResponse(['success' => false, 'message' => 'No se puede eliminar el superusuario principal'], 400);
        return;
    }
    if (!$model->userExists($id)) throw new \Exception('No existe el usuario');

    $currentPassword = $_POST['current_password'] ?? '';
    if ($currentPassword === '') {
        jsonResponse(['success' => false, 'message' => 'Debes ingresar tu contraseña para eliminar un usuario.'], 400);
        return;
    }
    $userId = \SysInescolara\helpers\Auth::id();
    if ($userId <= 0 || !$model->verifyPassword($userId, $currentPassword)) {
        jsonResponse(['success' => false, 'message' => 'Contraseña incorrecta. No se puede eliminar el usuario.'], 403);
        return;
    }

    $oldData = $model->getById($id);
    if (!$model->delete($id)) {
        jsonResponse(['success' => false, 'message' => 'No se pudo eliminar el usuario. Puede tener registros asociados (ej. sesiones activas, auditoría).'], 500);
        return;
    }
    AuditLog::record('DELETE', 'usuarios', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Usuario desactivado', 'userId' => $id]);
}

function users_getUsersAjax(): void
{
    $model = new User();
    $users = $model->getAll();
    foreach ($users as &$u) {
        $u['permisos'] = ($u['rol_id'] ?? 0) !== 1 ? $model->getUserPermissions((int)$u['id']) : [];
    }
    unset($u);
    jsonResponse(['success' => true, 'users' => $users, 'count' => count($users)]);
}
