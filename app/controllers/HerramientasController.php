<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\AuditLog;
use SysInescolara\models\Herramienta;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_tools'          => get_tools(),
                'POST_add_ajax'          => add_ajax(),
                'POST_edit_ajax'         => edit_ajax(),
                'POST_delete_ajax'       => delete_ajax(),
                'POST_record_usage_ajax' => record_usage_ajax(),
                'GET_get_usages'         => get_usages(),
                default                  => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/herramientas.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de herramientas no encontrada.';
        return;
    }
    require $view;
}

function get_tools(): void { checkModuleAuth(); tools_getToolsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('herramientas:crear'); tools_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('herramientas:editar'); tools_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('herramientas:eliminar'); tools_handleDelete(); }
function record_usage_ajax(): void { checkModuleAuth(); checkPermisoOrFail('herramientas:crear'); tools_recordUsageAjax(); }
function get_usages(): void { checkModuleAuth(); tools_getUsagesAjax(); }

function tools_handleAddEdit(string $mode): void
{
    $model = new Herramienta();
    $nombre = trim((string)($_POST['nombre_herramienta'] ?? ''));
    if ($nombre === '') {
        throw new \Exception('El nombre de la herramienta es requerido.');
    }

    $tipo = trim((string)($_POST['tipo'] ?? ''));
    if ($tipo === '') $tipo = null;

    $estado = trim((string)($_POST['estado'] ?? ''));
    if ($estado === '') $estado = 'disponible';

    $fechaAdquisicion = trim((string)($_POST['fecha_adquisicion'] ?? ''));
    if ($fechaAdquisicion === '') {
        $fechaAdquisicion = null;
    } else {
        $d = \DateTime::createFromFormat('Y-m-d', $fechaAdquisicion);
        if (!$d || $d->format('Y-m-d') !== $fechaAdquisicion) {
            throw new \InvalidArgumentException('Formato de fecha de adquisición inválido.');
        }
        $todayStr = (new \DateTime('today'))->format('Y-m-d');
        if ($fechaAdquisicion > $todayStr) {
            throw new \InvalidArgumentException('La fecha de adquisición no puede ser posterior al día de hoy.');
        }
    }

    $fechaUltimoMantenimiento = trim((string)($_POST['fecha_ultimo_mantenimiento'] ?? ''));
    if ($fechaUltimoMantenimiento === '') {
        $fechaUltimoMantenimiento = null;
    } else {
        $d = \DateTime::createFromFormat('Y-m-d', $fechaUltimoMantenimiento);
        if (!$d || $d->format('Y-m-d') !== $fechaUltimoMantenimiento) {
            throw new \InvalidArgumentException('Formato de fecha de último mantenimiento inválido.');
        }
        $todayStr = (new \DateTime('today'))->format('Y-m-d');
        if ($fechaUltimoMantenimiento > $todayStr) {
            throw new \InvalidArgumentException('La fecha de último mantenimiento no puede ser posterior al día de hoy.');
        }
    }

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

    if ($mode === 'add') {
        $model->add($nombre, $cantidad, $tipo, $estado, $fechaAdquisicion, $fechaUltimoMantenimiento, $observacion);
        $newId = $model->getLastInsertId() ?? 0;
        jsonResponse([
            'success' => true, 'message' => 'Herramienta agregada correctamente',
            'herramienta' => ['id' => $newId, 'nombre_herramienta' => $nombre, 'cantidad' => $cantidad, 'tipo' => $tipo, 'estado' => $estado],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $model->update($id, $nombre, $cantidad, $tipo, $estado, $fechaAdquisicion, $fechaUltimoMantenimiento, $observacion);
    jsonResponse([
        'success' => true, 'message' => 'Herramienta actualizada correctamente',
        'herramienta' => ['id' => $id],
    ]);
}

function tools_handleDelete(): void
{
    $model = new Herramienta();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$model->exists($id)) throw new \Exception('No existe la herramienta');

    $model->delete($id);
    jsonResponse(['success' => true, 'message' => 'Herramienta desactivada correctamente', 'herramientaId' => $id]);
}

function tools_getToolsAjax(): void
{
    $model = new Herramienta();
    $tools = $model->getAll();
    jsonResponse(['success' => true, 'tools' => $tools, 'count' => count($tools)]);
}

// -- Uso de herramientas (transaccional) --

function tools_recordUsageAjax(): void
{
    $data = getRequestData();

    $usageData = [
        'id_asignacion'            => (int)($data['id_asignacion'] ?? 0),
        'id_herramienta'           => (int)($data['id_herramienta'] ?? 0),
        'fecha_uso'                => $data['fecha_uso'] ?? date('Y-m-d'),
        'observacion'              => trim((string)($data['observacion'] ?? '')),
        'estado_herramienta_post_uso' => $data['estado_herramienta_post_uso'] ?? 'ok',
    ];

    if (!$usageData['id_asignacion'] || !$usageData['id_herramienta']) {
        jsonResponse(['success' => false, 'message' => 'Se requieren asignación y herramienta.'], 400);
    }

    $model = new Herramienta();
    $usoId = $model->recordUsageWithStateUpdate($usageData);

    jsonResponse(['success' => true, 'message' => 'Uso de herramienta registrado', 'id_uso' => $usoId]);
}

function tools_getUsagesAjax(): void
{
    $herramientaId = (int)($_GET['id_herramienta'] ?? 0);
    if ($herramientaId <= 0) jsonResponse(['success' => false, 'message' => 'ID de herramienta inválido'], 400);

    $model = new Herramienta();
    $usages = $model->getUsages($herramientaId);
    jsonResponse(['success' => true, 'usages' => $usages, 'count' => count($usages)]);
}
