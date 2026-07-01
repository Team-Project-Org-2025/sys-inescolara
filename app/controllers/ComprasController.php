<?php

require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Purchase;
use SysInescolara\models\Proveedor;
use SysInescolara\models\Insumo;
use SysInescolara\models\Herramienta;
use SysInescolara\models\Ubicacion;
use SysInescolara\models\Planta;
use SysInescolara\models\UnidadMedida;
function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_obtener_compras'        => compras_obtenerComprasAjax(),
                'GET_obtener_detalles'       => compras_obtenerDetallesAjax(),
                'POST_agregar_ajax'          => compras_manejarAgregarEditar('add'),
                'POST_editar_ajax'           => compras_manejarAgregarEditar('edit'),
                'POST_eliminar_ajax'         => compras_manejarEliminar(),
                'POST_recibir_ajax'           => compras_manejarRecibir(),
                'POST_cancelar_ajax'         => compras_manejarCancelar(),
                'POST_agregar_planta_rapido'      => compras_agregarPlantaRapido(),
                'POST_agregar_insumo_rapido'       => compras_agregarInsumoRapido(),
                'POST_agregar_herramienta_rapido'  => compras_agregarHerramientaRapido(),
                default                            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }

    $modeloProveedor = new Proveedor();
    $proveedores = $modeloProveedor->getAll();
    $modeloInsumo = new Insumo();
    $insumos = $modeloInsumo->getAll();
    $modeloUbicacion = new Ubicacion();
    $ubicaciones = $modeloUbicacion->getAll();
    $modeloUnidad = new UnidadMedida();
    $unidadesMedida = $modeloUnidad->getAll();

    $vista = ROOT_PATH . 'app/views/dashboard/compras.php';
    if (!is_file($vista)) {
        http_response_code(500);
        echo 'Vista de compras no encontrada.';
        return;
    }
    require $vista;
}

function obtener_compras(): void { checkModuleAuth(); compras_obtenerComprasAjax(); }
function obtener_detalles(): void { checkModuleAuth(); compras_obtenerDetallesAjax(); }
function agregar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('compras:crear'); compras_manejarAgregarEditar('add'); }
function editar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('compras:editar'); compras_manejarAgregarEditar('edit'); }
function eliminar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('compras:eliminar'); compras_manejarEliminar(); }
function recibir_ajax(): void { checkModuleAuth(); checkPermisoOrFail('compras:editar'); compras_manejarRecibir(); }
function cancelar_ajax(): void { checkModuleAuth(); checkPermisoOrFail('compras:editar'); compras_manejarCancelar(); }
function agregar_planta_rapido(): void { checkModuleAuth(); compras_agregarPlantaRapido(); }
function agregar_insumo_rapido(): void { checkModuleAuth(); compras_agregarInsumoRapido(); }
function agregar_herramienta_rapido(): void { checkModuleAuth(); compras_agregarHerramientaRapido(); }

function compras_manejarAgregarEditar(string $modo): void
{
    $modelo = new Purchase();

    $idProveedor = (int)($_POST['id_proveedor'] ?? 0);
    if ($idProveedor <= 0) throw new \Exception('El proveedor es requerido.');

    $fechaCompra = trim((string)($_POST['fecha_compra'] ?? ''));
    if ($fechaCompra === '') throw new \Exception('La fecha es requerida.');
    $fechaCompraObj = \DateTime::createFromFormat('Y-m-d', $fechaCompra);
    if (!$fechaCompraObj || $fechaCompraObj->format('Y-m-d') !== $fechaCompra) {
        throw new \Exception('Formato de fecha inválido (YYYY-MM-DD).');
    }
    $todayStr = date('Y-m-d');
    if ($fechaCompra > $todayStr) throw new \Exception('La fecha no puede ser posterior al día de hoy.');

    $tipoComprobante = trim((string)($_POST['tipo_comprobante'] ?? ''));
    if ($tipoComprobante === '') $tipoComprobante = 'Factura';

    $numeroComprobante = trim((string)($_POST['numero_comprobante'] ?? ''));
    if ($numeroComprobante === '') $numeroComprobante = null;

    $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
    $iva = isset($_POST['iva']) ? floatval($_POST['iva']) : 0;
    $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

    if ($subtotal < 0 || $iva < 0 || $total <= 0) throw new \Exception('El total debe ser mayor a cero. Verifique los costos de los items.');

    $observacion = trim((string)($_POST['observacion'] ?? ''));
    if ($observacion === '') $observacion = null;

    $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    if (!is_array($items) || empty($items)) throw new \Exception('Debe agregar al menos un item.');

    $modelo->iniciarTransaccion();

    try {
        if ($modo === 'add') {
            $modelo->agregar($idProveedor, $fechaCompra, $tipoComprobante, $numeroComprobante, $subtotal, $iva, $total, $observacion);
            $nuevoId = $modelo->obtenerUltimoId() ?? 0;

            foreach ($items as $item) {
                $tipoItem = $item['tipo_item'] ?? '';
                $idItem = (int)($item['id_item'] ?? 0);
                $cantidad = (float)($item['cantidad'] ?? 0);
                $costoUnitario = (float)($item['costo_unitario'] ?? 0);
                $subtotalItem = $cantidad * $costoUnitario;
                $categoriaLote = $tipoItem === 'planta' ? ($item['categoria_lote'] ?? null) : null;
                $idUbicacionItem = $tipoItem === 'planta' ? (!empty($item['id_ubicacion']) ? (int)$item['id_ubicacion'] : null) : null;
                $modelo->agregarDetalle($nuevoId, $tipoItem, $idItem, $cantidad, $costoUnitario, $subtotalItem, $categoriaLote, $idUbicacionItem);
            }

            if (!$modelo->crearCuentaPagar($nuevoId, $total)) {
                throw new \Exception('Error al crear la cuenta por pagar.');
            }

            $modelo->confirmarTransaccion();

            jsonResponse(['success' => true, 'message' => 'Compra registrada correctamente.', 'id' => $nuevoId]);
            return;
        }

        // --- Modo editar ---
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new \Exception('ID inválido');

        $modelo->actualizar($id, $idProveedor, $fechaCompra, $tipoComprobante, $numeroComprobante, $subtotal, $iva, $total, $observacion);

        $modelo->eliminarDetalles($id);
        foreach ($items as $item) {
            $tipoItem = $item['tipo_item'] ?? '';
            $idItem = (int)($item['id_item'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);
            $costoUnitario = (float)($item['costo_unitario'] ?? 0);
            $subtotalItem = $cantidad * $costoUnitario;
            $categoriaLote = $tipoItem === 'planta' ? ($item['categoria_lote'] ?? null) : null;
            $idUbicacionItem = $tipoItem === 'planta' ? (!empty($item['id_ubicacion']) ? (int)$item['id_ubicacion'] : null) : null;
            $modelo->agregarDetalle($id, $tipoItem, $idItem, $cantidad, $costoUnitario, $subtotalItem, $categoriaLote, $idUbicacionItem);
        }

        if (!$modelo->actualizarCuentaPagar($id, $total)) {
            // No existe — créala (cura la inconsistencia de datos anteriores con CuentaPagar faltante)
            if (!$modelo->crearCuentaPagar($id, $total)) {
                throw new \Exception('No se pudo crear la cuenta por pagar.');
            }
        }

        $modelo->confirmarTransaccion();

        jsonResponse(['success' => true, 'message' => 'Compra actualizada correctamente.']);
    } catch (\Exception $e) {
        $modelo->revertirTransaccion();
        throw $e;
    }
}

function compras_manejarEliminar(): void
{
    $modelo = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    if (!$modelo->obtenerPorId($id)) throw new \Exception('No existe la compra');

    if ($modelo->tienePagosCuentaPagar($id)) {
        throw new \Exception('No se puede eliminar una compra que ya tiene pagos registrados.');
    }

    $modelo->iniciarTransaccion();
    try {
        $modelo->eliminar($id);
        $modelo->eliminarCuentaPagarPorCompra($id);
        $modelo->confirmarTransaccion();
    } catch (\Exception $e) {
        $modelo->revertirTransaccion();
        throw $e;
    }

    jsonResponse(['success' => true, 'message' => 'Compra eliminada correctamente.']);
}

function compras_manejarRecibir(): void
{
    $modelo = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $modelo->aplicarStock($id);
    $modelo->marcarRecibida($id);

    jsonResponse(['success' => true, 'message' => 'Compra recibida y stock actualizado.']);
}

function compras_manejarCancelar(): void
{
    $modelo = new Purchase();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new \Exception('ID inválido');

    $compra = $modelo->obtenerPorId($id);
    if (!$compra) throw new \Exception('No existe la compra');
    if ($compra['estado'] !== 'pendiente') throw new \Exception('Solo se pueden cancelar compras pendientes.');

    $modelo->iniciarTransaccion();
    try {
        $modelo->actualizarEstado($id, 'cancelada');
        $modelo->eliminarCuentaPagarPorCompra($id);
        $modelo->confirmarTransaccion();
    } catch (\Exception $e) {
        $modelo->revertirTransaccion();
        throw $e;
    }

    jsonResponse(['success' => true, 'message' => 'Compra cancelada.']);
}

function compras_obtenerComprasAjax(): void
{
    $modelo = new Purchase();
    $compras = $modelo->obtenerTodas();
    jsonResponse(['success' => true, 'compras' => $compras, 'count' => count($compras)]);
}

function compras_obtenerDetallesAjax(): void
{
    $idCompra = (int)($_GET['id_compra'] ?? 0);
    if ($idCompra <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de compra inválido.'], 400);
        return;
    }
    $modelo = new Purchase();
    $detalles = $modelo->obtenerDetalles($idCompra);
    $compra = $modelo->obtenerPorId($idCompra);
    jsonResponse(['success' => true, 'details' => $detalles, 'compra' => $compra]);
}

function compras_agregarPlantaRapido(): void
{
    $nombre = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombre === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre de la planta es requerido.'], 400);
        return;
    }

    $modelo = new Planta();
    $modelo->add($nombre, $nombre);
    $nuevoId = $modelo->getLastInsertId() ?? 0;

    if ($nuevoId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Error al crear la planta.'], 500);
        return;
    }

    $planta = $modelo->getById($nuevoId);

    jsonResponse([
        'success' => true,
        'message' => 'Planta creada correctamente.',
        'planta' => ['id' => $planta['id_planta'] ?? $nuevoId, 'nombre_comun' => $planta['nombre_comun'] ?? $nombre],
    ]);
}

function compras_agregarInsumoRapido(): void
{
    $nombre = trim((string)($_POST['nombre_insumo'] ?? ''));
    if ($nombre === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre del insumo es requerido.'], 400);
        return;
    }
    $idUnidad = (int)($_POST['id_unidad_medida'] ?? 0);
    if ($idUnidad <= 0) {
        jsonResponse(['success' => false, 'message' => 'La unidad de medida es requerida.'], 400);
        return;
    }

    $modelo = new Insumo([
        'nombre_insumo'          => $nombre,
        'id_unidad_medida'       => $idUnidad,
        'stock_actual'           => 0,
        'costo_unitario_actual'  => 0,
    ]);
    $modelo->save();
    $nuevoId = $modelo->getId() ?? 0;

    if ($nuevoId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Error al crear el insumo.'], 500);
        return;
    }

    $insumo = $modelo->getById($nuevoId);

    jsonResponse([
        'success' => true,
        'message' => 'Insumo creado correctamente.',
        'insumo' => ['id' => $insumo['id_insumo'] ?? $nuevoId, 'nombre_insumo' => $insumo['nombre_insumo'] ?? $nombre],
    ]);
}

function compras_agregarHerramientaRapido(): void
{
    $nombre = trim((string)($_POST['nombre_herramienta'] ?? ''));
    if ($nombre === '') {
        jsonResponse(['success' => false, 'message' => 'El nombre de la herramienta es requerido.'], 400);
        return;
    }

    $modelo = new Herramienta([
        'nombre_herramienta' => $nombre,
        'cantidad'           => 1,
        'estado'             => 'disponible',
    ]);
    $modelo->save();
    $nuevoId = $modelo->getId() ?? 0;

    if ($nuevoId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Error al crear la herramienta.'], 500);
        return;
    }

    $herramienta = $modelo->getById($nuevoId);

    jsonResponse([
        'success' => true,
        'message' => 'Herramienta creada correctamente.',
        'herramienta' => ['id' => $herramienta['id_herramienta'] ?? $nuevoId, 'nombre_herramienta' => $herramienta['nombre_herramienta'] ?? $nombre],
    ]);
}
