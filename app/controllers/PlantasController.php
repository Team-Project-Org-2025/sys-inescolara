<?php
require_once __DIR__ . '/controller_helpers.php';

use SysInescolara\models\Planta;
use SysInescolara\models\Especie;

function index(): void
{
    checkModuleAuth();
    $action = $_GET['action'] ?? '';
    
    if (isAjaxRequest() && $action !== '') {
        try {
            match ($_SERVER['REQUEST_METHOD'] . '_' . $action) {
                'GET_get_plants'   => plants_getPlantsAjax(),
                'POST_add_ajax'    => plants_handleAddEdit('add'),
                'POST_edit_ajax'   => plants_handleAddEdit('edit'),
                'POST_delete_ajax' => plants_handleDelete(),
                default            => jsonResponse(['success' => false, 'message' => 'Acción AJAX inválida'], 400),
            };
        } catch (\Exception $e) {
            handleError($e, true);
        }
        return;
    }
    
    $speciesModel = new Especie();
    $species = $speciesModel->getAll();
    $view = ROOT_PATH . 'app/views/dashboard/plantas.php';
    
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }
    
    require $view;
}

function get_plants(): void { checkModuleAuth(); plants_getPlantsAjax(); }
function add_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_CREATE'); plants_handleAddEdit('add'); }
function edit_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_EDIT'); plants_handleAddEdit('edit'); }
function delete_ajax(): void { checkModuleAuth(); checkPermisoOrFail('PLANTAS_DELETE'); plants_handleDelete(); }

function plants_handleAddEdit(string $mode): void
{
    $nombreComun = trim((string)($_POST['nombre_comun'] ?? ''));
    if ($nombreComun === '') {
        throw new \Exception('El nombre común es requerido.');
    }
    
    $nombreTecnico = trim((string)($_POST['nombre_tecnico'] ?? ''));
    if ($nombreTecnico === '') $nombreTecnico = null;
    
    $especieId = !empty($_POST['especie_id']) ? (int)$_POST['especie_id'] : null;
    
    $imagen = null;
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/plants');
        $result = $uploader->upload($_FILES['imagen'], 'plant');
        if (!$result['success']) {
            throw new \Exception(implode(', ', $result['errors']));
        }
        $imagen = $result['data']['url'];
    }
    
    $planta = new Planta();

    if ($mode === 'add') {
        $planta->setNombreComun($nombreComun)
               ->setNombreTecnico($nombreTecnico)
               ->setIdEspecie($especieId)
               ->setImagen($imagen);
        
        if (!$planta->save()) {
            throw new \Exception('Error al guardar la planta en la base de datos.');
        }
        
        $newId = $planta->getId(); 
        
        jsonResponse([
            'success' => true, 
            'message' => 'Planta agregada correctamente', 
            'plant' => ['id' => $newId, 'nombre_comun' => $nombreComun, 'imagen' => $imagen]
        ]);
    }
 
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new \Exception('ID inválido');
    }
    
    if (!$planta->loadById($id)) {
        throw new \Exception('No existe la planta con ID: ' . $id);
    }
    
    if ($imagen === null) {
        $imagen = $planta->getImagen();
    } else {
        if (!empty($planta->getImagen())) {
            (new \SysInescolara\helpers\ImageUploader())->delete($planta->getImagen());
        }
    }
    
    $planta->setNombreComun($nombreComun)
           ->setNombreTecnico($nombreTecnico)
           ->setIdEspecie($especieId)
           ->setImagen($imagen);
    
    if (!$planta->save()) {
        throw new \Exception('Error al actualizar la planta.');
    }
    
    jsonResponse([
        'success' => true, 
        'message' => 'Planta actualizada correctamente', 
        'plant' => ['id' => $id, 'imagen' => $imagen]
    ]);
}

function plants_handleDelete(): void
{
    $planta = new Planta();
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new \Exception('ID inválido');
    }
    
    if (!$planta->loadById($id)) {
        throw new \Exception('No existe la planta');
    }
    
    try {
        if (!$planta->delete($id)) {
            throw new \Exception('Error al desactivar la planta.');
        }
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000 && str_contains($e->getMessage(), '1451')) {
            jsonResponse([
                'success' => false, 
                'message' => 'No se puede eliminar esta planta porque tiene lotes asociados.', 
                'type' => 'foreign_key'
            ]);
            return;
        }
        throw $e;
    }
    
    if (!empty($planta->getImagen())) {
        (new \SysInescolara\helpers\ImageUploader())->delete($planta->getImagen());
    }
    
    jsonResponse([
        'success' => true, 
        'message' => 'Planta desactivada correctamente', 
        'plantId' => $id
    ]);
}

function plants_getPlantsAjax(): void
{
    $model = new Planta();
    jsonResponse([
        'success' => true, 
        'plantas' => $model->getAll(), 
        'count' => 0
    ]);
}