<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Location;
use SysInescolara\models\AuditLog;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function locationsCheckAuth(): void
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

$GLOBALS['locationModel'] = new Location();

function index(): void
{
    $locationModel = $GLOBALS['locationModel'] ?? new Location();
    locationsCheckAuth();
    
    // Si es una petición AJAX, la manejamos aquí directamente
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        handleRequest($locationModel);
        return;
    }

    $view = ROOT_PATH . 'app/views/dashboard/locations.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ubicaciones no encontrada.';
        return;
    }
    require $view;
}

function get_locations(): void
{
    $locationModel = $GLOBALS['locationModel'] ?? new Location();
    locationsCheckAuth();
    getLocationsAjax($locationModel);
}

function add_ajax(): void
{
    $locationModel = $GLOBALS['locationModel'] ?? new Location();
    locationsCheckAuth();
    handleAddEditAjax($locationModel, 'add');
}

function edit_ajax(): void
{
    $locationModel = $GLOBALS['locationModel'] ?? new Location();
    locationsCheckAuth();
    handleAddEditAjax($locationModel, 'edit');
}

function delete_ajax(): void
{
    $locationModel = $GLOBALS['locationModel'] ?? new Location();
    locationsCheckAuth();
    handleDeleteAjax($locationModel);
}

function handleRequest(Location $locationModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            $routes = [
                'GET_get_locations'   => fn() => getLocationsAjax($locationModel),
                'POST_add_ajax'       => fn() => handleAddEditAjax($locationModel, 'add'),
                'POST_edit_ajax'      => fn() => handleAddEditAjax($locationModel, 'edit'),
                'POST_delete_ajax'    => fn() => handleDeleteAjax($locationModel),
            ];
            
            $route = $_SERVER['REQUEST_METHOD'] . '_' . $action;
            if (isset($routes[$route])) {
                $routes[$route]();
                return;
            }
            jsonResponse(['success' => false, 'message' => "Acción AJAX inválida o no mapeada: $route"], 400);
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

function getLocationsAjax(Location $locationModel): void
{
    $locations = $locationModel->getAll();
    jsonResponse(['success' => true, 'locations' => $locations, 'count' => count($locations)]);
}

function handleAddEditAjax(Location $locationModel, string $mode): void
{
    $nombreUbicacion = trim((string)($_POST['nombre_ubicacion'] ?? ''));
    if ($nombreUbicacion === '') {
        throw new Exception('El nombre de la ubicación es requerido.');
    }

    if ($mode === 'add') {
        $locationModel->add($nombreUbicacion);
        $newId = $locationModel->getLastInsertId() ?? 0;
        AuditLog::record('CREATE', 'ubicaciones', $newId, null, [
            'nombre_ubicacion' => $nombreUbicacion
        ]);
        jsonResponse([
            'success' => true,
            'message' => 'Ubicación agregada correctamente',
            'location' => [
                'id' => $newId,
                'nombre_ubicacion' => $nombreUbicacion,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');
    $oldData = $locationModel->getById($id);
    if (!$oldData) throw new Exception('La ubicación que intenta editar no existe.');
    $locationModel->update($id, $nombreUbicacion);
    AuditLog::record('UPDATE', 'ubicaciones', $id, $oldData, [
        'nombre_ubicacion' => $nombreUbicacion,
    ]);
    jsonResponse([
        'success' => true,
        'message' => 'Ubicación actualizada correctamente',
        'location' => ['id' => $id, 'nombre_ubicacion' => $nombreUbicacion],
    ]);
}

function handleDeleteAjax(Location $locationModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID de ubicación inválido');
    $oldData = $locationModel->getById($id);
    if (!$oldData) throw new Exception('No existe la ubicación solicitada.');
    $locationModel->delete($id);
    AuditLog::record('DELETE', 'ubicaciones', $id, $oldData, null);
    jsonResponse([
        'success' => true, 
        'message' => 'Ubicación eliminada correctamente', 
        'locationId' => $id
    ]);
}