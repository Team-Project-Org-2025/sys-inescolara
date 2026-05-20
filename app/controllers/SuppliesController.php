<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\AuditLog;
use SysInescolara\models\Supplies;
use SysInescolara\helpers\Validation;

require_once __DIR__ . '/LoginController.php';

checkAuth();

$suppliesModel = new Supplies();

function index()
{
    global $dolarBCVRate;
    require __DIR__ . '/../views/dashboard/insumos.php';
}

handleRequest($suppliesModel);

// ============================================
// CORE REQUEST HANDLER
// ============================================

function handleRequest($suppliesModel)
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'POST_add_ajax'    => fn() => handleAddEditAjax($suppliesModel, 'add'),
                'POST_edit_ajax'   => fn() => handleAddEditAjax($suppliesModel, 'edit'),
                'POST_delete_ajax' => fn() => handleDeleteAjax($suppliesModel),
                'GET_get_supplies' => fn() => getSuppliesAjax($suppliesModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
        } else {
            $routes = [
                'POST_add'   => fn() => handleAddEdit($suppliesModel, 'add'),
                'POST_edit'  => fn() => handleAddEdit($suppliesModel, 'edit'),
                'GET_delete' => fn() => handleDelete($suppliesModel)
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

function validateSupplyData($data, $mode)
{
    $rules = [
        'nombre' => ['type' => null, 'required' => true],
        'tipo'   => ['type' => null, 'required' => true],
        'unidad' => ['type' => null, 'required' => true],
        'id'     => ['type' => null, 'required' => ($mode === 'edit')]
    ];

    $validation = Validation::validate($data, $rules);

    if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
    }
}

// ============================================
// NON-AJAX HANDLERS
// ============================================

function handleAddEdit($suppliesModel, $mode)
{
    try {
        $fields = ['nombre', 'tipo', 'unidad'];
        if ($mode === 'edit') $fields[] = 'id';

        foreach ($fields as $f) {
            if (empty($_POST[$f])) {
                throw new Exception("El campo '$f' es requerido");
            }
        }

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $unidad = trim($_POST['unidad']);

        if ($mode === 'add') {
            $suppliesModel->add($nombre, $tipo, $unidad);
            $newId = $suppliesModel->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'insumos', $newId, null, [
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad,
            ]);
            header("Location: supplies-admin.php?success=add");
            exit();
        } else {
            $oldData = $suppliesModel->getById($id);
            $suppliesModel->update($id, $nombre, $tipo, $unidad);
            AuditLog::record('UPDATE', 'insumos', $id, $oldData, [
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad,
            ]);
            header("Location: supplies-admin.php?success=edit");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function handleDelete($suppliesModel)
{
    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("ID inválido");
        }

        $id = intval($_GET['id']);
        $oldData = $suppliesModel->getById($id);
        $suppliesModel->delete($id);
        AuditLog::record('DELETE', 'insumos', $id, $oldData, null);
        header("Location: supplies-admin.php?success=delete");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

function handleAddEditAjax($suppliesModel, $mode)
{
    try {
        validateSupplyData($_POST, $mode);

        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $unidad = trim($_POST['unidad']);

        if ($mode === 'add') {
            $suppliesModel->add($nombre, $tipo, $unidad);
            $newId = $suppliesModel->getLastInsertId() ?? 0;
            AuditLog::record('CREATE', 'insumos', $newId, null, [
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad,
            ]);
            
            $msg = 'Insumo agregado con éxito';
            $supply = [
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad
            ];
        } else {
            $oldData = $suppliesModel->getById($id);
            $suppliesModel->update($id, $nombre, $tipo, $unidad);
            AuditLog::record('UPDATE', 'insumos', $id, $oldData, [
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad,
            ]);
            
            $supply = [
                'id'     => $id,
                'nombre' => $nombre,
                'tipo'   => $tipo,
                'unidad' => $unidad
            ];
            $msg = 'Insumo actualizado con éxito';
        }

        jsonResponse(['success' => true, 'message' => $msg, 'supply' => $supply]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleDeleteAjax($suppliesModel)
{
    try {
        $validation = Validation::validateField($_POST['id'] ?? '', 'id');
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        $id = intval($_POST['id']);
        if (!$suppliesModel->exists($id)) {
            throw new Exception("No existe el insumo solicitado");
        }

        $oldData = $suppliesModel->getById($id);
        $suppliesModel->delete($id);
        AuditLog::record('DELETE', 'insumos', $id, $oldData, null);
        jsonResponse(['success' => true, 'message' => 'Insumo eliminado de forma exitosa', 'supplyId' => $id]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function getSuppliesAjax($suppliesModel)
{
    try {
        if (isset($_GET['id'])) {
            $supply = $suppliesModel->getById(intval($_GET['id']));
            if (!$supply) {
                throw new Exception("Insumo no encontrado");
            }
            jsonResponse(['success' => true, 'supplies' => [$supply]]);
        }

        $supplies = $suppliesModel->getAll();
        jsonResponse(['success' => true, 'supplies' => $supplies, 'count' => count($supplies)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}