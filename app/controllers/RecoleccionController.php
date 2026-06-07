<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\SeedCollection;
use SysInescolara\models\Location;
use SysInescolara\models\Employee;
use SysInescolara\models\Supplies;
use SysInescolara\models\UnidadMedida;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_recolecciones'   => recoleccion_getRecoleccionesAjax(),
                'POST_add_ajax'           => recoleccion_handleAddEdit('add'),
                'POST_edit_ajax'          => recoleccion_handleAddEdit('edit'),
                'POST_delete_ajax'        => recoleccion_handleDelete(),
                'POST_completar_ajax'     => recoleccion_handleCompletar(),
                'POST_registrar_insumo_ajax' => recoleccion_handleRegistrarInsumo(),
                default                   => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $locationModel = new Location();
    $ubicaciones = $locationModel->getAll();
    $employeeModel = new Employee();
    $trabajadores = $employeeModel->getAll();
    $unidadMedidaModel = new UnidadMedida();
    $unidades = $unidadMedidaModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/seed-collection.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de recolección no encontrada.';
        return;
    }
    require $view;
}

function get_recolecciones(): void { checkModuleAuth(); recoleccion_getRecoleccionesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('RECOLECCION_CREATE'); recoleccion_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('RECOLECCION_EDIT'); recoleccion_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('RECOLECCION_DELETE'); recoleccion_handleDelete(); }
function completar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('RECOLECCION_COMPLETE'); recoleccion_handleCompletar(); }
function registrar_insumo_ajax(): void { checkModuleAuth(); checkPermisoOrFail('RECOLECCION_COMPLETE'); recoleccion_handleRegistrarInsumo(); }

function recoleccion_handleAddEdit(string $mode): void
{
    $model = new SeedCollection();
    $idTrabajador = (int)($_POST['id_trabajador'] ?? 0);
    if ($idTrabajador <= 0) throw new \Exception('El trabajador es requerido.');
    $idUbicacion = (int)($_POST['id_ubicacion'] ?? 0);
    if ($idUbicacion <= 0) throw new \Exception('La ubicación es requerida.');
    $fechaAsignacion = trim((string)($_POST['fecha_asignacion'] ?? ''));
    if ($fechaAsignacion === '') throw new \Exception('La fecha de asignación es requerida.');
    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    if ($mode === 'add') {
        $model->add($idTrabajador, $idUbicacion, $fechaAsignacion, $observacion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'recoleccion_semillas', $newId, null, compact('idTrabajador', 'idUbicacion', 'fechaAsignacion', 'observacion'));
        jsonResponse(['success' => true, 'message' => 'Recolección registrada correctamente', 'id' => $newId]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    $oldData = $model->getById($id);
    $model->update($id, $idTrabajador, $idUbicacion, $fechaAsignacion, $observacion);
    AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $oldData, compact('idTrabajador', 'idUbicacion', 'fechaAsignacion', 'observacion'));
    jsonResponse(['success' => true, 'message' => 'Recolección actualizada correctamente']);
}

function recoleccion_handleDelete(): void
{
    $model = new SeedCollection();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la recolección');

    $oldData = $model->getById($id);
    try {
        $model->delete($id);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000 && str_contains($e->getMessage(), '1451')) {
            jsonResponse(['success' => false, 'message' => 'No se puede eliminar esta recolección porque tiene registros asociados.', 'type' => 'foreign_key']);
            return;
        }
        throw $e;
    }
    AuditLog::record('DELETE', 'recoleccion_semillas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Recolección eliminada correctamente']);
}

function recoleccion_handleCompletar(): void
{
    $model = new SeedCollection();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $data = $model->getById($id);
    if (!$data) throw new \Exception('No existe la recolección');
    if ($data['estatus'] !== 'Pendiente') throw new \Exception('Solo se pueden completar recolecciones pendientes.');

    $fechaRecoleccion = trim((string)($_POST['fecha_recoleccion'] ?? ''));
    if ($fechaRecoleccion === '') {
        $fechaRecoleccion = date('Y-m-d');
    }

    $model->complete($id, $fechaRecoleccion);
    AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $data, ['estatus' => 'Realizada', 'fecha_recoleccion' => $fechaRecoleccion]);
    jsonResponse(['success' => true, 'message' => 'Recolección completada correctamente']);
}

function recoleccion_handleRegistrarInsumo(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model = new SeedCollection();
    $data = $model->getById($id);
    if (!$data) throw new \Exception('No existe la recolección');
    if ($data['estatus'] !== 'Realizada') throw new \Exception('Debe completar la recolección antes de registrar el insumo.');
    if (!empty($data['id_insumo'])) throw new \Exception('Esta recolección ya tiene un insumo registrado.');

    $nombreInsumo = trim((string)($_POST['nombre_insumo'] ?? ''));
    if ($nombreInsumo === '') throw new \Exception('El nombre del insumo es requerido.');
    $idUnidadMedida = (int)($_POST['id_unidad_medida'] ?? 0);
    if ($idUnidadMedida <= 0) throw new \Exception('La unidad de medida es requerida.');
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    if ($cantidad <= 0) throw new \Exception('La cantidad debe ser mayor a cero.');

    $suppliesModel = new Supplies();
    $ok = $suppliesModel->add($nombreInsumo, $idUnidadMedida, 'Semillas', $cantidad, 0);
    if (!$ok) throw new \Exception('Error al crear el insumo.');

    $idInsumo = $suppliesModel->getLastInsertId();
    if ($idInsumo === null) throw new \Exception('Error al obtener el ID del insumo.');

    $model->linkInsumo($id, $idInsumo, $cantidad);
    AuditLog::record('CREATE', 'insumo', $idInsumo, null, ['nombre_insumo' => $nombreInsumo, 'cantidad' => $cantidad, 'origen' => 'recoleccion_semillas', 'id_recoleccion' => $id]);
    jsonResponse(['success' => true, 'message' => 'Insumo registrado correctamente', 'id_insumo' => $idInsumo]);
}

function recoleccion_getRecoleccionesAjax(): void
{
    $model = new SeedCollection();
    jsonResponse(['success' => true, 'recolecciones' => $model->getAll()]);
}
