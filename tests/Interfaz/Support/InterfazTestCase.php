<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

abstract class InterfazTestCase extends TestCase
{
    use BrowserTrait;

    protected bool $requiresAuth = true;
    protected static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->requiresAuth) {
            Session::ensureLoggedIn();
        }
    }

    protected function tearDown(): void
    {
        $status = $this->status();
        if ($status->isFailure() || $status->isError()) {
            $file = $this->screenshot($this->name() . '_' . $this->dataName());
            fwrite(STDERR, "\n[Interfaz] Captura de fallo: " . $file . "\n");
        }
        parent::tearDown();
    }

    protected function driver(): RemoteWebDriver
    {
        return Session::driver();
    }

    protected function unique(string $prefix, int $maxLength = 40): string
    {
        self::$sequence++;
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $hash = hash('sha256', Config::runId() . '|' . $prefix . '|' . self::$sequence);
        $token = '';
        for ($i = 0; $i < 8; $i++) {
            $token .= $alphabet[hexdec($hash[$i]) % 26];
        }

        $value = 'EAA ' . $token . ' ' . $prefix;
        return mb_substr($value, 0, $maxLength);
    }

    protected function assertPageContains(string $text): void
    {
        $body = $this->bodyText();
        $this->assertStringContainsString($text, $body, 'La página no contiene: ' . $text);
    }

    protected function assertHeadingContains(string $text): void
    {
        $heading = $this->driver()->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector('h1'));
        $this->assertNotEmpty($heading, 'La página no tiene h1');
        $this->assertStringContainsString($text, trim((string) $heading[0]->getText()));
    }
}
