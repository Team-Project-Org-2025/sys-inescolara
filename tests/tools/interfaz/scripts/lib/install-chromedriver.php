<?php

declare(strict_types=1);

/**
 * Instalador de ChromeDriver para las pruebas de interfaz.
 *
 * Detecta el navegador instalado (Chrome, Chromium o Brave), consulta el
 * catálogo de Chrome for Testing y descarga la versión de ChromeDriver que
 * coincide con su versión mayor. Así el binario queda en la ruta que espera
 * tests/Interfaz/Support/Config sin intervención manual.
 *
 * Uso:
 *   php tests/tools/interfaz/scripts/lib/install-chromedriver.php
 *   php tests/tools/interfaz/scripts/lib/install-chromedriver.php --force
 *   php tests/tools/interfaz/scripts/lib/install-chromedriver.php --browser=/usr/bin/brave
 *   php tests/tools/interfaz/scripts/lib/install-chromedriver.php --output=/tmp/chromedriver
 *
 * Variables de entorno:
 *   INTERFAZ_BROWSER_BIN / CHROME_BIN  Navegador a usar (si no se pasa --browser).
 *   INTERFAZ_DRIVER                   Destino del binario (si no se pasa --output).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este instalador solo se ejecuta desde la línea de comandos.\n");
    exit(1);
}

const CFT_KNOWN_GOOD = 'https://googlechromelabs.github.io/chrome-for-testing/known-good-versions-with-downloads.json';
const CFT_LAST_GOOD = 'https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json';

$ROOT = dirname(__DIR__, 5);

// ---------------------------------------------------------------------------
// Argumentos
// ---------------------------------------------------------------------------
$options = [
    'output'  => '',
    'browser' => '',
    'force'   => false,
    'help'    => false,
];

$args = $argv;
array_shift($args);
for ($i = 0; $i < count($args); $i++) {
    $arg = $args[$i];
    $value = null;

    if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
        [$arg, $value] = explode('=', $arg, 2);
    }

    switch ($arg) {
        case '--output':
            $options['output'] = $value ?? ($args[++$i] ?? '');
            break;
        case '--browser':
            $options['browser'] = $value ?? ($args[++$i] ?? '');
            break;
        case '--force':
            $options['force'] = true;
            break;
        case '-h':
        case '--help':
            $options['help'] = true;
            break;
        default:
            fwrite(STDERR, "Opción no reconocida: {$arg}\n");
            exit(2);
    }
}

if ($options['help']) {
    echo <<<TXT
    Instalador de ChromeDriver para las pruebas de interfaz

    Uso:
      php tests/tools/interfaz/scripts/lib/install-chromedriver.php [opciones]

    Opciones:
      --output=RUTA    Ruta del binario a escribir (por defecto tests/Interfaz/bin)
      --browser=RUTA   Navegador del que tomar la versión (Chrome, Chromium, Brave)
      --force          Vuelve a descargar aunque el binario ya exista
      -h, --help       Muestra esta ayuda


    TXT;
    exit(0);
}

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------
/**
 * @return list<string>
 */
function browserCandidates(): array
{
    $candidates = [];

    if (PHP_OS_FAMILY === 'Windows') {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            'C:\\Program Files (x86)\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
        ];
    } else {
        $candidates = [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/opt/google/chrome/chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
            '/usr/bin/brave',
            '/usr/bin/brave-browser',
            '/opt/brave.com/brave/brave',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser',
        ];

        foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', 'brave', 'brave-browser'] as $name) {
            $found = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
            if (is_string($found) && trim($found) !== '') {
                $candidates[] = trim($found);
            }
        }
    }

    return array_values(array_unique($candidates));
}

function detectBrowser(string $explicit): ?string
{
    if ($explicit !== '') {
        if (!is_file($explicit)) {
            throw new RuntimeException("El navegador indicado no existe: {$explicit}");
        }
        return $explicit;
    }

    foreach (['INTERFAZ_BROWSER_BIN', 'CHROME_BIN', 'CHROME_PATH'] as $variable) {
        $value = getenv($variable);
        if (is_string($value) && $value !== '' && is_file($value)) {
            return $value;
        }
    }

    foreach (browserCandidates() as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function browserMajor(string $binary): ?int
{
    $version = @shell_exec(escapeshellarg($binary) . ' --version 2>/dev/null');
    if (!is_string($version) || trim($version) === '') {
        return null;
    }

    if (preg_match('/(\d+)\.\d+\.\d+/', $version, $match) !== 1) {
        return null;
    }

    return (int) $match[1];
}

function cftPlatform(): string
{
    $arch = strtolower((string) php_uname('m'));
    $bits = PHP_INT_SIZE * 8;

    if (PHP_OS_FAMILY === 'Windows') {
        return $bits === 64 ? 'win64' : 'win32';
    }

    $isArm = str_contains($arch, 'arm') || str_contains($arch, 'aarch64');

    if (PHP_OS_FAMILY === 'Darwin') {
        return $isArm ? 'mac-arm64' : 'mac-x64';
    }

    return $isArm ? 'linux-arm64' : 'linux64';
}

function driverFileName(): string
{
    return PHP_OS_FAMILY === 'Windows' ? 'chromedriver.exe' : 'chromedriver';
}

function httpGet(string $url): string
{
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_USERAGENT      => 'sys-inescolara-tests',
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $status !== 200) {
            throw new RuntimeException("No se pudo descargar {$url} (HTTP {$status}). {$error}");
        }

        return $body;
    }

    $context = stream_context_create([
        'http' => ['timeout' => 180, 'user_agent' => 'sys-inescolara-tests'],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $context);
    if (!is_string($body)) {
        throw new RuntimeException("No se pudo descargar {$url}. Revisa la conexión a Internet.");
    }

    return $body;
}

/**
 * @return array{version:string,url:string}
 */
function findDriverDownload(string $platform, ?int $major): array
{
    $catalog = json_decode(httpGet(CFT_KNOWN_GOOD), true);
    $versions = is_array($catalog['versions'] ?? null) ? $catalog['versions'] : [];

    $best = null;
    $fallback = null;

    foreach ($versions as $entry) {
        $version = (string) ($entry['version'] ?? '');
        $drivers = $entry['downloads']['chromedriver'] ?? null;
        if ($version === '' || !is_array($drivers)) {
            continue;
        }

        $url = null;
        foreach ($drivers as $download) {
            if (($download['platform'] ?? '') === $platform) {
                $url = (string) ($download['url'] ?? '');
                break;
            }
        }
        if ($url === null || $url === '') {
            continue;
        }

        $candidate = ['version' => $version, 'url' => $url];

        // El catálogo viene en orden ascendente: el último encontrado es el más nuevo.
        if ($major !== null && (int) explode('.', $version)[0] === $major) {
            $best = $candidate;
        }
        $fallback = $candidate;
    }

    if ($best !== null) {
        return $best;
    }

    if ($major === null && $fallback !== null) {
        return $fallback;
    }

    if ($major !== null) {
        $lastGood = json_decode(httpGet(CFT_LAST_GOOD), true);
        foreach (['Stable', 'Beta', 'Dev', 'Canary'] as $channel) {
            $drivers = $lastGood['channels'][$channel]['downloads']['chromedriver'] ?? null;
            if (!is_array($drivers)) {
                continue;
            }
            foreach ($drivers as $download) {
                if (($download['platform'] ?? '') === $platform) {
                    return [
                        'version' => (string) ($lastGood['channels'][$channel]['version'] ?? 'desconocida'),
                        'url'     => (string) ($download['url'] ?? ''),
                    ];
                }
            }
        }
    }

    throw new RuntimeException("No hay ChromeDriver publicado para la plataforma {$platform}.");
}

function extractDriver(string $zipBytes, string $output): void
{
    $temp = tempnam(sys_get_temp_dir(), 'chromedriver-');
    if ($temp === false) {
        throw new RuntimeException('No se pudo crear el archivo temporal para el ZIP.');
    }

    try {
        file_put_contents($temp, $zipBytes);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($temp) !== true) {
                throw new RuntimeException('El paquete descargado no es un ZIP válido.');
            }

            $target = driverFileName();
            $written = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (basename($name) === $target) {
                    $contents = $zip->getFromIndex($i);
                    if (is_string($contents)) {
                        file_put_contents($output, $contents);
                        $written = true;
                    }
                    break;
                }
            }
            $zip->close();

            if (!$written) {
                throw new RuntimeException("El paquete descargado no contiene {$target}.");
            }

            return;
        }

        // Sin ext-zip: se intenta con las herramientas del sistema.
        if (PHP_OS_FAMILY === 'Windows') {
            $script = sprintf(
                'Expand-Archive -LiteralPath %s -DestinationPath %s -Force',
                escapeshellarg($temp),
                escapeshellarg(dirname($output))
            );
            @shell_exec('powershell -NoProfile -Command ' . escapeshellarg($script));
        } else {
            $dir = dirname($output);
            @shell_exec('unzip -o -j ' . escapeshellarg($temp) . ' ' . escapeshellarg('*/' . driverFileName()) . ' -d ' . escapeshellarg($dir) . ' 2>/dev/null');
        }

        if (!is_file($output)) {
            throw new RuntimeException(
                'No se pudo extraer ChromeDriver. Instala la extensión zip de PHP o descomprime el paquete manualmente.'
            );
        }
    } finally {
        @unlink($temp);
    }
}

// ---------------------------------------------------------------------------
// Inicio
// ---------------------------------------------------------------------------
$output = $options['output'] !== ''
    ? $options['output']
    : ($ROOT . '/tests/Interfaz/bin/' . driverFileName());

if (is_file($output) && !$options['force']) {
    $current = trim((string) @shell_exec(escapeshellarg($output) . ' --version 2>/dev/null'));
    echo 'ChromeDriver ya instalado: ' . ($current !== '' ? $current : $output) . PHP_EOL;
    echo 'Usa --force para volver a descargarlo.' . PHP_EOL;
    exit(0);
}

try {
    $browser = detectBrowser((string) $options['browser']);
    $major = $browser !== null ? browserMajor($browser) : null;

    if ($browser !== null && $major !== null) {
        echo 'Navegador detectado: ' . $browser . ' (versión mayor ' . $major . ')' . PHP_EOL;
    } else {
        echo 'No se detectó un navegador compatible; se usará la última versión estable de ChromeDriver.' . PHP_EOL;
    }

    $platform = cftPlatform();
    echo 'Plataforma: ' . $platform . PHP_EOL;

    $download = findDriverDownload($platform, $major);
    echo 'Descargando ChromeDriver ' . $download['version'] . '…' . PHP_EOL;

    $bytes = httpGet($download['url']);
    $dir = dirname($output);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("No se pudo crear el directorio {$dir}.");
    }

    extractDriver($bytes, $output);

    if (PHP_OS_FAMILY !== 'Windows') {
        @chmod($output, 0755);
    }

    $installed = trim((string) @shell_exec(escapeshellarg($output) . ' --version 2>/dev/null'));
    echo 'ChromeDriver instalado en ' . $output . PHP_EOL;
    if ($installed !== '') {
        echo $installed . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    fwrite(STDERR, 'Descarga manualmente el binario desde https://googlechromelabs.github.io/chrome-for-testing/ ' . PHP_EOL);
    fwrite(STDERR, 'colócalo en ' . $output . ' y vuelve a ejecutar la preparación.' . PHP_EOL);
    exit(3);
}
