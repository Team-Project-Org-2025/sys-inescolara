<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Species;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function speciesCheckAuth(): void
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

$GLOBALS['speciesModel'] = new Species();

function index(): void
{
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    speciesCheckAuth();
    handleRequest($speciesModel);

    $view = ROOT_PATH . 'app/views/dashboard/species.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de especies no encontrada.';
        return;
    }

    require $view;
}

function get_species(): void
{
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    speciesCheckAuth();
    getSpeciesAjax($speciesModel);
}

function add_ajax(): void
{
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    speciesCheckAuth();
    handleAddEditAjax($speciesModel, 'add');
}

function edit_ajax(): void
{
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    speciesCheckAuth();
    handleAddEditAjax($speciesModel, 'edit');
}

function delete_ajax(): void
{
    $speciesModel = $GLOBALS['speciesModel'] ?? new Species();
    speciesCheckAuth();
    handleDeleteAjax($speciesModel);
}

function handleRequest(Species $speciesModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_species'   => fn() => getSpeciesAjax($speciesModel),
                'POST_add_ajax'     => fn() => handleAddEditAjax($speciesModel, 'add'),
                'POST_edit_ajax'    => fn() => handleAddEditAjax($speciesModel, 'edit'),
                'POST_delete_ajax'  => fn() => handleDeleteAjax($speciesModel),
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

function handleAddEditAjax(Species $speciesModel, string $mode): void
{
    $nombreComun = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombreComun === '') {
        throw new Exception('El nombre común es requerido.');
    }

    $nombreTecnico = trim((string)($_POST['nombre_tecnico'] ?? ''));
    if ($nombreTecnico === '') {
        $nombreTecnico = null;
    }

    if ($mode === 'add') {
        $speciesModel->add($nombreComun, $nombreTecnico);
        $newId = $speciesModel->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'especies', $newId, null, [
            'nombre_comun' => $nombreComun,
            'nombre_tecnico' => $nombreTecnico,
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Especie agregada correctamente',
            'species' => [
                'id' => $newId,
                'nombre_comun' => $nombreComun,
                'nombre_tecnico' => $nombreTecnico,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $oldData = $speciesModel->getById($id);
    $speciesModel->update($id, $nombreComun, $nombreTecnico);
    AuditLog::record('UPDATE', 'especies', $id, $oldData, [
        'nombre_comun' => $nombreComun,
        'nombre_tecnico' => $nombreTecnico,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Especie actualizada correctamente',
        'species' => [
            'id' => $id,
            'nombre_comun' => $nombreComun,
            'nombre_tecnico' => $nombreTecnico,
        ],
    ]);
}

function handleDeleteAjax(Species $speciesModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    if (!$speciesModel->exists($id)) {
        throw new Exception('No existe la especie');
    }

    $oldData = $speciesModel->getById($id);
    $speciesModel->delete($id);
    AuditLog::record('DELETE', 'especies', $id, $oldData, null);
    jsonResponse([
        'success' => true,
        'message' => 'Especie eliminada correctamente',
        'speciesId' => $id,
    ]);
}

function getSpeciesAjax(Species $speciesModel): void
{
    $species = $speciesModel->getAll();
    jsonResponse([
        'success' => true,
        'species' => $species,
        'count' => count($species),
    ]);
}
