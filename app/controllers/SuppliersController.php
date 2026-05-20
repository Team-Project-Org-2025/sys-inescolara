<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Supplier;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function suppliersCheckAuth(): void
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

$GLOBALS['supplierModel'] = new Supplier();

function index(): void
{
    $supplierModel = $GLOBALS['supplierModel'] ?? new Supplier();
    suppliersCheckAuth();
    handleRequest($supplierModel);

    $view = ROOT_PATH . 'app/views/dashboard/suppliers.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de proveedores no encontrada.';
        return;
    }

    require $view;
}

function get_suppliers(): void
{
    $supplierModel = $GLOBALS['supplierModel'] ?? new Supplier();
    suppliersCheckAuth();
    getSuppliersAjax($supplierModel);
}

function add_ajax(): void
{
    $supplierModel = $GLOBALS['supplierModel'] ?? new Supplier();
    suppliersCheckAuth();
    handleAddEditAjax($supplierModel, 'add');
}

function edit_ajax(): void
{
    $supplierModel = $GLOBALS['supplierModel'] ?? new Supplier();
    suppliersCheckAuth();
    handleAddEditAjax($supplierModel, 'edit');
}

function delete_ajax(): void
{
    $supplierModel = $GLOBALS['supplierModel'] ?? new Supplier();
    suppliersCheckAuth();
    handleDeleteAjax($supplierModel);
}

function handleRequest(Supplier $supplierModel): void
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'GET_get_suppliers'   => fn() => getSuppliersAjax($supplierModel),
                'POST_add_ajax'       => fn() => handleAddEditAjax($supplierModel, 'add'),
                'POST_edit_ajax'      => fn() => handleAddEditAjax($supplierModel, 'edit'),
                'POST_delete_ajax'    => fn() => handleDeleteAjax($supplierModel),
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

function handleAddEditAjax(Supplier $supplierModel, string $mode): void
{
    $nombreProveedor = trim((string)($_POST['nombre_proveedor'] ?? ''));
    if ($nombreProveedor === '') {
        throw new Exception('El nombre del proveedor es requerido.');
    }

    $rifProveedor = trim((string)($_POST['rif_proveedor'] ?? ''));
    if ($rifProveedor === '') {
        $rifProveedor = null;
    }

    $contactoVendedor = trim((string)($_POST['contacto_vendedor'] ?? ''));
    if ($contactoVendedor === '') {
        $contactoVendedor = null;
    }

    $telefonoProveedor = trim((string)($_POST['telefono_proveedor'] ?? ''));
    if ($telefonoProveedor === '') {
        $telefonoProveedor = null;
    }

    if ($mode === 'add') {
        $supplierModel->add($nombreProveedor, $rifProveedor, $contactoVendedor, $telefonoProveedor);
        jsonResponse([
            'success' => true,
            'message' => 'Proveedor agregado correctamente',
            'supplier' => [
                'id' => $supplierModel->getLastInsertId() ?? 0,
                'nombre_proveedor' => $nombreProveedor,
                'rif_proveedor' => $rifProveedor,
                'contacto_vendedor' => $contactoVendedor,
                'telefono_proveedor' => $telefonoProveedor,
            ],
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $supplierModel->update($id, $nombreProveedor, $rifProveedor, $contactoVendedor, $telefonoProveedor);
    jsonResponse([
        'success' => true,
        'message' => 'Proveedor actualizado correctamente',
        'supplier' => [
            'id' => $id,
            'nombre_proveedor' => $nombreProveedor,
            'rif_proveedor' => $rifProveedor,
            'contacto_vendedor' => $contactoVendedor,
            'telefono_proveedor' => $telefonoProveedor,
        ],
    ]);
}

function handleDeleteAjax(Supplier $supplierModel): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    if (!$supplierModel->exists($id)) {
        throw new Exception('No existe el proveedor');
    }

    $supplierModel->delete($id);
    jsonResponse([
        'success' => true,
        'message' => 'Proveedor eliminado correctamente',
        'supplierId' => $id,
    ]);
}

function getSuppliersAjax(Supplier $supplierModel): void
{
    $suppliers = $supplierModel->getAll();
    jsonResponse([
        'success' => true,
        'suppliers' => $suppliers,
        'count' => count($suppliers),
    ]);
}
