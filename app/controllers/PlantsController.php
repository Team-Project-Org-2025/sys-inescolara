<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Plant;
use SysInescolara\models\Species;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function plantsCheckAuth(): void
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

$GLOBALS['plantModel'] = new Plant();
$GLOBALS['speciesModel'] = new Species();

function index(): void
{
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    plantsCheckAuth();
    handleRequest($plantModel);

    $species = $speciesModel->getAll();
    $view = ROOT_PATH . 'app/views/dashboard/plants.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }

    require $view;
}

function get_plants(): void
{
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    plantsCheckAuth();
    getPlantsAjax($plantModel);
}

function add_ajax(): void
{
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    plantsCheckAuth();
    handleAddEditAjax($plantModel, 'add');
}

function edit_ajax(): void
{
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    plantsCheckAuth();
    handleAddEditAjax($plantModel, 'edit');
}

function delete_ajax(): void
{
    $plantModel = $GLOBALS['plantModel'] ?? new Plant();
    plantsCheckAuth();
    handleDeleteAjax($plantModel);
}

function handleRequest(Plant $plantModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_plants'     => fn() => getPlantsAjax($plantModel),
                'POST_add_ajax'      => fn() => handleAddEditAjax($plantModel, 'add'),
                'POST_edit_ajax'     => fn() => handleAddEditAjax($plantModel, 'edit'),
                'POST_delete_ajax'   => fn() => handleDeleteAjax($plantModel),
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

function handleAddEditAjax(Plant $plantModel, string $mode): void
{
    $nombreComun = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombreComun === '') {
        throw new Exception('El nombre común es requerido.');
    }

    $nombreTecnico = trim((string)($_POST['nombre_tecnico'] ?? ''));
    if ($nombreTecnico === '') {
        $nombreTecnico = null;
    }

    $especieId = !empty($_POST['especie_id']) ? (int)$_POST['especie_id'] : null;

    $imagen = null;

    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/plants');
        $result = $uploader->upload($_FILES['imagen'], 'plant');
        if (!$result['success']) {
            throw new Exception(implode(', ', $result['errors']));
        }
        $imagen = $result['data']['url'];
    }

    if ($mode === 'add') {
        $plantModel->add($nombreComun, $nombreTecnico, $especieId, $imagen);
        jsonResponse([
            'success' => true,
            'message' => 'Planta agregada correctamente',
            'plant' => [
                'id' => $plantModel->getLastInsertId() ?? 0,
                'nombre_comun' => $nombreComun,
                'imagen' => $imagen,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');

    if ($imagen === null) {
        $existing = $plantModel->getById($id);
        $imagen = $existing['imagen'] ?? null;
    } else {
        $existing = $plantModel->getById($id);
        if (!empty($existing['imagen'])) {
            $uploader = new \SysInescolara\helpers\ImageUploader();
            $uploader->delete($existing['imagen']);
        }
    }

    $plantModel->update($id, $nombreComun, $nombreTecnico, $especieId, $imagen);
    jsonResponse([
        'success' => true,
        'message' => 'Planta actualizada correctamente',
        'plant' => ['id' => $id, 'imagen' => $imagen],
    ]);
}

function handleDeleteAjax(Plant $plantModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');
    if (!$plantModel->exists($id)) throw new Exception('No existe la planta');

    $existing = $plantModel->getById($id);
    if (!empty($existing['imagen'])) {
        $uploader = new \SysInescolara\helpers\ImageUploader();
        $uploader->delete($existing['imagen']);
    }

    $plantModel->delete($id);
    jsonResponse(['success' => true, 'message' => 'Planta eliminada correctamente', 'plantId' => $id]);
}

function getPlantsAjax(Plant $plantModel): void
{
    $plants = $plantModel->getAll();
    jsonResponse(['success' => true, 'plants' => $plants, 'count' => count($plants)]);
}
