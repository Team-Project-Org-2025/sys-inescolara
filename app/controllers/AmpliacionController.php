<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Ampliacion;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_exchanges'      => get_exchanges(),
                'GET_get_detail'         => get_detail(),
                'GET_get_lotes'          => get_lotes(),
                'GET_get_plantas'        => get_plantas(),
                'GET_get_ubicaciones'    => get_ubicaciones(),
                'GET_get_especies'       => get_especies(),
                'POST_add_ajax'          => add_ajax(),
                'POST_delete_ajax'       => delete_ajax(),
                default                  => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    header('Location: ' . BASE_URL . 'dashboard/ampliacion');
    exit();
}

function get_exchanges(): void { checkModuleAuth(); ampliacion_getExchangesAjax(); }
function get_detail(): void { checkModuleAuth(); ampliacion_getDetailAjax(); }
function get_lotes(): void { checkModuleAuth(); ampliacion_getLotesAjax(); }
function get_plantas(): void { checkModuleAuth(); ampliacion_getPlantasAjax(); }
function get_ubicaciones(): void { checkModuleAuth(); ampliacion_getUbicacionesAjax(); }
function get_especies(): void { checkModuleAuth(); ampliacion_getEspeciesAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('ampliacion:crear'); ampliacion_handleAdd(); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('ampliacion:eliminar'); ampliacion_handleDelete(); }

function ampliacion_handleAdd(): void
{
    $data = getRequestData();

    $idCliente = (int)($data['id_cliente'] ?? 0);
    $idTrabajador = (int)($data['id_trabajador_gestor'] ?? 0);
    if ($idTrabajador <= 0) throw new \Exception('El trabajador gestor es requerido.');

    $salidaItems = isset($data['salida_items']) ? json_decode($data['salida_items'], true) : [];
    $entradaItems = isset($data['entrada_items']) ? json_decode($data['entrada_items'], true) : [];

    if (!is_array($salidaItems)) $salidaItems = [];
    if (!is_array($entradaItems)) $entradaItems = [];

    if (empty($salidaItems) && empty($entradaItems)) {
        throw new \Exception('Debe agregar al menos un item de salida o entrada.');
    }

    foreach ($salidaItems as $item) {
        if ((int)($item['id_lote'] ?? 0) <= 0 || (int)($item['cantidad'] ?? 0) <= 0) {
            throw new \Exception('Complete correctamente todos los items de salida.');
        }
    }

    foreach ($entradaItems as $item) {
        $idPlanta = (int)($item['id_planta'] ?? 0);
        $idUbicacion = (int)($item['id_ubicacion'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);
        $hasNewPlantName = !empty(trim((string)($item['nueva_planta_nombre'] ?? '')));
        if ($idUbicacion <= 0 || $cantidad <= 0) {
            throw new \Exception('Complete correctamente todos los items de entrada.');
        }
        if ($idPlanta <= 0 && !$hasNewPlantName) {
            throw new \Exception('Seleccione una planta existente o agregue una nueva.');
        }
    }

    $model = new Ampliacion();
    $payload = [
        'id_cliente' => $idCliente,
        'id_trabajador_gestor' => $idTrabajador,
        'fecha_movimiento' => trim((string)($data['fecha_movimiento'] ?? '')),
        'observacion' => trim((string)($data['observacion'] ?? '')),
        'salida_items' => $salidaItems,
        'entrada_items' => $entradaItems,
    ];

    $newId = $model->registerExchange($payload);

    AuditLog::record('CREATE', 'movimiento_planta', $newId, null, [
        'tipo' => 'intercambio',
        'id_cliente' => $idCliente,
        'id_trabajador' => $idTrabajador,
        'salida_items' => $salidaItems,
        'entrada_items' => $entradaItems,
    ]);

    jsonResponse(['success' => true, 'message' => 'Ampliación de especies registrada correctamente', 'id' => $newId]);
}

function ampliacion_handleDelete(): void
{
    $model = new Ampliacion();
    $data = getRequestData();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la ampliación');

    $oldData = $model->getById($id);
    $model->delete($id);
    AuditLog::record('DEACTIVATE', 'movimiento_planta', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Ampliación desactivada correctamente']);
}

function ampliacion_getExchangesAjax(): void
{
    $model = new Ampliacion();
    $exchanges = $model->getAll();
    jsonResponse(['success' => true, 'ampliaciones' => $exchanges]);
}

function ampliacion_getDetailAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
        return;
    }
    $model = new Ampliacion();
    $exchange = $model->getById($id);
    if (!$exchange) {
        jsonResponse(['success' => false, 'message' => 'No se encontró el intercambio'], 404);
        return;
    }
    jsonResponse(['success' => true, 'ampliacion' => $exchange]);
}

function ampliacion_getLotesAjax(): void
{
    $model = new Ampliacion();
    $lotes = $model->getAvailableLots();
    jsonResponse(['success' => true, 'lotes' => $lotes]);
}

function ampliacion_getPlantasAjax(): void
{
    $model = new Ampliacion();
    $plantas = $model->getPlants();
    jsonResponse(['success' => true, 'plantas' => $plantas]);
}

function ampliacion_getUbicacionesAjax(): void
{
    $model = new Ampliacion();
    $ubicaciones = $model->getLocations();
    jsonResponse(['success' => true, 'ubicaciones' => $ubicaciones]);
}

function ampliacion_getEspeciesAjax(): void
{
    $model = new Ampliacion();
    $especies = $model->getSpecies();
    jsonResponse(['success' => true, 'especies' => $especies]);
}
