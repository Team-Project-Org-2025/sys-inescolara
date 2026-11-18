#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Ejecutor multiplataforma de las suites de integración y humo.
 *
 * Uso:
 *   php tests/tools/phpunit/scripts/lib/run-tests.php
 *   php tests/tools/phpunit/scripts/lib/run-tests.php --suite=integration
 *   php tests/tools/phpunit/scripts/lib/run-tests.php --module=13
 *   php tests/tools/phpunit/scripts/lib/run-tests.php --module=Venta --suite=smoke
 *
 * Funciona igual en Linux, macOS y Windows porque invoca PHP y PHPUnit
 * mediante rutas absolutas y argumentos en forma de arreglo (sin shell).
 */

const EXIT_OK = 0;
const EXIT_FAIL = 1;
const EXIT_USAGE = 2;
const EXIT_ENV = 3;

$root = dirname(__DIR__, 5);
$config = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'phpunit.xml';
$phpunit = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'phpunit'
    . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR . 'phpunit';
$resultsDefault = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR . 'formatos' . DIRECTORY_SEPARATOR . 'resultados';

/**
 * @return array<string, mixed>
 */
function parseArguments(array $argv): array
{
    $options = [
        'suite' => 'all',
        'module' => null,
        'testdox' => true,
        'junit' => true,
        'dry-run' => false,
        'verbose' => false,
        'list' => false,
        'help' => false,
        'extensions' => [],
        'phpArgs' => [],
        'resultsDir' => null,
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
            case '--suite':
                $options['suite'] = strtolower(trim($needValue()));
                break;
            case '--module':
                $options['module'] = trim($needValue());
                break;
            case '--extension':
                $options['extensions'][] = trim($needValue());
                break;
            case '--php-arg':
                $options['phpArgs'][] = $needValue();
                break;
            case '--results-dir':
                $options['resultsDir'] = trim($needValue());
                break;
            case '--no-testdox':
                $options['testdox'] = false;
                break;
            case '--no-junit':
                $options['junit'] = false;
                break;
            case '--dry-run':
                $options['dry-run'] = true;
                break;
            case '--verbose':
                $options['verbose'] = true;
                break;
            case '--list':
                $options['list'] = true;
                break;
            case '--help':
            case '-h':
                $options['help'] = true;
                break;
            default:
                fwrite(STDERR, "[ERROR] Opción no reconocida: {$arg}" . PHP_EOL);
                fwrite(STDERR, "Use --help para ver las opciones disponibles." . PHP_EOL);
                exit(EXIT_USAGE);
        }
    }

    if (!in_array($options['suite'], ['all', 'integration', 'smoke'], true)) {
        fwrite(STDERR, "[ERROR] --suite debe ser all, integration o smoke." . PHP_EOL);
        exit(EXIT_USAGE);
    }

    return $options;
}

function showHelp(): void
{
    $help = <<<'TXT'
Ejecutor de pruebas — Sys Inescolara
=====================================

Uso:
  php tests/tools/phpunit/scripts/lib/run-tests.php [opciones]

Opciones:
  --suite=all|integration|smoke  Suite a ejecutar (por defecto: all)
  --module=NN|Nombre            Ejecuta solo un módulo (01-31 o su nombre)
  --extension=RUTA              Carga una extensión PHP (repetible)
  --php-arg=ARG                 Argumento adicional para el PHP hijo (repetible)
  --results-dir=RUTA            Directorio de logs y XML (por defecto tests/tools/phpunit/formatos/resultados)
  --no-testdox                  No muestra el detalle testdox
  --no-junit                    No genera reportes JUnit XML
  --dry-run                     Muestra los comandos sin ejecutarlos
  --list                        Lista los 31 módulos disponibles
  --verbose                     Muestra información adicional
  -h, --help                    Muestra esta ayuda

Ejemplos:
  php tests/tools/phpunit/scripts/lib/run-tests.php
  php tests/tools/phpunit/scripts/lib/run-tests.php --suite=integration
  php tests/tools/phpunit/scripts/lib/run-tests.php --suite=smoke
  php tests/tools/phpunit/scripts/lib/run-tests.php --module=13
  php tests/tools/phpunit/scripts/lib/run-tests.php --module=Venta --suite=smoke
  php tests/tools/phpunit/scripts/lib/run-tests.php --module=05 --extension=/usr/lib/php/modules/pdo_sqlite.so
  php tests/tools/phpunit/scripts/lib/run-tests.php --dry-run

En Linux/macOS también puede usar:
  ./tests/tools/phpunit/scripts/run-tests.sh

En Windows:
  powershell -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-tests.ps1

Requisitos:
  - PHP 8.2 o superior con las extensiones PDO y pdo_sqlite para integración.
  - Composer instalado (vendor/ presente).
  - En Arch/CachyOS: sudo pacman -S php-sqlite
  - En Debian/Ubuntu: sudo apt install php-sqlite3
  - En Windows: habilite extension=pdo_sqlite en php.ini.
TXT;

    fwrite(STDOUT, $help . PHP_EOL);
}

/**
 * @return list<array{n:int,label:string,file:string,smoke:string}>
 */
function loadModules(string $config): array
{
    $dom = new DOMDocument();
    $loaded = @$dom->load($config);

    if (!$loaded) {
        fwrite(STDERR, "[ERROR] No se pudo leer {$config}" . PHP_EOL);
        exit(EXIT_ENV);
    }

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//testsuite[@name="Integration"]/file');
    if ($nodes === false || $nodes->length === 0) {
        fwrite(STDERR, "[ERROR] No se encontró la suite Integration en phpunit.xml" . PHP_EOL);
        exit(EXIT_ENV);
    }

    $modules = [];
    $index = 0;
    foreach ($nodes as $node) {
        $index++;
        $file = trim($node->textContent);
        $base = basename($file, '.php');
        $label = preg_replace('/RepositoryTest$/', '', $base) ?: $base;
        $modules[] = [
            'n' => $index,
            'label' => $label,
            'file' => $file,
            'smoke' => sprintf('CP-SMOKE-%02d', $index),
        ];
    }

    return $modules;
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

/**
 * @param list<array{n:int,label:string,file:string,smoke:string}> $modules
 * @return array{n:int,label:string,file:string,smoke:string}|null
 */
function resolveModule(?string $input, array $modules): ?array
{
    if ($input === null || trim($input) === '' || strtolower(trim($input)) === 'all') {
        return null;
    }

    $input = trim($input);
    if (ctype_digit($input)) {
        $number = (int) $input;
        foreach ($modules as $module) {
            if ($module['n'] === $number) {
                return $module;
            }
        }
        return null;
    }

    $needle = normalize($input);
    foreach ($modules as $module) {
        if (normalize($module['label']) === $needle) {
            return $module;
        }
    }

    foreach ($modules as $module) {
        if (str_contains(normalize($module['label']), $needle)) {
            return $module;
        }
    }

    return null;
}

function showModules(array $modules): void
{
    fwrite(STDOUT, sprintf("%-4s %-24s %-58s %s\n", 'N°', 'Módulo', 'Integración', 'Humo'));
    fwrite(STDOUT, str_repeat('-', 108) . PHP_EOL);
    foreach ($modules as $module) {
        fwrite(STDOUT, sprintf(
            "%02d   %-24s %-58s %s\n",
            $module['n'],
            $module['label'],
            $module['file'],
            $module['smoke']
        ));
    }
}

/**
 * Busca extensiones SQLite en ubicaciones habituales.
 *
 * @return list<string>
 */
function autoDetectSqliteExtensions(): array
{
    $extensionDir = (string) ini_get('extension_dir');
    $dirs = array_values(array_unique(array_filter([
        $extensionDir,
        dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'ext',
        '/usr/lib/php/modules',
        '/usr/lib64/php/modules',
        '/usr/local/lib/php/modules',
    ], static fn (string $dir): bool => $dir !== '')));

    $names = [
        'pdo_sqlite.so', 'sqlite3.so',
        'php_pdo_sqlite.dll', 'php_sqlite3.dll',
        'pdo_sqlite.dll', 'sqlite3.dll',
    ];

    $found = [];
    foreach ($dirs as $dir) {
        foreach ($names as $name) {
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($path) && !in_array($path, $found, true)) {
                $found[] = $path;
            }
        }
    }

    return $found;
}

/**
 * Ejecuta un comando y devuelve su salida y código de salida.
 *
 * @param list<string> $command
 * @return array{exit:int,stdout:string,stderr:string,seconds:float}
 */
function execute(array $command, ?string $logFile = null, bool $stream = true): array
{
    $started = microtime(true);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        return [
            'exit' => EXIT_ENV,
            'stdout' => '',
            'stderr' => 'No se pudo iniciar el proceso.',
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
                fwrite($log, '$ ' . implode(' ', array_map('escapeshellarg', $command)) . PHP_EOL . PHP_EOL);
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
 * @param list<string> $phpArgs
 */
function childHasSqlite(array $phpArgs): bool
{
    $result = execute(
        array_merge([PHP_BINARY], $phpArgs, [
            '-r',
            'echo in_array("sqlite", PDO::getAvailableDrivers(), true) ? "1" : "0";',
        ]),
        null,
        false
    );

    return $result['exit'] === 0 && trim($result['stdout']) === '1';
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
 * @param list<string> $command
 */
function displayCommand(array $command): string
{
    return implode(' ', array_map(
        static fn (string $part): string => preg_match('/^[A-Za-z0-9_\.\-\/:=]+$/', $part) === 1
            ? $part
            : escapeshellarg($part),
        $command
    ));
}

/* ------------------------------------------------------------------ */
/* Inicio                                                              */
/* ------------------------------------------------------------------ */

$options = parseArguments($argv);

if ($options['help']) {
    showHelp();
    exit(EXIT_OK);
}

if (!is_file($phpunit)) {
    fwrite(STDERR, "[ERROR] No se encontró PHPUnit en {$phpunit}" . PHP_EOL);
    fwrite(STDERR, "Ejecute 'composer install' antes de correr las pruebas." . PHP_EOL);
    exit(EXIT_ENV);
}

$modules = loadModules($config);

if ($options['list']) {
    showModules($modules);
    exit(EXIT_OK);
}

$module = resolveModule($options['module'], $modules);
if ($options['module'] !== null && trim($options['module']) !== '' && strtolower(trim($options['module'])) !== 'all' && $module === null) {
    fwrite(STDERR, "[ERROR] Módulo no encontrado: {$options['module']}" . PHP_EOL);
    fwrite(STDERR, "Use --list para ver los módulos disponibles." . PHP_EOL);
    exit(EXIT_USAGE);
}

$resultsDir = $options['resultsDir'] !== null
    ? $options['resultsDir']
    : $resultsDefault;

if (!is_dir($resultsDir) && !@mkdir($resultsDir, 0777, true) && !is_dir($resultsDir)) {
    fwrite(STDERR, "[ERROR] No se pudo crear el directorio de resultados: {$resultsDir}" . PHP_EOL);
    exit(EXIT_ENV);
}

$phpArgs = $options['phpArgs'];
foreach ($options['extensions'] as $extension) {
    $path = realpath($extension) ?: $extension;
    if (!is_file($path)) {
        fwrite(STDERR, "[ERROR] No existe la extensión indicada: {$extension}" . PHP_EOL);
        exit(EXIT_USAGE);
    }
    $phpArgs[] = '-d';
    $phpArgs[] = 'extension=' . $path;
}

$needsIntegration = in_array($options['suite'], ['all', 'integration'], true);
$sqliteReady = $options['dry-run'] ? true : childHasSqlite($phpArgs);

if ($needsIntegration && !$sqliteReady && $options['extensions'] === []) {
    $detected = autoDetectSqliteExtensions();
    if ($detected !== []) {
        $candidateArgs = $phpArgs;
        foreach ($detected as $extension) {
            $candidateArgs[] = '-d';
            $candidateArgs[] = 'extension=' . $extension;
        }
        if (childHasSqlite($candidateArgs)) {
            $phpArgs = $candidateArgs;
            $sqliteReady = true;
            fwrite(STDOUT, '[INFO] Extensiones SQLite detectadas automáticamente: ' . implode(', ', $detected) . PHP_EOL);
        }
    }
}

if ($needsIntegration && !$sqliteReady) {
    fwrite(STDERR, PHP_EOL);
    fwrite(STDERR, '[ERROR] La suite de integración requiere el driver PDO SQLite.' . PHP_EOL);
    fwrite(STDERR, '        Linux Arch/CachyOS : sudo pacman -S php-sqlite' . PHP_EOL);
    fwrite(STDERR, '        Debian/Ubuntu      : sudo apt install php-sqlite3' . PHP_EOL);
    fwrite(STDERR, '        Windows            : habilite extension=pdo_sqlite en php.ini' . PHP_EOL);
    fwrite(STDERR, '        Ruta manual        : --extension=/ruta/a/pdo_sqlite.so' . PHP_EOL);
    fwrite(STDERR, '        También puede usar --extension=... dos veces (sqlite3 y pdo_sqlite).' . PHP_EOL);
}

$stamp = date('Ymd-His');
$moduleSlug = $module !== null ? sprintf('%02d-%s', $module['n'], $module['label']) : 'all';
$moduleSlug = (string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $moduleSlug);

/**
 * @var list<array{key:string,label:string,args:list<string>,junit:?string,log:string,skip:bool,skipReason:?string}> $runs
 */
$runs = [];

$junitArgs = static function (string $file) use ($options): array {
    return $options['junit'] ? ['--log-junit', $file] : [];
};

if ($needsIntegration) {
    $junit = $resultsDir . DIRECTORY_SEPARATOR . "integration-{$moduleSlug}-{$stamp}.xml";
    $log = $resultsDir . DIRECTORY_SEPARATOR . "integration-{$moduleSlug}-{$stamp}.log";
    $args = $module !== null
        ? [$root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $module['file'])]
        : ['--testsuite', 'Integration'];
    if ($options['testdox']) {
        $args[] = '--testdox';
    }
    $args[] = '--colors=never';
    $args[] = '--do-not-cache-result';
    $args = array_merge($args, $junitArgs($junit));

    $runs[] = [
        'key' => 'integration',
        'label' => $module !== null ? "Integración · {$module['label']}" : 'Integración · suite completa',
        'args' => $args,
        'junit' => $options['junit'] ? $junit : null,
        'log' => $log,
        'skip' => !$sqliteReady,
        'skipReason' => !$sqliteReady ? 'falta el driver pdo_sqlite' : null,
    ];
}

if (in_array($options['suite'], ['all', 'smoke'], true)) {
    $junit = $resultsDir . DIRECTORY_SEPARATOR . "smoke-{$moduleSlug}-{$stamp}.xml";
    $log = $resultsDir . DIRECTORY_SEPARATOR . "smoke-{$moduleSlug}-{$stamp}.log";
    $args = ['--testsuite', 'Smoke'];
    if ($module !== null) {
        $args[] = '--filter';
        $args[] = $module['smoke'];
    }
    if ($options['testdox']) {
        $args[] = '--testdox';
    }
    $args[] = '--colors=never';
    $args[] = '--do-not-cache-result';
    $args = array_merge($args, $junitArgs($junit));

    $runs[] = [
        'key' => 'smoke',
        'label' => $module !== null ? "Humo · {$module['label']} ({$module['smoke']})" : 'Humo · suite completa',
        'args' => $args,
        'junit' => $options['junit'] ? $junit : null,
        'log' => $log,
        'skip' => false,
        'skipReason' => null,
    ];
}

$baseCommand = array_merge([PHP_BINARY], $phpArgs, [$phpunit, '-c', $config]);

fwrite(STDOUT, PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' Sys Inescolara · Ejecutor de pruebas' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' PHP       : ' . PHP_VERSION . ' (' . PHP_BINARY . ')' . PHP_EOL);
fwrite(STDOUT, ' Proyecto  : ' . $root . PHP_EOL);
fwrite(STDOUT, ' Suite     : ' . $options['suite'] . PHP_EOL);
fwrite(STDOUT, ' Módulo    : ' . ($module !== null ? sprintf('%02d · %s', $module['n'], $module['label']) : 'todos') . PHP_EOL);
fwrite(STDOUT, ' Resultados: ' . $resultsDir . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL . PHP_EOL);

$results = [];
$exitCode = EXIT_OK;

foreach ($runs as $run) {
    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);
    fwrite(STDOUT, '>> ' . $run['label'] . PHP_EOL);
    fwrite(STDOUT, '------------------------------------------------------------' . PHP_EOL);

    $command = array_merge($baseCommand, $run['args']);
    fwrite(STDOUT, 'Comando: ' . displayCommand($command) . PHP_EOL);

    if ($run['skip']) {
        fwrite(STDOUT, '[OMITIDA] ' . $run['skipReason'] . PHP_EOL . PHP_EOL);
        $results[] = [
            'label' => $run['label'],
            'exit' => null,
            'summary' => null,
            'junit' => null,
            'log' => null,
            'skipped' => true,
        ];
        $exitCode = EXIT_ENV;
        continue;
    }

    if ($options['dry-run']) {
        fwrite(STDOUT, '[DRY-RUN] No se ejecutó.' . PHP_EOL . PHP_EOL);
        $results[] = [
            'label' => $run['label'],
            'exit' => 0,
            'summary' => null,
            'junit' => $run['junit'],
            'log' => $run['log'],
            'skipped' => false,
        ];
        continue;
    }

    $result = execute($command, $run['log'], true);
    $summary = $run['junit'] !== null ? junitSummary($run['junit']) : null;

    fwrite(STDOUT, PHP_EOL . '[código de salida: ' . $result['exit'] . ']' . PHP_EOL . PHP_EOL);
    if ($summary !== null) {
        fwrite(STDOUT, sprintf(
            "[resumen] casos=%d aserciones=%d fallos=%d errores=%d omitidos=%d tiempo=%.3fs\n",
            $summary['tests'],
            $summary['assertions'],
            $summary['failures'],
            $summary['errors'],
            $summary['skipped'],
            $summary['time']
        ));
    }
    fwrite(STDOUT, '[log] ' . $run['log'] . PHP_EOL);
    if ($run['junit'] !== null) {
        fwrite(STDOUT, '[junit] ' . $run['junit'] . PHP_EOL);
    }
    fwrite(STDOUT, PHP_EOL);

    if ($result['exit'] !== 0) {
        $exitCode = EXIT_FAIL;
    }

    $results[] = [
        'label' => $run['label'],
        'exit' => $result['exit'],
        'summary' => $summary,
        'junit' => $run['junit'],
        'log' => $run['log'],
        'skipped' => false,
    ];
}

fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, ' RESUMEN FINAL' . PHP_EOL);
fwrite(STDOUT, '============================================================' . PHP_EOL);
fwrite(STDOUT, sprintf(
    "%-42s %7s %12s %7s %8s %8s %10s %s\n",
    'Ejecución',
    'Casos',
    'Aserciones',
    'Fallos',
    'Errores',
    'Omitidos',
    'Tiempo',
    'Estado'
));
fwrite(STDOUT, str_repeat('-', 112) . PHP_EOL);

$totals = ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0, 'time' => 0.0];
foreach ($results as $result) {
    if ($result['skipped']) {
        fwrite(STDOUT, sprintf("%-42s %7s %12s %7s %8s %8s %10s %s\n", $result['label'], '-', '-', '-', '-', '-', '-', 'NO EJECUTADA'));
        continue;
    }

    $summary = $result['summary'];
    if ($summary === null) {
        $status = $result['exit'] === 0 ? 'OK' : 'FALLO';
        fwrite(STDOUT, sprintf("%-42s %7s %12s %7s %8s %8s %10s %s\n", $result['label'], '-', '-', '-', '-', '-', '-', $status));
        continue;
    }

    $totals['tests'] += $summary['tests'];
    $totals['assertions'] += $summary['assertions'];
    $totals['failures'] += $summary['failures'];
    $totals['errors'] += $summary['errors'];
    $totals['skipped'] += $summary['skipped'];
    $totals['time'] += $summary['time'];

    $status = ($result['exit'] === 0 && $summary['failures'] === 0 && $summary['errors'] === 0) ? 'PASS' : 'FALLO';
    fwrite(STDOUT, sprintf(
        "%-42s %7d %12d %7d %8d %8d %9.3fs %s\n",
        $result['label'],
        $summary['tests'],
        $summary['assertions'],
        $summary['failures'],
        $summary['errors'],
        $summary['skipped'],
        $summary['time'],
        $status
    ));
}

fwrite(STDOUT, str_repeat('-', 112) . PHP_EOL);
fwrite(STDOUT, sprintf(
    "%-42s %7d %12d %7d %8d %8d %9.3fs %s\n",
    'TOTAL',
    $totals['tests'],
    $totals['assertions'],
    $totals['failures'],
    $totals['errors'],
    $totals['skipped'],
    $totals['time'],
    $exitCode === EXIT_OK ? 'PASS' : 'REVISAR'
));
fwrite(STDOUT, '============================================================' . PHP_EOL);

exit($exitCode);
