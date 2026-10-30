<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Pages;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use SysInescolara\Tests\Interfaz\Support\BrowserTrait;

abstract class BasePage
{
    use BrowserTrait;

    protected RemoteWebDriver $driver;
    protected string $base;

    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
        $this->base = \SysInescolara\Tests\Interfaz\Support\Config::baseUrl();
    }

    protected function driver(): RemoteWebDriver
    {
        return $this->driver;
    }

    public function open(string $path): static
    {
        $this->driver->navigate()->to($this->base . ltrim($path, '/'));
        return $this;
    }
}
