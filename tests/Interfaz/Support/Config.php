<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

final class Config
{
    private static bool $envLoaded = false;

    public static function baseUrl(): string
    {
        return rtrim(self::env('INTERFAZ_BASE_URL', 'http://localhost/sys-inescolara/'), '/') . '/';
    }

    public static function driverPath(): string
    {
        $file = PHP_OS_FAMILY === 'Windows' ? 'chromedriver.exe' : 'chromedriver';

        return self::env('INTERFAZ_DRIVER', PROJECT_ROOT . '/tests/Interfaz/bin/' . $file);
    }

    /**
     * Navegador que controlará ChromeDriver. Si no se indica, se detecta el
     * primero disponible (Chrome, Chromium o Brave).
     */
    public static function browserBinary(): string
    {
        $explicit = self::env('INTERFAZ_BROWSER_BIN', self::env('CHROME_BIN', ''));
        if ($explicit !== '') {
            return $explicit;
        }

        foreach (self::browserCandidates() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private static function browserCandidates(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            ];
        }

        return [
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
    }

    public static function headless(): bool
    {
        return filter_var(self::env('INTERFAZ_HEADLESS', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function user(): string
    {
        return self::env('INTERFAZ_USER', 'e2e_admin');
    }

    public static function password(): string
    {
        return self::env('INTERFAZ_PASSWORD', 'E2eAdmin123!');
    }

    public static function runId(): string
    {
        static $runId = null;
        if ($runId === null) {
            $runId = self::env('INTERFAZ_RUN_ID', date('Ymd_His') . '_' . bin2hex(random_bytes(3)));
        }
        return $runId;
    }

    public static function resultsDir(): string
    {
        return self::ensureDir(self::env('INTERFAZ_RESULTS', PROJECT_ROOT . '/tests/tools/interfaz/formatos/resultados'));
    }

    public static function screenshotsDir(): string
    {
        return self::ensureDir(self::resultsDir() . '/screenshots');
    }

    public static function downloadsDir(): string
    {
        return self::ensureDir(self::ensureDir(self::resultsDir()) . '/downloads');
    }

    public static function dbHost(): string
    {
        return self::env('DB_HOST', 'localhost');
    }

    public static function dbPort(): string
    {
        return self::env('DB_PORT', '3306');
    }

    public static function dbName(): string
    {
        return self::env('DB_NAME', 'sysinescolara');
    }

    public static function dbUser(): string
    {
        return self::env('DB_USER', 'root');
    }

    public static function dbPassword(): string
    {
        return self::env('DB_PASSWORD', '');
    }

    public static function securityDbHost(): string
    {
        return self::env('DB_SEC_HOST', self::dbHost());
    }

    public static function securityDbPort(): string
    {
        return self::env('DB_SEC_PORT', self::dbPort());
    }

    public static function securityDbName(): string
    {
        return self::env('DB_SEC_NAME', 'SysInescolara-Seguridad');
    }

    public static function securityDbUser(): string
    {
        return self::env('DB_SEC_USER', self::dbUser());
    }

    public static function securityDbPassword(): string
    {
        return self::env('DB_SEC_PASSWORD', self::dbPassword());
    }

    public static function env(string $key, string $default = ''): string
    {
        self::loadEnv();
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    private static function loadEnv(): void
    {
        if (self::$envLoaded) {
            return;
        }
        self::$envLoaded = true;

        $file = PROJECT_ROOT . '/.env';
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }
    }

    private static function ensureDir(string $dir): string
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio: ' . $dir);
        }
        return $dir;
    }
}
