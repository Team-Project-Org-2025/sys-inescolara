<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\SeedCollection;//no lo borre
use SysInescolara\models\Ubicacion;
use SysInescolara\models\Empleado;//no lo borre
use SysInescolara\models\Planta;
use SysInescolara\models\Insumo;
use SysInescolara\models\UnidadMedida;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_recolecciones'   => get_recolecciones(),
                'POST_add_ajax'           => add_ajax(),
                'POST_edit_ajax'          => edit_ajax(),
                'POST_delete_ajax'        => delete_ajax(),
                'POST_completar_ajax'     => completar_ajax(),
                'POST_registrar_insumo_ajax' => registrar_insumo_ajax(),
                default                   => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    header('Location: ' . BASE_URL . 'dashboard/seed-collection');
    exit();
}

function get_recolecciones(): void { checkModuleAuth(); recoleccion_getRecoleccionesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('seed_collection:crear'); recoleccion_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('seed_collection:editar'); recoleccion_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('seed_collection:eliminar'); recoleccion_handleDelete(); }
function completar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('seed_collection:editar'); recoleccion_handleCompletar(); }
function registrar_insumo_ajax(): void { checkModuleAuth(); checkPermisoOrFail('seed_collection:editar'); recoleccion_handleRegistrarInsumo(); }

function recoleccion_handleAddEdit(string $mode): void
{
    $model = new SeedCollection();
    $data = getRequestData();

    $idTrabajador = (int)($data['id_trabajador'] ?? 0);
    if ($idTrabajador <= 0) throw new \Exception('El trabajador es requerido.');
    $idUbicacion = (int)($data['id_ubicacion'] ?? 0);
    if ($idUbicacion <= 0) throw new \Exception('La ubicación es requerida.');

    $fechaAsignacion = trim((string)($data['fecha_asignacion'] ?? ''));
    if ($fechaAsignacion === '') throw new \Exception('La fecha de asignación es requerida.');
    $d = \DateTime::createFromFormat('Y-m-d', $fechaAsignacion);
    if (!$d || $d->format('Y-m-d') !== $fechaAsignacion) throw new \InvalidArgumentException('Formato de fecha inválido');
    $observacion = trim((string)($data['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    if ($mode === 'add') {
        $model->add($idTrabajador, $idUbicacion, $fechaAsignacion, $observacion);
        $newId = $model->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'recoleccion_semillas', $newId, null, compact('idTrabajador', 'idUbicacion', 'fechaAsignacion', 'observacion'));
        jsonResponse(['success' => true, 'message' => 'Recolección registrada correctamente', 'id' => $newId]);
    }

    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    $oldData = $model->getById($id);
    $model->update($id, $idTrabajador, $idUbicacion, $fechaAsignacion, $observacion);
    AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $oldData, compact('idTrabajador', 'idUbicacion', 'fechaAsignacion', 'observacion'));
    jsonResponse(['success' => true, 'message' => 'Recolección actualizada correctamente']);
}

function recoleccion_handleDelete(): void
{
    $model = new SeedCollection();
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
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
    AuditLog::record('DEACTIVATE', 'recoleccion_semillas', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Recolección desactivada correctamente']);
}

function recoleccion_handleCompletar(): void
{
    $model = new SeedCollection();
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $recoleccion = $model->getById($id);
    if (!$recoleccion) throw new \Exception('No existe la recolección');
    if ($recoleccion['estatus'] !== 'Pendiente') throw new \Exception('Solo se pueden completar recolecciones pendientes.');

    $fechaRecoleccion = trim((string)($data['fecha_recoleccion'] ?? ''));
    if ($fechaRecoleccion === '') {
        $fechaRecoleccion = date('Y-m-d');
    }

    $model->complete($id, $fechaRecoleccion);
    AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $recoleccion, ['estatus' => 'Realizada', 'fecha_recoleccion' => $fechaRecoleccion]);
    jsonResponse(['success' => true, 'message' => 'Recolección completada correctamente']);
}

function recoleccion_handleRegistrarInsumo(): void
{
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model = new SeedCollection();
    $recoleccion = $model->getById($id);
    if (!$recoleccion) throw new \Exception('No existe la recolección');
    if ($recoleccion['estatus'] !== 'Realizada') throw new \Exception('Debe completar la recolección antes de registrar los insumos.');

    $itemsJson = trim((string)($data['items'] ?? ''));
    if ($itemsJson === '') throw new \Exception('Debe agregar al menos un tipo de semilla.');

    $items = json_decode($itemsJson, true);
    if (!is_array($items) || empty($items)) throw new \Exception('Debe agregar al menos un tipo de semilla.');

    $createdCount = $model->registerSeedsWithTransaction($id, $items);

    if ($createdCount === 0) throw new \Exception('No se pudo registrar ningún insumo. Verifique los datos.');

    AuditLog::record('UPDATE', 'recoleccion_semillas', $id, $recoleccion, ['insumos_registrados' => $createdCount]);
    jsonResponse(['success' => true, 'message' => "$createdCount tipo(s) de semilla registrado(s) correctamente"]);
}

function recoleccion_getRecoleccionesAjax(): void
{
    $model = new SeedCollection();
    jsonResponse(['success' => true, 'recolecciones' => $model->getAll()]);
}
