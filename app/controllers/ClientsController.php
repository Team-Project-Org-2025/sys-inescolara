<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Clients;
use SysInescolara\helpers\Validation;

require_once __DIR__ . '/LoginController.php';

checkAuth();

$clientModel = new Clients();

function index()
{
    global $dolarBCVRate;
    require __DIR__ . '/../views/dashboard/clientes.php';
}

handleRequest($clientModel);

// ============================================
// CORE REQUEST HANDLER
// ============================================

function handleRequest($clientModel)
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'POST_add_ajax'    => fn() => handleAddEditAjax($clientModel, 'add'),
                'POST_edit_ajax'   => fn() => handleAddEditAjax($clientModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($clientModel),
                'GET_get_clients'  => fn() => getClientsAjax($clientModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
        } else {
            $routes = [
                'POST_add'   => fn() => handleAddEdit($clientModel, 'add'),
                'POST_edit'  => fn() => handleAddEdit($clientModel, 'edit'),
                'GET_delete' => fn() => handleDelete($clientModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            }
        }
    } catch (Exception $e) {
        handleError($e, $isAjax);
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function handleError($e, $isAjax)
{
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()], 500);
    } else {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// VALIDATION
// ============================================

function validateClientData($data, $mode)
{
    $rules = [
        'nombre'               => ['type' => null, 'required' => true],
        'informacion_contacto' => ['type' => null, 'required' => true],
        'id'                   => ['type' => null, 'required' => ($mode === 'edit')]
    ];

    $validation = Validation::validate($data, $rules);

    if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
    }
}

// ============================================
// NON-AJAX HANDLERS
// ============================================

function handleAddEdit($clientModel, $mode)
{
    try {
        $fields = ['nombre', 'informacion_contacto'];
        if ($mode === 'edit') $fields[] = 'id';

        foreach ($fields as $f) {
            if (empty($_POST[$f])) {
                throw new Exception("El campo '$f' es requerido");
            }
        }

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $informacion_contacto = trim($_POST['informacion_contacto']);

        if ($mode === 'add') {
            $clientModel->add($nombre, $informacion_contacto);
            header("Location: clients-admin.php?success=add");
            exit();
        } else {
            $clientModel->update($id, $nombre, $informacion_contacto);
            header("Location: clients-admin.php?success=edit");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function handleDelete($clientModel)
{
    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("ID inválido");
        }

        $id = intval($_GET['id']);
        $clientModel->delete($id);
        header("Location: clients-admin.php?success=delete");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

function handleAddEditAjax($clientModel, $mode)
{
    try {
        validateClientData($_POST, $mode);

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $informacion_contacto = trim($_POST['informacion_contacto']);

        if ($mode === 'add') {
            $clientModel->add($nombre, $informacion_contacto);
            
            $msg = 'Cliente registrado con éxito';
            $client = [
                'nombre' => $nombre,
                'informacion_contacto' => $informacion_contacto
            ];
        } else {
            $clientModel->update($id, $nombre, $informacion_contacto);
            
            $client = [
                'id' => $id,
                'nombre' => $nombre,
                'informacion_contacto' => $informacion_contacto
            ];
            $msg = 'Cliente actualizado con éxito';
        }

        jsonResponse(['success' => true, 'message' => $msg, 'client' => $client]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleDeleteAjax($clientModel)
{
    try {
        $validation = Validation::validateField($_POST['id'] ?? '', 'id');
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        $id = intval($_POST['id']);
        if (!$clientModel->exists($id)) {
            throw new Exception("No existe el cliente solicitado");
        }

        $clientModel->delete($id);
        jsonResponse(['success' => true, 'message' => 'Cliente eliminado con éxito', 'clientId' => $id]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function getClientsAjax($clientModel)
{
    try {
        if (isset($_GET['id'])) {
            $client = $clientModel->getById(intval($_GET['id']));
            if (!$client) {
                throw new Exception("Cliente no encontrado");
            }
            jsonResponse(['success' => true, 'clients' => [$client]]);
        }

        $clients = $clientModel->getAll();
        jsonResponse(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}