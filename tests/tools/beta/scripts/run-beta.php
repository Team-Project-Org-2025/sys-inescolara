#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Ejecutor de pruebas BETA — Sys Inescolara.
 *
 * Las pruebas beta se ejecutan sobre un entorno real (staging o producción
 * controlada) y combinan verificación automática y validación manual:
 *
 *   humo       Comprueba disponibilidad, páginas públicas, login y seguridad.
 *   api        Ejecuta la colección Postman contra el entorno indicado.
 *   checklist  Recorrido manual guiado para testers (genera un acta .md).
 *
 * Uso:
 *   php tests/tools/beta/scripts/run-beta.php
 *   php tests/tools/beta/scripts/run-beta.php humo
 *   php tests/tools/beta/scripts/run-beta.php --test=humo --base-url=https://mi-app.onrender.com
 *   php tests/tools/beta/scripts/run-beta.php --test=api --usuario=admin
 *   php tests/tools/beta/scripts/run-beta.php --test=checklist
 *   php tests/tools/beta/scripts/run-beta.php --manual
 */

const EXIT_OK = 0;
const EXIT_FAIL = 1;
const EXIT_USAGE = 2;
const EXIT_ENV = 3;

$root = dirname(__DIR__, 4);
$resultsDefault = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'beta' . DIRECTORY_SEPARATOR . 'formatos' . DIRECTORY_SEPARATOR . 'resultados';
$checklistTemplate = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools'
    . DIRECTORY_SEPARATOR . 'beta' . DIRECTORY_SEPARATOR . 'formatos' . DIRECTORY_SEPARATOR . 'plantillas'
    . DIRECTORY_SEPARATOR . 'checklist-beta.plantilla.md';
$collection = $root . DIRECTORY_SEPARATOR . 'pruebas' . DIRECTORY_SEPARATOR . 'collections'
    . DIRECTORY_SEPARATOR . 'SYSINECOLARA API Tests.postman_collection.json';
$environmentFile = $root . DIRECTORY_SEPARATOR . 'pruebas' . DIRECTORY_SEPARATOR . 'environments'
    . DIRECTORY_SEPARATOR . 'SysInescolara Local.postman_environment.json';
$csvFile = $root . DIRECTORY_SEPARATOR . 'pruebas' . DIRECTORY_SEPARATOR . 'data'
    . DIRECTORY_SEPARATOR . 'plantas.csv';

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
        'baseUrl' => null,
        'usuario' => null,
        'password' => null,
        'csv' => false,
        'insecure' => false,
        'maxMs' => null,
        'noInteraction' => false,
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
            case '--base-url':
                $options['baseUrl'] = rtrim(trim($needValue()), '/');
                break;
            case '--usuario':
                $options['usuario'] = trim($needValue());
                break;
            case '--password':
                $options['password'] = $needValue();
                break;
            case '--csv':
                $options['csv'] = true;
                break;
            case '--insecure':
                $options['insecure'] = true;
                break;
            case '--max-ms':
                $value = $needValue();
                if (!ctype_digit($value) || (int) $value <= 0) {
                    fwrite(STDERR, '[ERROR] --max-ms debe ser un entero positivo.' . PHP_EOL);
                    exit(EXIT_USAGE);
                }
                $options['maxMs'] = (int) $value;
                break;
            case '--no-interaction':
                $options['noInteraction'] = true;
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
Pruebas BETA — Sys Inescolara
=============================

Las pruebas beta validan la aplicación en un entorno real (staging o producción
controlada). Agrupan tres pruebas independientes:

  humo       Disponibilidad, páginas públicas, login y rutas protegidas por HTTP.
  api        Colección Postman ejecutada contra el entorno indicado.
  checklist  Recorrido manual guiado para testers; genera un acta en Markdown.

Uso:
  php tests/tools/beta/scripts/run-beta.php [prueba|all|core] [opciones]

Selección:
  all                 Ejecuta humo + api (por defecto).
  core                Ejecuta solo humo.
  humo|api|checklist  Ejecuta una sola prueba.
  --test=NOMBRE       Equivalente a pasar el nombre como argumento.
  --manual            Menú interactivo para elegir qué pruebas ejecutar.
  --list              Lista las pruebas y sus requisitos.

Entorno:
  --base-url=URL      URL base del entorno beta. También BETA_BASE_URL.
  --usuario=USUARIO   Usuario para la colección Postman. También BETA_USUARIO.
  --password=CLAVE    Contraseña para la colección Postman. También BETA_PASSWORD.
  --csv               Envía plantas.csv a la colección Postman.
  --insecure          No verifica el certificado TLS (solo entornos de prueba).
  --max-ms=N          Falla si una página pública tarda más de N milisegundos.

Opciones:
  --dry-run           Muestra el plan sin ejecutarlo.
  --verbose           Muestra información adicional.
  --no-junit          No genera reportes JUnit XML.
  --results-dir=RUTA  Directorio de logs y XML.
  --no-interaction    Falla si una prueba requiere interacción.
  -h, --help          Muestra esta ayuda.

Ejemplos:
  php tests/tools/beta/scripts/run-beta.php --base-url=http://localhost/sys-inescolara
  php tests/tools/beta/scripts/run-beta.php humo --base-url=https://mi-app.onrender.com
  php tests/tools/beta/scripts/run-beta.php api --usuario=admin
  php tests/tools/beta/scripts/run-beta.php checklist
  php tests/tools/beta/scripts/run-beta.php --manual

En Linux/macOS también puede usar:
  ./tests/tools/beta/scripts/run-beta.sh

En Windows:
  tests\tools\beta\scripts\run-beta.bat

Códigos de salida:
  0  Todas las pruebas ejecutadas pasaron.
  1  Alguna prueba falló.
  2  Error de uso (opción o prueba no válida).
  3  Faltan requisitos del entorno (Postman CLI, credenciales, plantilla...).
TXT;

    fwrite(STDOUT, $help . PHP_EOL);
}

function normalize(string $value): string
{
    $value = strtr($value, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'ñ' => 'n', 'Ñ' => 'N',
    ]);

    return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', $value));
}

function findExecutable(string $name): ?string
{
    $isWindows = PHP_OS_FAMILY === 'Windows';
    $extensions = $isWindows ? ['.exe', '.cmd', '.bat', ''] : [''];

    foreach ($extensions as $extension) {
        $candidate = $name . $extension;
        $output = [];
        $exit = 0;

        if ($isWindows) {
            @exec('where ' . escapeshellarg($candidate) . ' 2>NUL', $output, $exit);
        } else {
            @exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null', $output, $exit);
        }

        if ($exit === 0 && isset($output[0]) && trim($output[0]) !== '') {
            return trim($output[0]);
        }
    }

    return null;
}

/**
 * @param list<string>          $command
 * @param array<string, string> $environment
 * @return array{exit:int,stdout:string,stderr:string,seconds:float}
 */
function execute(array $command, ?string $logFile = null, bool $stream = true, array $environment = []): array
{
    $started = microtime(true);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $env = null;
    if ($environment !== []) {
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        foreach ($environment as $key => $value) {
            $env[$key] = $value;
        }
    }

    $process = @proc_open($command, $descriptors, $pipes, null, $env, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        return [
            'exit' => EXIT_ENV,
            'stdout' => '',
            'stderr' => 'No se pudo iniciar el proceso: ' . implode(' ', $command),
            'seconds' => microtime(true) - $started,
        ];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $log = null;
    if ($logFile !== null) {
        $dir = dirname($logFile);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            fwrite(STDERR, "[ERROR] No se pudo crear el directorio {$dir}" . PHP_EOL);
        } else {
            $log = @fopen($logFile, 'wb');
            if ($log !== false) {
                fwrite($log, '$ ' . displayCommand($command) . PHP_EOL . PHP_EOL);
            }
        }
    }

    $stdout = '';
    $stderr = '';

    while (!feof($pipes[1]) || !feof($pipes[2])) {
        $read = [];
        if (!feof($pipes[1])) {
            $read[] = $pipes[1];
        }
        if (!feof($pipes[2])) {
            $read[] = $pipes[2];
        }
        if ($read === []) {
            break;
        }

        $write = null;
        $except = null;
        $selected = @stream_select($read, $write, $except, 0, 200000);
        if ($selected === false) {
            break;
        }
        if ($selected === 0) {
            continue;
        }

        foreach ($read as $streamPipe) {
            $chunk = fread($streamPipe, 8192);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            if ($streamPipe === $pipes[2]) {
                $stderr .= $chunk;
            } else {
                $stdout .= $chunk;
            }
            if ($stream) {
                fwrite(STDOUT, $chunk);
            }
            if (is_resource($log)) {
                fwrite($log, $chunk);
            }
        }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $seconds = microtime(true) - $started;

    if (is_resource($log)) {
        fwrite($log, PHP_EOL . '[código de salida: ' . $exit . ']' . PHP_EOL);
        fclose($log);
    }

    return [
        'exit' => $exit,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'seconds' => $seconds,
    ];
}

/**
 * @param list<string> $command
 */
function displayCommand(array $command): string
{
    return implode(' ', array_map(
        static function (string $part): string {
            if (stripos($part, 'password=') === 0) {
                return 'password=***';
            }
            return preg_match('/^[A-Za-z0-9_\.\-\/:=]+$/', $part) === 1
                ? $part
                : escapeshellarg($part);
        },
        $command
    ));
}

/**
 * @param list<string> $secrets
 */
function redact(string $text, array $secrets): string
{
    foreach ($secrets as $secret) {
        if ($secret !== '') {
            $text = str_replace($secret, '***', $text);
        }
    }

    return $text;
}

/**
 * @param list<string> $secrets
 */
function redactFile(string $file, array $secrets): void
{
    if (!is_file($file)) {
        return;
    }

    $contents = @file_get_contents($file);
    if ($contents === false) {
        return;
    }

    $redacted = redact($contents, $secrets);
    if ($redacted !== $contents) {
        @file_put_contents($file, $redacted);
    }
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

function xmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * @param list<array{name:string,time:float,failure:?string,assertions:int}> $cases
 */
function writeJunit(string $file, string $suiteName, array $cases, float $time, string $class = 'Beta\\Prueba'): void
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

    foreach ($cases as $index => $case) {
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
 * Ejecuta una petición HTTP y devuelve estado, cabeceras y cuerpo.
 *
 * @param list<string> $headers
 * @return array{status:int,headers:array<string,string>,body:string,ms:float,error:?string}
 */
function httpGet(string $url, array $headers = [], bool $follow = true, bool $insecure = false, int $timeout = 20): array
{
    if (!extension_loaded('curl')) {
        return [
            'status' => 0,
            'headers' => [],
            'body' => '',
            'ms' => 0.0,
            'error' => 'la extensión curl de PHP no está habilitada',
        ];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['status' => 0, 'headers' => [], 'body' => '', 'ms' => 0.0, 'error' => 'no se pudo iniciar curl'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => !$insecure,
        CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
        CURLOPT_USERAGENT => 'SysInescolara-Beta/1.0',
        CURLOPT_HTTPHEADER => array_merge(['Accept: text/html,application/json;q=0.9,*/*;q=0.8'], $headers),
    ]);

    $started = microtime(true);
    $raw = curl_exec($ch);
    $ms = (microtime(true) - $started) * 1000;
    $error = $raw === false ? (curl_error($ch) ?: 'error de conexión') : null;
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($raw === false) {
        return ['status' => 0, 'headers' => [], 'body' => '', 'ms' => $ms, 'error' => $error];
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
    ];
}

/**
 * Prueba de humo HTTP contra el entorno beta.
 *
 * @return array{exit:int,cases:int,failures:int,seconds:float,log:string,junit:?string}
 */
function runHumo(
    string $baseUrl,
    string $resultsDir,
    bool $junit,
    bool $insecure,
    ?int $maxMs,
    string $stamp,
    bool $verbose = false
): array {
    $unique = 'ruta-inexistente-beta-' . date('YmdHis');
    $checks = [
        [
            'name' => 'Inicio público',
            'path' => '/',
            'status' => [200],
            'contains' => ['Vivero Institucional INECOLARA'],
        ],
        [
            'name' => 'Catálogo público',
            'path' => '/catalogo',
            'status' => [200],
            'contains' => ['Especies disponibles para reforestación'],
        ],
        [
            'name' => 'Servicios públicos',
            'path' => '/servicios',
            'status' => [200],
            'contains' => ['Producción y suministro para programas ambientales'],
        ],
        [
            'name' => 'Nosotros',
            'path' => '/nosotros',
            'status' => [200],
            'contains' => ['Instituto de Ecosocialismo del Estado Lara'],
        ],
        [
            'name' => 'Contacto',
            'path' => '/contacto',
            'status' => [200],
            'contains' => ['Estamos para servirte'],
        ],
        [
            'name' => 'Formulario de login',
            'path' => '/login',
            'status' => [200],
            'contains' => ['name="password"', 'g-recaptcha'],
        ],
        [
            'name' => 'Dashboard protegido redirige al login',
            'path' => '/dashboard',
            'status' => [301, 302, 303, 307, 308],
            'locationContains' => 'login',
            'follow' => false,
        ],
        [
            'name' => 'Endpoint AJAX protegido responde 401',
            'path' => '/plantas/get_plants',
            'status' => [401],
            'contains' => ['"success":false'],
            'headers' => ['X-Requested-With: XMLHttpRequest', 'Accept: application/json'],
        ],
        [
            'name' => 'Ruta inexistente responde 404',
            'path' => '/' . $unique,
            'status' => [404],
        ],
    ];

    $cases = [];
    $logLines = [];
    $failures = 0;
    $started = microtime(true);

    foreach ($checks as $check) {
        $url = rtrim($baseUrl, '/') . '/' . ltrim((string) $check['path'], '/');
        $response = httpGet(
            $url,
            $check['headers'] ?? [],
            (bool) ($check['follow'] ?? true),
            $insecure
        );

        $problems = [];

        if ($response['error'] !== null) {
            $problems[] = 'Error de conexión: ' . $response['error'];
        }

        if (!in_array($response['status'], (array) $check['status'], true)) {
            $problems[] = sprintf(
                'Se esperaba HTTP %s y se recibió %d',
                implode('/', (array) $check['status']),
                $response['status']
            );
        }

        foreach ((array) ($check['contains'] ?? []) as $needle) {
            if (!str_contains($response['body'], (string) $needle)) {
                $problems[] = 'No se encontró en el HTML: ' . $needle;
            }
        }

        if (isset($check['locationContains'])) {
            $location = $response['headers']['location'] ?? '';
            if (!str_contains(strtolower($location), strtolower((string) $check['locationContains']))) {
                $problems[] = 'La cabecera Location no apunta a ' . $check['locationContains'] . ' (recibido: ' . ($location ?: 'vacía') . ')';
            }
        }

        if ($maxMs !== null && $response['ms'] > $maxMs) {
            $problems[] = sprintf('Tiempo de respuesta %.0f ms supera el máximo de %d ms', $response['ms'], $maxMs);
        }

        $ok = $problems === [];
        if (!$ok) {
            $failures++;
        }

        $cases[] = [
            'name' => $check['name'],
            'time' => $response['ms'] / 1000,
            'failure' => $ok ? null : implode('; ', $problems),
            'assertions' => 1,
        ];

        $logLines[] = sprintf(
            '[%s] %s | HTTP %d | %.0f ms | %s',
            $ok ? 'PASS' : 'FALLO',
            $check['name'],
            $response['status'],
            $response['ms'],
            $url
        );
        foreach ($problems as $problem) {
            $logLines[] = '        -> ' . $problem;
        }
        if ($verbose) {
            $logLines[] = sprintf(
                '        content-type: %s | bytes: %d',
                $response['headers']['content-type'] ?? 'no indicado',
                strlen($response['body'])
            );
        }
    }

    $seconds = microtime(true) - $started;
    $logPath = $resultsDir . DIRECTORY_SEPARATOR . 'humo-' . $stamp . '.log';
    @file_put_contents($logPath, implode(PHP_EOL, $logLines) . PHP_EOL . PHP_EOL . sprintf(
        'Entorno: %s%sCasos: %d | Fallos: %d%s',
        $baseUrl,
        PHP_EOL,
        count($cases),
        $failures,
        PHP_EOL
    ));

    $junitPath = null;
    if ($junit) {
        $junitPath = $resultsDir . DIRECTORY_SEPARATOR . 'junit-humo.xml';
        writeJunit($junitPath, 'Beta\\Humo', $cases, $seconds, 'Beta\\HumoTest');
    }

    foreach ($logLines as $line) {
        fwrite(STDOUT, $line . PHP_EOL);
    }

    return [
        'exit' => $failures === 0 ? EXIT_OK : EXIT_FAIL,
        'cases' => count($cases),
        'failures' => $failures,
        'seconds' => $seconds,
        'log' => $logPath,
        'junit' => $junitPath,
    ];
}

/**
 * Ejecuta la colección Postman y traduce su reporte JSON a JUnit.
 *
 * @return array{exit:int,cases:int,failures:int,seconds:float,log:string,junit:?string}
 */
function runApi(
    string $postman,
    string $collection,
    string $environmentFile,
    string $baseUrl,
    string $usuario,
    string $password,
    bool $csv,
    string $csvFile,
    string $resultsDir,
    bool $junit,
    string $stamp
): array {
    $jsonReport = $resultsDir . DIRECTORY_SEPARATOR . 'postman-' . $stamp . '.json';
    $logPath = $resultsDir . DIRECTORY_SEPARATOR . 'api-' . $stamp . '.log';
    $junitPath = $resultsDir . DIRECTORY_SEPARATOR . 'junit-api.xml';

    $command = [$postman, 'collection', 'run', $collection, '-e', $environmentFile];
    $command[] = '--env-var';
    $command[] = 'baseUrl=' . $baseUrl;
    $command[] = '--env-var';
    $command[] = 'usuario=' . $usuario;
    $command[] = '--env-var';
    $command[] = 'password=' . $password;
    $command[] = '--reporters';
    $command[] = 'cli,json';
    $command[] = '--reporter-json-export';
    $command[] = $jsonReport;

    if ($csv && is_file($csvFile)) {
        $command[] = '-d';
        $command[] = $csvFile;
    }

    if (PHP_OS_FAMILY === 'Windows' && preg_match('/\.(cmd|bat)$/i', $postman) === 1) {
        $command = array_merge(['cmd', '/c'], $command);
    }

    fwrite(STDOUT, 'Comando: ' . displayCommand($command) . PHP_EOL . PHP_EOL);

    $result = execute($command, $logPath, false);
    $secrets = [$password];

    $safeOut = redact($result['stdout'], $secrets);
    $safeErr = redact($result['stderr'], $secrets);
    if ($safeOut !== '') {
        fwrite(STDOUT, $safeOut);
        if (!str_ends_with($safeOut, PHP_EOL)) {
            fwrite(STDOUT, PHP_EOL);
        }
    }
    if ($safeErr !== '') {
        fwrite(STDERR, $safeErr);
        if (!str_ends_with($safeErr, PHP_EOL)) {
            fwrite(STDERR, PHP_EOL);
        }
    }

    $cases = postmanCases($jsonReport, $result['exit']);
    $seconds = $result['seconds'];

    if ($junit) {
        writeJunit($junitPath, 'Beta\\API', $cases, $seconds, 'Beta\\ApiTest');
    } else {
        $junitPath = null;
    }

    // El reporte JSON puede contener cuerpos de petición con credenciales.
    if (is_file($jsonReport)) {
        @unlink($jsonReport);
    }
    redactFile($logPath, $secrets);

    $failures = 0;
    foreach ($cases as $case) {
        if ($case['failure'] !== null) {
            $failures++;
        }
    }

    return [
        'exit' => $result['exit'],
        'cases' => count($cases),
        'failures' => $failures,
        'seconds' => $seconds,
        'log' => $logPath,
        'junit' => $junitPath,
    ];
}

/**
 * @return list<array{name:string,time:float,failure:?string,assertions:int}>
 */
function postmanCases(string $jsonReport, int $exitCode): array
{
    $fallback = [[
        'name' => 'Colección Postman',
        'time' => 0.0,
        'failure' => $exitCode === 0 ? null : 'La colección terminó con código ' . $exitCode,
        'assertions' => 1,
    ]];

    if (!is_file($jsonReport)) {
        return $fallback;
    }

    $contents = @file_get_contents($jsonReport);
    if ($contents === false) {
        return $fallback;
    }

    $data = json_decode($contents, true);
    if (!is_array($data) || !isset($data['run']) || !is_array($data['run'])) {
        return $fallback;
    }

    $executions = $data['run']['executions'] ?? null;
    if (!is_array($executions) || $executions === []) {
        return $fallback;
    }

    $cases = [];
    foreach ($executions as $execution) {
        if (!is_array($execution)) {
            continue;
        }

        $name = (string) ($execution['item']['name'] ?? 'Solicitud');
        $assertions = is_array($execution['assertions'] ?? null) ? $execution['assertions'] : [];
        $problems = [];
        foreach ($assertions as $assertion) {
            if (is_array($assertion) && !empty($assertion['error'])) {
                $problems[] = (string) $assertion['error'];
            }
        }

        $responseCode = $execution['response']['code'] ?? null;
        if ($responseCode === null && $problems === [] && $exitCode !== 0) {
            // Sin detalle de aserciones: el código global decide.
            continue;
        }

        $cases[] = [
            'name' => $name,
            'time' => 0.0,
            'failure' => $problems === [] ? null : implode('; ', $problems),
            'assertions' => max(1, count($assertions)),
        ];
    }

    if ($cases === []) {
        return $fallback;
    }

    if ($exitCode !== 0) {
        $hasFailure = false;
        foreach ($cases as $case) {
            if ($case['failure'] !== null) {
                $hasFailure = true;
                break;
            }
        }
        if (!$hasFailure) {
            $cases[] = [
                'name' => 'Resultado global de la colección',
                'time' => 0.0,
                'failure' => 'La colección terminó con código ' . $exitCode,
                'assertions' => 1,
            ];
        }
    }

    return $cases;
}

/**
 * @return string|null Texto leído o null si se cerró la entrada.
 */
function askLine(string $prompt, ?string $default = null): ?string
{
    fwrite(STDOUT, $prompt);
    $line = fgets(STDIN);
    if ($line === false) {
        return null;
    }

    $line = trim($line);
    if ($line === '' && $default !== null) {
        return $default;
    }

    return $line;
}

/**
 * Prueba manual guiada: llena la plantilla de checklist y genera un acta.
 *
 * @return array{exit:int,cases:int,failures:int,seconds:float,log:string,junit:?string}
 */
function runChecklist(
    string $template,
    string $resultsDir,
    bool $junit,
    bool $noInteraction,
    string $baseUrl,
    string $stamp
): array {
    $started = microtime(true);

    if ($noInteraction) {
        fwrite(STDERR, '[ERROR] La prueba checklist es interactiva; quite --no-interaction.' . PHP_EOL);
        return [
            'exit' => EXIT_USAGE,
            'cases' => 0,
            'failures' => 0,
            'seconds' => 0.0,
            'log' => $template,
            'junit' => null,
        ];
    }

    $contents = @file_get_contents($template);
    if ($contents === false) {
        fwrite(STDERR, '[ERROR] No se pudo leer la plantilla: ' . $template . PHP_EOL);
        return [
            'exit' => EXIT_ENV,
            'cases' => 0,
            'failures' => 0,
            'seconds' => 0.0,
            'log' => $template,
            'junit' => null,
        ];
    }

    $scenarios = [];
    foreach (preg_split('/\R/', $contents) ?: [] as $line) {
        if (preg_match('/^\|\s*(\d+)\s*\|\s*(.+?)\s*\|\s*\{\{R\1\}\}\s*\|\s*\{\{O\1\}\}\s*\|/', $line, $matches) === 1) {
            $scenarios[(int) $matches[1]] = trim($matches[2]);
        }
    }

    if ($scenarios === []) {
        fwrite(STDERR, '[ERROR] La plantilla no contiene escenarios con {{R1}}, {{O1}}, ...' . PHP_EOL);
        return [
            'exit' => EXIT_ENV,
            'cases' => 0,
            'failures' => 0,
            'seconds' => 0.0,
            'log' => $template,
            'junit' => null,
        ];
    }

    $user = getenv('USER') ?: getenv('USERNAME') ?: 'Tester';
    $meta = [
        'FECHA' => date('Y-m-d H:i'),
        'TESTER' => (string) (askLine('Tester [' . $user . ']: ', $user) ?? $user),
        'ENTORNO' => (string) (askLine('Entorno [' . $baseUrl . ']: ', $baseUrl) ?? $baseUrl),
        'NAVEGADOR' => (string) (askLine('Navegador [Chrome]: ', 'Chrome') ?? 'Chrome'),
        'VERSION' => (string) (askLine('Versión de la aplicación [No indicada]: ', 'No indicada') ?? 'No indicada'),
    ];

    $answers = [];
    $failures = 0;

    foreach ($scenarios as $number => $name) {
        fwrite(STDOUT, PHP_EOL . sprintf('[%d/%d] %s%s', $number, count($scenarios), $name, PHP_EOL));

        $result = null;
        while ($result === null) {
            $answer = askLine('  Resultado [P]asa / [F]alla / [N]o aplica (P): ', 'P');
            if ($answer === null) {
                fwrite(STDERR, PHP_EOL . '[ERROR] Entrada cerrada antes de terminar el checklist.' . PHP_EOL);
                return [
                    'exit' => EXIT_ENV,
                    'cases' => 0,
                    'failures' => 0,
                    'seconds' => microtime(true) - $started,
                    'log' => $template,
                    'junit' => null,
                ];
            }

            $normalized = normalize($answer);
            if (in_array($normalized, ['p', 'pa', 'pasa', 'pass'], true)) {
                $result = 'P';
            } elseif (in_array($normalized, ['f', 'fa', 'falla', 'fail'], true)) {
                $result = 'F';
            } elseif (in_array($normalized, ['n', 'na', 'noaplica'], true)) {
                $result = 'N/A';
            } else {
                fwrite(STDOUT, '  Respuesta no válida. Use P, F o N.' . PHP_EOL);
            }
        }

        $observation = (string) (askLine('  Observaciones (opcional): ', '') ?? '');

        if ($result === 'F') {
            $failures++;
        }

        $answers[$number] = [
            'result' => $result,
            'observation' => str_replace(['|', "\r", "\n"], ['\\|', ' ', ' '], $observation),
        ];
    }

    $general = (string) (askLine(PHP_EOL . 'Observaciones generales (opcional): ', '') ?? '');
    if (trim($general) === '') {
        $general = 'Sin observaciones generales.';
    }

    $filled = $contents;
    foreach ($meta as $key => $value) {
        $filled = str_replace('{{' . $key . '}}', $value, $filled);
    }
    $filled = str_replace('{{OBSERVACIONES}}', $general, $filled);

    foreach ($answers as $number => $answer) {
        $filled = str_replace('{{R' . $number . '}}', $answer['result'], $filled);
        $filled = str_replace('{{O' . $number . '}}', $answer['observation'], $filled);
    }

    $output = $resultsDir . DIRECTORY_SEPARATOR . 'checklist-beta-' . $stamp . '.md';
    @file_put_contents($output, $filled);

    $cases = [];
    foreach ($answers as $number => $answer) {
        $cases[] = [
            'name' => sprintf('%d. %s', $number, $scenarios[$number]),
            'time' => 0.0,
            'failure' => $answer['result'] === 'F'
                ? ('Falló en la prueba manual' . ($answer['observation'] !== '' ? ': ' . $answer['observation'] : ''))
                : null,
            'assertions' => 1,
        ];
    }

    $junitPath = null;
    if ($junit) {
        $junitPath = $resultsDir . DIRECTORY_SEPARATOR . 'junit-checklist.xml';
        writeJunit($junitPath, 'Beta\\Checklist', $cases, microtime(true) - $started, 'Beta\\ChecklistTest');
    }

    return [
        'exit' => $failures === 0 ? EXIT_OK : EXIT_FAIL,
        'cases' => count($answers),
        'failures' => $failures,
        'seconds' => microtime(true) - $started,
        'log' => $output,
        'junit' => $junitPath,
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

$baseUrl = $options['baseUrl'] !== null
    ? (string) $options['baseUrl']
    : (string) (getenv('BETA_BASE_URL') ?: 'http://localhost/sys-inescolara');
$baseUrl = rtrim($baseUrl, '/');

$maxMs = $options['maxMs'] !== null ? (int) $options['maxMs'] : null;
if ($maxMs === null) {
    $envMaxMs = getenv('BETA_MAX_MS');
    if ($envMaxMs !== false && ctype_digit((string) $envMaxMs) && (int) $envMaxMs > 0) {
        $maxMs = (int) $envMaxMs;
    }
}

$usuario = $options['usuario'] !== null
    ? (string) $options['usuario']
    : (string) (getenv('BETA_USUARIO') ?: getenv('POSTMAN_USUARIO') ?: 'admin');
$password = $options['password'] !== null
    ? (string) $options['password']
    : (string) (getenv('BETA_PASSWORD') ?: getenv('POSTMAN_PASSWORD') ?: '');
$postman = findExecutable('postman');

$tests = [];
$tests['humo'] = [
    'label' => 'Humo HTTP',
    'description' => 'Disponibilidad, páginas públicas, login y rutas protegidas.',
    'auto' => true,
    'core' => true,
];
$tests['api'] = [
    'label' => 'API (Postman)',
    'description' => 'Colección Postman contra el entorno beta.',
    'auto' => true,
    'core' => false,
    'requires' => static function () use ($postman, $collection, $environmentFile, $password): ?string {
        if ($postman === null) {
            return 'no se encontró Postman CLI (postman --version)';
        }
        if (!is_file($collection)) {
            return 'no existe la colección ' . $collection;
        }
        if (!is_file($environmentFile)) {
            return 'no existe el environment ' . $environmentFile;
        }
        if ($password === '') {
            return 'faltan credenciales (defina BETA_PASSWORD o use --password)';
        }
        return null;
    },
];
$tests['checklist'] = [
    'label' => 'Checklist manual',
    'description' => 'Recorrido guiado para testers; genera un acta en Markdown.',
    'auto' => false,
    'core' => false,
    'requires' => static function () use ($checklistTemplate): ?string {
        return is_file($checklistTemplate)
            ? null
            : 'no existe la plantilla ' . $checklistTemplate;
    },
];

if ($options['list']) {
    fwrite(STDOUT, sprintf("%-12s %-20s %-8s %-8s %s\n", 'Prueba', 'Nombre', 'Automática', 'Núcleo', 'Descripción'));
    fwrite(STDOUT, str_repeat('-', 110) . PHP_EOL);
    foreach ($tests as $key => $test) {
        fwrite(STDOUT, sprintf(
            "%-12s %-20s %-8s %-8s %s\n",
            $key,
            $test['label'],
            $test['auto'] ? 'sí' : 'no',
            $test['core'] ? 'sí' : 'no',
            $test['description']
        ));
    }
    fwrite(STDOUT, PHP_EOL . 'Entorno beta: ' . $baseUrl . PHP_EOL);
    exit(EXIT_OK);
}

function resolveBetaSelection(string $selector, array $tests): array
{
    $normalized = normalize($selector);

    if (in_array($normalized, ['all', 'todo', 'todas', 'todos', '*'], true)) {
        return array_keys(array_filter($tests, static fn (array $test): bool => $test['auto'] === true));
    }

    if (in_array($normalized, ['core', 'nucleo', 'basico'], true)) {
        return array_keys(array_filter($tests, static fn (array $test): bool => $test['core'] === true));
    }

    $aliases = [
        'humo' => 'humo',
        'smoke' => 'humo',
        'http' => 'humo',
        'api' => 'api',
        'postman' => 'api',
        'checklist' => 'checklist',
        'manual' => 'checklist',
    ];

    if (isset($aliases[$normalized], $tests[$aliases[$normalized]])) {
        return [$aliases[$normalized]];
    }

    fwrite(STDERR, "[ERROR] Prueba beta no encontrada: {$selector}" . PHP_EOL);
    fwrite(STDERR, 'Use --list para ver las pruebas disponibles.' . PHP_EOL);
    exit(EXIT_USAGE);
}

function manualBetaSelection(array $tests): array
{
    $keys = array_keys($tests);

    fwrite(STDOUT, PHP_EOL . 'Pruebas beta disponibles:' . PHP_EOL);
    foreach ($keys as $index => $key) {
        $test = $tests[$key];
        fwrite(STDOUT, sprintf(
            "  %d) %-12s %-20s %s\n",
            $index + 1,
            $key,
            $test['label'],
            $test['description']
        ));
    }

    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Seleccione una o varias (ej. 1,3), "a" para todas, "c" para el núcleo' . PHP_EOL);
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
        return array_keys(array_filter($tests, static fn (array $test): bool => $test['core'] === true));
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

$selected = null;
if ($options['manual']) {
    $selected = manualBetaSelection($tests);
    if ($selected === []) {
        fwrite(STDOUT, 'No se seleccionó ninguna prueba.' . PHP_EOL);
        exit(EXIT_OK);
    }
} elseif ($options['selector'] !== null && trim((string) $options['selector']) !== '') {
    $selected = resolveBetaSelection((string) $options['selector'], $tests);
} else {
    $selected = array_keys(array_filter($tests, static fn (array $test): bool => $test['auto'] === true));
}

$stamp = date('Ymd-His');

fwrite(STDOUT, PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' Sys Inescolara · Pruebas BETA' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' PHP        : ' . PHP_VERSION . ' (' . PHP_BINARY . ')' . PHP_EOL);
fwrite(STDOUT, ' Proyecto   : ' . $root . PHP_EOL);
fwrite(STDOUT, ' Entorno    : ' . $baseUrl . PHP_EOL);
fwrite(STDOUT, ' Usuario    : ' . $usuario . ($password !== '' ? ' (con contraseña)' : ' (sin contraseña)') . PHP_EOL);
fwrite(STDOUT, ' Pruebas    : ' . implode(', ', $selected) . PHP_EOL);
fwrite(STDOUT, ' Resultados : ' . $resultsDir . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL . PHP_EOL);

$summaryRows = [];
$exitCode = EXIT_OK;
$executed = 0;
$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($selected as $key) {
    $test = $tests[$key];

    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);
    fwrite(STDOUT, '>> ' . $key . ' · ' . $test['label'] . PHP_EOL);
    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);

    $reason = null;
    if (isset($test['requires']) && is_callable($test['requires'])) {
        $reason = $test['requires']();
    }

    if ($options['dry-run']) {
        if ($reason !== null) {
            fwrite(STDOUT, '[AVISO] Requisito pendiente: ' . $reason . PHP_EOL);
        }
        if ($key === 'humo') {
            fwrite(STDOUT, '[DRY-RUN] GETs HTTP contra ' . $baseUrl . ' (inicio, catálogo, servicios, nosotros, contacto, login, dashboard, AJAX, 404)' . PHP_EOL . PHP_EOL);
        } elseif ($key === 'api') {
            $command = [
                $postman ?? 'postman',
                'collection',
                'run',
                $collection,
                '-e',
                $environmentFile,
                '--env-var',
                'baseUrl=' . $baseUrl,
                '--env-var',
                'usuario=' . $usuario,
                '--env-var',
                'password=***',
            ];
            fwrite(STDOUT, '[DRY-RUN] ' . displayCommand($command) . PHP_EOL . PHP_EOL);
        } else {
            fwrite(STDOUT, '[DRY-RUN] Checklist interactivo sobre ' . $checklistTemplate . PHP_EOL . PHP_EOL);
        }

        $summaryRows[] = [
            'key' => $key,
            'status' => 'DRY-RUN',
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'seconds' => 0.0,
            'log' => null,
            'junit' => null,
        ];
        continue;
    }

    if ($reason !== null) {
        fwrite(STDOUT, '[OMITIDA] ' . $reason . PHP_EOL . PHP_EOL);
        $summaryRows[] = [
            'key' => $key,
            'status' => 'OMITIDA',
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'seconds' => 0.0,
            'log' => null,
            'junit' => null,
        ];
        $skipped++;
        $exitCode = EXIT_ENV;
        continue;
    }

    if ($key === 'humo') {
        $outcome = runHumo(
            $baseUrl,
            $resultsDir,
            (bool) $options['junit'],
            (bool) $options['insecure'],
            $options['maxMs'] !== null ? (int) $options['maxMs'] : $maxMs,
            $stamp,
            (bool) $options['verbose']
        );
    } elseif ($key === 'api') {
        $outcome = runApi(
            (string) $postman,
            $collection,
            $environmentFile,
            $baseUrl,
            $usuario,
            $password,
            (bool) $options['csv'],
            $csvFile,
            $resultsDir,
            (bool) $options['junit'],
            $stamp
        );
    } else {
        $outcome = runChecklist(
            $checklistTemplate,
            $resultsDir,
            (bool) $options['junit'],
            (bool) $options['noInteraction'],
            $baseUrl,
            $stamp
        );
    }

    $junit = $outcome['junit'] !== null ? junitSummary((string) $outcome['junit']) : null;

    $status = ($outcome['exit'] === 0 && $outcome['failures'] === 0) ? 'PASS' : 'FALLO';
    if ($outcome['exit'] === EXIT_USAGE || $outcome['exit'] === EXIT_ENV) {
        $status = 'OMITIDA';
        $skipped++;
        $exitCode = EXIT_ENV;
    } elseif ($status === 'PASS') {
        $passed++;
        $executed++;
    } else {
        $failed++;
        $executed++;
        $exitCode = EXIT_FAIL;
    }

    fwrite(STDOUT, sprintf(
        PHP_EOL . '[%s] casos=%d | fallos=%d | tiempo=%.2fs' . PHP_EOL,
        $status,
        $outcome['cases'],
        $outcome['failures'],
        $outcome['seconds']
    ));
    if ($key === 'checklist' && $outcome['exit'] !== EXIT_USAGE && $outcome['exit'] !== EXIT_ENV) {
        fwrite(STDOUT, '[acta] ' . $outcome['log'] . PHP_EOL);
    } else {
        fwrite(STDOUT, '[log] ' . $outcome['log'] . PHP_EOL);
    }
    if ($outcome['junit'] !== null) {
        fwrite(STDOUT, '[junit] ' . $outcome['junit'] . PHP_EOL);
    }
    fwrite(STDOUT, PHP_EOL);

    $summaryRows[] = [
        'key' => $key,
        'status' => $status,
        'tests' => $junit['tests'] ?? $outcome['cases'],
        'assertions' => $junit['assertions'] ?? $outcome['cases'],
        'failures' => $junit['failures'] ?? $outcome['failures'],
        'errors' => $junit['errors'] ?? 0,
        'skipped' => $junit['skipped'] ?? 0,
        'seconds' => $outcome['seconds'],
        'log' => $outcome['log'],
        'junit' => $outcome['junit'],
    ];
}

fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' RESUMEN FINAL · PRUEBAS BETA' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, sprintf(
    "%-18s %-10s %7s %12s %7s %7s %8s %9s\n",
    'Prueba',
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
    if (in_array($row['status'], ['OMITIDA', 'DRY-RUN'], true)) {
        fwrite(STDOUT, sprintf(
            "%-18s %-10s %7s %12s %7s %7s %8s %9s\n",
            $row['key'],
            $row['status'],
            '-',
            '-',
            '-',
            '-',
            '-',
            '-'
        ));
        continue;
    }

    $totals['tests'] += $row['tests'];
    $totals['assertions'] += $row['assertions'];
    $totals['failures'] += $row['failures'];
    $totals['errors'] += $row['errors'];
    $totals['skipped'] += $row['skipped'];
    $totals['time'] += $row['seconds'];

    fwrite(STDOUT, sprintf(
        "%-18s %-10s %7d %12d %7d %7d %8d %8.3fs\n",
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
    "%-18s %-10s %7d %12d %7d %7d %8d %8.3fs\n",
    'TOTAL',
    $exitCode === EXIT_OK ? 'PASS' : ($exitCode === EXIT_ENV ? 'REVISAR' : 'FALLO'),
    $totals['tests'],
    $totals['assertions'],
    $totals['failures'],
    $totals['errors'],
    $totals['skipped'],
    $totals['time']
));
fwrite(STDOUT, sprintf(
    'Ejecutadas: %d | PASS: %d | FALLO: %d | OMITIDAS: %d' . PHP_EOL,
    $executed,
    $passed,
    $failed,
    $skipped
));
fwrite(STDOUT, '============================================================' . PHP_EOL);

exit($exitCode);
