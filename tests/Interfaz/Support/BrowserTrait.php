<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\TimeoutException;
use PHPUnit\Framework\Assert;

trait BrowserTrait
{
    abstract protected function driver(): RemoteWebDriver;

    protected function baseUrl(): string
    {
        return Config::baseUrl();
    }

    protected function visit(string $path = ''): void
    {
        $this->driver()->navigate()->to($this->baseUrl() . ltrim($path, '/'));
    }

    protected function currentUrl(): string
    {
        return (string) $this->driver()->getCurrentURL();
    }

    protected function path(): string
    {
        $path = parse_url($this->currentUrl(), PHP_URL_PATH) ?? '';
        $base = parse_url($this->baseUrl(), PHP_URL_PATH) ?? '/';
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return '/' . ltrim($path, '/');
    }

    protected function wait(int $seconds = 15): WebDriverWait
    {
        return new WebDriverWait($this->driver(), $seconds, 200);
    }

    protected function byCss(string $selector): WebDriverBy
    {
        return WebDriverBy::cssSelector($selector);
    }

    protected function waitForCss(string $selector, int $seconds = 15): WebDriverElement
    {
        return $this->wait($seconds)->until(
            WebDriverExpectedCondition::presenceOfElementLocated($this->byCss($selector))
        );
    }

    protected function waitForVisibleCss(string $selector, int $seconds = 15): WebDriverElement
    {
        return $this->wait($seconds)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($this->byCss($selector))
        );
    }

    protected function waitForText(string $selector, int $seconds = 15): string
    {
        $this->wait($seconds)->until(
            WebDriverExpectedCondition::presenceOfElementLocated($this->byCss($selector))
        );
        return trim((string) $this->driver()->findElement($this->byCss($selector))->getText());
    }

    protected function waitUntilGone(string $selector, int $seconds = 15): void
    {
        try {
            $this->wait($seconds)->until(
                WebDriverExpectedCondition::invisibilityOfElementLocated($this->byCss($selector))
            );
        } catch (NoSuchElementException) {
        } catch (StaleElementReferenceException) {
        }
    }

    protected function waitForUrlContains(string $fragment, int $seconds = 15): void
    {
        $this->wait($seconds)->until(
            fn (): bool => str_contains((string) $this->driver()->getCurrentURL(), $fragment)
        );
    }

    protected function click(string $selector): void
    {
        $element = $this->waitForVisibleCss($selector);
        $this->scrollIntoView($element);
        try {
            $element->click();
        } catch (\Throwable) {
            $this->driver()->executeScript('arguments[0].click();', [$element]);
        }
    }

    protected function clickIn(WebDriverElement $parent, string $selector): void
    {
        $element = $parent->findElement($this->byCss($selector));
        $this->scrollIntoView($element);
        try {
            $element->click();
        } catch (\Throwable) {
            $this->driver()->executeScript('arguments[0].click();', [$element]);
        }
    }

    protected function fill(string $selector, string|int|float $value): void
    {
        $element = $this->waitForVisibleCss($selector);
        $element->clear();
        $element->sendKeys((string) $value);
    }

    protected function selectOption(string $selector, string $visibleText): void
    {
        $element = $this->waitForVisibleCss($selector);
        (new WebDriverSelect($element))->selectByVisibleText($visibleText);
    }

    protected function selectValue(string $selector, string $value): void
    {
        $element = $this->waitForVisibleCss($selector);
        (new WebDriverSelect($element))->selectByValue($value);
    }

    protected function selectFirstOption(string $selector): string
    {
        $element = $this->waitForVisibleCss($selector);
        $select = new WebDriverSelect($element);
        $options = array_values(array_filter(
            $select->getOptions(),
            static fn ($option): bool => $option->isEnabled()
                && trim((string) $option->getText()) !== ''
                && (string) $option->getAttribute('value') !== ''
        ));
        if ($options === []) {
            throw new \RuntimeException('El select ' . $selector . ' no tiene opciones disponibles.');
        }
        $text = trim($options[0]->getText());
        $select->selectByVisibleText($text);
        return $text;
    }

    protected function waitForAjaxIdle(int $seconds = 15): void
    {
        $this->wait($seconds)->until(function (): bool {
            try {
                return (bool) $this->driver()->executeScript(
                    'return !window.jQuery || window.jQuery.active === 0;'
                );
            } catch (\Throwable) {
                return false;
            }
        });
    }

    protected function waitForSelectOptions(string $selector, int $seconds = 15): void
    {
        $this->wait($seconds)->until(function () use ($selector): bool {
            try {
                $element = $this->driver()->findElement($this->byCss($selector));
                foreach ($element->findElements(WebDriverBy::tagName('option')) as $option) {
                    if ((string) $option->getAttribute('value') !== '') {
                        return true;
                    }
                }
            } catch (\Throwable) {
            }
            return false;
        });
    }

    protected function setSelectValue(string $selector, string $value): void
    {
        $this->waitForCss($selector);
        $this->driver()->executeScript(
            "const el = document.querySelector(arguments[0]);"
            . "if (!el) { throw new Error('No existe ' + arguments[0]); }"
            . "el.value = arguments[1];"
            . "if (window.jQuery) { window.jQuery(el).val(arguments[1]).trigger('change'); }"
            . "el.dispatchEvent(new Event('input', {bubbles: true}));"
            . "el.dispatchEvent(new Event('change', {bubbles: true}));",
            [$selector, $value]
        );
    }

    protected function selectFirstAvailable(string $selector, int $seconds = 15): string
    {
        $this->waitForSelectOptions($selector, $seconds);
        $value = $this->driver()->executeScript(
            "const el = document.querySelector(arguments[0]);"
            . "if (!el) { throw new Error('No existe ' + arguments[0]); }"
            . "const option = [...el.options].find(o => o.value !== '' && !o.disabled);"
            . "if (!option) { return null; }"
            . "el.value = option.value;"
            . "if (window.jQuery) { window.jQuery(el).val(option.value).trigger('change'); }"
            . "el.dispatchEvent(new Event('input', {bubbles: true}));"
            . "el.dispatchEvent(new Event('change', {bubbles: true}));"
            . "return option.value;",
            [$selector]
        );
        if ($value === null || $value === '') {
            throw new \RuntimeException('El select ' . $selector . ' no tiene opciones disponibles.');
        }
        return (string) $value;
    }

    protected function setValue(string $selector, string $value): void
    {
        $this->driver()->executeScript(
            "const el = document.querySelector(arguments[0]);"
            . "if (!el) { throw new Error('No existe ' + arguments[0]); }"
            . "el.value = arguments[1];"
            . "el.dispatchEvent(new Event('input', {bubbles: true}));"
            . "el.dispatchEvent(new Event('change', {bubbles: true}));",
            [$selector, $value]
        );
    }

    protected function submitForm(string $formSelector): void
    {
        $this->driver()->executeScript(
            "const form = document.querySelector(arguments[0]);"
            . "if (!form) { throw new Error('No existe ' + arguments[0]); }"
            . "if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }",
            [$formSelector]
        );
    }

    protected function scrollIntoView(WebDriverElement $element): void
    {
        $this->driver()->executeScript('arguments[0].scrollIntoView({block: "center"});', [$element]);
    }

    protected function bodyText(): string
    {
        return trim((string) $this->driver()->findElement(WebDriverBy::tagName('body'))->getText());
    }

    protected function waitForToast(int $seconds = 15): string
    {
        $this->waitForVisibleCss('.swal2-toast', $seconds);
        return trim((string) $this->driver()->findElement($this->byCss('.swal2-toast'))->getText());
    }

    protected function assertToastContains(string $needle, int $seconds = 15): void
    {
        Assert::assertStringContainsString($needle, $this->waitForToast($seconds));
    }

    protected function waitForSwal(int $seconds = 15): void
    {
        $this->waitForVisibleCss('.swal2-popup', $seconds);
    }

    protected function confirmSwal(string $buttonText = 'Sí, eliminar'): void
    {
        $this->waitForSwal();
        $selector = '.swal2-confirm';
        $button = $this->driver()->findElement($this->byCss($selector));
        $text = trim((string) $button->getText());
        if ($text !== '' && mb_strtolower($text) !== mb_strtolower($buttonText)) {
            $selector = '//button[contains(@class, "swal2-confirm")]';
        }
        $this->click($selector);
    }

    protected function cancelSwal(): void
    {
        $this->waitForSwal();
        $this->click('.swal2-cancel');
    }

    protected function closeModal(string $modalSelector): void
    {
        $this->driver()->executeScript(
            "const modal = document.querySelector(arguments[0]);"
            . "if (modal) { modal.querySelectorAll('[data-bs-dismiss=\"modal\"]').forEach(b => b.click()); }",
            [$modalSelector]
        );
        $this->waitUntilGone($modalSelector . '.show', 10);
    }

    protected function waitForDataTable(string $tableId, int $seconds = 20): void
    {
        $this->waitForCss('#' . $tableId . '_wrapper', $seconds);
        $this->wait($seconds)->until(function () use ($tableId): bool {
            try {
                $driver = $this->driver();
                $rows = $driver->findElements(WebDriverBy::cssSelector('#' . $tableId . ' tbody tr'));
                if ($rows === []) {
                    return false;
                }
                foreach ($rows as $row) {
                    try {
                        $classes = (string) $row->getAttribute('class');
                    } catch (StaleElementReferenceException) {
                        return false;
                    }
                    if (!str_contains($classes, 'skeleton-table-row')) {
                        return true;
                    }
                }
            } catch (StaleElementReferenceException) {
                return false;
            }
            return false;
        });
    }

    protected function searchTable(string $tableId, string $term): void
    {
        $selector = '#' . $tableId . '_filter input';
        $input = $this->waitForVisibleCss($selector);
        $input->clear();
        $input->sendKeys($term);
        usleep(700000);
    }

    protected function tableRowWithText(string $tableId, string $text, int $seconds = 15): WebDriverElement
    {
        $this->searchTable($tableId, $text);
        $row = $this->wait($seconds)->until(function () use ($tableId, $text): ?WebDriverElement {
            try {
                $rows = $this->driver()->findElements(WebDriverBy::cssSelector('#' . $tableId . ' tbody tr'));
                foreach ($rows as $candidate) {
                    try {
                        $classes = (string) $candidate->getAttribute('class');
                        if (str_contains($classes, 'skeleton-table-row') || str_contains($classes, 'dataTables_empty')) {
                            continue;
                        }
                        if (str_contains($candidate->getText(), $text)) {
                            return $candidate;
                        }
                    } catch (StaleElementReferenceException) {
                        return null;
                    }
                }
            } catch (StaleElementReferenceException) {
                return null;
            }
            return null;
        });
        if (!$row instanceof WebDriverElement) {
            throw new TimeoutException('No se encontró la fila con "' . $text . '" en ' . $tableId);
        }
        return $row;
    }

    protected function assertRowExists(string $tableId, string $text): void
    {
        $row = $this->tableRowWithText($tableId, $text);
        Assert::assertStringContainsString($text, $row->getText());
    }

    protected function assertRowMissing(string $tableId, string $text, int $seconds = 15): void
    {
        $this->searchTable($tableId, $text);
        $deadline = microtime(true) + $seconds;
        do {
            try {
                $rows = $this->driver()->findElements(WebDriverBy::cssSelector('#' . $tableId . ' tbody tr'));
                $found = false;
                foreach ($rows as $row) {
                    try {
                        if (str_contains($row->getText(), $text)) {
                            $found = true;
                            break;
                        }
                    } catch (StaleElementReferenceException) {
                        $found = true;
                        break;
                    }
                }
            } catch (StaleElementReferenceException) {
                $found = true;
            }
            if (!$found) {
                Assert::assertTrue(true);
                return;
            }
            usleep(300000);
        } while (microtime(true) < $deadline);

        Assert::fail('La fila con "' . $text . '" sigue visible en ' . $tableId);
    }

    protected function assertNo404(): void
    {
        $text = $this->bodyText();
        Assert::assertStringNotContainsString('Página no encontrada', $text);
        Assert::assertStringNotContainsString('404', $text);
        Assert::assertStringNotContainsString('No autorizado', $text);
    }

    protected function screenshot(string $name): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name) ?: 'captura';
        $file = Config::screenshotsDir() . '/' . date('His') . '_' . $safe . '.png';
        try {
            $this->driver()->takeScreenshot($file);
        } catch (\Throwable) {
        }
        return $file;
    }
}


