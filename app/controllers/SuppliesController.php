<?php

        declare(strict_types=1);

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        use SysInescolara\models\Supplies;
        use SysInescolara\models\AuditLog;
        use SysInescolara\helpers\Validation;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Autenticación específica para suppLlies
        function suppliesCheckAuth(): void
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

        $GLOBALS['suppliesModel'] = new Supplies();

        // Vista principal
        function index(): void
        {
            $suppliesModel = $GLOBALS['suppliesModel'] ?? new Supplies();
            suppliesCheckAuth();
            handleRequest($suppliesModel);

            $view = ROOT_PATH . 'app/views/dashboard/supplies.php';
            if (!is_file($view)) {
                http_response_code(500);
                echo 'Vista de insumos no encontrada.';
                return;
            }
            require $view;
        }

        // AJAX: obtener insumos
        function get_supplies(): void
        {
            $suppliesModel = $GLOBALS['suppliesModel'] ?? new Supplies();
            suppliesCheckAuth();
            getSuppliesAjax($suppliesModel);
        }

        // AJAX: agregar insumo
        function add_ajax(): void
        {
            $suppliesModel = $GLOBALS['suppliesModel'] ?? new Supplies();
            suppliesCheckAuth();
            handleAddEditAjax($suppliesModel, 'add');
        }

        // AJAX: editar insumo
        function edit_ajax(): void
        {
            $suppliesModel = $GLOBALS['suppliesModel'] ?? new Supplies();
            suppliesCheckAuth();
            handleAddEditAjax($suppliesModel, 'edit');
        }

        // AJAX: eliminar insumo
        function delete_ajax(): void
        {
            $suppliesModel = $GLOBALS['suppliesModel'] ?? new Supplies();
            suppliesCheckAuth();
            handleDeleteAjax($suppliesModel);
        }

        // Handler principal de requests
        function handleRequest(Supplies $suppliesModel): void
        {
            $action = $_GET['action'] ?? '';
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            try {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    $routes = [
                        'GET_get_supplies'     => fn() => getSuppliesAjax($suppliesModel),
                        'POST_add_ajax'        => fn() => handleAddEditAjax($suppliesModel, 'add'),
                        'POST_edit_ajax'       => fn() => handleAddEditAjax($suppliesModel, 'edit'),
                        'POST_delete_ajax'     => fn() => handleDeleteAjax($suppliesModel),
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

        // Helpers y utilidades
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

        // Handler AJAX para agregar/editar insumo
        function handleAddEditAjax(Supplies $suppliesModel, string $mode): void
        {
            $nombre = trim((string)($_POST['nombre_insumo'] ?? ''));
            if ($nombre === '') {
                throw new Exception('El nombre del insumo es requerido.');
            }
            $unidad = trim((string)($_POST['unidad_medida'] ?? ''));
            if ($unidad === '') {
                throw new Exception('La unidad de medida es requerida.');
            }
            $stock = isset($_POST['stock_actual']) ? floatval($_POST['stock_actual']) : null;
            if ($stock === null) {
                throw new Exception('El stock actual es requerido.');
            }
            $costo = isset($_POST['costo_unitario_actual']) ? floatval($_POST['costo_unitario_actual']) : null;
            if ($costo === null) {
                throw new Exception('El costo unitario es requerido.');
            }

            if ($mode === 'add') {
                $suppliesModel->add($nombre, $unidad, $stock, $costo);
                $newId = $suppliesModel->getLastInsertId() ?? 0;
                AuditLog::record('CREATE', 'insumo', $newId, null, [
                    'nombre_insumo' => $nombre,
                    'unidad_medida' => $unidad,
                    'stock_actual' => $stock,
                    'costo_unitario_actual' => $costo,
                ]);
                jsonResponse([
                    'success' => true,
                    'message' => 'Insumo agregado correctamente',
                    'supply' => [
                        'id' => $newId,
                        'nombre_insumo' => $nombre,
                        'unidad_medida' => $unidad,
                        'stock_actual' => $stock,
                        'costo_unitario_actual' => $costo,
                    ],
                ]);
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID inválido');

            $oldData = $suppliesModel->getById($id);
            $suppliesModel->update($id, $nombre, $unidad, $stock, $costo);
            AuditLog::record('UPDATE', 'insumo', $id, $oldData, [
                'nombre_insumo' => $nombre,
                'unidad_medida' => $unidad,
                'stock_actual' => $stock,
                'costo_unitario_actual' => $costo,
            ]);
            jsonResponse([
                'success' => true,
                'message' => 'Insumo actualizado correctamente',
                'supply' => [
                    'id' => $id,
                    'nombre_insumo' => $nombre,
                    'unidad_medida' => $unidad,
                    'stock_actual' => $stock,
                    'costo_unitario_actual' => $costo,
                ],
            ]);
        }

        // Handler AJAX para eliminar insumo
        function handleDeleteAjax(Supplies $suppliesModel): void
        {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID inválido');
            if (!$suppliesModel->exists($id)) throw new Exception('No existe el insumo');

            $oldData = $suppliesModel->getById($id);
            $suppliesModel->delete($id);
            AuditLog::record('DELETE', 'insumo', $id, $oldData, null);
            jsonResponse(['success' => true, 'message' => 'Insumo eliminado correctamente', 'supplyId' => $id]);
        }

        // Handler AJAX para obtener insumos
        function getSuppliesAjax(Supplies $suppliesModel): void
        {
            $supplies = $suppliesModel->getAll();
            jsonResponse(['success' => true, 'supplies' => $supplies, 'count' => count($supplies)]);
        }
