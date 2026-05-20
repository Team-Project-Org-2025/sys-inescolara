<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Batch;
use SysInescolara\models\Plant;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function batchesCheckAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado', 'redirect' => BASE_URL . 'login']);
            exit();
        }
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

$GLOBALS['batchModel'] = new Batch();
$GLOBALS['plantModel'] = new Plant();

function index(): void
{
    $batchModel = $GLOBALS['batchModel'] ?? new Batch();
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    batchesCheckAuth();
    handleRequest($batchModel);

    $plants = $plantModel->getAll();
    $view = ROOT_PATH . 'app/views/dashboard/batches.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de lotes no encontrada.';
        return;
    }

    require $view;
}

function get_batches(): void
{
    $batchModel = $GLOBALS['batchModel'] ?? new Batch();
    batchesCheckAuth();
    getBatchesAjax($batchModel);
}

function add_ajax(): void
{
    $batchModel = $GLOBALS['batchModel'] ?? new Batch();
    batchesCheckAuth();
    handleAddEditAjax($batchModel, 'add');
}

function edit_ajax(): void
{
    $batchModel = $GLOBALS['batchModel'] ?? new Batch();
    batchesCheckAuth();
    handleAddEditAjax($batchModel, 'edit');
}

function delete_ajax(): void
{
    $batchModel = $GLOBALS['batchModel'] ?? new Batch();
    batchesCheckAuth();
    handleDeleteAjax($batchModel);
}

function handleRequest(Batch $batchModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_batches'    => fn() => getBatchesAjax($batchModel),
                'POST_add_ajax'      => fn() => handleAddEditAjax($batchModel, 'add'),
                'POST_edit_ajax'     => fn() => handleAddEditAjax($batchModel, 'edit'),
                'POST_delete_ajax'   => fn() => handleDeleteAjax($batchModel),
            ];

            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;

            if (isset($routes[$route])) {
                $routes[$route]();
            }

            jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function handleError(Exception $e, bool $isAjax): void
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit();
}

function handleAddEditAjax(Batch $batchModel, string $mode): void
{
    $id_planta = (int)($_POST['id_planta'] ?? 0);
    $fecha_siembra = trim((string)($_POST['fecha_siembra'] ?? ''));
    $cantidad_inicial = (int)($_POST['cantidad_inicial'] ?? 0);
    $cantidad_actual = (int)($_POST['cantidad_actual'] ?? 0);
    $estado = trim((string)($_POST['estado'] ?? ''));
    $ubicacion = trim((string)($_POST['ubicacion'] ?? ''));

    if ($id_planta <= 0) throw new Exception('Selecciona una planta.');
    if ($fecha_siembra === '') throw new Exception('La fecha de siembra es requerida.');
    if ($cantidad_inicial <= 0) throw new Exception('La cantidad inicial debe ser mayor a 0.');
    if ($cantidad_actual < 0) throw new Exception('La cantidad actual no puede ser negativa.');
    if ($estado === '') throw new Exception('El estado es requerido.');
    if ($ubicacion === '') throw new Exception('La ubicación es requerida.');

    $imagen = null;

    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/batches');
        $result = $uploader->upload($_FILES['imagen'], 'batch');
        if (!$result['success']) {
            throw new Exception(implode(', ', $result['errors']));
        }
        $imagen = $result['data']['url'];
    }

    if ($mode === 'add') {
        $batchModel->add($id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen);
        $newId = $batchModel->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'lote', $newId, null, [
            'id_planta' => $id_planta,
            'fecha_siembra' => $fecha_siembra,
            'cantidad_inicial' => $cantidad_inicial,
            'cantidad_actual' => $cantidad_actual,
            'estado' => $estado,
            'ubicacion' => $ubicacion,
            'imagen' => $imagen,
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Lote agregado correctamente',
            'batch' => [
                'id' => $newId,
                'id_planta' => $id_planta,
                'imagen' => $imagen,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');

    if ($imagen === null) {
        $oldData = $batchModel->getById($id);
        $imagen = $oldData['imagen'] ?? null;
    } else {
        $oldData = $batchModel->getById($id);
        if (!empty($oldData['imagen'])) {
            $uploader = new \SysInescolara\helpers\ImageUploader();
            $uploader->delete($oldData['imagen']);
        }
    }

    $batchModel->update($id, $id_planta, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion, $imagen);
    AuditLog::record('UPDATE', 'lote', $id, $oldData, [
        'id_planta' => $id_planta,
        'fecha_siembra' => $fecha_siembra,
        'cantidad_inicial' => $cantidad_inicial,
        'cantidad_actual' => $cantidad_actual,
        'estado' => $estado,
        'ubicacion' => $ubicacion,
        'imagen' => $imagen,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Lote actualizado correctamente',
        'batch' => ['id' => $id, 'imagen' => $imagen],
    ]);
}

function handleDeleteAjax(Batch $batchModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');
    if (!$batchModel->exists($id)) throw new Exception('No existe el lote');

    $oldData = $batchModel->getById($id);
    if (!empty($oldData['imagen'])) {
        $uploader = new \SysInescolara\helpers\ImageUploader();
        $uploader->delete($oldData['imagen']);
    }

    $batchModel->delete($id);
    AuditLog::record('DELETE', 'lote', $id, $oldData, null);
    jsonResponse(['success' => true, 'message' => 'Lote eliminado correctamente', 'batchId' => $id]);
}

function getBatchesAjax(Batch $batchModel): void
{
    $batches = $batchModel->getAll();
    jsonResponse(['success' => true, 'batches' => $batches, 'count' => count($batches)]);
}
