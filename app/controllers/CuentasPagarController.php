<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\CuentaPagar;
use SysInescolara\models\AuditLog;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_obtener_cuentas'      => cuentas_obtenerCuentasAjax(),
                'GET_obtener_detalle'      => cuentas_obtenerDetalleAjax(),
                'GET_obtener_pagos'        => cuentas_obtenerPagosAjax(),
                'POST_registrar_pago'      => cuentas_registrarPago(),
                'POST_anular_pago'         => cuentas_anularPago(),
                default                    => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $vista = ROOT_PATH . 'app/views/dashboard/cuentas-pagar.php';
    if (!is_file($vista)) {
        http_response_code(500);
        echo 'Vista de cuentas por pagar no encontrada.';
        return;
    }
    require $vista;
}

function obtener_cuentas(): void { checkModuleAuth(); cuentas_obtenerCuentasAjax(); }
function obtener_detalle(): void { checkModuleAuth(); cuentas_obtenerDetalleAjax(); }
function obtener_pagos(): void { checkModuleAuth(); cuentas_obtenerPagosAjax(); }
function registrar_pago(): void { checkModuleAuth(); checkPermisoOrFail('CUENTAS_PAGAR'); cuentas_registrarPago(); }
function anular_pago(): void { checkModuleAuth(); checkPermisoOrFail('CUENTAS_DELETE'); cuentas_anularPago(); }

function cuentas_obtenerCuentasAjax(): void
{
    $modelo = new CuentaPagar();
    $cuentas = $modelo->obtenerTodas();
    jsonResponse(['success' => true, 'cuentas' => $cuentas, 'count' => count($cuentas)]);
}

function cuentas_obtenerDetalleAjax(): void
{
    $id = (int)($_GET['id_cuenta'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de cuenta inválido.'], 400);
        return;
    }
    $modelo = new CuentaPagar();
    $cuenta = $modelo->obtenerPorId($id);
    $pagos = $modelo->obtenerPagos($id);
    jsonResponse(['success' => true, 'cuenta' => $cuenta, 'pagos' => $pagos]);
}

function cuentas_obtenerPagosAjax(): void
{
    $idCuenta = (int)($_GET['id_cuenta'] ?? 0);
    if ($idCuenta <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de cuenta inválido.'], 400);
        return;
    }
    $modelo = new CuentaPagar();
    $pagos = $modelo->obtenerPagos($idCuenta);
    jsonResponse(['success' => true, 'pagos' => $pagos]);
}

function cuentas_registrarPago(): void
{
    $idCuentaPagar = (int)($_POST['id'] ?? 0);
    if ($idCuentaPagar <= 0) throw new \Exception('ID de cuenta inválido.');

    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    if ($monto <= 0) throw new \Exception('El monto debe ser mayor a cero.');

    $fechaPago = trim((string)($_POST['fecha_pago'] ?? ''));
    if ($fechaPago === '') $fechaPago = date('Y-m-d');

    $tipoPago = trim((string)($_POST['tipo_pago'] ?? ''));
    if ($tipoPago === '') $tipoPago = 'Efectivo';

    $referencia = trim((string)($_POST['referencia'] ?? ''));
    if ($referencia === '') $referencia = null;

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    $modelo = new CuentaPagar();

    $cuenta = $modelo->obtenerPorId($idCuentaPagar);
    if (!$cuenta) throw new \Exception('No existe la cuenta por pagar.');
    if ($monto > $cuenta['saldo_pendiente']) throw new \Exception('El monto supera el saldo pendiente.');

    $modelo->registrarPago($idCuentaPagar, $monto, $fechaPago, $tipoPago, $referencia, $observacion);

    $nuevoId = $modelo->obtenerUltimoId();

    AuditLog::record('CREATE', 'pago_compra', $nuevoId, null, [
        'id_cuenta_pagar' => $idCuentaPagar, 'monto' => $monto, 'tipo_pago' => $tipoPago,
    ]);

    jsonResponse(['success' => true, 'message' => 'Pago registrado correctamente.']);
}

function cuentas_anularPago(): void
{
    $idPago = (int)($_POST['id_pago'] ?? 0);
    if ($idPago <= 0) throw new \Exception('ID de pago inválido.');

    $modelo = new CuentaPagar();
    $modelo->anularPago($idPago);

    AuditLog::record('DEACTIVATE', 'pago_compra', $idPago, null, null);
    jsonResponse(['success' => true, 'message' => 'Pago anulado correctamente.']);
}
