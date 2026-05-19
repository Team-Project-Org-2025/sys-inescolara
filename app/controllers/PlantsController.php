<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SysInescolara\models\Plants;
use SysInescolara\helpers\Validation;

require_once __DIR__ . '/LoginController.php';

checkAuth();

$plantModel = new Plants();

/**
 * Acción principal que renderiza la vista de lotes/plantas
 */
function index()
{
    global $dolarBCVRate;
    require __DIR__ . '/../views/dashboard/plantas.php';
}

// Procesamos la petición delegando al manejador central
handleRequest($plantModel);

// ============================================
// CORE REQUEST HANDLER
// ============================================

function handleRequest($plantModel)
{
    $action = $_GET['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    try {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');

            $routes = [
                'POST_add_ajax'     => fn() => handleAddEditAjax($plantModel, 'add'),
                'POST_edit_ajax'    => fn() => handleAddEditAjax($plantModel, 'edit'),
                'POST_delete_ajax'  => fn() => handleDeleteAjax($plantModel),
                'GET_get_plants'    => fn() => getPlantsAjax($plantModel),
                'GET_search_ajax'   => fn() => handleSearchAjax($plantModel)
            ];

            $route = "{$_SERVER['REQUEST_METHOD']}_$action";

            if (isset($routes[$route])) {
                $routes[$route]();
            } else {
                jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400);
            }
        } else {
            $routes = [
                'POST_add'   => fn() => handleAddEdit($plantModel, 'add'),
                'POST_edit'  => fn() => handleAddEdit($plantModel, 'edit'),
                'GET_delete' => fn() => handleDelete($plantModel)
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

function validatePlantData($data, $mode)
{
    $rules = [
        'especie_id'       => ['type' => null, 'required' => true],
        'fecha_siembra'    => ['type' => null, 'required' => true],
        'cantidad_inicial' => ['type' => null, 'required' => true],
        'cantidad_actual'  => ['type' => null, 'required' => true],
        'estado'           => ['type' => null, 'required' => true],
        'ubicacion'        => ['type' => null, 'required' => true],
        'id'               => ['type' => null, 'required' => ($mode === 'edit')]
    ];

    $validation = Validation::validate($data, $rules);

    if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
    }
}

// ============================================
// NON-AJAX HANDLERS
// ============================================

function handleAddEdit($plantModel, $mode)
{
    try {
        $fields = ['especie_id', 'fecha_siembra', 'cantidad_inicial', 'cantidad_actual', 'estado', 'ubicacion'];
        if ($mode === 'edit') $fields[] = 'id';

        foreach ($fields as $f) {
            if (empty($_POST[$f]) && $_POST[$f] !== '0') {
                throw new Exception("El campo '$f' es requerido");
            }
        }

        $id = intval($_POST['id'] ?? 0);
        $especie_id = intval($_POST['especie_id']);
        $fecha_siembra = trim($_POST['fecha_siembra']);
        $cantidad_inicial = intval($_POST['cantidad_inicial']);
        $cantidad_actual = intval($_POST['cantidad_actual']);
        $estado = trim($_POST['estado']);
        $ubicacion = trim($_POST['ubicacion']);

        if ($mode === 'add') {
            $plantModel->add($especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion);
            header("Location: plants-admin.php?success=add");
            exit();
        } else {
            $plantModel->update($id, $especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion);
            header("Location: plants-admin.php?success=edit");
            exit();
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function handleDelete($plantModel)
{
    try {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            throw new Exception("ID inválido");
        }

        $id = intval($_GET['id']);
        $plantModel->delete($id);
        header("Location: plants-admin.php?success=delete");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// AJAX HANDLERS
// ============================================

function handleAddEditAjax($plantModel, $mode)
{
    try {
        validatePlantData($_POST, $mode);

        $id = intval($_POST['id'] ?? 0);
        $especie_id = intval($_POST['especie_id']);
        $fecha_siembra = trim($_POST['fecha_siembra']);
        $cantidad_inicial = intval($_POST['cantidad_inicial']);
        $cantidad_actual = intval($_POST['cantidad_actual']);
        $estado = trim($_POST['estado']);
        $ubicacion = trim($_POST['ubicacion']);

        if ($mode === 'add') {
            $plantModel->add($especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion);
            
            // Reutilizando el flujo para retornar la planta recién agregada
            $msg = 'Lote de plantas agregado con éxito';
            $plant = [
                'especie_id' => $especie_id,
                'fecha_siembra' => $fecha_siembra,
                'cantidad_inicial' => $cantidad_inicial,
                'cantidad_actual' => $cantidad_actual,
                'estado' => $estado,
                'ubicacion' => $ubicacion
            ];
        } else {
            $plantModel->update($id, $especie_id, $fecha_siembra, $cantidad_inicial, $cantidad_actual, $estado, $ubicacion);
            $plant = [
                'id' => $id,
                'especie_id' => $especie_id,
                'fecha_siembra' => $fecha_siembra,
                'cantidad_inicial' => $cantidad_inicial,
                'cantidad_actual' => $cantidad_actual,
                'estado' => $estado,
                'ubicacion' => $ubicacion
            ];
            $msg = 'Lote de plantas actualizado con éxito';
        }

        jsonResponse(['success' => true, 'message' => $msg, 'plant' => $plant]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleDeleteAjax($plantModel)
{
    try {
        $validation = Validation::validateField($_POST['id'] ?? '', 'id');
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        $id = intval($_POST['id']);
        if (!$plantModel->exists($id)) {
            throw new Exception("No existe el lote de plantas solicitado");
        }

        $plantModel->delete($id);
        jsonResponse(['success' => true, 'message' => 'Lote eliminado de forma exitosa', 'plantId' => $id]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function getPlantsAjax($plantModel)
{
    try {
        if (isset($_GET['id'])) {
            $plant = $plantModel->getById(intval($_GET['id']));
            if (!$plant) {
                throw new Exception("Lote de plantas no encontrado");
            }
            jsonResponse(['success' => true, 'plants' => [$plant]]);
        }

        $plants = $plantModel->getAll();
        jsonResponse(['success' => true, 'plants' => $plants, 'count' => count($plants)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function handleSearchAjax($plantModel)
{
    try {
        $query = trim($_GET['query'] ?? '');
        if ($query === '') {
            jsonResponse(['success' => true, 'plants' => [], 'count' => 0]);
        }

        $results = $plantModel->searchByLocation($query);
        jsonResponse(['success' => true, 'plants' => $results, 'count' => count($results)]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}