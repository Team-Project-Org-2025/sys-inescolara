<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

final class DriverFactory
{
    private const PORT = 9515;

    private static ?RemoteWebDriver $driver = null;
    private static $process = null;
    private static bool $startedByUs = false;
    private static bool $shutdownRegistered = false;

    public static function make(): RemoteWebDriver
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $driverPath = Config::driverPath();
        if (!is_file($driverPath)) {
            throw new \RuntimeException(
                'No se encontró chromedriver.exe en ' . $driverPath . '. Ejecute tests/tools/setup-interfaz.ps1.'
            );
        }

        if (!self::portOpen(self::PORT)) {
            self::startDriver($driverPath);
        }

        $options = new ChromeOptions();
        $options->addArguments([
            '--window-size=1440,900',
            '--lang=es-VE',
            '--disable-notifications',
            '--disable-search-engine-choice-screen',
            '--disable-features=Translate,OptimizationHints',
            '--no-first-run',
            '--no-default-browser-check',
        ]);
        if (Config::headless()) {
            $options->addArguments(['--headless=new', '--disable-gpu']);
        }
        $options->setExperimentalOption('prefs', [
            'download.default_directory' => Config::downloadsDir(),
            'download.prompt_for_download' => false,
            'download.directory_upgrade' => true,
            'safebrowsing.enabled' => false,
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        self::$driver = RemoteWebDriver::create(
            'http://127.0.0.1:' . self::PORT,
            $capabilities,
            30000,
            60000
        );

        self::registerShutdown();
        return self::$driver;
    }

    public static function stop(): void
    {
        if (self::$driver !== null) {
            try {
                self::$driver->quit();
            } catch (\Throwable) {
            }
            self::$driver = null;
        }

        if (is_resource(self::$process)) {
            $status = proc_get_status(self::$process);
            if (($status['running'] ?? false) && !empty($status['pid'])) {
                @exec('taskkill /F /T /PID ' . (int) $status['pid'] . ' 2>NUL');
            }
            @proc_terminate(self::$process);
            @proc_close(self::$process);
            self::$process = null;
        }
        self::$startedByUs = false;
    }

    private static function startDriver(string $driverPath): void
    {
        $log = Config::resultsDir() . '/chromedriver.log';
        $out = Config::resultsDir() . '/chromedriver.out.log';
        $err = Config::resultsDir() . '/chromedriver.err.log';
        $command = [
            $driverPath,
            '--port=' . self::PORT,
            '--verbose',
            '--log-path=' . $log,
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $out, 'a'],
            2 => ['file', $err, 'a'],
        ];
        $process = proc_open($command, $descriptors, $pipes, PROJECT_ROOT);
        if (!is_resource($process)) {
            throw new \RuntimeException('No se pudo iniciar ChromeDriver.');
        }
        if (isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        self::$process = $process;
        self::$startedByUs = true;

        $deadline = microtime(true) + 20;
        while (microtime(true) < $deadline) {
            if (self::portOpen(self::PORT)) {
                return;
            }
            $status = proc_get_status($process);
            if (!($status['running'] ?? false)) {
                $detail = trim((string) @file_get_contents($err));
                throw new \RuntimeException('ChromeDriver terminó inesperadamente. ' . $detail);
            }
            usleep(200000);
        }

        throw new \RuntimeException('ChromeDriver no abrió el puerto ' . self::PORT . ' a tiempo.');
    }

    private static function portOpen(int $port): bool
    {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
        if (is_resource($socket)) {
            fclose($socket);
            return true;
        }
        return false;
    }

    private static function registerShutdown(): void
    {
        if (self::$shutdownRegistered) {
            return;
        }
        self::$shutdownRegistered = true;
        register_shutdown_function(static function (): void {
            self::stop();
        });
    }
}
