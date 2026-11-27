#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Ejecutor de pruebas ALFA — Sys Inescolara.
 *
 * Las pruebas alfa son verificaciones funcionales propias del equipo de
 * desarrollo. No ejecutan las suites Unit, Integration, Smoke, Interfaz ni
 * Aceptación: cada grupo es independiente y se apoya en la aplicación real
 * servida por un PHP embebido que levanta este mismo ejecutor.
 *
 * Grupos:
 *   auth        Páginas públicas, login, sesión y control de acceso.
 *   catalogo    Especies, plantas, ubicaciones, lotes, precios y unidades.
 *   inventario  Inventario consolidado, insumos y herramientas.
 *   comercial   Clientes, ventas, cuentas por cobrar y ornatos.
 *   compras     Proveedores, compras y cuentas por pagar.
 *   operaciones Tareas, trazabilidad, mermas, ampliación y recolección.
 *   reportes    Dashboard, reportes y bitácora.
 *   sistema     Usuarios, roles y respaldos.
 *
 * Uso:
 *   php tests/tools/alfa/scripts/run-alfa.php
 *   php tests/tools/alfa/scripts/run-alfa.php auth
 *   php tests/tools/alfa/scripts/run-alfa.php --test=catalogo
 *   php tests/tools/alfa/scripts/run-alfa.php core
 *   php tests/tools/alfa/scripts/run-alfa.php --manual
 *   php tests/tools/alfa/scripts/run-alfa.php --list
 *
 * Requisitos: PHP 8.2+ con curl y pdo_mysql, MySQL con las bases del .env y el
 * usuario de pruebas `alfa_admin` (lo crea scripts/seed-alfa.php).
 */

const EXIT_OK = 0;
const EXIT_FAIL = 1;
const EXIT_USAGE = 2;
const EXIT_ENV = 3;

$root = dirname(__DIR__, 4);
$resultsDefault = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'alfa' . DIRECTORY_SEPARATOR . 'formatos' . DIRECTORY_SEPARATOR . 'resultados';
$serverEntry = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'alfa' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'index.php';
$serverRouter = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'alfa' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'router.php';
$seeder = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'alfa' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'seed-alfa.php';

/**
 * @return array<string, mixed>
 */
function parseArguments(array $argv): array
{
    $options = [
        'selector' => null,
        'manual' => false,
        'list' => false,
        'dry-run' => false,
        'verbose' => false,
        'help' => false,
        'junit' => true,
        'resultsDir' => null,
        'port' => null,
        'timeout' => 20,
        'skipSeed' => false,
        'keepServer' => false,
    ];

    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = $argv[$i];
        $value = null;

        if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
            [$arg, $value] = explode('=', $arg, 2);
        }

        $needValue = static function () use ($argv, &$i, $value, $arg): string {
            if ($value !== null && $value !== '') {
                return $value;
            }
            if (!isset($argv[$i + 1])) {
                fwrite(STDERR, "[ERROR] La opción {$arg} requiere un valor." . PHP_EOL);
                exit(EXIT_USAGE);
            }
            return $argv[++$i];
        };

        switch ($arg) {
            case '--test':
            case '--prueba':
                $options['selector'] = trim($needValue());
                break;
            case '--manual':
                $options['manual'] = true;
                break;
            case '--list':
                $options['list'] = true;
                break;
            case '--dry-run':
                $options['dry-run'] = true;
                break;
            case '--verbose':
                $options['verbose'] = true;
                break;
            case '--no-junit':
                $options['junit'] = false;
                break;
            case '--results-dir':
                $options['resultsDir'] = trim($needValue());
                break;
            case '--port':
                $value = $needValue();
                if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 65535) {
                    fwrite(STDERR, '[ERROR] --port debe ser un puerto entre 1 y 65535.' . PHP_EOL);
                    exit(EXIT_USAGE);
                }
                $options['port'] = (int) $value;
                break;
            case '--timeout':
                $value = $needValue();
                if (!ctype_digit($value) || (int) $value < 1) {
                    fwrite(STDERR, '[ERROR] --timeout debe ser un entero positivo.' . PHP_EOL);
                    exit(EXIT_USAGE);
                }
                $options['timeout'] = (int) $value;
                break;
            case '--skip-seed':
                $options['skipSeed'] = true;
                break;
            case '--keep-server':
                $options['keepServer'] = true;
                break;
            case '--help':
            case '-h':
                $options['help'] = true;
                break;
            default:
                if (str_starts_with($arg, '-')) {
                    fwrite(STDERR, "[ERROR] Opción no reconocida: {$arg}" . PHP_EOL);
                    fwrite(STDERR, 'Use --help para ver las opciones disponibles.' . PHP_EOL);
                    exit(EXIT_USAGE);
                }
                if ($options['selector'] === null) {
                    $options['selector'] = trim($arg);
                } else {
                    fwrite(STDERR, "[ERROR] Argumento no reconocido: {$arg}" . PHP_EOL);
                    exit(EXIT_USAGE);
                }
        }
    }

    return $options;
}

function showHelp(): void
{
    $help = <<<'TXT'
Pruebas ALFA — Sys Inescolara
=============================

Pruebas funcionales propias del equipo de desarrollo. NO ejecutan las suites
Unit, Integration, Smoke, Interfaz ni Aceptación: cada grupo es independiente y
se valida por HTTP contra la aplicación real.

Grupos disponibles:

  auth         Páginas públicas, login, sesión y control de acceso.
  catalogo     Especies, plantas, ubicaciones, lotes, precios y unidades.
  inventario   Inventario consolidado, insumos y herramientas.
  comercial    Clientes, ventas, cuentas por cobrar y ornatos.
  compras      Proveedores, compras y cuentas por pagar.
  operaciones  Tareas, trazabilidad, mermas, ampliación y recolección.
  reportes     Dashboard, reportes y bitácora.
  sistema      Usuarios, roles y respaldos.

Uso:
  php tests/tools/alfa/scripts/run-alfa.php [grupo|all|core] [opciones]

Selección:
  all                 Ejecuta los ocho grupos (por defecto).
  core                Ejecuta auth + catalogo + inventario.
  <grupo>             Ejecuta un solo grupo.
  --test=NOMBRE       Equivalente a pasar el nombre como argumento.
  --manual            Menú interactivo para elegir uno o varios grupos.
  --list              Lista los grupos y sus comprobaciones.

Entorno:
  --port=N            Puerto del servidor PHP embebido (por defecto: libre).
  --timeout=SEG       Espera máxima a que arranque el servidor (por defecto 20).
  --skip-seed         No crea/actualiza el usuario alfa_admin.
  --keep-server       Deja el servidor PHP en marcha al terminar (depuración).

Opciones:
  --dry-run           Muestra el plan sin arrancar el servidor.
  --verbose           Muestra la URL y el tiempo de cada comprobación.
  --no-junit          No genera reportes JUnit XML.
  --results-dir=RUTA  Directorio de logs y XML.
  -h, --help          Muestra esta ayuda.

Ejemplos:
  php tests/tools/alfa/scripts/run-alfa.php
  php tests/tools/alfa/scripts/run-alfa.php core
  php tests/tools/alfa/scripts/run-alfa.php auth
  php tests/tools/alfa/scripts/run-alfa.php --manual
  php tests/tools/alfa/scripts/run-alfa.php --dry-run

En Linux/macOS también puede usar:
  ./tests/tools/alfa/scripts/run-alfa.sh auth

En Windows:
  tests\tools\alfa\scripts\run-alfa.bat auth

Códigos de salida:
  0  Todas las comprobaciones ejecutadas pasaron.
  1  Alguna comprobación falló.
  2  Error de uso (opción o grupo no válido).
  3  Faltan requisitos del entorno (curl, pdo_mysql, MySQL, servidor...).
TXT;

    fwrite(STDOUT, $help . PHP_EOL);
}

/**
 * Normaliza un texto para aceptar acentos y mayúsculas.
 */
function normalize(string $value): string
{
    $value = strtr($value, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'ñ' => 'n', 'Ñ' => 'N',
    ]);

    return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', $value));
}

function xmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * @param list<array{name:string,time:float,failure:?string,assertions:int}> $cases
 */
function writeJunit(string $file, string $suiteName, array $cases, float $time, string $class = 'Alfa\\Funcional'): void
{
    $failures = 0;
    $assertions = 0;
    foreach ($cases as $case) {
        $assertions += $case['assertions'];
        if ($case['failure'] !== null) {
            $failures++;
        }
    }

    $lines = [];
    $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $lines[] = sprintf(
        '<testsuites><testsuite name="%s" tests="%d" assertions="%d" errors="0" failures="%d" skipped="0" time="%.3f">',
        xmlEscape($suiteName),
        count($cases),
        $assertions,
        $failures,
        $time
    );

    foreach ($cases as $case) {
        $lines[] = sprintf(
            '  <testcase name="%s" class="%s" classname="%s" assertions="%d" time="%.3f">',
            xmlEscape($case['name']),
            xmlEscape($class),
            xmlEscape($class),
            $case['assertions'],
            $case['time']
        );
        if ($case['failure'] !== null) {
            $lines[] = '    <failure type="AssertionError">' . xmlEscape($case['failure']) . '</failure>';
        }
        $lines[] = '  </testcase>';
    }

    $lines[] = '</testsuite></testsuites>';

    @file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @return array{tests:int,assertions:int,failures:int,errors:int,skipped:int,time:float}|null
 */
function junitSummary(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }

    $dom = new DOMDocument();
    if (!@$dom->load($file)) {
        return null;
    }

    $nodes = (new DOMXPath($dom))->query('/*/testsuite');
    if ($nodes === false || $nodes->length === 0) {
        $nodes = [$dom->documentElement];
    }

    $summary = ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0, 'time' => 0.0];
    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        $summary['tests'] += (int) $node->getAttribute('tests');
        $summary['assertions'] += (int) $node->getAttribute('assertions');
        $summary['failures'] += (int) $node->getAttribute('failures');
        $summary['errors'] += (int) $node->getAttribute('errors');
        $summary['skipped'] += (int) $node->getAttribute('skipped');
        $summary['time'] += (float) $node->getAttribute('time');
    }

    return $summary;
}

/**
 * Lee una ruta con notación de puntos: success, data.count, etc.
 */
function jsonGet(array $data, string $path, bool &$found): mixed
{
    $current = $data;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            $found = false;
            return null;
        }
        $current = $current[$segment];
    }

    $found = true;
    return $current;
}

function describeValue(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null) {
        return 'null';
    }
    if (is_scalar($value)) {
        return (string) $value;
    }

    return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Cliente HTTP mínimo con cookie jar y token de sesión alfa.
 */
final class AlfaHttpClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private string $cookieFile,
        private bool $verbose = false
    ) {
    }

    /**
     * @param array{method?:string,auth?:bool,ajax?:bool,follow?:bool,body?:?string,extraHeaders?:list<string>} $options
     * @return array{status:int,headers:array<string,string>,body:string,ms:float,error:?string,url:string}
     */
    public function request(string $path, array $options = []): array
    {
        $method = strtoupper((string) ($options['method'] ?? 'GET'));
        $auth = (bool) ($options['auth'] ?? true);
        $ajax = (bool) ($options['ajax'] ?? true);
        $follow = (bool) ($options['follow'] ?? false);
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        if (!extension_loaded('curl')) {
            return [
                'status' => 0,
                'headers' => [],
                'body' => '',
                'ms' => 0.0,
                'error' => 'la extensión curl de PHP no está habilitada',
                'url' => $url,
            ];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'headers' => [], 'body' => '', 'ms' => 0.0, 'error' => 'no se pudo iniciar curl', 'url' => $url];
        }

        $headers = [
            $ajax
                ? 'Accept: application/json, text/html;q=0.9, */*;q=0.8'
                : 'Accept: text/html, application/xhtml+xml, */*;q=0.8',
        ];
        if ($ajax) {
            $headers[] = 'X-Requested-With: XMLHttpRequest';
        }
        if ($auth) {
            $headers[] = 'X-Alfa-Token: ' . $this->token;
        }
        foreach ((array) ($options['extraHeaders'] ?? []) as $extra) {
            $headers[] = $extra;
        }

        // Las comprobaciones sin sesión usan un cookie jar aparte para que la
        // cookie de una comprobación autenticada no las vuelva a autenticar.
        $cookieFile = $auth ? $this->cookieFile : $this->cookieFile . '.anon';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'SysInescolara-Alfa/1.0',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) ($options['body'] ?? ''));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (isset($options['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $options['body']);
            }
        }

        $started = microtime(true);
        $raw = curl_exec($ch);
        $ms = (microtime(true) - $started) * 1000;
        $error = $raw === false ? (curl_error($ch) ?: 'error de conexión') : null;
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($raw === false) {
            return ['status' => 0, 'headers' => [], 'body' => '', 'ms' => $ms, 'error' => $error, 'url' => $url];
        }

        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $blocks = preg_split("/\r\n\r\n/", trim($headerText)) ?: [];
        $lastBlock = $blocks === [] ? '' : (string) end($blocks);
        $parsedHeaders = [];
        foreach (preg_split("/\r\n/", $lastBlock) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $parsedHeaders[strtolower(trim($name))] = trim($value);
            }
        }

        return [
            'status' => $status,
            'headers' => $parsedHeaders,
            'body' => $body,
            'ms' => $ms,
            'error' => null,
            'url' => $url,
        ];
    }
}

/**
 * Construye el comando de un proceso PHP hijo conservando los sockets MySQL
 * configurados en el PHP actual. Algunos entornos (p. ej. un wrapper de `php`
 * que pasa -d pdo_mysql.default_socket=...) fijan el socket por CLI y
 * PHP_BINARY no lo hereda, lo que rompe las conexiones a MySQL.
 *
 * @param list<string> $args
 * @return list<string>
 */
function phpChildCommand(array $args): array
{
    $command = [PHP_BINARY];
    foreach (['pdo_mysql.default_socket', 'mysqli.default_socket'] as $ini) {
        $value = ini_get($ini);
        if (is_string($value) && $value !== '') {
            $command[] = '-d';
            $command[] = $ini . '=' . $value;
        }
    }
    return array_merge($command, $args);
}

/**
 * @return array{process:?resource,log:string,errorLog:string,port:int,command:list<string>}
 */
function startServer(string $root, string $serverRouter, int $port, string $token, string $resultsDir, string $stamp): array
{
    $log = $resultsDir . DIRECTORY_SEPARATOR . 'server-' . $stamp . '.log';
    $errorLog = $resultsDir . DIRECTORY_SEPARATOR . 'server-' . $stamp . '.err.log';
    $command = phpChildCommand(['-S', '127.0.0.1:' . $port, '-t', $root, $serverRouter]);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $log, 'wb'],
        2 => ['file', $errorLog, 'wb'],
    ];

    $environment = getenv();
    if (!is_array($environment)) {
        $environment = [];
    }
    $environment['ALFA_SESSION_TOKEN'] = $token;

    $process = @proc_open($command, $descriptors, $pipes, $root, $environment, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        return ['process' => null, 'log' => $log, 'errorLog' => $errorLog, 'port' => $port, 'command' => $command];
    }

    fclose($pipes[0]);

    return ['process' => $process, 'log' => $log, 'errorLog' => $errorLog, 'port' => $port, 'command' => $command];
}

/**
 * @param array{process:?resource,log:string,errorLog:string,port:int,command:list<string>} $server
 */
function stopServer(array $server): void
{
    if (!is_resource($server['process'])) {
        return;
    }

    $status = @proc_get_status($server['process']);
    if (is_array($status) && ($status['running'] ?? false)) {
        @proc_terminate($server['process']);
        usleep(250000);
    }

    @proc_close($server['process']);
}

/**
 * @param array{process:?resource,log:string,errorLog:string,port:int,command:list<string>} $server
 */
function serverIsRunning(array $server): bool
{
    if (!is_resource($server['process'])) {
        return false;
    }

    $status = @proc_get_status($server['process']);
    return is_array($status) && ($status['running'] ?? false);
}

/**
 * @param array{process:?resource,log:string,errorLog:string,port:int,command:list<string>} $server
 */
function waitForServer(array $server, AlfaHttpClient $client, int $timeout): bool
{
    $deadline = microtime(true) + $timeout;
    do {
        $probe = $client->request('/login', ['auth' => false, 'ajax' => false]);
        if ($probe['status'] > 0) {
            return true;
        }
        if (!serverIsRunning($server)) {
            return false;
        }
        usleep(200000);
    } while (microtime(true) < $deadline);

    return false;
}

function findFreePort(): int
{
    $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        return 8098;
    }

    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);

    $position = strrpos($name, ':');
    if ($position === false) {
        return 8098;
    }

    $port = (int) substr($name, $position + 1);
    return $port > 0 ? $port : 8098;
}

/**
 * Definición declarativa de los grupos y sus comprobaciones.
 *
 * @return array<string, array<string, mixed>>
 */
function alphaGroups(string $stamp): array
{
    return [
        'auth' => [
            'label' => 'Autenticación y acceso',
            'description' => 'Páginas públicas, login, sesión y control de acceso.',
            'core' => true,
            'checks' => [
                [
                    'name' => 'Inicio público responde',
                    'path' => '/',
                    'auth' => false,
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['Vivero Institucional INECOLARA'],
                ],
                [
                    'name' => 'Catálogo público responde',
                    'path' => '/catalogo',
                    'auth' => false,
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['Especies disponibles para reforestación'],
                ],
                [
                    'name' => 'Formulario de login con reCAPTCHA',
                    'path' => '/login',
                    'auth' => false,
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['name="email"', 'name="password"', 'g-recaptcha'],
                ],
                [
                    'name' => 'Login sin reCAPTCHA se rechaza',
                    'method' => 'POST',
                    'path' => '/login',
                    'auth' => false,
                    'ajax' => false,
                    'status' => 200,
                    'body' => 'email=alfa_admin&password=incorrecta',
                    'contains' => ['verificación de seguridad'],
                ],
                [
                    'name' => 'Dashboard protegido redirige al login',
                    'path' => '/dashboard',
                    'auth' => false,
                    'ajax' => false,
                    'status' => [301, 302, 303, 307, 308],
                    'locationContains' => 'login',
                ],
                [
                    'name' => 'Login con sesión redirige al dashboard',
                    'path' => '/login',
                    'auth' => true,
                    'ajax' => false,
                    'status' => [301, 302, 303, 307, 308],
                    'locationContains' => 'dashboard',
                ],
                [
                    'name' => 'Dashboard autenticado responde',
                    'path' => '/dashboard',
                    'auth' => true,
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Inicio - SYSINECOLARA</title>'],
                ],
                [
                    'name' => 'Endpoint AJAX protegido responde 401',
                    'path' => '/plantas?action=get_plants',
                    'auth' => false,
                    'ajax' => true,
                    'status' => 401,
                    'contains' => ['"success":false'],
                ],
                [
                    'name' => 'Sesión activa se informa por AJAX',
                    'path' => '/login/check_session',
                    'auth' => true,
                    'ajax' => true,
                    'status' => 200,
                    'contains' => ['"active":true'],
                    'json' => ['active' => true],
                ],
                [
                    'name' => 'Logout AJAX exige POST y cabecera AJAX',
                    'path' => '/login/logout_ajax',
                    'auth' => true,
                    'ajax' => false,
                    'status' => 400,
                ],
                [
                    'name' => 'Ruta inexistente responde 404',
                    'path' => '/ruta-inexistente-alfa-' . $stamp,
                    'auth' => false,
                    'ajax' => false,
                    'status' => 404,
                    'contains' => ['Error 404'],
                ],
            ],
        ],
        'catalogo' => [
            'label' => 'Catálogo',
            'description' => 'Especies, plantas, ubicaciones, lotes, precios y unidades.',
            'core' => true,
            'checks' => [
                ['name' => 'Especies: listado AJAX', 'path' => '/especies?action=get_species', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Plantas: listado AJAX', 'path' => '/plantas?action=get_plants', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ubicaciones: listado AJAX', 'path' => '/ubicaciones?action=get_locations', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Lotes: listado AJAX', 'path' => '/lotes?action=get_batches', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Precios: listado AJAX', 'path' => '/precios?action=get_prices', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Unidades de medida: listado AJAX', 'path' => '/unidades-medida?action=get_units', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Especies: vista de gestión',
                    'path' => '/especies',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Especies - INECOLARA</title>', 'Gestión de Especies'],
                ],
                [
                    'name' => 'Especies: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/especies?action=add_ajax',
                    'status' => 500,
                    'body' => 'nombre_especie=',
                    'contains' => ['El nombre de la especie es requerido'],
                ],
                [
                    'name' => 'Plantas: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/plantas?action=add_ajax',
                    'status' => 500,
                    'body' => 'nombre_comun=',
                    'contains' => ['El nombre común es requerido'],
                ],
                [
                    'name' => 'Catálogo: acción AJAX inválida',
                    'path' => '/especies?action=no_existe',
                    'status' => 400,
                    'contains' => ['Acción AJAX inválida'],
                ],
            ],
        ],
        'inventario' => [
            'label' => 'Inventario',
            'description' => 'Inventario consolidado, insumos y herramientas.',
            'core' => true,
            'checks' => [
                ['name' => 'Inventario consolidado AJAX', 'path' => '/inventario?action=get_consolidated', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de inventario',
                    'path' => '/inventario',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Inventario - INECOLARA</title>'],
                ],
                ['name' => 'Insumos: listado AJAX', 'path' => '/insumos?action=get_supplies', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Herramientas: listado AJAX', 'path' => '/herramientas?action=get_tools', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Herramientas: usos exige ID',
                    'path' => '/herramientas?action=get_usages&id_herramienta=0',
                    'status' => 400,
                    'contains' => ['ID de herramienta inválido'],
                ],
                [
                    'name' => 'Insumos: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/insumos?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre del insumo es requerido'],
                ],
                [
                    'name' => 'Herramientas: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/herramientas?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre de la herramienta es requerido'],
                ],
                [
                    'name' => 'Lotes: valida planta vacía',
                    'method' => 'POST',
                    'path' => '/lotes?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['Selecciona una planta'],
                ],
            ],
        ],
        'comercial' => [
            'label' => 'Comercial',
            'description' => 'Clientes, ventas, cuentas por cobrar y ornatos.',
            'core' => false,
            'checks' => [
                ['name' => 'Clientes: listado AJAX', 'path' => '/clientes?action=get_clients', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ventas: listado AJAX', 'path' => '/ventas?accion=listar', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Cuentas por cobrar: listado AJAX', 'path' => '/cuentas_cobrar?action=obtener_lista', 'status' => 200, 'contains' => ['"recordsTotal"']],
                ['name' => 'Cuentas por cobrar: estadísticas AJAX', 'path' => '/cuentas_cobrar?action=obtener_estadisticas', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ornatos: listado AJAX', 'path' => '/ornatos?accion=listar', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de ventas',
                    'path' => '/ventas',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Ventas - INECOLARA</title>'],
                ],
                [
                    'name' => 'Ventas: valida cliente obligatorio',
                    'method' => 'POST',
                    'path' => '/ventas?accion=guardar',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El cliente es requerido'],
                ],
                [
                    'name' => 'Clientes: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/clientes?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre del cliente es requerido'],
                ],
                ['name' => 'Ventas: búsqueda de clientes', 'path' => '/ventas?accion=buscar_clientes&q=a', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ventas: trabajadores activos', 'path' => '/ventas?accion=trabajadores', 'status' => 200, 'contains' => ['"success":true']],
            ],
        ],
        'compras' => [
            'label' => 'Compras',
            'description' => 'Proveedores, compras y cuentas por pagar.',
            'core' => false,
            'checks' => [
                ['name' => 'Proveedores: listado AJAX', 'path' => '/proveedores?action=get_suppliers', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Compras: listado AJAX', 'path' => '/compras?action=obtener_compras', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Cuentas por pagar: listado AJAX', 'path' => '/cuentas-pagar?action=obtener_cuentas', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de compras',
                    'path' => '/compras',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Compras - INECOLARA</title>'],
                ],
                [
                    'name' => 'Compras: valida proveedor obligatorio',
                    'method' => 'POST',
                    'path' => '/compras?action=agregar_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El proveedor es requerido'],
                ],
                [
                    'name' => 'Proveedores: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/proveedores?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre del proveedor es requerido'],
                ],
            ],
        ],
        'operaciones' => [
            'label' => 'Operaciones',
            'description' => 'Tareas, trazabilidad, mermas, ampliación y recolección.',
            'core' => false,
            'checks' => [
                ['name' => 'Tareas: asignaciones AJAX', 'path' => '/tareas?action=get_assignments', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Trazabilidad: listado AJAX', 'path' => '/trazabilidad?action=get_trazabilidad', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Trazabilidad: lotes AJAX', 'path' => '/trazabilidad?action=get_batches', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Mermas: listado AJAX', 'path' => '/mermas?action=get_mermas', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Mermas: cuarentena AJAX', 'path' => '/mermas?action=get_quarantine', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ampliación: intercambios AJAX', 'path' => '/ampliacion?action=get_exchanges', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Ampliación: lotes AJAX', 'path' => '/ampliacion?action=get_lotes', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Recolección: listado AJAX', 'path' => '/recoleccion?action=get_recolecciones', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de tareas',
                    'path' => '/tareas',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Asignar Tareas - INECOLARA</title>'],
                ],
                [
                    'name' => 'Mermas: valida motivo',
                    'method' => 'POST',
                    'path' => '/mermas?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['Debe seleccionar un registro de cuarentena'],
                ],
            ],
        ],
        'reportes' => [
            'label' => 'Reportes',
            'description' => 'Dashboard, reportes y bitácora.',
            'core' => false,
            'checks' => [
                ['name' => 'Reportes: módulos disponibles', 'path' => '/reports?action=get_modules', 'status' => 200, 'contains' => ['"success":true', '"plantas"']],
                ['name' => 'Reportes: filtros de plantas', 'path' => '/reports?action=get_filters&module=plantas', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Reportes: datos de plantas', 'path' => '/reports?action=get_report_data&module=plantas', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de reportes',
                    'path' => '/reports',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Reportes - SYSINECOLARA</title>'],
                ],
                ['name' => 'Bitácora: listado AJAX', 'path' => '/auditlog?action=get_auditlogs', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Dashboard de inventario',
                    'path' => '/dashboard/inventario',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Inventario - INECOLARA</title>'],
                ],
                [
                    'name' => 'Dashboard de ventas',
                    'path' => '/dashboard/ventas',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['- INECOLARA</title>'],
                ],
                [
                    'name' => 'Dashboard de clientes',
                    'path' => '/dashboard/clientes',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['- INECOLARA</title>'],
                ],
            ],
        ],
        'sistema' => [
            'label' => 'Sistema',
            'description' => 'Usuarios, roles y respaldos.',
            'core' => false,
            'checks' => [
                ['name' => 'Usuarios: listado AJAX', 'path' => '/usuarios?action=get_users', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Roles: listado AJAX', 'path' => '/roles?action=get_roles', 'status' => 200, 'contains' => ['"success":true']],
                ['name' => 'Respaldos: listado AJAX', 'path' => '/backups?action=get_backups', 'status' => 200, 'contains' => ['"success":true']],
                [
                    'name' => 'Vista de usuarios',
                    'path' => '/usuarios',
                    'ajax' => false,
                    'status' => 200,
                    'contains' => ['<title>Usuarios - INECOLARA</title>'],
                ],
                [
                    'name' => 'Usuarios: valida datos obligatorios',
                    'method' => 'POST',
                    'path' => '/usuarios?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre de usuario es requerido'],
                ],
                [
                    'name' => 'Roles: valida nombre vacío',
                    'method' => 'POST',
                    'path' => '/roles?action=add_ajax',
                    'status' => 500,
                    'body' => '',
                    'contains' => ['El nombre del rol es requerido'],
                ],
                [
                    'name' => 'Usuarios: sin sesión responde 401',
                    'path' => '/usuarios?action=get_users',
                    'auth' => false,
                    'ajax' => true,
                    'status' => 401,
                    'contains' => ['"success":false'],
                ],
            ],
        ],
    ];
}

/**
 * @param array<string, array<string, mixed>> $groups
 * @return list<string>
 */
function resolveSelection(string $selector, array $groups): array
{
    $normalized = normalize($selector);

    if (in_array($normalized, ['all', 'todo', 'todas', 'todos', '*'], true)) {
        return array_keys($groups);
    }

    if (in_array($normalized, ['core', 'nucleo', 'basico'], true)) {
        return array_keys(array_filter(
            $groups,
            static fn (array $group): bool => ($group['core'] ?? false) === true
        ));
    }

    $aliases = [
        'auth' => 'auth',
        'autenticacion' => 'auth',
        'login' => 'auth',
        'acceso' => 'auth',
        'catalogo' => 'catalogo',
        'catalog' => 'catalogo',
        'inventario' => 'inventario',
        'comercial' => 'comercial',
        'ventas' => 'comercial',
        'compras' => 'compras',
        'operaciones' => 'operaciones',
        'reportes' => 'reportes',
        'sistema' => 'sistema',
    ];

    if (isset($aliases[$normalized]) && isset($groups[$aliases[$normalized]])) {
        return [$aliases[$normalized]];
    }

    fwrite(STDERR, "[ERROR] Grupo alfa no encontrado: {$selector}" . PHP_EOL);
    fwrite(STDERR, 'Use --list para ver los grupos disponibles.' . PHP_EOL);
    exit(EXIT_USAGE);
}

/**
 * @param array<string, array<string, mixed>> $groups
 * @return list<string>
 */
function manualSelection(array $groups): array
{
    $keys = array_keys($groups);

    fwrite(STDOUT, PHP_EOL . 'Grupos alfa disponibles:' . PHP_EOL);
    foreach ($keys as $index => $key) {
        $group = $groups[$key];
        fwrite(STDOUT, sprintf(
            "  %d) %-12s %-24s %s\n",
            $index + 1,
            $key,
            $group['label'],
            $group['description']
        ));
    }

    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Seleccione uno o varios (ej. 1,3-4), "a" para todos, "c" para el núcleo' . PHP_EOL);
    fwrite(STDOUT, 'o Enter para cancelar: ');

    $line = fgets(STDIN);
    if ($line === false) {
        return [];
    }

    $line = strtolower(trim($line));
    if ($line === '') {
        return [];
    }
    if (in_array($line, ['a', 'all', 'todas'], true)) {
        return $keys;
    }
    if (in_array($line, ['c', 'core', 'nucleo'], true)) {
        return array_keys(array_filter(
            $groups,
            static fn (array $group): bool => ($group['core'] ?? false) === true
        ));
    }

    $selected = [];
    foreach (explode(',', $line) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (str_contains($part, '-')) {
            [$from, $to] = array_map('intval', explode('-', $part, 2));
            if ($from > 0 && $to >= $from) {
                for ($i = $from; $i <= $to; $i++) {
                    if (isset($keys[$i - 1])) {
                        $selected[] = $keys[$i - 1];
                    }
                }
            }
            continue;
        }
        $number = (int) $part;
        if ($number > 0 && isset($keys[$number - 1])) {
            $selected[] = $keys[$number - 1];
        }
    }

    return array_values(array_unique($selected));
}

/**
 * @param array<string, array<string, mixed>> $groups
 */
function showGroups(array $groups): void
{
    fwrite(STDOUT, sprintf("%-12s %-24s %-7s %-7s %s\n", 'Grupo', 'Nombre', 'Núcleo', 'Casos', 'Descripción'));
    fwrite(STDOUT, str_repeat('-', 110) . PHP_EOL);
    foreach ($groups as $key => $group) {
        fwrite(STDOUT, sprintf(
            "%-12s %-24s %-7s %-7d %s\n",
            $key,
            $group['label'],
            ($group['core'] ?? false) ? 'sí' : 'no',
            count((array) $group['checks']),
            $group['description']
        ));
    }
}

/**
 * @param array<string, mixed> $check
 * @return array{name:string,time:float,failure:?string,assertions:int,status:int,ms:float,url:string}
 */
function runCheck(AlfaHttpClient $client, array $check): array
{
    $response = $client->request((string) $check['path'], [
        'method' => (string) ($check['method'] ?? 'GET'),
        'auth' => (bool) ($check['auth'] ?? true),
        'ajax' => (bool) ($check['ajax'] ?? true),
        'follow' => (bool) ($check['follow'] ?? false),
        'body' => $check['body'] ?? null,
    ]);

    $problems = [];
    $assertions = 0;

    if ($response['error'] !== null) {
        $problems[] = 'Error de conexión: ' . $response['error'];
    }

    $expectedStatuses = array_map('intval', (array) ($check['status'] ?? [200]));
    $assertions++;
    if (!in_array($response['status'], $expectedStatuses, true)) {
        $problems[] = sprintf(
            'Se esperaba HTTP %s y se recibió %d',
            implode('/', $expectedStatuses),
            $response['status']
        );
    }

    foreach ((array) ($check['contains'] ?? []) as $needle) {
        $assertions++;
        if (!str_contains($response['body'], (string) $needle)) {
            $problems[] = 'No se encontró en la respuesta: ' . $needle;
        }
    }

    foreach ((array) ($check['notContains'] ?? []) as $needle) {
        $assertions++;
        if (str_contains($response['body'], (string) $needle)) {
            $problems[] = 'No se esperaba encontrar en la respuesta: ' . $needle;
        }
    }

    if (isset($check['locationContains'])) {
        $assertions++;
        $location = (string) ($response['headers']['location'] ?? '');
        if (!str_contains(strtolower($location), strtolower((string) $check['locationContains']))) {
            $problems[] = 'La cabecera Location no apunta a ' . $check['locationContains']
                . ' (recibido: ' . ($location !== '' ? $location : 'vacía') . ')';
        }
    }

    foreach ((array) ($check['json'] ?? []) as $path => $expected) {
        $assertions++;
        $data = json_decode($response['body'], true);
        if (!is_array($data)) {
            $problems[] = 'La respuesta no es JSON válido para comprobar ' . $path;
            break;
        }
        $found = false;
        $actual = jsonGet($data, (string) $path, $found);
        if (!$found || $actual !== $expected) {
            $problems[] = sprintf(
                'JSON %s: se esperaba %s y se recibió %s',
                $path,
                describeValue($expected),
                $found ? describeValue($actual) : 'ausente'
            );
        }
    }

    return [
        'name' => (string) $check['name'],
        'time' => $response['ms'] / 1000,
        'failure' => $problems === [] ? null : implode('; ', $problems),
        'assertions' => max(1, $assertions),
        'status' => $response['status'],
        'ms' => $response['ms'],
        'url' => $response['url'],
    ];
}

/* ------------------------------------------------------------------ */
/* Inicio                                                              */
/* ------------------------------------------------------------------ */

$options = parseArguments($argv);

if ($options['help']) {
    showHelp();
    exit(EXIT_OK);
}

$resultsDir = $options['resultsDir'] !== null
    ? (string) $options['resultsDir']
    : $resultsDefault;

if (!is_dir($resultsDir) && !@mkdir($resultsDir, 0777, true) && !is_dir($resultsDir)) {
    fwrite(STDERR, "[ERROR] No se pudo crear el directorio de resultados: {$resultsDir}" . PHP_EOL);
    exit(EXIT_ENV);
}

if (!extension_loaded('curl')) {
    fwrite(STDERR, '[ERROR] La extensión curl de PHP no está habilitada.' . PHP_EOL);
    exit(EXIT_ENV);
}

if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, '[ERROR] Falta el driver pdo_mysql de PHP.' . PHP_EOL);
    exit(EXIT_ENV);
}

if (!is_file($serverEntry) || !is_file($serverRouter)) {
    fwrite(STDERR, '[ERROR] Faltan los archivos del servidor alfa en tests/tools/alfa/server/.' . PHP_EOL);
    exit(EXIT_ENV);
}

if (!is_file($seeder)) {
    fwrite(STDERR, '[ERROR] No se encontró el sembrador: ' . $seeder . PHP_EOL);
    exit(EXIT_ENV);
}

$stamp = date('Ymd-His');
$groups = alphaGroups($stamp);

if ($options['list']) {
    showGroups($groups);
    exit(EXIT_OK);
}

$selected = null;
if ($options['manual']) {
    $selected = manualSelection($groups);
    if ($selected === []) {
        fwrite(STDOUT, 'No se seleccionó ningún grupo.' . PHP_EOL);
        exit(EXIT_OK);
    }
} elseif ($options['selector'] !== null && trim((string) $options['selector']) !== '') {
    $selected = resolveSelection((string) $options['selector'], $groups);
} else {
    $selected = array_keys($groups);
}

$totalChecks = 0;
foreach ($selected as $key) {
    $totalChecks += count((array) $groups[$key]['checks']);
}

fwrite(STDOUT, PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' Sys Inescolara · Pruebas ALFA' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' PHP        : ' . PHP_VERSION . ' (' . PHP_BINARY . ')' . PHP_EOL);
fwrite(STDOUT, ' Proyecto   : ' . $root . PHP_EOL);
fwrite(STDOUT, ' Grupos     : ' . implode(', ', $selected) . ' (' . $totalChecks . ' casos)' . PHP_EOL);
fwrite(STDOUT, ' Resultados : ' . $resultsDir . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL . PHP_EOL);

if ($options['dry-run']) {
    foreach ($selected as $key) {
        $group = $groups[$key];
        fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);
        fwrite(STDOUT, '>> ' . $key . ' · ' . $group['label'] . PHP_EOL);
        fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);
        if (!$options['skipSeed']) {
            fwrite(STDOUT, '[DRY-RUN] Prepararía el usuario alfa_admin y arrancaría el servidor PHP embebido.' . PHP_EOL);
        } else {
            fwrite(STDOUT, '[DRY-RUN] Arrancaría el servidor PHP embebido sin sembrar el usuario.' . PHP_EOL);
        }
        foreach ((array) $group['checks'] as $check) {
            fwrite(STDOUT, sprintf(
                '  - %-6s %-55s espera HTTP %s%s' . PHP_EOL,
                (string) ($check['method'] ?? 'GET'),
                (string) $check['path'],
                implode('/', array_map('intval', (array) ($check['status'] ?? [200]))),
                (($check['auth'] ?? true) ? '' : ' (sin sesión)')
            ));
        }
        fwrite(STDOUT, PHP_EOL);
    }
    fwrite(STDOUT, 'Simulación completada: no se arrancó el servidor ni se modificó la base de datos.' . PHP_EOL);
    exit(EXIT_OK);
}

if (!$options['skipSeed']) {
    fwrite(STDOUT, '>>> Preparando usuario de pruebas alfa_admin' . PHP_EOL);
    $seedLog = $resultsDir . DIRECTORY_SEPARATOR . 'seed-alfa-' . $stamp . '.log';
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $seedProcess = @proc_open(
        phpChildCommand([$seeder]),
        $descriptors,
        $seedPipes,
        $root,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($seedProcess)) {
        fwrite(STDERR, '[ERROR] No se pudo ejecutar el sembrador de usuario.' . PHP_EOL);
        exit(EXIT_ENV);
    }

    $seedOut = stream_get_contents($seedPipes[1]) ?: '';
    $seedErr = stream_get_contents($seedPipes[2]) ?: '';
    fclose($seedPipes[1]);
    fclose($seedPipes[2]);
    $seedExit = proc_close($seedProcess);
    @file_put_contents($seedLog, $seedOut . $seedErr);

    if ($seedExit !== 0) {
        fwrite(STDERR, '[ERROR] No se pudo preparar el usuario de pruebas.' . PHP_EOL);
        fwrite(STDERR, trim($seedOut . PHP_EOL . $seedErr) . PHP_EOL);
        exit(EXIT_ENV);
    }
    fwrite(STDOUT, trim($seedOut) . PHP_EOL . PHP_EOL);
}

$port = $options['port'] !== null ? (int) $options['port'] : findFreePort();
$token = bin2hex(random_bytes(24));
$baseUrl = 'http://127.0.0.1:' . $port;

$server = startServer($root, $serverRouter, $port, $token, $resultsDir, $stamp);
$keepServer = (bool) $options['keepServer'];

if (!is_resource($server['process'])) {
    fwrite(STDERR, '[ERROR] No se pudo arrancar el servidor PHP embebido.' . PHP_EOL);
    exit(EXIT_ENV);
}

$GLOBALS['alfaServer'] = $server;
$GLOBALS['alfaKeepServer'] = $keepServer;
register_shutdown_function(static function (): void {
    if (($GLOBALS['alfaKeepServer'] ?? false) === true) {
        return;
    }
    if (isset($GLOBALS['alfaServer']) && is_array($GLOBALS['alfaServer'])) {
        stopServer($GLOBALS['alfaServer']);
    }
});

$cookieFile = $resultsDir . DIRECTORY_SEPARATOR . 'cookies-' . $stamp . '.txt';
$client = new AlfaHttpClient($baseUrl, $token, $cookieFile, (bool) $options['verbose']);

fwrite(STDOUT, 'Servidor alfa: ' . $baseUrl . ' (puerto ' . $port . ')' . PHP_EOL);

if (!waitForServer($server, $client, (int) $options['timeout'])) {
    fwrite(STDERR, '[ERROR] El servidor alfa no respondió en ' . $options['timeout'] . ' segundos.' . PHP_EOL);
    if (is_file($server['errorLog'])) {
        $tail = @file($server['errorLog'], FILE_IGNORE_NEW_LINES) ?: [];
        foreach (array_slice($tail, -20) as $line) {
            fwrite(STDERR, '  ' . $line . PHP_EOL);
        }
    }
    exit(EXIT_ENV);
}

fwrite(STDOUT, 'Sesión     : inyectada con la cabecera X-Alfa-Token' . PHP_EOL . PHP_EOL);

$summaryRows = [];
$exitCode = EXIT_OK;
$executed = 0;
$passed = 0;
$failed = 0;

foreach ($selected as $key) {
    $group = $groups[$key];

    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);
    fwrite(STDOUT, '>> ' . $key . ' · ' . $group['label'] . PHP_EOL);
    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);

    $cases = [];
    $logLines = [];
    $failures = 0;
    $started = microtime(true);

    foreach ((array) $group['checks'] as $check) {
        $case = runCheck($client, $check);
        $ok = $case['failure'] === null;
        if (!$ok) {
            $failures++;
        }

        $cases[] = [
            'name' => $case['name'],
            'time' => $case['time'],
            'failure' => $case['failure'],
            'assertions' => $case['assertions'],
        ];

        $logLines[] = sprintf(
            '[%s] %-6s %-55s | HTTP %d | %.0f ms',
            $ok ? 'PASS' : 'FALLO',
            (string) ($check['method'] ?? 'GET'),
            (string) $check['path'],
            $case['status'],
            $case['ms']
        );
        if (!$ok) {
            foreach (explode('; ', (string) $case['failure']) as $problem) {
                $logLines[] = '        -> ' . $problem;
            }
        }
        if ($options['verbose']) {
            $logLines[] = '        url: ' . $case['url'];
        }
    }

    $seconds = microtime(true) - $started;

    $logPath = $resultsDir . DIRECTORY_SEPARATOR . $key . '-' . $stamp . '.log';
    @file_put_contents($logPath, implode(PHP_EOL, $logLines) . PHP_EOL . PHP_EOL . sprintf(
        'Grupo: %s%sCasos: %d | Fallos: %d%s',
        $key,
        PHP_EOL,
        count($cases),
        $failures,
        PHP_EOL
    ));

    $junitPath = null;
    if ($options['junit']) {
        $junitPath = $resultsDir . DIRECTORY_SEPARATOR . 'junit-' . $key . '.xml';
        writeJunit($junitPath, 'Alfa\\' . ucfirst($key), $cases, $seconds, 'Alfa\\' . ucfirst($key) . 'Test');
    }

    foreach ($logLines as $line) {
        fwrite(STDOUT, $line . PHP_EOL);
    }

    $status = $failures === 0 ? 'PASS' : 'FALLO';
    if ($status === 'PASS') {
        $passed++;
    } else {
        $failed++;
        $exitCode = EXIT_FAIL;
    }
    $executed++;

    fwrite(STDOUT, sprintf(
        PHP_EOL . '[%s] casos=%d | fallos=%d | tiempo=%.2fs' . PHP_EOL,
        $status,
        count($cases),
        $failures,
        $seconds
    ));
    fwrite(STDOUT, '[log] ' . $logPath . PHP_EOL);
    if ($junitPath !== null) {
        fwrite(STDOUT, '[junit] ' . $junitPath . PHP_EOL);
    }
    fwrite(STDOUT, PHP_EOL);

    $summaryRows[] = [
        'key' => $key,
        'status' => $status,
        'tests' => count($cases),
        'assertions' => array_sum(array_column($cases, 'assertions')),
        'failures' => $failures,
        'errors' => 0,
        'skipped' => 0,
        'seconds' => $seconds,
        'log' => $logPath,
        'junit' => $junitPath,
    ];
}

if (!$keepServer) {
    stopServer($server);
    unset($GLOBALS['alfaServer']);
} else {
    fwrite(STDOUT, '[servidor] ' . $baseUrl . ' sigue en marcha (--keep-server).' . PHP_EOL . PHP_EOL);
}

fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' RESUMEN FINAL · PRUEBAS ALFA' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, sprintf(
    "%-14s %-10s %7s %12s %7s %7s %8s %9s\n",
    'Grupo',
    'Estado',
    'Casos',
    'Aserciones',
    'Fallos',
    'Errores',
    'Omitidos',
    'Tiempo'
));
fwrite(STDOUT, str_repeat('-', 96) . PHP_EOL);

$totals = ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0, 'time' => 0.0];
foreach ($summaryRows as $row) {
    $totals['tests'] += $row['tests'];
    $totals['assertions'] += $row['assertions'];
    $totals['failures'] += $row['failures'];
    $totals['errors'] += $row['errors'];
    $totals['skipped'] += $row['skipped'];
    $totals['time'] += $row['seconds'];

    fwrite(STDOUT, sprintf(
        "%-14s %-10s %7d %12d %7d %7d %8d %8.3fs\n",
        $row['key'],
        $row['status'],
        $row['tests'],
        $row['assertions'],
        $row['failures'],
        $row['errors'],
        $row['skipped'],
        $row['seconds']
    ));
}

fwrite(STDOUT, str_repeat('-', 96) . PHP_EOL);
fwrite(STDOUT, sprintf(
    "%-14s %-10s %7d %12d %7d %7d %8d %8.3fs\n",
    'TOTAL',
    $exitCode === EXIT_OK ? 'PASS' : 'FALLO',
    $totals['tests'],
    $totals['assertions'],
    $totals['failures'],
    $totals['errors'],
    $totals['skipped'],
    $totals['time']
));
fwrite(STDOUT, sprintf(
    'Ejecutados: %d | PASS: %d | FALLO: %d' . PHP_EOL,
    $executed,
    $passed,
    $failed
));
fwrite(STDOUT, '============================================================' . PHP_EOL);

exit($exitCode);
