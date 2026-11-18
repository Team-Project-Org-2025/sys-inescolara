<?php

declare(strict_types=1);

/**
 * Motor del formato Word de pruebas de integración.
 *
 * Invocado por tests/tools/integration/scripts/linux/run-integration-doc.sh.
 * También puede ejecutarse directamente:
 *
 *   php tests/tools/integration/scripts/lib/integration-doc.php
 *   php tests/tools/integration/scripts/lib/integration-doc.php --module=29
 *   php .../integration-doc.php --module=Backup --output=/tmp/cuadro-29.docx
 *
 * Para cada módulo (RF-01 a RF-31):
 *   1. Ejecuta su archivo de integración con PHPUnit y guarda el JUnit.
 *   2. Genera una captura PNG de la salida real con Chrome headless.
 *   3. Rellena el cuadro correspondiente en una COPIA del formato Word.
 *
 * No requiere Microsoft Word ni la extensión zip: el .docx se lee y escribe
 * como paquete Open XML con un lector/escritor ZIP propio sobre zlib.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este motor solo se ejecuta desde la línea de comandos.\n");
    exit(1);
}

const NS_W  = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
const NS_A  = 'http://schemas.openxmlformats.org/drawingml/2006/main';
const NS_R  = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
const NS_WP = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';

$ROOT       = dirname(__DIR__, 5);
$AREA       = dirname(__DIR__, 2);
$TEMPLATE   = $AREA . '/formatos/plantillas/Formato-Prueba-Integración.docx';
$RESULTS    = $AREA . '/formatos/resultados';
$CAPTURES   = $RESULTS . '/capturas';
$CONFIG     = $ROOT . '/tests/phpunit.xml';
$PHPUNIT    = $ROOT . '/vendor/phpunit/phpunit/phpunit';
$MANIFEST   = $RESULTS . '/ultima-ejecucion.json';
$PHP_BIN    = PHP_BINARY;

// ---------------------------------------------------------------------------
// Argumentos
// ---------------------------------------------------------------------------
$OPTIONS = [
    'module'       => 'all',
    'output'       => '',
    'keep-working' => false,
    'help'         => false,
];

$argvCopy = $argv;
array_shift($argvCopy);
$count = count($argvCopy);
for ($i = 0; $i < $count; $i++) {
    $arg = $argvCopy[$i];
    $value = null;

    if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
        [$arg, $value] = explode('=', $arg, 2);
    }

    switch ($arg) {
        case '--module':
            $OPTIONS['module'] = $value ?? ($argvCopy[++$i] ?? '');
            break;
        case '--output':
            $OPTIONS['output'] = $value ?? ($argvCopy[++$i] ?? '');
            break;
        case '--keep-working':
            $OPTIONS['keep-working'] = true;
            break;
        case '-h':
        case '--help':
            $OPTIONS['help'] = true;
            break;
        default:
            if (str_starts_with($arg, '-')) {
                fwrite(STDERR, "Opción no reconocida: {$arg}\n");
                exit(2);
            }
            if ($OPTIONS['module'] === 'all' && $value === null) {
                $OPTIONS['module'] = $arg;
            }
            break;
    }
}

if ($OPTIONS['help']) {
    $self = basename(__FILE__);
    echo <<<TXT
    Motor del formato Word de pruebas de integración

    Uso:
      php tests/tools/integration/scripts/lib/integration-doc.php [opciones] [módulo]

    Opciones:
      --module=NN|Nombre   all (por defecto), número 01-31 o parte del modelo
      --output=RUTA        Ruta del .docx de salida
      --keep-working       Conserva los JUnit temporales de la corrida
      -h, --help           Muestra esta ayuda

    La captura PNG se genera con Chrome headless. Define CHROME_BIN si el
    navegador no está en una ruta conocida.


    TXT;
    exit(0);
}

// ---------------------------------------------------------------------------
// Lector / escritor ZIP mínimo (solo lo necesario para un .docx)
// ---------------------------------------------------------------------------
final class ZipEntry
{
    public string $name = '';
    public int $method = 0;
    public int $crc = 0;
    public int $compSize = 0;
    public int $uncompSize = 0;
    public int $localOffset = 0;
    public int $flags = 0;
    public int $modTime = 0;
    public int $modDate = 0;
    public int $extAttr = 0;
    public int $intAttr = 0;
    public int $versionMadeBy = 20;
    public string $raw = '';
}

final class ZipArchiveLite
{
    /** @var list<ZipEntry> */
    public array $entries = [];

    /** @var array<string, ZipEntry> */
    private array $byName = [];

    private string $data;

    public function __construct(string $path)
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("No se pudo leer el archivo: {$path}");
        }
        $this->data = $data;

        $eocd = strrpos($data, "PK\x05\x06");
        if ($eocd === false) {
            throw new RuntimeException("El archivo no es un ZIP válido: {$path}");
        }

        $end = unpack('vdisk/vstart/ventriesDisk/ventries/Vsize/Voffset/vcomment', substr($data, $eocd + 4, 18));
        $total = (int) $end['entries'];
        $offset = (int) $end['offset'];

        for ($i = 0; $i < $total; $i++) {
            if (substr($data, $offset, 4) !== "PK\x01\x02") {
                throw new RuntimeException('Directorio central ZIP corrupto.');
            }

            $header = unpack(
                'vversionMadeBy/vversionNeeded/vflags/vmethod/vmodTime/vmodDate/'
                . 'Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/'
                . 'vdiskStart/vintAttr/VextAttr/VlocalOffset',
                substr($data, $offset + 4, 42)
            );

            $entry = new ZipEntry();
            $entry->versionMadeBy = (int) $header['versionMadeBy'];
            $entry->flags = (int) $header['flags'];
            $entry->method = (int) $header['method'];
            $entry->modTime = (int) $header['modTime'];
            $entry->modDate = (int) $header['modDate'];
            $entry->crc = (int) $header['crc'];
            $entry->compSize = (int) $header['compSize'];
            $entry->uncompSize = (int) $header['uncompSize'];
            $entry->intAttr = (int) $header['intAttr'];
            $entry->extAttr = (int) $header['extAttr'];
            $entry->localOffset = (int) $header['localOffset'];

            $nameLen = (int) $header['nameLen'];
            $extraLen = (int) $header['extraLen'];
            $commentLen = (int) $header['commentLen'];
            $entry->name = substr($data, $offset + 46, $nameLen);

            // Datos comprimidos: el tamaño fiable está en el directorio central.
            $local = $entry->localOffset;
            if (substr($data, $local, 4) !== "PK\x03\x04") {
                throw new RuntimeException("Cabecera local ZIP inválida para {$entry->name}.");
            }
            $localHeader = unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen', substr($data, $local + 4, 26));
            $dataOffset = $local + 30 + (int) $localHeader['nameLen'] + (int) $localHeader['extraLen'];
            $entry->raw = substr($data, $dataOffset, $entry->compSize);

            $this->entries[] = $entry;
            $this->byName[$entry->name] = $entry;

            $offset += 46 + $nameLen + $extraLen + $commentLen;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->byName[$name]);
    }

    public function content(string $name): string
    {
        $entry = $this->byName[$name] ?? null;
        if ($entry === null) {
            throw new RuntimeException("No existe la entrada {$name} en el paquete.");
        }

        if ($entry->method === 0) {
            return $entry->raw;
        }
        if ($entry->method === 8) {
            $content = @gzinflate($entry->raw);
            if ($content === false) {
                throw new RuntimeException("No se pudo descomprimir {$name}.");
            }
            return $content;
        }

        throw new RuntimeException("Método de compresión ZIP no soportado ({$entry->method}) en {$name}.");
    }

    /**
     * Escribe un ZIP nuevo conservando el orden y los metadatos de las
     * entradas. Las entradas reemplazadas usan el contenido nuevo.
     *
     * @param array<string, string> $replacements nombre => contenido sin comprimir
     */
    public function writeTo(string $path, array $replacements = []): void
    {
        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("No se pudo crear el archivo: {$path}");
        }

        $central = '';
        $offset = 0;

        try {
            foreach ($this->entries as $entry) {
                $content = array_key_exists($entry->name, $replacements)
                    ? $replacements[$entry->name]
                    : $this->content($entry->name);

                $deflated = @gzdeflate($content, 6);
                if ($deflated !== false && strlen($deflated) < strlen($content)) {
                    $method = 8;
                    $payload = $deflated;
                } else {
                    $method = 0;
                    $payload = $content;
                }

                $crc = crc32($content);
                $compSize = strlen($payload);
                $uncompSize = strlen($content);
                $flags = $entry->flags & ~0x0008; // sin descriptor de datos
                $name = $entry->name;

                $localHeader = pack(
                    'VvvvvvVVVvv',
                    0x04034b50,
                    20,
                    $flags,
                    $method,
                    $entry->modTime,
                    $entry->modDate,
                    $crc,
                    $compSize,
                    $uncompSize,
                    strlen($name),
                    0
                );

                fwrite($handle, $localHeader . $name . $payload);

                $central .= pack(
                    'VvvvvvvVVVvvvvvVV',
                    0x02014b50,
                    $entry->versionMadeBy,
                    20,
                    $flags,
                    $method,
                    $entry->modTime,
                    $entry->modDate,
                    $crc,
                    $compSize,
                    $uncompSize,
                    strlen($name),
                    0,
                    0,
                    0,
                    $entry->intAttr,
                    $entry->extAttr,
                    $offset
                ) . $name;

                $offset += strlen($localHeader) + strlen($name) + strlen($payload);
            }

            $cdOffset = $offset;
            fwrite($handle, $central);

            $count = count($this->entries);
            fwrite($handle, pack(
                'VvvvvVVv',
                0x06054b50,
                0,
                0,
                $count,
                $count,
                strlen($central),
                $cdOffset,
                0
            ));
        } finally {
            fclose($handle);
        }
    }
}

// ---------------------------------------------------------------------------
// Utilidades de imagen
// ---------------------------------------------------------------------------
/**
 * @return array{0:int,1:int}
 */
function pngSize(string $bytes): array
{
    if (substr($bytes, 0, 8) !== "\x89PNG\r\n\x1a\n" || substr($bytes, 12, 4) !== 'IHDR') {
        throw new RuntimeException('La imagen no es un PNG válido.');
    }

    /** @var array{1:int,2:int} $size */
    $size = unpack('Nwidth/Nheight', substr($bytes, 16, 8));

    return [(int) $size['width'], (int) $size['height']];
}

function formatIntEs(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function formatTimeEs(float $value): string
{
    return number_format($value, 3, ',', '.');
}

// ---------------------------------------------------------------------------
// Catálogo de módulos
// ---------------------------------------------------------------------------
/**
 * @return list<array{Number:int,Index:int,Model:string,TestFile:string,TestPath:string,ImageWidth:int}>
 */
function moduleCatalog(string $configPath, string $root): array
{
    $xml = @file_get_contents($configPath);
    if ($xml === false) {
        throw new RuntimeException("No se pudo leer {$configPath}.");
    }

    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml)) {
        throw new RuntimeException("El archivo {$configPath} no es XML válido.");
    }

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//testsuite[@name="Integration"]/file');
    if ($nodes === false) {
        throw new RuntimeException('No se encontró la suite Integration en tests/phpunit.xml.');
    }

    $catalog = [];
    $number = 0;
    foreach ($nodes as $node) {
        $number++;
        $relative = trim($node->textContent);
        $fileName = pathinfo($relative, PATHINFO_FILENAME);
        $model = preg_replace('/RepositoryTest$/', '', $fileName);

        $catalog[] = [
            'Number'     => $number,
            'Index'      => $number - 1,
            'Model'      => $model,
            'TestFile'   => $relative,
            'TestPath'   => $root . '/tests/' . $relative,
            'ImageWidth' => 707,
        ];
    }

    return $catalog;
}

/**
 * @param list<array<string,mixed>> $catalog
 * @return list<array<string,mixed>>
 */
function selectModules(array $catalog, string $filter): array
{
    $raw = trim($filter);

    if ($raw === '' || strtolower($raw) === 'all') {
        return $catalog;
    }

    if (preg_match('/^\d{1,2}$/', $raw) === 1) {
        $number = (int) $raw;
        return array_values(array_filter($catalog, static fn (array $m): bool => $m['Number'] === $number));
    }

    return array_values(array_filter($catalog, static function (array $m) use ($raw): bool {
        return stripos($m['Model'], $raw) !== false || stripos($m['TestFile'], $raw) !== false;
    }));
}

// ---------------------------------------------------------------------------
// Anchos originales de las capturas dentro del formato
// ---------------------------------------------------------------------------
/**
 * @return list<int>
 */
function templateImageWidths(ZipArchiveLite $zip): array
{
    $document = $zip->content('word/document.xml');
    $rels = $zip->content('word/_rels/document.xml.rels');

    $dom = new DOMDocument();
    if (!@$dom->loadXML($document)) {
        throw new RuntimeException('word/document.xml no es XML válido.');
    }

    $relDom = new DOMDocument();
    if (!@$relDom->loadXML($rels)) {
        throw new RuntimeException('document.xml.rels no es XML válido.');
    }

    $relMap = [];
    foreach ($relDom->getElementsByTagName('Relationship') as $rel) {
        $relMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', NS_W);
    $xpath->registerNamespace('a', NS_A);
    $xpath->registerNamespace('r', NS_R);

    $widths = [];
    foreach ($xpath->query('//w:body/w:tbl') as $table) {
        $width = 707;
        $blip = $xpath->query('.//a:blip', $table)->item(0);
        if ($blip instanceof DOMElement) {
            $rid = $blip->getAttributeNS(NS_R, 'embed');
            $target = $relMap[$rid] ?? null;
            if ($target !== null && $zip->has('word/' . $target)) {
                try {
                    [$width] = pngSize($zip->content('word/' . $target));
                } catch (RuntimeException) {
                    $width = 707;
                }
            }
        }
        $widths[] = $width;
    }

    return $widths;
}

// ---------------------------------------------------------------------------
// Chrome headless
// ---------------------------------------------------------------------------
function findChrome(): string
{
    $fromEnv = getenv('CHROME_BIN') ?: getenv('CHROME_PATH');
    if (is_string($fromEnv) && $fromEnv !== '' && is_file($fromEnv)) {
        return $fromEnv;
    }

    $candidates = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
        ];
    } else {
        foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', 'brave-browser', 'brave', 'microsoft-edge'] as $name) {
            $found = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
            if (is_string($found) && trim($found) !== '') {
                $candidates[] = trim($found);
            }
        }
        $candidates[] = '/usr/bin/google-chrome';
        $candidates[] = '/usr/bin/chromium';
        $candidates[] = '/usr/bin/chromium-browser';
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException(
        'No se encontró Chrome/Chromium para generar las capturas. '
        . 'Define CHROME_BIN con la ruta del navegador.'
    );
}

function escapeHtml(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Genera la captura PNG de la salida de PHPUnit y devuelve sus dimensiones.
 *
 * @param list<string> $lines
 * @return array{0:int,1:int}
 */
function renderScreenshot(array $lines, string $path, int $width, string $chrome, string $workDir): array
{
    $lineHeight = 19;
    $marginX = 8;
    $marginY = 5;
    $charWidth = 8.4;

    $maxChars = max(40, (int) floor(($width - (2 * $marginX)) / $charWidth));

    $prepared = [];
    foreach ($lines as $rawLine) {
        $clean = rtrim(preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $rawLine) ?? '');

        // Evidencia de la prueba, no publicidad del framework.
        if (preg_match('/^PHPUnit\s+[\d.]+\s+by\s+Sebastian Bergmann/', $clean) === 1) {
            continue;
        }

        // PHPUnit escribe el check como '?' cuando la consola no puede
        // representar U+2714; se restituye para la captura.
        $clean = preg_replace('/^(\s*)\?(\s.*)$/u', "\$1\u{2714}\$2", $clean) ?? $clean;

        if ($clean === '') {
            $prepared[] = '';
            continue;
        }

        while (mb_strlen($clean, 'UTF-8') > $maxChars) {
            $prepared[] = mb_substr($clean, 0, $maxChars, 'UTF-8');
            $clean = '  ' . mb_substr($clean, $maxChars, null, 'UTF-8');
        }
        $prepared[] = $clean;
    }

    while ($prepared !== [] && $prepared[0] === '') {
        array_shift($prepared);
    }
    while ($prepared !== [] && $prepared[count($prepared) - 1] === '') {
        array_pop($prepared);
    }
    if ($prepared === []) {
        $prepared[] = '';
    }

    $height = ($marginY * 2) + (count($prepared) * $lineHeight);

    $html = [];
    foreach ($prepared as $line) {
        $brush = '';
        if (preg_match('/^\s*OK\b/', $line) === 1) {
            $brush = 'ok';
        } elseif (preg_match('/FAILURES!|ERRORS!/', $line) === 1) {
            $brush = 'err';
        } elseif (preg_match('/^\s*Tests:/', $line) === 1) {
            $brush = 'warn';
        } elseif (preg_match('/^PHPUnit /', $line) === 1) {
            $brush = 'cyan';
        } elseif (preg_match('/^(Runtime|Configuration|Time):/', $line) === 1) {
            $brush = 'dim';
        }

        if (preg_match('/^(\s*)([\x{2714}\x{2713}])(\s.*)$/u', $line, $match) === 1) {
            $html[] = escapeHtml($match[1])
                . '<span class="check">' . $match[2] . '</span>'
                . escapeHtml($match[3]);
            continue;
        }

        $html[] = $brush === ''
            ? escapeHtml($line)
            : '<span class="' . $brush . '">' . escapeHtml($line) . '</span>';
    }

    $body = implode("\n", $html);
    $document = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
html, body { margin: 0; padding: 0; background: #0c0c0c; }
pre {
  margin: 0;
  padding: {$marginY}px {$marginX}px;
  font-family: Consolas, "DejaVu Sans Mono", "Liberation Mono", monospace;
  font-size: 14px;
  line-height: {$lineHeight}px;
  color: #d4d4d4;
  white-space: pre;
  overflow: hidden;
}
.dim { color: #969696; }
.ok { color: #4ec978; }
.err { color: #f44747; }
.warn { color: #e5c05a; }
.cyan { color: #56b6c2; }
.check { color: #4ec978; font-size: 12px; }
</style>
</head>
<body><pre>{$body}</pre></body>
</html>
HTML;

    $htmlPath = $workDir . '/captura.html';
    file_put_contents($htmlPath, $document);

    $userDataDir = $workDir . '/chrome-profile';
    if (!is_dir($userDataDir)) {
        mkdir($userDataDir, 0777, true);
    }

    $url = 'file:///' . ltrim(str_replace('\\', '/', $htmlPath), '/');
    $command = [
        $chrome,
        '--headless=new',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--force-device-scale-factor=1',
        '--disable-extensions',
        '--disable-background-networking',
        '--no-first-run',
        '--no-default-browser-check',
        '--virtual-time-budget=1000',
        '--window-size=' . $width . ',' . $height,
        '--user-data-dir=' . $userDataDir,
        '--screenshot=' . $path,
        $url,
    ];

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $descriptors, $pipes, $workDir);
    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo iniciar Chrome headless.');
    }

    $output = stream_get_contents($pipes[1]) ?: '';
    $errors = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    if ($exit !== 0 || !is_file($path)) {
        throw new RuntimeException("Chrome no generó la captura {$path}.\n{$output}\n{$errors}");
    }

    return pngSize((string) file_get_contents($path));
}

// ---------------------------------------------------------------------------
// Ejecución de PHPUnit por módulo
// ---------------------------------------------------------------------------
/**
 * @param array<string,mixed> $module
 * @return array<string,mixed>
 */
function runModule(array $module, string $php, string $phpunit, string $config, string $root, string $workDir, string $chrome, string $capturesDir): array
{
    $number = (int) $module['Number'];
    $junit = $workDir . sprintf('/junit-%02d.xml', $number);
    if (is_file($junit)) {
        unlink($junit);
    }

    $relativeTest = 'tests/' . str_replace('\\', '/', (string) $module['TestFile']);
    $displayTest = str_replace('/', '\\', (string) $module['TestFile']);

    $stdoutPath = $workDir . sprintf('/stdout-%02d.txt', $number);
    $stderrPath = $workDir . sprintf('/stderr-%02d.txt', $number);
    foreach ([$stdoutPath, $stderrPath] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    $command = [
        $php,
        $phpunit,
        '-c', $config,
        $relativeTest,
        '--do-not-cache-result',
        '--colors=never',
        '--testdox',
        '--log-junit', $junit,
    ];

    $descriptors = [
        1 => ['file', $stdoutPath, 'w'],
        2 => ['file', $stderrPath, 'w'],
    ];

    $started = microtime(true);
    $process = proc_open($command, $descriptors, $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException("No se pudo ejecutar PHPUnit para el módulo {$number}.");
    }
    $exitCode = proc_close($process);
    $elapsed = microtime(true) - $started;

    $raw = is_file($stdoutPath) ? (string) file_get_contents($stdoutPath) : '';
    $errorOutput = is_file($stderrPath) ? (string) file_get_contents($stderrPath) : '';

    if (!is_file($junit)) {
        throw new RuntimeException("PHPUnit no generó el JUnit del módulo {$number} ({$module['Model']}).\n{$raw}\n{$errorOutput}");
    }

    $report = new DOMDocument();
    if (!@$report->loadXML((string) file_get_contents($junit))) {
        throw new RuntimeException("El JUnit del módulo {$number} no es XML válido.");
    }

    $suite = $report->getElementsByTagName('testsuite')->item(0);
    if ($suite === null) {
        throw new RuntimeException("El JUnit del módulo {$number} no contiene <testsuite>.");
    }

    $tests = (int) $suite->getAttribute('tests');
    $assertions = (int) $suite->getAttribute('assertions');
    $failures = (int) $suite->getAttribute('failures');
    $errors = (int) $suite->getAttribute('errors');
    $skipped = (int) $suite->getAttribute('skipped');
    $time = (float) $suite->getAttribute('time');

    $capture = $capturesDir . sprintf('/captura-%02d-%s.png', $number, $module['Model']);
    $lines = $raw !== '' ? preg_split('/\r?\n/', $raw) : ['(sin salida de PHPUnit)'];
    $lines = is_array($lines) ? $lines : ['(sin salida de PHPUnit)'];

    [$captureWidth, $captureHeight] = renderScreenshot(
        $lines,
        $capture,
        (int) $module['ImageWidth'],
        $chrome,
        $workDir
    );

    $status = ($failures > 0 || $errors > 0) ? 'FAIL' : 'PASS';

    return [
        'Module'        => $module,
        'Tests'         => $tests,
        'Assertions'    => $assertions,
        'Failures'      => $failures,
        'Errors'        => $errors,
        'Skipped'       => $skipped,
        'Time'          => $time,
        'Elapsed'       => $elapsed,
        'ExitCode'      => $exitCode,
        'Status'        => $status,
        'Capture'       => $capture,
        'CaptureWidth'  => $captureWidth,
        'CaptureHeight' => $captureHeight,
        'Output'        => $raw,
        'ErrorOutput'   => $errorOutput,
    ];
}

function resultLine(array $run): string
{
    $time = formatTimeEs((float) $run['Time']);
    $assertions = formatIntEs((int) $run['Assertions']);

    if ((int) $run['Failures'] > 0 || (int) $run['Errors'] > 0) {
        $passed = (int) $run['Tests'] - (int) $run['Skipped'] - (int) $run['Failures'] - (int) $run['Errors'];
        return sprintf(
            'FAIL - %d/%d casos · %d fallos · %d errores · %d omitidos · %s s',
            $passed,
            (int) $run['Tests'],
            (int) $run['Failures'],
            (int) $run['Errors'],
            (int) $run['Skipped'],
            $time
        );
    }

    if ((int) $run['Skipped'] > 0) {
        $executed = (int) $run['Tests'] - (int) $run['Skipped'];
        return sprintf(
            'PASS - %d/%d casos ejecutados · %d omitido(s) · %s aserciones · 0 fallos · 0 errores · %s s',
            $executed,
            (int) $run['Tests'],
            (int) $run['Skipped'],
            $assertions,
            $time
        );
    }

    return sprintf(
        'PASS - %d/%d casos · %s aserciones · 0 fallos · 0 errores · 0 omitidos · %s s',
        (int) $run['Tests'],
        (int) $run['Tests'],
        $assertions,
        $time
    );
}

// ---------------------------------------------------------------------------
// Edición del documento Word
// ---------------------------------------------------------------------------
function setParagraphText(DOMXPath $xpath, ?DOMNode $paragraph, string $text): void
{
    if (!$paragraph instanceof DOMNode) {
        return;
    }

    $nodes = $xpath->query('.//w:t', $paragraph);
    if ($nodes === false || $nodes->length === 0) {
        return;
    }

    $nodes->item(0)->nodeValue = $text;
    for ($i = 1; $i < $nodes->length; $i++) {
        $nodes->item($i)->nodeValue = '';
    }
}

function findBodyParagraph(DOMXPath $xpath, string $prefix): ?DOMNode
{
    $paragraphs = $xpath->query('//w:body/w:p');
    if ($paragraphs === false) {
        return null;
    }

    foreach ($paragraphs as $paragraph) {
        $text = '';
        $nodes = $xpath->query('.//w:t', $paragraph);
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $text .= $node->nodeValue;
            }
        }
        $text = trim(preg_replace('/^[\x{2022}\x{00B7}\x{25CF}\x{25AA}\x{25E6}\x{2023}\x{2043}\x{2013}\x{2014}\-]\s*/u', '', trim($text)) ?? trim($text));

        if (str_starts_with($text, $prefix)) {
            return $paragraph;
        }
    }

    return null;
}

/**
 * @param list<array<string,mixed>> $runs
 */
function fillDocument(
    string $template,
    string $outPath,
    array $runs,
    string $phpVersion,
    string $phpUnitVersion
): void {
    $read = new ZipArchiveLite($template);

    $document = $read->content('word/document.xml');
    $relsXml = $read->content('word/_rels/document.xml.rels');

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;
    if (!@$dom->loadXML($document)) {
        throw new RuntimeException('word/document.xml no es XML válido.');
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', NS_W);
    $xpath->registerNamespace('a', NS_A);
    $xpath->registerNamespace('r', NS_R);
    $xpath->registerNamespace('wp', NS_WP);

    $tables = $xpath->query('//w:body/w:tbl');
    if ($tables === false || $tables->length !== 31) {
        $found = $tables === false ? 0 : $tables->length;
        throw new RuntimeException("El formato original contiene {$found} cuadros; se esperaban 31.");
    }

    $relDom = new DOMDocument();
    if (!@$relDom->loadXML($relsXml)) {
        throw new RuntimeException('document.xml.rels no es XML válido.');
    }

    $relMap = [];
    foreach ($relDom->getElementsByTagName('Relationship') as $rel) {
        $relMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
    }

    $replacements = [];

    foreach ($runs as $run) {
        $module = $run['Module'];
        $table = $tables->item((int) $module['Index']);
        if (!$table instanceof DOMNode) {
            throw new RuntimeException("No se encontró el cuadro del módulo {$module['Number']}.");
        }

        $rows = $xpath->query('./w:tr', $table);
        if ($rows === false || $rows->length < 6) {
            throw new RuntimeException("El cuadro {$module['Number']} no tiene las 6 filas esperadas.");
        }

        $resultCells = $xpath->query('./w:tc', $rows->item(4));
        if ($resultCells === false || $resultCells->length < 2) {
            throw new RuntimeException("El cuadro {$module['Number']} no tiene la columna de resultado.");
        }
        $resultCell = $resultCells->item(1);

        // 1) Texto de resultado
        $resultParagraphs = $xpath->query('./w:p', $resultCell);
        if ($resultParagraphs !== false && $resultParagraphs->length > 0) {
            $firstResult = $resultParagraphs->item(0);
            setParagraphText($xpath, $firstResult, resultLine($run));

            $textRuns = $xpath->query('./w:r[.//w:t]', $firstResult);
            $textRun = $textRuns !== false ? $textRuns->item(0) : null;
            if ($textRun instanceof DOMNode) {
                $colors = $xpath->query('./w:rPr/w:color', $textRun);
                $color = $colors !== false ? $colors->item(0) : null;
                if ($color instanceof DOMElement) {
                    $attribute = $color->getAttributeNodeNS(NS_W, 'val');
                    if ($attribute instanceof DOMAttr) {
                        $attribute->value = $run['Status'] === 'PASS' ? '006100' : 'C00000';
                    }
                }
            }
        }

        // 2) Total de casos del cuadro
        $caseCells = $xpath->query('./w:tc', $rows->item(3));
        if ($caseCells !== false && $caseCells->length >= 2) {
            $caseParagraphs = $xpath->query('./w:p', $caseCells->item(1));
            if ($caseParagraphs !== false && $caseParagraphs->length >= 2) {
                $totalText = (int) $run['Skipped'] > 0
                    ? sprintf(
                        'Total: %d casos automatizados (%d ejecutados y %d omitido(s) justificado(s)), con evidencia en la captura.',
                        (int) $run['Tests'],
                        (int) $run['Tests'] - (int) $run['Skipped'],
                        (int) $run['Skipped']
                    )
                    : sprintf(
                        'Total: %d casos automatizados, ejecutados con el comando PHPUnit del módulo y evidencia en la captura.',
                        (int) $run['Tests']
                    );
                setParagraphText($xpath, $caseParagraphs->item(1), $totalText);
            }
        }

        // 3) Observaciones: avisar si esta corrida trae fallos
        if ((int) $run['Failures'] > 0 || (int) $run['Errors'] > 0) {
            $obsCells = $xpath->query('./w:tc', $rows->item(5));
            if ($obsCells !== false && $obsCells->length >= 2) {
                $obsParagraphs = $xpath->query('./w:p', $obsCells->item(1));
                if ($obsParagraphs !== false && $obsParagraphs->length > 0) {
                    $previous = '';
                    $obsTexts = $xpath->query('.//w:t', $obsParagraphs->item(0));
                    if ($obsTexts !== false) {
                        foreach ($obsTexts as $node) {
                            $previous .= $node->nodeValue;
                        }
                    }
                    $note = sprintf(
                        ' Esta corrida registró %d fallos y %d errores; revisar el JUnit de la ejecución.',
                        (int) $run['Failures'],
                        (int) $run['Errors']
                    );
                    setParagraphText($xpath, $obsParagraphs->item(0), rtrim($previous) . $note);
                }
            }
        }

        // 4) Captura nueva: mismo rId, bytes nuevos
        $blip = $xpath->query('.//a:blip', $resultCell)->item(0);
        if ($blip instanceof DOMElement) {
            $embedId = $blip->getAttributeNS(NS_R, 'embed');
            $target = $relMap[$embedId] ?? null;
            if ($target !== null && $read->has('word/' . $target)) {
                $replacements['word/' . $target] = (string) file_get_contents($run['Capture']);
            }

            $docPr = $xpath->query('.//wp:docPr', $resultCell)->item(0);
            if ($docPr instanceof DOMElement) {
                $docPr->setAttribute('descr', sprintf('Salida de PHPUnit del módulo %02d (%s)', (int) $module['Number'], $module['Model']));
            }

            // El marco se ajusta a la proporción real de la captura.
            if ((int) $run['CaptureWidth'] > 0 && (int) $run['CaptureHeight'] > 0) {
                $ratio = (int) $run['CaptureWidth'] / (int) $run['CaptureHeight'];

                $extent = $xpath->query('.//wp:extent', $resultCell)->item(0);
                $newCy = 0;
                if ($extent instanceof DOMElement) {
                    $cx = (float) $extent->getAttribute('cx');
                    $newCy = (int) round($cx / $ratio);
                    $extent->setAttribute('cy', (string) $newCy);
                }

                $drawingExtent = $xpath->query('.//a:xfrm/a:ext', $resultCell)->item(0);
                if ($drawingExtent instanceof DOMElement && $newCy > 0) {
                    $drawingExtent->setAttribute('cy', (string) $newCy);
                }
            }
        }
    }

    // 5) Resumen general
    $executed = count($runs);
    $partial = $executed < 31;

    $sumTests = 0;
    $sumAssertions = 0;
    $sumFailures = 0;
    $sumErrors = 0;
    $sumSkipped = 0;
    $sumTime = 0.0;
    $exitCode = 0;

    foreach ($runs as $run) {
        $sumTests += (int) $run['Tests'];
        $sumAssertions += (int) $run['Assertions'];
        $sumFailures += (int) $run['Failures'];
        $sumErrors += (int) $run['Errors'];
        $sumSkipped += (int) $run['Skipped'];
        $sumTime += (float) $run['Time'];
        $exitCode = max($exitCode, (int) $run['ExitCode']);
    }

    $suiteParagraph = findBodyParagraph($xpath, 'Suite:');
    if ($suiteParagraph !== null) {
        setParagraphText($xpath, $suiteParagraph, sprintf(
            '• Suite: PHPUnit %s · PHP %s · SQLite en memoria con el esquema real del sistema.',
            $phpUnitVersion,
            $phpVersion
        ));
    }

    $globalParagraph = findBodyParagraph($xpath, 'Resultado global');
    if ($globalParagraph !== null) {
        $scope = $partial
            ? sprintf('Resultado global (parcial, %d de 31 módulos)', $executed)
            : 'Resultado global';
        setParagraphText($xpath, $globalParagraph, sprintf(
            '• %s: %s casos · %s aserciones · %d fallos · %d errores · %d casos omitidos justificados.',
            $scope,
            formatIntEs($sumTests),
            formatIntEs($sumAssertions),
            $sumFailures,
            $sumErrors,
            $sumSkipped
        ));
    }

    $timeParagraph = findBodyParagraph($xpath, 'Tiempo total');
    if ($timeParagraph !== null) {
        setParagraphText($xpath, $timeParagraph, sprintf(
            '• Tiempo total: %s s · Código de salida: %d.',
            formatTimeEs($sumTime),
            $exitCode
        ));
    }

    if ($partial) {
        $coverageParagraph = findBodyParagraph($xpath, 'Cobertura:');
        if ($coverageParagraph !== null) {
            setParagraphText($xpath, $coverageParagraph, sprintf(
                '• Cobertura: %d de 31 módulos ejecutados en esta corrida parcial; el resto conserva los datos del formato.',
                $executed
            ));
        }
    }

    if (!$partial) {
        $finalParagraph = findBodyParagraph($xpath, 'Observación general');
        if ($finalParagraph !== null) {
            setParagraphText($xpath, $finalParagraph, sprintf(
                'Observación general: la suite completa finalizó con código de salida %d. Los %d casos omitidos corresponden a operaciones no portables a SQLite y se justifican en cada módulo. Evidencia detallada: tests/phpunit.xml, tests/Integration/ y reporte JUnit de la ejecución.',
                $exitCode,
                $sumSkipped
            ));
        }
    }

    $dom->xmlStandalone = true;
    $replacements['word/document.xml'] = (string) $dom->saveXML();

    // 6) Metadatos: fecha de modificación
    if ($read->has('docProps/core.xml')) {
        $core = $read->content('docProps/core.xml');
        $utc = gmdate('Y-m-d\TH:i:s\Z');
        $core = preg_replace(
            '/(<dcterms:modified[^>]*>)[^<]*(<\/dcterms:modified>)/',
            '${1}' . $utc . '${2}',
            $core
        ) ?? $core;
        $replacements['docProps/core.xml'] = $core;
    }

    $outDir = dirname($outPath);
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }

    if (realpath($outPath) === realpath($template)) {
        throw new RuntimeException('La salida no puede sobrescribir el formato original.');
    }

    $read->writeTo($outPath, $replacements);
}

// ---------------------------------------------------------------------------
// Inicio
// ---------------------------------------------------------------------------
echo PHP_EOL . '===== Formato Word de pruebas de integración =====' . PHP_EOL . PHP_EOL;

if (!is_file($PHPUNIT)) {
    fwrite(STDERR, "ERROR: falta PHPUnit en {$PHPUNIT}. Ejecuta composer install." . PHP_EOL);
    exit(3);
}

if (!is_file($TEMPLATE)) {
    fwrite(STDERR, "ERROR: falta el formato original en {$TEMPLATE}." . PHP_EOL);
    exit(3);
}

if (!is_dir($RESULTS) && !mkdir($RESULTS, 0777, true) && !is_dir($RESULTS)) {
    fwrite(STDERR, "ERROR: no se pudo crear {$RESULTS}." . PHP_EOL);
    exit(3);
}
if (!is_dir($CAPTURES) && !mkdir($CAPTURES, 0777, true) && !is_dir($CAPTURES)) {
    fwrite(STDERR, "ERROR: no se pudo crear {$CAPTURES}." . PHP_EOL);
    exit(3);
}

$phpVersion = PHP_VERSION;
$versionLine = @shell_exec(escapeshellarg($PHP_BIN) . ' ' . escapeshellarg($PHPUNIT) . ' --version 2>&1');
$phpUnitVersion = is_string($versionLine) && preg_match('/PHPUnit\s+([0-9.]+)/', $versionLine, $match) === 1
    ? $match[1]
    : '11.x';

try {
    $chrome = findChrome();
} catch (RuntimeException $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(3);
}

$catalog = moduleCatalog($CONFIG, $ROOT);
if (count($catalog) !== 31) {
    fwrite(STDERR, sprintf(
        'ERROR: se esperaban 31 módulos en tests/phpunit.xml y se encontraron %d.%s',
        count($catalog),
        PHP_EOL
    ));
    exit(3);
}

try {
    $widths = templateImageWidths(new ZipArchiveLite($TEMPLATE));
} catch (RuntimeException $exception) {
    $widths = [];
    fwrite(STDERR, 'Aviso: no se pudieron leer los anchos del formato; se usará 707 px. ' . $exception->getMessage() . PHP_EOL);
}

foreach ($catalog as $index => &$module) {
    $module['ImageWidth'] = $widths[$index] ?? 707;
}
unset($module);

$selected = selectModules($catalog, (string) $OPTIONS['module']);
if ($selected === []) {
    fwrite(STDERR, "ERROR: '{$OPTIONS['module']}' no coincide con ningún módulo (01-31 o nombre del modelo)." . PHP_EOL);
    exit(2);
}

$workDir = sys_get_temp_dir() . '/integration-doc-' . bin2hex(random_bytes(8));
if (!mkdir($workDir, 0777, true) && !is_dir($workDir)) {
    fwrite(STDERR, "ERROR: no se pudo crear el directorio temporal {$workDir}." . PHP_EOL);
    exit(3);
}

$runs = [];
$failuresTotal = 0;

try {
    foreach ($selected as $module) {
        echo sprintf('[%02d/31] %-16s ', (int) $module['Number'], (string) $module['Model']);

        $run = runModule($module, $PHP_BIN, $PHPUNIT, $CONFIG, $ROOT, $workDir, $chrome, $CAPTURES);
        $runs[] = $run;
        if ($run['Status'] === 'FAIL') {
            $failuresTotal++;
        }

        echo sprintf(
            "%s  %d/%d casos · %s aserciones · %s s\n",
            $run['Status'],
            (int) $run['Tests'] - (int) $run['Skipped'],
            (int) $run['Tests'],
            formatIntEs((int) $run['Assertions']),
            formatTimeEs((float) $run['Time'])
        );
    }

    $output = (string) $OPTIONS['output'];
    if (trim($output) === '') {
        $output = $RESULTS . '/Formato-Prueba-Integración-' . date('Ymd-His') . '.docx';
    } elseif (!str_starts_with($output, '/') && preg_match('/^[A-Za-z]:[\\\\\/]/', $output) !== 1) {
        $output = getcwd() . '/' . $output;
    }

    echo PHP_EOL . 'Generando copia del formato Word...' . PHP_EOL;
    fillDocument($TEMPLATE, $output, $runs, $phpVersion, $phpUnitVersion);

    $manifestModules = [];
    foreach ($runs as $run) {
        $manifestModules[] = [
            'numero'     => (int) $run['Module']['Number'],
            'modelo'     => $run['Module']['Model'],
            'archivo'    => $run['Module']['TestFile'],
            'casos'      => (int) $run['Tests'],
            'aserciones' => (int) $run['Assertions'],
            'fallos'     => (int) $run['Failures'],
            'errores'    => (int) $run['Errors'],
            'omitidos'   => (int) $run['Skipped'],
            'tiempo'     => (float) $run['Time'],
            'estado'     => $run['Status'],
            'salida'     => (int) $run['ExitCode'],
            'captura'    => $run['Capture'],
        ];
    }

    $manifest = [
        'generado'  => date(DATE_ATOM),
        'documento' => $output,
        'plantilla' => $TEMPLATE,
        'php'       => $phpVersion,
        'phpunit'   => $phpUnitVersion,
        'modulos'   => $manifestModules,
    ];

    file_put_contents(
        $MANIFEST,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );

    $sumTests = 0;
    $sumAssertions = 0;
    $sumSkipped = 0;
    $sumTime = 0.0;
    foreach ($runs as $run) {
        $sumTests += (int) $run['Tests'];
        $sumAssertions += (int) $run['Assertions'];
        $sumSkipped += (int) $run['Skipped'];
        $sumTime += (float) $run['Time'];
    }

    echo PHP_EOL . '================ RESUMEN ================' . PHP_EOL;
    echo 'Módulos ejecutados : ' . count($runs) . ' de 31' . PHP_EOL;
    echo 'Casos              : ' . formatIntEs($sumTests) . PHP_EOL;
    echo 'Aserciones         : ' . formatIntEs($sumAssertions) . PHP_EOL;
    echo 'Omitidos           : ' . $sumSkipped . PHP_EOL;
    echo 'Tiempo total       : ' . formatTimeEs($sumTime) . ' s' . PHP_EOL;
    echo 'Módulos con fallos : ' . $failuresTotal . PHP_EOL . PHP_EOL;
    echo 'Documento          : ' . $output . PHP_EOL;
    echo 'Capturas           : ' . $CAPTURES . PHP_EOL;
    echo 'Manifiesto         : ' . $MANIFEST . PHP_EOL . PHP_EOL;

    if ($failuresTotal > 0) {
        echo 'RESULTADO: hay módulos con fallos. Revisa el JUnit y las observaciones.' . PHP_EOL;
        exit(1);
    }

    echo 'RESULTADO: copia generada sin fallos.' . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, PHP_EOL . 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(3);
} finally {
    if (!$OPTIONS['keep-working']) {
        removeDirectory($workDir);
    } else {
        echo 'JUnit temporales: ' . $workDir . PHP_EOL;
    }
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $directory . '/' . $item;
        if (is_dir($path) && !is_link($path)) {
            removeDirectory($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($directory);
}
