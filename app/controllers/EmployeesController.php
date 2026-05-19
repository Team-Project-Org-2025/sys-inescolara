<?php

declare(strict_types=1);

namespace SysInescolara\controllers;

use SysInescolara\models\Employees;
use Exception;

class EmployeesController 
{
    private Employees $employeeModel;

    public function __construct()
    {
        $this->employeeModel = new Employees();
    }

    /**
     * Retorna el listado completo de trabajadores en formato JSON
     */
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $employees = $this->employeeModel->getAll();
        echo json_encode([
            'status' => 'success',
            'data'   => $employees
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Obtiene un trabajador específico por su ID único
     */
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'El ID del trabajador proporcionado es inválido.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $employee = $this->employeeModel->getById($id);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Trabajador no encontrado.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $employee
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Registra un nuevo trabajador validando duplicados
     */
    public function create(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Método de petición no permitido.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Sanitización estricta de las entradas básicas
        $nombre   = trim((string) filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW));
        $cedula   = trim((string) filter_input(INPUT_POST, 'cedula', FILTER_UNSAFE_RAW));
        $telefono = trim((string) filter_input(INPUT_POST, 'telefono', FILTER_UNSAFE_RAW));

        // Validación de campos requeridos vacíos
        if ($nombre === '' || $cedula === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'El nombre y la cédula son campos estrictamente obligatorios.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validación de formato de Cédula (V-12345678, E-12345678 o solo dígitos numéricos mínimos)
        if (!preg_match('/^[VEve]?[-]?[0-9]{6,9}$/', $cedula)) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'El formato de la cédula de identidad no es válido.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $success = $this->employeeModel->add($nombre, $cedula, $telefono);

            if ($success) {
                http_response_code(21);
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Trabajador registrado exitosamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("No se pudo insertar el registro en la base de datos.");
            }
        } catch (Exception $e) {
            http_response_code(409); // Conflicto (Cédula duplicada u otra restricción)
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Actualiza los datos de un trabajador existente
     */
    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Método no permitido para actualización.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $id       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre   = trim((string) filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW));
        $cedula   = trim((string) filter_input(INPUT_POST, 'cedula', FILTER_UNSAFE_RAW));
        $telefono = trim((string) filter_input(INPUT_POST, 'telefono', FILTER_UNSAFE_RAW));

        if (!$id || $nombre === '' || $cedula === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Datos insuficientes o ID inválido para realizar la actualización.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Verificar existencia previa
        $currentEmployee = $this->employeeModel->getById($id);
        if (!$currentEmployee) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'El trabajador que intenta actualizar no existe.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Si cambia la cédula, validar que la nueva no esté en uso por otro trabajador
        if ($cedula !== $currentEmployee['cedula'] && $this->employeeModel->exists($cedula)) {
            http_response_code(409);
            echo json_encode([
                'status'  => 'error',
                'message' => 'La nueva cédula ingresada ya pertenece a otro trabajador.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($this->employeeModel->update($id, $nombre, $cedula, $telefono)) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Datos del trabajador actualizados correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Ocurrió un error interno al intentar actualizar el registro.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Elimina un trabajador por su ID
     */
    public function delete(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Se asume POST/DELETE por compatibilidad básica de formularios
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'ID inválido para proceder con la eliminación.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($this->employeeModel->delete($id)) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Trabajador eliminado del sistema correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'No se pudo eliminar el trabajador de la base de datos.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Endpoint de búsqueda dinámica por nombre o cédula
     */
    public function search(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $query = trim((string) filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW));

        if ($query === '') {
            echo json_encode([
                'status' => 'success',
                'data'   => []
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Invoca la consulta preparada con cláusulas LIKE del modelo
        $results = $this->employeeModel->searchByLocation($query);

        echo json_encode([
            'status' => 'success',
            'data'   => $results
        ], JSON_UNESCAPED_UNICODE);
    }
}