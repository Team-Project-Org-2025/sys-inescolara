<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Trazabilidad;
use SysInescolara\models\Lote;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_trazabilidad'     => get_trazabilidad(),
                'GET_get_batches'          => get_batches(),
                'POST_add_ajax'            => add_ajax(),
                'POST_edit_ajax'           => edit_ajax(),
                'POST_delete_ajax'         => delete_ajax(),
                default                    => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/trazabilidad.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de trazabilidad no encontrada.';
        return;
    }
    require $view;
}

function get_trazabilidad(): void { checkModuleAuth(); trazabilidad_getTrazabilidadAjax(); }
function get_batches(): void { checkModuleAuth(); trazabilidad_getBatchesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('trazabilidad:crear'); trazabilidad_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('trazabilidad:editar'); trazabilidad_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('trazabilidad:eliminar'); trazabilidad_handleDelete(); }

function trazabilidad_handleAddEdit(string $mode): void
{
    $model = new Trazabilidad();
    $batchModel = new Lote();

    $idLote = (int)($_POST['id_lote'] ?? 0);
    if ($idLote <= 0) throw new \Exception('Debe seleccionar un lote.');

    $cantidad = (int)($_POST['cantidad'] ?? 0);
    if ($cantidad <= 0) throw new \Exception('La cantidad debe ser mayor a cero.');

    $lote = $batchModel->getById($idLote);
    if (!$lote) throw new \Exception('El lote seleccionado no existe.');

    if ($mode === 'add') {
        if ($cantidad > (int)$lote['cantidad_actual']) {
            throw new \Exception("El lote solo tiene {$lote['cantidad_actual']} ejemplares disponibles.");
        }
    }

    $estadoSalud = trim((string)($_POST['estado_salud'] ?? ''));
    if ($estadoSalud === '') throw new \Exception('El estado de salud es requerido.');

    $fechaRegistro = trim((string)($_POST['fecha_registro'] ?? ''));
    if ($fechaRegistro === '') throw new \Exception('La fecha de cuarentena es requerida.');
    $d = \DateTime::createFromFormat('Y-m-d', $fechaRegistro);
    if (!$d || $d->format('Y-m-d') !== $fechaRegistro) {
        throw new \InvalidArgumentException('Formato de fecha inválido (debe ser YYYY-MM-DD).');
    }
    $todayStr = (new \DateTime('today'))->format('Y-m-d');
    if ($fechaRegistro > $todayStr) {
        throw new \InvalidArgumentException('La fecha de cuarentena no puede ser posterior al día de hoy.');
    }

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    if ($mode === 'add') {
        try {
            $model->beginTransaction();

            $model->add($idLote, $cantidad, $estadoSalud, $fechaRegistro, $observacion);
            $newId = $model->getLastInsertId() ?? 0;

            $model->deductBatchStock($idLote, $cantidad);

            $model->commit();

            AuditLog::record('CREATE', 'trazabilidad', $newId, null, compact('idLote', 'cantidad', 'estadoSalud', 'fechaRegistro', 'observacion'));
            jsonResponse(['success' => true, 'message' => 'Cuarentena registrada correctamente. Stock del lote actualizado.', 'id' => $newId]);
        } catch (\Exception $e) {
            $model->rollback();
            throw $e;
        }
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $oldData = $model->getById($id);
    $oldLoteId = (int)($oldData['id_lote'] ?? 0);
    $oldCantidad = (int)($oldData['cantidad'] ?? 0);

    if ($oldLoteId !== $idLote) {
        $newLote = $batchModel->getById($idLote);
        if (!$newLote) throw new \Exception('El nuevo lote seleccionado no existe.');
        if ($cantidad > (int)$newLote['cantidad_actual']) {
            throw new \Exception("El nuevo lote solo tiene {$newLote['cantidad_actual']} ejemplares disponibles.");
        }

        $model->beginTransaction();
        try {
            $model->restoreBatchStock($oldLoteId, $oldCantidad);
            $model->deductBatchStock($idLote, $cantidad);
            $model->update($id, $idLote, $cantidad, $estadoSalud, $fechaRegistro, $observacion);
            $model->commit();
        } catch (\Exception $e) {
            $model->rollback();
            throw $e;
        }
    } elseif ($oldCantidad !== $cantidad) {
        $diff = $cantidad - $oldCantidad;

        $model->beginTransaction();
        try {
            if ($diff > 0) {
                $model->deductBatchStock($idLote, $diff);
            } else {
                $model->restoreBatchStock($idLote, abs($diff));
            }
            $model->update($id, $idLote, $cantidad, $estadoSalud, $fechaRegistro, $observacion);
            $model->commit();
        } catch (\Exception $e) {
            $model->rollback();
            throw $e;
        }
    } else {
        $model->update($id, $idLote, $cantidad, $estadoSalud, $fechaRegistro, $observacion);
    }

    AuditLog::record('UPDATE', 'trazabilidad', $id, $oldData, compact('idLote', 'cantidad', 'estadoSalud', 'fechaRegistro', 'observacion'));
    jsonResponse(['success' => true, 'message' => 'Monitoreo actualizado correctamente.']);
}

function trazabilidad_handleDelete(): void
{
    $model = new Trazabilidad();
    $batchModel = new Lote();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $data = $model->getById($id);
    if (!$data) throw new \Exception('No existe el registro de trazabilidad');

    $idLote = (int)($data['id_lote'] ?? 0);
    $cantidad = (int)($data['cantidad'] ?? 0);

    try {
        $model->beginTransaction();

        $model->delete($id);

        if ($idLote > 0 && $cantidad > 0) {
            $lote = $batchModel->getById($idLote);
            if ($lote) {
                $model->restoreBatchStock($idLote, $cantidad);
            }
        }

        $model->commit();
    } catch (\Exception $e) {
        $model->rollback();
        throw $e;
    }

    AuditLog::record('DEACTIVATE', 'trazabilidad', $id, $data, null);
    jsonResponse(['success' => true, 'message' => 'Registro de cuarentena desactivado correctamente. Stock del lote restaurado.']);
}

function trazabilidad_getTrazabilidadAjax(): void
{
    $model = new Trazabilidad();
    jsonResponse(['success' => true, 'trazabilidad' => $model->getAll()]);
}

function trazabilidad_getBatchesAjax(): void
{
    $model = new Trazabilidad();
    jsonResponse(['success' => true, 'batches' => $model->getAvailableBatches()]);
}
