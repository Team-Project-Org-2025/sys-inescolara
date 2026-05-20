<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Client;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clientsCheckAuth(): void
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

$GLOBALS['clientModel'] = new Client();

function index(): void
{
    $clientModel = $GLOBALS['clientModel'] ?? new Client();
    clientsCheckAuth();
    handleRequest($clientModel);

    $view = ROOT_PATH . 'app/views/dashboard/clients.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de clientes no encontrada.';
        return;
    }

    require $view;
}

function get_clients(): void
{
    $clientModel = $GLOBALS['clientModel'] ?? new Client();
    clientsCheckAuth();
    getClientsAjax($clientModel);
}

function add_ajax(): void
{
    $clientModel = $GLOBALS['clientModel'] ?? new Client();
    clientsCheckAuth();
    handleAddEditAjax($clientModel, 'add');
}

function edit_ajax(): void
{
    $clientModel = $GLOBALS['clientModel'] ?? new Client();
    clientsCheckAuth();
    handleAddEditAjax($clientModel, 'edit');
}

function delete_ajax(): void
{
    $clientModel = $GLOBALS['clientModel'] ?? new Client();
    clientsCheckAuth();
    handleDeleteAjax($clientModel);
}

function handleRequest(Client $clientModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_clients'   => fn() => getClientsAjax($clientModel),
                'POST_add_ajax'     => fn() => handleAddEditAjax($clientModel, 'add'),
                'POST_edit_ajax'    => fn() => handleAddEditAjax($clientModel, 'edit'),
                'POST_delete_ajax'  => fn() => handleDeleteAjax($clientModel),
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

function handleAddEditAjax(Client $clientModel, string $mode): void
{
    $nombreCliente = trim((string)($_POST['nombre_cliente'] ?? ''));
    if ($nombreCliente === '') {
        throw new Exception('El nombre del cliente es requerido.');
    }

    $contactoCliente = trim((string)($_POST['contacto_cliente'] ?? ''));
    if ($contactoCliente === '') {
        $contactoCliente = null;
    }

    if ($mode === 'add') {
        $clientModel->add($nombreCliente, $contactoCliente);
        jsonResponse([
            'success' => true,
            'message' => 'Cliente agregado correctamente',
            'client' => [
                'id' => $clientModel->getLastInsertId() ?? 0,
                'nombre_cliente' => $nombreCliente,
                'contacto_cliente' => $contactoCliente,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $clientModel->update($id, $nombreCliente, $contactoCliente);
    jsonResponse([
        'success' => true,
        'message' => 'Cliente actualizado correctamente',
        'client' => [
            'id' => $id,
            'nombre_cliente' => $nombreCliente,
            'contacto_cliente' => $contactoCliente,
        ],
    ]);
}

function handleDeleteAjax(Client $clientModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    if (!$clientModel->exists($id)) {
        throw new Exception('No existe el cliente');
    }

    $clientModel->delete($id);
    jsonResponse([
        'success' => true,
        'message' => 'Cliente eliminado correctamente',
        'clientId' => $id,
    ]);
}

function getClientsAjax(Client $clientModel): void
{
    $clients = $clientModel->getAll();
    jsonResponse([
        'success' => true,
        'clients' => $clients,
        'count' => count($clients),
    ]);
}
