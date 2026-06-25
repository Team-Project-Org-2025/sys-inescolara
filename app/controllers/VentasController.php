<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Venta;
use SysInescolara\models\Cliente;
use SysInescolara\models\Empleado;
use SysInescolara\helpers\PdfHelper;

function index(): void
{
    checkModuleAuth();
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'comprobante') {
        ventas_comprobanteAjax();
        return;
    }

    if (isAjaxRequest() && $accion !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $accion) {
                'GET_listar'           => listar(),
                'POST_guardar'         => guardar(),
                'GET_detalles'         => detalles(),
                'POST_cancelar'        => cancelar(),
                'GET_buscar_lotes'     => buscar_lotes(),
                'GET_precio_lote'      => precio_lote(),
                'GET_buscar_clientes'  => buscar_clientes(),
                'GET_trabajadores'     => trabajadores(),
                default                => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $modeloCliente = new Cliente();
    $clientes = $modeloCliente->getAll();
    $modeloTrabajador = new Empleado();
    $trabajadores = $modeloTrabajador->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/ventas.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ventas no encontrada.';
        return;
    }
    require $view;
}

function listar(): void { checkModuleAuth(); ventas_listarAjax(); }
function guardar(): void { checkModuleAuth(); checkPermisoOrFail('ventas:crear'); ventas_manejarGuardar(); }
function cancelar(): void { checkModuleAuth(); checkPermisoOrFail('ventas:eliminar'); ventas_manejarCancelar(); }
function detalles(): void { checkModuleAuth(); ventas_obtenerDetallesAjax(); }
function buscar_lotes(): void { checkModuleAuth(); ventas_buscarLotesAjax(); }
function buscar_clientes(): void { checkModuleAuth(); ventas_buscarClientesAjax(); }
function precio_lote(): void { checkModuleAuth(); ventas_precioLoteAjax(); }
function trabajadores(): void { checkModuleAuth(); ventas_trabajadoresAjax(); }
function comprobante(): void { checkModuleAuth(); checkPermisoOrFail('ventas:ver'); ventas_comprobanteAjax(); }

function ventas_listarAjax(): void
{
    $modelo = new Venta();
    $ventas = $modelo->getAll();

    foreach ($ventas as &$v) {
        $subtotalConIva = (float)($v['monto_subtotal'] ?? 0);
        $montoSinIva = $subtotalConIva / 1.16;
        $montoIva = $montoSinIva * 0.16;
        $montoTotal = $montoSinIva + $montoIva;
        $v['monto_sin_iva'] = round($montoSinIva, 2);
        $v['monto_iva'] = round($montoIva, 2);
        $v['monto_total'] = round($montoTotal, 2);
    }
    unset($v);

    jsonResponse(['success' => true, 'ventas' => $ventas, 'total' => count($ventas)]);
}

function ventas_obtenerDetallesAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $modelo = new Venta();
    $venta = $modelo->obtenerVentaConDetalles($id);
    if (!$venta) throw new \Exception('Venta no encontrada.');

    jsonResponse(['success' => true, 'venta' => $venta]);
}

function ventas_buscarLotesAjax(): void
{
    $query = trim((string)($_GET['q'] ?? ''));
    if (strlen($query) < 2) {
        jsonResponse(['success' => true, 'lotes' => []]);
        return;
    }

    $modelo = new Venta();
    $lotes = $modelo->obtenerLotesDisponibles($query);
    jsonResponse(['success' => true, 'lotes' => $lotes]);
}

function ventas_precioLoteAjax(): void
{
    $idLote = (int)($_GET['id_lote'] ?? 0);
    if ($idLote <= 0) throw new \Exception('ID de lote inválido');

    $modelo = new Venta();
    $lotes = $modelo->obtenerLotesDisponibles('');
    $precio = 0;
    foreach ($lotes as $l) {
        if ((int)$l['id_lote'] === $idLote) {
            $precio = (float)($l['precio_unitario'] ?? 0);
            break;
        }
    }

    jsonResponse(['success' => true, 'precio' => $precio]);
}

function ventas_buscarClientesAjax(): void
{
    $query = trim((string)($_GET['q'] ?? ''));
    if (strlen($query) < 2) {
        jsonResponse(['success' => true, 'clientes' => []]);
        return;
    }

    $modelo = new Venta();
    $clientes = $modelo->buscarClientes($query);
    jsonResponse(['success' => true, 'clientes' => $clientes]);
}

function ventas_trabajadoresAjax(): void
{
    $modelo = new Venta();
    $trabajadores = $modelo->obtenerTrabajadoresActivos();
    jsonResponse(['success' => true, 'trabajadores' => $trabajadores]);
}

function ventas_manejarGuardar(): void
{
    $modelo = new Venta();

    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    if ($idCliente <= 0) throw new \Exception('El cliente es requerido.');

    $idTrabajador = (int)($_POST['id_trabajador'] ?? 0);
    if ($idTrabajador <= 0) throw new \Exception('El vendedor es requerido.');

    $tipoVenta = trim((string)($_POST['tipo_venta'] ?? ''));
    if (!in_array($tipoVenta, ['contado', 'credito'], true)) {
        throw new \Exception('El tipo de venta debe ser contado o crédito.');
    }

    $fechaVenta = trim((string)($_POST['fecha_venta'] ?? ''));
    if ($fechaVenta === '') {
        $fechaVenta = date('Y-m-d H:i:s');
    }

    $observaciones = trim((string)($_POST['observaciones'] ?? ''));
    if ($observaciones === '') $observaciones = null;

    $productosRaw = $_POST['productos'] ?? '[]';
    $productos = is_array($productosRaw) ? $productosRaw : json_decode($productosRaw, true);
    if (!is_array($productos) || empty($productos)) {
        throw new \Exception('Debe agregar al menos un producto.');
    }

    foreach ($productos as $item) {
        if (empty($item['id_lote']) || (int)$item['id_lote'] <= 0) {
            throw new \Exception('Cada producto debe tener un lote seleccionado.');
        }
        if (empty($item['cantidad']) || (int)$item['cantidad'] <= 0) {
            throw new \Exception('La cantidad debe ser mayor a cero en todos los productos.');
        }
        if (!isset($item['precio_unitario']) || (float)$item['precio_unitario'] < 0) {
            throw new \Exception('El precio unitario es requerido en todos los productos.');
        }
    }

    $pagosRaw = $_POST['pagos'] ?? '[]';
    $pagos = is_array($pagosRaw) ? $pagosRaw : json_decode($pagosRaw, true);
    if (!is_array($pagos)) $pagos = [];

    foreach ($pagos as &$pago) {
        if (empty($pago['metodo']) || !in_array($pago['metodo'], ['efectivo', 'transferencia', 'pago_movil', 'punto'], true)) {
            throw new \Exception('Método de pago inválido.');
        }
        if (!isset($pago['monto']) || (float)$pago['monto'] <= 0) {
            throw new \Exception('El monto de pago debe ser mayor a cero.');
        }
        if (!in_array($pago['metodo'], ['efectivo', 'punto'], true) && !empty($pago['referencia'])) {
            $pago['referencia'] = preg_replace('/\D/', '', $pago['referencia']);
            if (strlen($pago['referencia']) > 6) {
                throw new \Exception('La referencia debe tener máximo 6 dígitos.');
            }
        } else {
            $pago['referencia'] = null;
        }
    }
    unset($pago);

    $datos = [
        'id_cliente'    => $idCliente,
        'id_trabajador' => $idTrabajador,
        'tipo_venta'    => $tipoVenta,
        'fecha_venta'   => $fechaVenta,
        'observaciones' => $observaciones,
        'productos'     => $productos,
        'pagos'         => $pagos,
    ];

    $nuevoId = $modelo->agregar($datos);
    if ($nuevoId <= 0) throw new \Exception('Error al guardar la venta.');

    $venta = $modelo->getById($nuevoId);

    jsonResponse([
        'success'    => true,
        'message'    => 'Venta registrada correctamente',
        'id'         => $nuevoId,
        'referencia' => $venta['referencia'] ?? '',
    ]);
}

function ventas_manejarCancelar(): void
{
    $modelo = new Venta();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');
    if (!$modelo->exists($id)) {
        throw new \Exception('No existe la venta solicitada.');
    }

    $datosViejos = $modelo->getById($id);

    if ($datosViejos['estado'] === 'cancelada') {
        throw new \Exception('La venta ya se encuentra cancelada.');
    }

    $ok = $modelo->cancelar($id);
    if (!$ok) throw new \Exception('Error al cancelar la venta.');

    jsonResponse(['success' => true, 'message' => 'Venta cancelada correctamente', 'id' => $id]);
}

function ventas_comprobanteAjax(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo 'ID inválido';
        exit();
    }

    $modelo = new Venta();
    $venta = $modelo->obtenerVentaConDetalles($id);
    if (!$venta) {
        http_response_code(404);
        echo 'Venta no encontrada';
        exit();
    }

    $subtotalConIva = array_sum(array_column($venta['detalles'], 'sub_total'));
    $montoSinIva = $subtotalConIva / 1.16;
    $montoIva = $montoSinIva * 0.16;
    $montoTotal = $montoSinIva + $montoIva;

    ob_start();
    require ROOT_PATH . 'app/views/dashboard/ventas_comprobante_pdf.php';
    $html = ob_get_clean();

    try {
        $pdf = new PdfHelper();
        $output = $pdf->fromHtml($html);
        $filename = 'comprobante-' . $venta['referencia'] . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit();
    } catch (\Exception $e) {
        error_log('Error al generar PDF: ' . $e->getMessage());
        http_response_code(500);
        echo 'Error al generar el comprobante PDF.';
        exit();
    }
}
