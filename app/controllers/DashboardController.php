<?php

declare(strict_types=1);



function dashboardCheckAuth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!\SysInescolara\helpers\Auth::check()) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    // Recargar permisos del usuario desde la BD (para reflejar cambios en tiempo real)
    require_once ROOT_PATH . 'vendor/autoload.php';
    $userModel = new \SysInescolara\models\Usuario();
    \SysInescolara\helpers\Auth::setField('user_permisos', $userModel->getRolePermissions(\SysInescolara\helpers\Auth::roleId(), \SysInescolara\helpers\Auth::id()));
}

function dashboardCheckPermiso(string $codigo): void
{
    dashboardCheckAuth();
    if (\SysInescolara\helpers\Auth::isAdmin()) {
        return;
    }
    if (!\SysInescolara\helpers\Auth::hasPermiso($codigo)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title>';
        echo '<link rel="icon" type="image/x-icon" href="' . BASE_URL . 'public/assets/images/favicon.ico">';
        echo '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}.card{text-align:center;padding:3rem;background:#fff;border-radius:1rem;box-shadow:0 4px 24px rgba(0,0,0,.08)}h1{font-size:4rem;margin:0;color:#1b5e20}p{color:#666;margin:1.5rem 0}a{display:inline-block;padding:.75rem 2rem;background:#1b5e20;color:#fff;text-decoration:none;border-radius:.5rem}</style>';
        echo '</head><body><div class="card"><h1>403</h1><p>No tienes permisos para acceder a este módulo.</p>';
        echo '<a href="' . BASE_URL . 'dashboard">Volver al inicio</a></div></body></html>';
        exit();
    }
}

function index(): void
{
    dashboardCheckAuth();

    require_once ROOT_PATH . 'vendor/autoload.php';

    $dashboardData = new \SysInescolara\models\DashboardData();

    $stats = $dashboardData->getStats();
    $recentActivity = $dashboardData->getRecentActivity(10);
    $lowStockLots = $dashboardData->getLowStockLots(10);
    $lowStockSupplies = $dashboardData->getLowStockSupplies(10);
    $plantsBySpecies = $dashboardData->getPlantsBySpecies();
    $inventorySummary = $dashboardData->getInventorySummary();
    $pendingTasks = $dashboardData->getPendingTasks();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'index.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de dashboard (index) no encontrada.';
        return;
    }

    require $view;
}

function asistente(): void
{
    dashboardCheckPermiso('asistente:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'asistente.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de asistente no encontrada.';
        return;
    }

    require $view;
}

function inventario(): void
{
    dashboardCheckPermiso('inventario:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'inventario.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inventario no encontrada.';
        return;
    }

    require $view;
}

function ventas(): void
{
    dashboardCheckPermiso('ventas:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $modeloCliente = new \SysInescolara\models\Cliente();
    $clientes = $modeloCliente->getAll();
    $modeloTrabajador = new \SysInescolara\models\Empleado();
    $trabajadores = $modeloTrabajador->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'ventas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ventas no encontrada.';
        return;
    }

    require $view;
}

function cuentas_cobrar(): void
{
    dashboardCheckPermiso('cuentas_cobrar:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $employeeModel = new \SysInescolara\models\Empleado();
    $employees = $employeeModel->getAll();

    $canPay = \SysInescolara\helpers\Auth::hasPermiso('cuentas_cobrar:editar');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'cuentas-cobrar.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de cuentas por cobrar no encontrada.';
        return;
    }

    require $view;
}

function usuarios(): void
{
    dashboardCheckPermiso('usuarios:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $userModel = new \SysInescolara\models\Usuario();
    $roles = $userModel->getRoles();
    $allPermisos = $userModel->getAllPermissions();
    $employeeModel = new \SysInescolara\models\Empleado();
    $trabajadores = $employeeModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'usuarios.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de usuarios no encontrada.';
        return;
    }

    require $view;
}

function plantas(): void
{
    dashboardCheckPermiso('plantas:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $speciesModel = new \SysInescolara\models\Especie();
    $species = $speciesModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'plantas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }

    require $view;
}

function lotes(): void
{
    dashboardCheckPermiso('lotes:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $plantModel = new \SysInescolara\models\Planta();
    $plants = $plantModel->getAll();
    $locationModel = new \SysInescolara\models\Ubicacion();
    $locations = $locationModel->getAll();
    $loteModel = new \SysInescolara\models\Lote();
    $estados = $loteModel->getEstados();
    $categorias = $loteModel->getCategorias();
    $origenes = $loteModel->getOrigenes();
    $estadoVivoId = $loteModel->getIdEstadoVivo();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'lotes.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de lotes no encontrada.';
        return;
    }

    require $view;
}

function ubicaciones(): void
{
    dashboardCheckPermiso('ubicaciones:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'ubicaciones.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ubicaciones no encontrada.';
        return;
    }

    require $view;
}

function herramientas(): void
{
    dashboardCheckPermiso('herramientas:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'herramientas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de herramientas no encontrada.';
        return;
    }

    require $view;
}

function proveedores(): void
{
    dashboardCheckPermiso('proveedores:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'proveedores.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de proveedores no encontrada.';
        return;
    }

    require $view;
}

function insumos(): void
{
    dashboardCheckPermiso('insumos:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $unidadMedidaModel = new \SysInescolara\models\UnidadMedida();
    $unidades = $unidadMedidaModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'insumos.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de suministros no encontrada.';
        return;
    }

    require $view;
}

function compras(): void
{
    dashboardCheckPermiso('compras:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $supplierModel = new \SysInescolara\models\Proveedor();
    $proveedores = $supplierModel->getAll();
    $supplyModel = new \SysInescolara\models\Insumo();
    $insumos = $supplyModel->getAll();
    $locationModel = new \SysInescolara\models\Ubicacion();
    $ubicaciones = $locationModel->getAll();
    $unitModel = new \SysInescolara\models\UnidadMedida();
    $unidadesMedida = $unitModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'compras.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de compras no encontrada.';
        return;
    }

    require $view;
}

function tasks(): void
{
    dashboardCheckPermiso('tareas:ver');
    $employeeModel = new \SysInescolara\models\Empleado();
    $trabajadores = $employeeModel->getAll();
    $batchModel = new \SysInescolara\models\Lote();
    $lotes = $batchModel->getAll();
    $suppliesModel = new \SysInescolara\models\Insumo();
    $insumos = $suppliesModel->getAll();
    $toolModel = new \SysInescolara\models\Herramienta();
    $herramientas = $toolModel->getAll();
    $view = ROOT_PATH . 'app/views/dashboard/task.php';
    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }
    require $view;
}

function empleados(): void
{
    dashboardCheckPermiso('empleados:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    try {
        $roleModel = new \SysInescolara\models\Role();
        $roles = $roleModel->getAll();
        $cargoOptions = array_map(fn($r) => $r['nombre_rol'], $roles);
        sort($cargoOptions);
    } catch (\Throwable $e) {
        $cargoOptions = [];
    }

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'empleados.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }

    require $view;
}

function tareas(): void
{
    dashboardCheckPermiso('tareas:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $employeeModel = new \SysInescolara\models\Empleado();
    $trabajadores = $employeeModel->getAll();
    $batchModel = new \SysInescolara\models\Lote();
    $lotes = $batchModel->getAll();
    $suppliesModel = new \SysInescolara\models\Insumo();
    $insumos = $suppliesModel->getAll();
    $toolModel = new \SysInescolara\models\Herramienta();
    $herramientas = $toolModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'tareas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de tareas no encontrada.';
        return;
    }

    require $view;
}

function clientes(): void
{
    dashboardCheckPermiso('clientes:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'clientes.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de clientes no encontrada.';
        return;
    }

    require $view;
}
function especies(): void
{
    dashboardCheckPermiso('especies:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'especies.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de especies no encontrada.';
        return;
    }

    require $view;
}

function auditlog(): void
{
    dashboardCheckPermiso('usuarios:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'auditlog.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de bitácora no encontrada.';
        return;
    }

    require $view;
}

function reports(): void
{
    dashboardCheckPermiso('reports:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'reports.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de reportes no encontrada.';
        return;
    }

    require $view;
}

function perfil(): void
{
    dashboardCheckAuth();
    require_once ROOT_PATH . 'vendor/autoload.php';
    $userModel = new \SysInescolara\models\Usuario();
    $userId = \SysInescolara\helpers\Auth::id();
    $user = $userModel->getById($userId);

    if (!$user) {
        http_response_code(404);
        echo 'Usuario no encontrado.';
        return;
    }

    $success = null;
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if ($nombre === '') {
            $error = 'El nombre de usuario es obligatorio.';
        } elseif ($currentPassword === '') {
            $error = 'Debes ingresar tu contraseña actual.';
        } elseif (!$userModel->verifyPassword($userId, $currentPassword)) {
            $error = 'La contraseña actual no es correcta.';
        } elseif ($password !== '' && $password !== $password2) {
            $error = 'Las contraseñas no coinciden.';
        } elseif ($password !== '' && !$userModel->isPasswordStrong($password)) {
            $error = 'La contraseña debe tener al menos 8 caracteres, mayúsculas, minúsculas, números y un carácter especial.';
        } else {
            $avatar = $user['avatar'] ?? null;
            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                require_once ROOT_PATH . 'vendor/autoload.php';
                $uploader = new \SysInescolara\helpers\ImageUploader('assets/uploads/avatars');
                $result = $uploader->upload($_FILES['avatar'], 'avatar');
                if (!$result['success']) {
                    $error = implode(', ', $result['errors']);
                } else {
                    if (!empty($user['avatar'])) {
                        $uploader->delete($user['avatar']);
                    }
                    $avatar = $result['data']['url'];
                }
            }
            if (!$error) {
                $ok = $userModel->updateProfile($userId, $nombre, $email ?: null, $password ?: null, $avatar);
                if ($ok) {
                    \SysInescolara\helpers\Auth::setField('user_nombre', $nombre);
                    \SysInescolara\helpers\Auth::setField('user_email', $email);
                    \SysInescolara\helpers\Auth::setField('user_avatar', $avatar);
                    $user['nombre_usuario'] = $nombre;
                    $user['correo_electronico'] = $email;
                    $user['avatar'] = $avatar;
                    $success = 'Perfil actualizado correctamente.';
                } else {
                    $error = 'Error al actualizar el perfil.';
                }
            }
        }
    }

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'perfil.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de perfil no encontrada.';
        return;
    }

    require $view;
}

function backups(): void
{
    dashboardCheckPermiso('usuarios:ver');
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'backups.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de respaldos no encontrada.';
        return;
    }

    require $view;
}
function ornatos(): void
{
    dashboardCheckPermiso('ornatos:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $modeloCliente = new \SysInescolara\models\Cliente();
    $clientes = $modeloCliente->getAll();
    $modeloLote = new \SysInescolara\models\Lote();
    $lotes = $modeloLote->getAll();

    $vista = ROOT_PATH . 'app/views/dashboard/ornatos.php';
    if (!is_file($vista)) {
        http_response_code(500);
        echo 'Vista de ornatos no encontrada.';
        return;
    }
    require $vista;
}

function roles(): void
{
    dashboardCheckPermiso('roles:ver');

    $view = ROOT_PATH . 'app/views/dashboard/roles.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de roles no encontrada.';
        return;
    }

    require $view;
}

function locations(): void
{
    dashboardCheckPermiso('ubicaciones:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'locations.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ubicaciones no encontrada.';
        return;
    }

    require $view;
}

function tools(): void
{
    dashboardCheckPermiso('herramientas:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'tools.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de herramientas no encontrada.';
        return;
    }

    require $view;
}

function precios(): void
{
    dashboardCheckPermiso('precios:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';

    $plantModel = new \SysInescolara\models\Planta();
    $plants = $plantModel->getAll();

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'precios.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de precios no encontrada.';
        return;
    }

    require $view;
}

function unidadesMedida(): void
{
    dashboardCheckPermiso('unidades_medida:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'unidades-medida.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de unidades de medida no encontrada.';
        return;
    }

    require $view;
}

function seedcollection(): void
{
    dashboardCheckPermiso('seed_collection:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $locationModel = new \SysInescolara\models\Ubicacion();
    $ubicaciones = $locationModel->getAll();
    $employeeModel = new \SysInescolara\models\Empleado();
    $trabajadores = $employeeModel->getAll();
    $plantModel = new \SysInescolara\models\Planta();
    $plantas = $plantModel->getAll();
    $unidadMedidaModel = new \SysInescolara\models\UnidadMedida();
    $unidades = $unidadMedidaModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/seed-collection.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de recolección no encontrada.';
        return;
    }

    require $view;
}

function ampliacion(): void
{
    dashboardCheckPermiso('ampliacion:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';
    $clientModel = new \SysInescolara\models\Cliente();
    $clientes = $clientModel->getAll();
    $employeeModel = new \SysInescolara\models\Empleado();
    $trabajadores = $employeeModel->getAll();

    $view = ROOT_PATH . 'app/views/dashboard/ampliacion.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ampliación no encontrada.';
        return;
    }

    require $view;
}

function trazabilidad(): void
{
    dashboardCheckPermiso('trazabilidad:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';

    $view = ROOT_PATH . 'app/views/dashboard/trazabilidad.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de trazabilidad no encontrada.';
        return;
    }

    require $view;
}

function mermas(): void
{
    dashboardCheckPermiso('mermas:ver');

    require_once ROOT_PATH . 'vendor/autoload.php';

    $view = ROOT_PATH . 'app/views/dashboard/mermas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de mermas no encontrada.';
        return;
    }

    require $view;
}

function cuentaspagar(): void
{
    cuentas_pagar();
}

function cuentas_pagar(): void
{
    dashboardCheckPermiso('cuentas_pagar:ver');

    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'cuentas-pagar.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de cuentas por pagar no encontrada.';
        return;
    }

    require $view;
}