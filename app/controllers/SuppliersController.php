<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Suppliers;
use SysInescolara\helpers\Validation;

require_once __DIR__ . '/LoginController.php';

checkAuth();

$supplierModel = new Suppliers();

function index()
{
    global $dolarBCVRate;
    require __DIR__ . '/../views/dashboard/suppliers.php';
}

handleRequest($supplierModel);

// ============================================
// CORE REQUEST HANDLER
// ============================================

function handleRequest($supplierModel)
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'POST_add_ajax'    => fn() => handleAddEditAjax($supplierModel, 'add'),
                'POST_edit_ajax'   => fn() => handleAddEditAjax($supplierModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($supplierModel),
                'GET_get_suppliers'=> fn() => getSuppliersAjax($supplierModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
        } else {
            $routes = [
                'POST_add'   => fn() => handleAddEdit($supplierModel, 'add'),
                'POST_edit'  => fn() => handleAddEdit($supplierModel, 'edit'),
                'GET_delete' => fn() => handleDelete($supplierModel)
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

function validateSupplierData($data, $mode)
{
    $rules = [
        'nombre'               => ['type' => null, 'required' => true],
        'tipo'                 => ['type' => null, 'required' => true],
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

function handleAddEdit($supplierModel, $mode)
{
    try {
        $fields = ['nombre', 'tipo', 'informacion_contacto'];
        if ($mode === 'edit') $fields[] = 'id';

        foreach ($fields as $f) {
            if (empty($_POST[$f])) {
                throw new Exception("El campo '$f' es requerido");
            }
        }

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $informacion_contacto = trim($_POST['informacion_contacto']);

        if ($mode === 'add') {
            $supplierModel->add($nombre, $tipo, $informacion_contacto);
            header("Location: suppliers-admin.php?success=add");
            exit();
        } else {
            $supplierModel->update($id, $nombre, $tipo, $informacion_contacto);
            header("Location: suppliers-admin.php?success=edit");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function handleDelete($supplierModel)
{
    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("ID inválido");
        }

        $id = intval($_GET['id']);
        $supplierModel->delete($id);
        header("Location: suppliers-admin.php?success=delete");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

function handleAddEditAjax($supplierModel, $mode)
{
    try {
        validateSupplierData($_POST, $mode);

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $informacion_contacto = trim($_POST['informacion_contacto']);

        if ($mode === 'add') {
            $supplierModel->add($nombre, $tipo, $informacion_contacto);
            
            $msg = 'Proveedor agregado con éxito';
            $supplier = [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'informacion_contacto' => $informacion_contacto
            ];
        } else {
            $supplierModel->update($id, $nombre, $tipo, $informacion_contacto);
            
            $supplier = [
                'id' => $id,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'informacion_contacto' => $informacion_contacto
            ];
            $msg = 'Proveedor actualizado con éxito';
        }

        jsonResponse(['success' => true, 'message' => $msg, 'supplier' => $supplier]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleDeleteAjax($supplierModel)
{
    try {
        $validation = Validation::validateField($_POST['id'] ?? '', 'id');
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        $id = intval($_POST['id']);
        if (!$supplierModel->exists($id)) {
            throw new Exception("No existe el proveedor solicitado");
        }

        $supplierModel->delete($id);
        jsonResponse(['success' => true, 'message' => 'Proveedor eliminado de forma exitosa', 'supplierId' => $id]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function getSuppliersAjax($supplierModel)
{
    try {
        if (isset($_GET['id'])) {
            $supplier = $supplierModel->getById(intval($_GET['id']));
            if (!$supplier) {
                throw new Exception("Proveedor no encontrado");
            }
            jsonResponse(['success' => true, 'suppliers' => [$supplier]]);
        }

        $suppliers = $supplierModel->getAll();
        jsonResponse(['success' => true, 'suppliers' => $suppliers, 'count' => count($suppliers)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}