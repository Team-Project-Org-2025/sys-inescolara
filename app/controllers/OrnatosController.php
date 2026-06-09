<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Ornato;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $accion = $_GET['accion'] ?? '';
    if (isAjaxRequest() && $accion !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $accion) {
                'GET_listar'      => ornatos_listarAjax(),
                'POST_guardar'    => ornatos_manejarGuardar('crear'),
                'POST_actualizar' => ornatos_manejarGuardar('editar'),
                'GET_detalles'    => ornatos_obtenerDetallesAjax(),
                'POST_eliminar'   => ornatos_manejarEliminar(),
                default           => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    jsonResponse(['success' => false, 'message' => 'Solicitud inválida'], 400);
}

function listar(): void { checkModuleAuth(); ornatos_listarAjax(); }
function guardar(): void { checkModuleAuth(); checkPermisoOrFail('ORNATOS_CREATE'); ornatos_manejarGuardar('crear'); }
function actualizar(): void { checkModuleAuth(); checkPermisoOrFail('ORNATOS_EDIT'); ornatos_manejarGuardar('editar'); }
function eliminar(): void { checkModuleAuth(); checkPermisoOrFail('ORNATOS_DELETE'); ornatos_manejarEliminar(); }
function detalles(): void { checkModuleAuth(); ornatos_obtenerDetallesAjax(); }

function ornatos_listarAjax(): void
{
    $modelo = new Ornato();
    $ornatos = $modelo->getAll();
    jsonResponse(['success' => true, 'ornatos' => $ornatos, 'total' => count($ornatos)]);
}

function ornatos_obtenerDetallesAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $modelo = new Ornato();
    $detalles = $modelo->obtenerDetalles($id);
    jsonResponse(['success' => true, 'detalles' => $detalles]);
}

function ornatos_manejarGuardar(string $modo): void
{
    $modelo = new Ornato();

    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    if ($idCliente <= 0) throw new \Exception('El cliente es requerido.');

    $tipoOrnato = trim((string)($_POST['tipo_ornato'] ?? ''));
    if (!in_array($tipoOrnato, ['Venta', 'Donacion'], true)) {
        throw new \Exception('El tipo de ornato debe ser Venta o Donación.');
    }

    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    if ($descripcion === '') $descripcion = null;

    $ubicacion = trim((string)($_POST['ubicacion'] ?? ''));
    if ($ubicacion === '') $ubicacion = null;

    $fecha = trim((string)($_POST['fecha'] ?? ''));
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new \Exception('La fecha es requerida y debe tener formato válido.');
    }

    $montoTotal = isset($_POST['monto_total']) ? floatval($_POST['monto_total']) : 0.00;
    if ($montoTotal < 0) throw new \Exception('El monto total no puede ser negativo.');

    $itemsRaw = $_POST['items'] ?? '[]';
    $items = is_array($itemsRaw) ? $itemsRaw : json_decode($itemsRaw, true);
    if (!is_array($items)) $items = [];

    foreach ($items as $item) {
        if (empty($item['id_lote']) || (int)$item['id_lote'] <= 0) {
            throw new \Exception('Cada detalle debe tener un lote seleccionado.');
        }
        if (empty($item['cantidad']) || (int)$item['cantidad'] <= 0) {
            throw new \Exception('La cantidad debe ser mayor a cero en todos los detalles.');
        }
    }

    $datos = [
        'id_cliente'  => $idCliente,
        'tipo_ornato' => $tipoOrnato,
        'descripcion' => $descripcion,
        'ubicacion'   => $ubicacion,
        'fecha'       => $fecha,
        'monto_total' => $montoTotal,
    ];

    if ($modo === 'crear') {
        $ok = $modelo->agregar($datos);
        if (!$ok) throw new \Exception('Error al guardar el ornato.');

        $nuevoId = $modelo->obtenerUltimoId() ?? 0;

        if (!empty($items)) {
            $okDetalles = $modelo->agregarDetalles($nuevoId, $items);
            if (!$okDetalles) {
                throw new \Exception('Error al guardar los detalles del ornato.');
            }
        }

        AuditLog::record('CREATE', 'ornatos', $nuevoId, null, $datos);
        jsonResponse(['success' => true, 'message' => 'Ornato registrado correctamente', 'id' => $nuevoId]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $datosViejos = $modelo->getById($id);
    $ok = $modelo->actualizar($id, $datos);
    if (!$ok) throw new \Exception('Error al actualizar el ornato.');

    $okDetalles = $modelo->actualizarDetalles($id, $items);
    if (!$okDetalles) {
        throw new \Exception('Error al actualizar los detalles del ornato.');
    }

    AuditLog::record('UPDATE', 'ornatos', $id, $datosViejos, $datos);
    jsonResponse(['success' => true, 'message' => 'Ornato actualizado correctamente', 'id' => $id]);
}

function ornatos_manejarEliminar(): void
{
    $modelo = new Ornato();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$modelo->exists($id)) {
        throw new \Exception('No existe el ornato solicitado.');
    }

    $datosViejos = $modelo->getById($id);
    $modelo->delete($id);
    AuditLog::record('DEACTIVATE', 'ornatos', $id, $datosViejos, null);
    jsonResponse(['success' => true, 'message' => 'Ornato eliminado correctamente', 'id' => $id]);
}
