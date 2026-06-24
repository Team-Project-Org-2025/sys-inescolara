<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Merma;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_mermas'        => get_mermas(),
                'GET_get_quarantine'    => get_quarantine(),
                'POST_add_ajax'         => add_ajax(),
                'POST_delete_ajax'      => delete_ajax(),
                default                 => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/mermas.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de mermas no encontrada.';
        return;
    }
    require $view;
}

function get_mermas(): void { checkModuleAuth(); mermas_getAllAjax(); }
function get_quarantine(): void { checkModuleAuth(); mermas_getQuarantineAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('mermas:crear'); mermas_handleAdd(); }
function delete_ajax(): void { checkModuleAuth(); mermas_handleDelete(); }

function mermas_handleAdd(): void
{
    $model = new Merma();

    $idTrazabilidad = (int)($_POST['id_trazabilidad'] ?? 0);
    if ($idTrazabilidad <= 0) throw new \Exception('Debe seleccionar un registro de cuarentena.');

    $cantidad = (int)($_POST['cantidad'] ?? 0);
    if ($cantidad <= 0) throw new \Exception('La cantidad debe ser mayor a cero.');

    $motivo = trim((string)($_POST['motivo'] ?? ''));
    $motivosValidos = ['plaga', 'daño_mecanico', 'factor_climatico', 'enfermedad', 'otro'];
    if (!in_array($motivo, $motivosValidos, true)) {
        throw new \Exception('Motivo de merma inválido.');
    }

    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    $fechaMerma = trim((string)($_POST['fecha_merma'] ?? ''));
    if ($fechaMerma === '') throw new \Exception('La fecha de merma es requerida.');

    $idUsuario = \SysInescolara\helpers\Auth::id();
    if ($idUsuario <= 0) throw new \Exception('Usuario no autenticado.');

    $newId = $model->registerLoss($idTrazabilidad, $cantidad, $motivo, $descripcion, $fechaMerma, $idUsuario);

    AuditLog::record('CREATE', 'mermas_historico', $newId, null, compact('idTrazabilidad', 'cantidad', 'motivo', 'descripcion', 'fechaMerma'));
    jsonResponse(['success' => true, 'message' => 'Merma registrada correctamente. Stock de cuarentena actualizado.', 'id' => $newId]);
}

function mermas_handleDelete(): void
{
    $model = new Merma();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $data = $model->getById($id);
    if (!$data) throw new \Exception('No existe el registro de merma');

    $model->delete($id);

    AuditLog::record('DEACTIVATE', 'mermas_historico', $id, $data, null);
    jsonResponse(['success' => true, 'message' => 'Registro de merma desactivado correctamente.']);
}

function mermas_getAllAjax(): void
{
    $model = new Merma();
    $mermas = $model->getAll();
    jsonResponse(['success' => true, 'mermas' => $mermas]);
}

function mermas_getQuarantineAjax(): void
{
    $model = new Merma();
    $quarantine = $model->getAvailableQuarantine();
    jsonResponse(['success' => true, 'quarantine' => $quarantine]);
}
