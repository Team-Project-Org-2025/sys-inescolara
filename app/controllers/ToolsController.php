<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Tool;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_tools'   => tools_getToolsAjax(),
                'POST_add_ajax'   => tools_handleAddEdit('add'),
                'POST_edit_ajax'  => tools_handleAddEdit('edit'),
                'POST_delete_ajax' => tools_handleDelete(),
                default           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/tools.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de herramientas no encontrada.';
        return;
    }
    require $view;
}

function get_tools(): void { checkModuleAuth(); tools_getToolsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('HERRAMIENTAS_CREATE'); tools_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('HERRAMIENTAS_EDIT'); tools_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('HERRAMIENTAS_DELETE'); tools_handleDelete(); }

function tools_handleAddEdit(string $mode): void
{
    $model = new Tool();
    $nombre = trim((string)($_POST['nombre_herramienta'] ?? ''));
    if ($nombre === '') {
        throw new \Exception('El nombre de la herramienta es requerido.');
    }

    $tipo = trim((string)($_POST['tipo'] ?? ''));
    if ($tipo === '') $tipo = null;

    $estado = trim((string)($_POST['estado'] ?? ''));
    if ($estado === '') $estado = 'disponible';

    $fechaAdquisicion = trim((string)($_POST['fecha_adquisicion'] ?? ''));
    if ($fechaAdquisicion === '') $fechaAdquisicion = null;

    $fechaUltimoMantenimiento = trim((string)($_POST['fecha_ultimo_mantenimiento'] ?? ''));
    if ($fechaUltimoMantenimiento === '') $fechaUltimoMantenimiento = null;

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    if ($mode === 'add') {
        $model->add($nombre, $tipo, $estado, $fechaAdquisicion, $fechaUltimoMantenimiento, $observacion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'herramienta', $newId, null, compact('nombre', 'tipo', 'estado', 'fechaAdquisicion', 'fechaUltimoMantenimiento', 'observacion'));
        jsonResponse([
            'success' => true, 'message' => 'Herramienta agregada correctamente',
            'tool' => ['id' => $newId, 'nombre_herramienta' => $nombre, 'tipo' => $tipo, 'estado' => $estado],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $model->update($id, $nombre, $tipo, $estado, $fechaAdquisicion, $fechaUltimoMantenimiento, $observacion);
    AuditLog::record('UPDATE', 'herramienta', $id, $oldData, compact('nombre', 'tipo', 'estado', 'fechaAdquisicion', 'fechaUltimoMantenimiento', 'observacion'));
    jsonResponse([
        'success' => true, 'message' => 'Herramienta actualizada correctamente',
        'tool' => ['id' => $id],
    ]);
}

function tools_handleDelete(): void
{
    $model = new Tool();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la herramienta');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DELETE', 'herramienta', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Herramienta eliminada correctamente', 'toolId' => $id]);
}

function tools_getToolsAjax(): void
{
    $model = new Tool();
    $tools = $model->getAll();
    jsonResponse(['success' => true, 'tools' => $tools, 'count' => count($tools)]);
}
