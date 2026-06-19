<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\CuentaCobrar;
use SysInescolara\models\Employee;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_obtener_lista'          => cc_obtenerListaAjax(),
                'GET_obtener_estadisticas'   => cc_obtenerEstadisticasAjax(),
                'GET_obtener_detalle'        => cc_obtenerDetalleAjax(),
                'GET_obtener_pagos'          => cc_obtenerPagosAjax(),
                'GET_obtener_clientes'       => cc_obtenerClientesAjax(),
                'POST_registrar_pago'        => cc_registrarPagoAjax(),
                default                      => jsonResponse(['success' => false, 'message' => 'Accion AJAX invalida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $employeeModel = new Employee();
    $employees = $employeeModel->getAll();

    $canPay = \SysInescolara\helpers\Auth::hasPermiso('CUENTAS_COBRAR_PAY');

    $view = ROOT_PATH . 'app/views/dashboard/cuentas-cobrar.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de cuentas por cobrar no encontrada.';
        return;
    }
    require $view;
}

function obtener_lista(): void { checkModuleAuth(); cc_obtenerListaAjax(); }
function obtener_estadisticas(): void { checkModuleAuth(); cc_obtenerEstadisticasAjax(); }
function obtener_detalle(): void { checkModuleAuth(); cc_obtenerDetalleAjax(); }
function obtener_pagos(): void { checkModuleAuth(); cc_obtenerPagosAjax(); }
function obtener_clientes(): void { checkModuleAuth(); cc_obtenerClientesAjax(); }
function registrar_pago(): void { checkModuleAuth(); checkPermisoOrFail('CUENTAS_COBRAR_PAY'); cc_registrarPagoAjax(); }

function cc_obtenerListaAjax(): void
{
    $draw = (int)($_GET['draw'] ?? 1);
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    $search = trim((string)($_GET['search']['value'] ?? ''));
    $estadoFilter = trim((string)($_GET['estado'] ?? ''));

    $model = new CuentaCobrar();
    $result = $model->obtenerTodos($start, $length, $search, $estadoFilter);

    jsonResponse([
        'draw' => $draw,
        'recordsTotal' => $result['total'],
        'recordsFiltered' => $result['filtered'],
        'data' => $result['data'],
    ]);
}

function cc_obtenerEstadisticasAjax(): void
{
    $model = new CuentaCobrar();
    $stats = $model->obtenerEstadisticas();
    jsonResponse(['success' => true, 'data' => $stats]);
}

function cc_obtenerDetalleAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de venta invalido'], 400);
        return;
    }

    $model = new CuentaCobrar();
    $venta = $model->obtenerPorId($id);
    if (!$venta) {
        jsonResponse(['success' => false, 'message' => 'Venta no encontrada'], 404);
        return;
    }

    jsonResponse(['success' => true, 'data' => $venta]);
}

function cc_obtenerPagosAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de venta invalido'], 400);
        return;
    }

    $model = new CuentaCobrar();
    $pagos = $model->obtenerPagos($id);
    jsonResponse(['success' => true, 'data' => $pagos]);
}

function cc_obtenerClientesAjax(): void
{
    $model = new CuentaCobrar();
    $clients = $model->obtenerClientes();
    jsonResponse(['success' => true, 'data' => $clients]);
}

function cc_registrarPagoAjax(): void
{
    $idVenta = (int)($_POST['id_venta'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo = trim((string)($_POST['metodo'] ?? ''));
    $referencia = trim((string)($_POST['referencia'] ?? ''));
    $fechaPago = trim((string)($_POST['fecha_pago'] ?? ''));
    $banco = trim((string)($_POST['banco'] ?? ''));
    $idTrabajador = (int)($_POST['id_trabajador'] ?? 0);
    $observaciones = trim((string)($_POST['observaciones'] ?? ''));

    if ($idVenta <= 0) throw new \Exception('ID de venta invalido.');
    if ($monto <= 0) throw new \Exception('El monto debe ser mayor a cero.');
    if ($idTrabajador <= 0) throw new \Exception('Debe seleccionar el trabajador que cobro.');
    if (!in_array($metodo, ['efectivo', 'transferencia', 'punto', 'pago_movil', 'otro'], true)) {
        throw new \Exception('Metodo de pago invalido.');
    }
    if ($fechaPago === '') throw new \Exception('La fecha de pago es requerida.');
    $d = \DateTime::createFromFormat('Y-m-d', $fechaPago);
    if (!$d || $d->format('Y-m-d') !== $fechaPago) {
        throw new \InvalidArgumentException('Formato de fecha de pago inválido.');
    }
    $todayStr = (new \DateTime('today'))->format('Y-m-d');
    if ($fechaPago > $todayStr) {
        throw new \InvalidArgumentException('La fecha de pago no puede ser posterior al día de hoy.');
    }

    if (in_array($metodo, ['transferencia', 'pago_movil'], true)) {
        if ($referencia === '') throw new \Exception('La referencia es requerida para transferencias y pago movil.');
        if (!preg_match('/^\d{6}$/', $referencia)) throw new \Exception('La referencia debe tener exactamente 6 digitos numericos.');
        if ($banco === '') throw new \Exception('El banco es requerido para este metodo de pago.');
    }

    $model = new CuentaCobrar();

    try {
        $model->iniciarTransaccion();

        $newId = $model->registrarPago(
            $idVenta, $monto, $metodo,
            $referencia !== '' ? $referencia : null,
            $fechaPago,
            $banco !== '' ? $banco : null,
            $idTrabajador,
            $observaciones !== '' ? $observaciones : null
        );

        $model->confirmarTransaccion();

        AuditLog::record('CREATE', 'pago_venta', $newId, null, [
            'id_venta' => $idVenta,
            'monto' => $monto,
            'metodo' => $metodo,
            'fecha_pago' => $fechaPago,
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Pago registrado correctamente',
            'id' => $newId,
        ]);
    } catch (\Throwable $e) {
        $model->revertirTransaccion();
        throw $e;
    }
}
