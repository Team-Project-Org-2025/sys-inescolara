<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\helpers\Validation;
use SysInescolara\models\User;
use SysInescolara\models\AuditLog;

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
    $rules = [
        'nombre_usuario' => ['type' => null, 'required' => true],
        'password'       => ['type' => 'password', 'required' => $mode === 'add'],
        'rol_id'         => ['type' => null, 'required' => true],
        'correo_electronico' => ['type' => null, 'required' => false],
    ];
    $validation = Validation::validate($data, $rules);
    if (!$validation['valid']) {
        throw new \Exception(implode(', ', $validation['errors']));
    }
    $email = trim((string)($data['correo_electronico'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('El formato del correo electrónico no es válido.');
    }
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
    if ($id === 1) $rolId = 1;

    $isChangingPassword = ($mode === 'edit' && $password !== '');
    $isOwnAccount = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id;
    $isAdmin = isset($_SESSION['user_rol_id']) && (int)$_SESSION['user_rol_id'] === 1;
    if ($isChangingPassword && ($isOwnAccount || $isAdmin)) {
        $currentPassword = $_POST['current_password'] ?? '';
        if ($currentPassword === '') {
            throw new \Exception('Debes ingresar tu contraseña actual para realizar este cambio.');
        }
        $verifyUserId = $isOwnAccount ? $id : (int)$_SESSION['user_id'];
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
        $model->add($nombreUsuario, $password, $rolId, $correoElectronico, $avatar);
        $newId = $model->getLastInsertId() ?? 0;
        if ($rolId !== 1) $model->setUserPermissions($newId, $permisoIds);
        AuditLog::record('CREATE', 'usuarios', $newId, null, compact('nombreUsuario', 'correoElectronico', 'rolId'));
        jsonResponse([
            'success' => true, 'message' => 'Usuario agregado',
            'user' => ['id' => $newId, 'nombre_usuario' => $nombreUsuario, 'correo_electronico' => $correoElectronico, 'rol_id' => $rolId, 'avatar' => $avatar, 'permisos' => $rolId !== 1 ? $model->getUserPermissions($newId) : []],
        ]);
    }

    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    if ($avatar === null) {
        $avatar = $oldData['avatar'] ?? null;
    } elseif (!empty($oldData['avatar'])) {
        (new \SysInescolara\helpers\ImageUploader())->delete($oldData['avatar']);
    }

    $model->update($id, $nombreUsuario, $rolId, $correoElectronico, $password !== '' ? $password : null, $avatar);
    if ($rolId !== 1) {
        $model->setUserPermissions($id, $permisoIds);
    } else {
        $model->setUserPermissions($id, []);
    }

    AuditLog::record('UPDATE', 'usuarios', $id, $oldData, compact('nombreUsuario', 'correoElectronico', 'rolId'));

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
        $_SESSION['user_nombre'] = $nombreUsuario;
        $_SESSION['user_avatar'] = $avatar;
    }

    jsonResponse([
        'success' => true, 'message' => 'Usuario actualizado',
        'user' => ['id' => $id, 'nombre_usuario' => $nombreUsuario, 'correo_electronico' => $correoElectronico, 'rol_id' => $rolId, 'avatar' => $avatar, 'permisos' => $rolId !== 1 ? $model->getUserPermissions($id) : []],
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
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || !$model->verifyPassword($userId, $currentPassword)) {
        jsonResponse(['success' => false, 'message' => 'Contraseña incorrecta. No se puede eliminar el usuario.'], 403);
        return;
    }

    $oldData = $model->getById($id);
    $model->delete($id);
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
