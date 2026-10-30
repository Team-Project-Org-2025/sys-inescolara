<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use SysInescolara\Tests\Interfaz\Pages\LoginPage;

final class Session
{
    private static ?RemoteWebDriver $driver = null;
    private static bool $loggedIn = false;

    public static function driver(): RemoteWebDriver
    {
        if (self::$driver === null) {
            self::$driver = DriverFactory::make();
        }
        return self::$driver;
    }

    public static function isLoggedIn(): bool
    {
        return self::$loggedIn;
    }

    public static function ensureLoggedIn(): void
    {
        if (self::$loggedIn) {
            return;
        }

        $driver = self::driver();
        $driver->navigate()->to(Config::baseUrl() . 'dashboard');

        if (str_contains((string) $driver->getCurrentURL(), '/login')) {
            (new LoginPage($driver))->login(Config::user(), Config::password());
        }

        self::$loggedIn = true;
    }

    public static function login(): void
    {
        $driver = self::driver();
        (new LoginPage($driver))->login(Config::user(), Config::password());
        self::$loggedIn = true;
    }

    public static function logout(): void
    {
        $driver = self::driver();
        $driver->navigate()->to(Config::baseUrl() . 'login/logout');
        self::$loggedIn = false;
    }

    public static function markLoggedIn(bool $loggedIn = true): void
    {
        self::$loggedIn = $loggedIn;
    }

    public static function stop(): void
    {
        self::$loggedIn = false;
        self::$driver = null;
        DriverFactory::stop();
    }
}
