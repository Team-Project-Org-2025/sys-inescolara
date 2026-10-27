<?php

declare(strict_types=1);

/**
 * Subproceso de humo: invoca PlantasController::index() en aislamiento.
 *
 * Los controladores terminan con exit() (jsonResponse/handleError), por lo
 * que cada escenario se ejecuta aquí y la prueba lo captura por:
 *   - stdout: cuerpo de la respuesta (JSON o vacío)
 *   - stderr: prefijo HARNESS_STATUS:<codigo HTTP> reportado en shutdown
 *
 * Uso: php tests/Support/controller_harness.php <escenario>
 */

if (PHP_SAPI !== 'cli') {
    exit(2);
}

ini_set('display_errors', 'stderr');
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');

define('BASE_URL', '/sysinescolara/');
define('ROOT_PATH', dirname(__DIR__, 2) . '/');

register_shutdown_function(static function (): void {
    $code = http_response_code();
    $status = ($code === false || $code === 0) ? 'none' : (string) $code;
    fwrite(STDERR, 'HARNESS_STATUS:' . $status . PHP_EOL);
});

$scenarios = [
    // Carga del archivo controlador sin ejecutar index()
    'load' => [
        'ajax' => false, 'method' => 'GET', 'auth' => null,
        'get' => [], 'post' => [],
    ],
    // Sin sesión
    'no_auth_ajax' => [
        'ajax' => true, 'method' => 'GET', 'auth' => null,
        'get' => ['action' => 'get_plants'], 'post' => [],
    ],
    'no_auth_web' => [
        'ajax' => false, 'method' => 'GET', 'auth' => null,
        'get' => [], 'post' => [],
    ],
    // Enrutamiento de acciones
    'bad_action' => [
        'ajax' => true, 'method' => 'GET', 'auth' => 'admin',
        'get' => ['action' => 'accion_inexistente'], 'post' => [],
    ],
    'bad_method' => [
        'ajax' => true, 'method' => 'GET', 'auth' => 'admin',
        'get' => ['action' => 'add_ajax'], 'post' => [],
    ],
    // Permisos
    'no_permission' => [
        'ajax' => true, 'method' => 'POST', 'auth' => 'trabajador',
        'get' => ['action' => 'add_ajax'],
        'post' => ['nombre_comun' => 'Rosa'],
    ],
    // Validaciones previas a la BD
    'add_missing_name' => [
        'ajax' => true, 'method' => 'POST', 'auth' => 'admin',
        'get' => ['action' => 'add_ajax'], 'post' => [],
    ],
    'edit_missing_name' => [
        'ajax' => true, 'method' => 'POST', 'auth' => 'admin',
        'get' => ['action' => 'edit_ajax'], 'post' => ['id' => '1'],
    ],
    'edit_bad_id' => [
        'ajax' => true, 'method' => 'POST', 'auth' => 'admin',
        'get' => ['action' => 'edit_ajax'],
        'post' => ['id' => '0', 'nombre_comun' => 'Rosa'],
    ],
    'delete_bad_id' => [
        'ajax' => true, 'method' => 'POST', 'auth' => 'admin',
        'get' => ['action' => 'delete_ajax'], 'post' => [],
    ],
    // Camino feliz (requiere MySQL disponible; la prueba lo omite si no lo está)
    'get_plants_ok' => [
        'ajax' => true, 'method' => 'GET', 'auth' => 'admin',
        'get' => ['action' => 'get_plants'], 'post' => [],
    ],
];

$scenarioName = $argv[1] ?? '';
if (!isset($scenarios[$scenarioName])) {
    fwrite(STDERR, 'escenario_desconocido' . PHP_EOL);
    exit(2);
}
$scenario = $scenarios[$scenarioName];

$_SERVER['REQUEST_METHOD'] = $scenario['method'];
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = BASE_URL . 'plantas';
if ($scenario['ajax']) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}
$_GET = $scenario['get'];
$_POST = $scenario['post'];
$_FILES = [];

if (is_file(ROOT_PATH . 'app/helpers/env_loader.php')) {
    require_once ROOT_PATH . 'app/helpers/env_loader.php';
    loadEnvFile(ROOT_PATH . '.env');
}

require_once ROOT_PATH . 'vendor/autoload.php';

if ($scenario['auth'] !== null) {
    session_start();
    $_SESSION = $scenario['auth'] === 'admin'
        ? ['user_id' => 1, 'user_rol_id' => 1, 'user_permisos' => []]
        : ['user_id' => 2, 'user_rol_id' => 2, 'user_permisos' => ['plantas:ver']];
}

require ROOT_PATH . 'app/controllers/PlantasController.php';

if ($scenarioName === 'load') {
    $functions = [
        'index',
        'get_plants',
        'add_ajax',
        'edit_ajax',
        'delete_ajax',
        'plants_handleAddEdit',
        'plants_handleDelete',
        'plants_getPlantsAjax',
    ];
    echo json_encode([
        'success' => true,
        'defined' => array_combine($functions, array_map('function_exists', $functions)),
    ]);
    exit(0);
}

index();
