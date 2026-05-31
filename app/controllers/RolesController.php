<?php

namespace SysInescolara\controllers;

use SysInescolara\models\Role;
use SysInescolara\models\AuditLog;
use SysInescolara\traits\ResponseTrait;
use SysInescolara\traits\PermissionTrait;

class RolesController
{
    use ResponseTrait, PermissionTrait;

    private Role $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new Role();
    }

    public function index(): void
    {
        $this->checkModuleAuth();
        $this->handleAjaxRequest();

        $allPermisos = $this->model->getAllPermissions();

        $view = ROOT_PATH . 'app/views/dashboard/roles.php';
        if (!is_file($view)) {
            http_response_code(500);
            echo 'Vista de roles no encontrada.';
            return;
        }
        require $view;
    }

    public function get_roles(): void { $this->checkModuleAuth(); $this->getRolesAjax(); }
    public function add_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('USUARIOS_MANAGE'); $this->handleAddEdit('add'); }
    public function edit_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('USUARIOS_MANAGE'); $this->handleAddEdit('edit'); }
    public function delete_ajax(): void { $this->checkModuleAuth(); $this->checkPermisoOrFail('USUARIOS_MANAGE'); $this->handleDelete(); }

    private function handleAjaxRequest(): void
    {
        $action = $_GET['action'] ?? '';
        if (!$this->isAjaxRequest() || $action === '') return;

        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_roles'   => $this->getRolesAjax(),
                'POST_add_ajax'   => $this->handleAddEdit('add'),
                'POST_edit_ajax'  => $this->handleAddEdit('edit'),
                'POST_delete_ajax' => $this->handleDelete(),
                default           => $this->jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            $this->handleError($e, true);
        }
    }

    private function handleAddEdit(string $mode): void
    {
        $nombreRol = trim((string)($_POST['nombre_rol'] ?? ''));
        if ($nombreRol === '') throw new \Exception('El nombre del rol es requerido.');

        $descripcion = trim((string)($_POST['descripcion_rol'] ?? ''));
        if ($descripcion === '') $descripcion = null;

        $permisoIds = isset($_POST['permisos']) ? array_map('intval', (array)$_POST['permisos']) : [];

        if ($mode === 'add') {
            if ($this->model->existsByName($nombreRol)) {
                throw new \Exception('Ya existe un rol con ese nombre.');
            }
            $this->model->add($nombreRol, $descripcion, $permisoIds);
            $newId = $this->model->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'roles', $newId, null, compact('nombreRol', 'descripcion', 'permisoIds'));
            $this->jsonResponse(['success' => true, 'message' => 'Rol creado correctamente', 'role' => ['id' => $newId, 'nombre_rol' => $nombreRol]]);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if ($id === 1) throw new \Exception('No se puede editar el rol de Administrador.');
        if (!$this->model->exists($id)) throw new \Exception('No existe el rol.');

        if ($this->model->existsByName($nombreRol, $id)) {
            throw new \Exception('Ya existe otro rol con ese nombre.');
        }

        $oldData = $this->model->getById($id);
        $this->model->update($id, $nombreRol, $descripcion, $permisoIds);
        AuditLog::record('UPDATE', 'roles', $id, $oldData, compact('nombreRol', 'descripcion', 'permisoIds'));
        $this->jsonResponse(['success' => true, 'message' => 'Rol actualizado correctamente', 'role' => ['id' => $id]]);
    }

    private function handleDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');
        if ($id <= 2) throw new \Exception('No se pueden eliminar los roles por defecto.');
        if (!$this->model->exists($id)) throw new \Exception('No existe el rol.');

        $oldData = $this->model->getById($id);
        $this->model->delete($id);
        AuditLog::record('DELETE', 'roles', $id, $oldData, null);
        $this->jsonResponse(['success' => true, 'message' => 'Rol eliminado correctamente', 'roleId' => $id]);
    }

    private function getRolesAjax(): void
    {
        $roles = $this->model->getAll();
        foreach ($roles as &$r) {
            $r['permisos'] = $this->model->getRolePermissions((int)$r['id']);
        }
        unset($r);
        $this->jsonResponse(['success' => true, 'roles' => $roles, 'count' => count($roles)]);
    }
}
