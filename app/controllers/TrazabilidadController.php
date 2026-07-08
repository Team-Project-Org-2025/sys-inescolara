<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Trazabilidad;
use SysInescolara\models\Lote;

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
                'POST_update_estado_ajax'  => update_estado_ajax(),
                'POST_restore_ajax'        => restore_ajax(),
                'POST_delete_ajax'         => delete_ajax(),
                default                    => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $loteModel = new Lote();
    $estados = $loteModel->getEstados();
    $estadoVivoId = $loteModel->getIdEstadoVivo();

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
function update_estado_ajax(): void { checkModuleAuth(); checkPermisoOrFail('trazabilidad:editar'); trazabilidad_handleUpdateEstado(); }
function restore_ajax(): void { checkModuleAuth(); checkPermisoOrFail('trazabilidad:editar'); trazabilidad_handleRestore(); }

function trazabilidad_validateAndParseInput(): array
{
    $idLote = (int)($_POST['id_lote'] ?? 0);
    if ($idLote <= 0) throw new \Exception('Debe seleccionar un lote.');

    $rawCantidad = $_POST['cantidad'] ?? '';
    if (!preg_match('/^[1-9]\d*$/', $rawCantidad)) {
        throw new \Exception('La cantidad debe ser un número entero positivo, sin decimales ni signos.');
    }
    $cantidad = (int)$rawCantidad;

    $idEstado = (int)($_POST['id_estado'] ?? 0);
    if ($idEstado <= 0) throw new \Exception('El estado de salud es requerido.');

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

    return [$idLote, $cantidad, $idEstado, $fechaRegistro, $observacion];
}

function trazabilidad_handleAddEdit(string $mode): void
{
    $batchModel = new Lote();

    [$idLote, $cantidad, $idEstado, $fechaRegistro, $observacion] = trazabilidad_validateAndParseInput();

    $lote = $batchModel->getById($idLote);
    if (!$lote) throw new \Exception('El lote seleccionado no existe.');

    if ($mode === 'add') {
        if ($cantidad > (int)$lote['cantidad_actual']) {
            throw new \Exception("El lote solo tiene {$lote['cantidad_actual']} ejemplares disponibles.");
        }

        $model = new Trazabilidad([
            'id_lote'       => $idLote,
            'cantidad'      => $cantidad,
            'id_estado'     => $idEstado,
            'fecha_registro'=> $fechaRegistro,
            'observacion'   => $observacion,
        ]);

        try {
            $model->beginTransaction();
            if (!$model->save()) {
                throw new \Exception('Error al guardar el registro de trazabilidad.');
            }
            $newId = $model->getId();

            $model->deductBatchStock($idLote, $cantidad);

            $model->commit();

            jsonResponse(['success' => true, 'message' => 'Cuarentena registrada correctamente. Stock del lote actualizado.', 'id' => $newId]);
        } catch (\Exception $e) {
            $model->rollback();
            throw $e;
        }
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $trace = Trazabilidad::find($id);
    if (!$trace) throw new \Exception('No existe el registro de trazabilidad.');

    $oldLoteId = $trace->getIdLote();
    $oldCantidad = $trace->getCantidad();

    if ($oldLoteId !== $idLote) {
        $newLote = $batchModel->getById($idLote);
        if (!$newLote) throw new \Exception('El nuevo lote seleccionado no existe.');
        if ($cantidad > (int)$newLote['cantidad_actual']) {
            throw new \Exception("El nuevo lote solo tiene {$newLote['cantidad_actual']} ejemplares disponibles.");
        }

        $trace->beginTransaction();
        try {
            $trace->restoreBatchStock($oldLoteId, $oldCantidad);
            $trace->deductBatchStock($idLote, $cantidad);
            $trace->setIdLote($idLote)
                  ->setCantidad($cantidad)
                  ->setIdEstado($idEstado)
                  ->setFechaRegistro($fechaRegistro)
                  ->setObservacion($observacion);
            if (!$trace->save()) {
                throw new \Exception('Error al actualizar el registro de trazabilidad.');
            }
            $trace->commit();
        } catch (\Exception $e) {
            $trace->rollback();
            throw $e;
        }
    } elseif ($oldCantidad !== $cantidad) {
        $diff = $cantidad - $oldCantidad;

        if ($diff > 0) {
            $currentLote = $batchModel->getById($idLote);
            if ($diff > (int)$currentLote['cantidad_actual']) {
                throw new \Exception("El lote solo tiene {$currentLote['cantidad_actual']} ejemplares disponibles adicionales.");
            }
        }

        $trace->beginTransaction();
        try {
            if ($diff > 0) {
                $trace->deductBatchStock($idLote, $diff);
            } else {
                $trace->restoreBatchStock($idLote, abs($diff));
            }
            $trace->setCantidad($cantidad)
                  ->setIdEstado($idEstado)
                  ->setFechaRegistro($fechaRegistro)
                  ->setObservacion($observacion);
            if (!$trace->save()) {
                throw new \Exception('Error al actualizar el registro de trazabilidad.');
            }
            $trace->commit();
        } catch (\Exception $e) {
            $trace->rollback();
            throw $e;
        }
    } else {
        $trace->setIdEstado($idEstado)
              ->setFechaRegistro($fechaRegistro)
              ->setObservacion($observacion);
        if (!$trace->save()) {
            throw new \Exception('Error al actualizar el registro de trazabilidad.');
        }
    }

    jsonResponse(['success' => true, 'message' => 'Monitoreo actualizado correctamente.']);
}

function trazabilidad_handleUpdateEstado(): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $idEstado = (int)($_POST['id_estado'] ?? 0);
    if ($idEstado <= 0) throw new \Exception('Debe seleccionar un estado.');

    $trace = Trazabilidad::find($id);
    if (!$trace) throw new \Exception('No existe el registro de trazabilidad');

    $trace->setIdEstado($idEstado);
    if (!$trace->save()) {
        throw new \Exception('Error al actualizar el estado.');
    }

    jsonResponse(['success' => true, 'message' => 'Estado actualizado correctamente.']);
}

function trazabilidad_handleRestore(): void
{
    $batchModel = new Lote();
    $estadoVivoId = $batchModel->getIdEstadoVivo();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $trace = Trazabilidad::find($id);
    if (!$trace) throw new \Exception('No existe el registro de trazabilidad');

    $idLote = $trace->getIdLote();
    $cantidad = $trace->getCantidad();

    try {
        $trace->beginTransaction();

        $trace->setIdEstado($estadoVivoId);
        if (!$trace->save()) {
            throw new \Exception('Error al actualizar el estado.');
        }

        if (!$trace->delete($id)) {
            throw new \Exception('Error al desactivar el registro de trazabilidad.');
        }

        if ($idLote > 0 && $cantidad > 0) {
            $lote = $batchModel->getById($idLote);
            if ($lote) {
                $trace->restoreBatchStock($idLote, $cantidad);
            }
        }

        $trace->commit();

        jsonResponse(['success' => true, 'message' => 'Ejemplar restaurado correctamente. Stock devuelto al lote.']);
    } catch (\Exception $e) {
        $trace->rollback();
        throw $e;
    }
}

function trazabilidad_handleDelete(): void
{
    $batchModel = new Lote();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $trace = Trazabilidad::find($id);
    if (!$trace) throw new \Exception('No existe el registro de trazabilidad');

    $idLote = $trace->getIdLote();
    $cantidad = $trace->getCantidad();

    try {
        $trace->beginTransaction();

        if (!$trace->delete($id)) {
            throw new \Exception('Error al desactivar el registro de trazabilidad.');
        }

        if ($idLote > 0 && $cantidad > 0) {
            $lote = $batchModel->getById($idLote);
            if ($lote) {
                $trace->restoreBatchStock($idLote, $cantidad);
            }
        }

        $trace->commit();
    } catch (\Exception $e) {
        $trace->rollback();
        throw $e;
    }

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
    $includeId = isset($_GET['include_id']) && $_GET['include_id'] !== '' ? (int)$_GET['include_id'] : null;
    jsonResponse(['success' => true, 'batches' => $model->getAvailableBatches($includeId)]);
}
