<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Pages;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

final class CrudPage extends BasePage
{
    private array $config;

    public function __construct(\Facebook\WebDriver\Remote\RemoteWebDriver $driver, array $config)
    {
        parent::__construct($driver);
        $this->config = $config;
    }

    public function openModule(): static
    {
        $this->open($this->config['path']);
        $this->waitForDataTable($this->config['table']);
        return $this;
    }

    public function tableId(): string
    {
        return $this->config['table'];
    }

    public function create(array $data): void
    {
        $this->searchTable($this->config['table'], '');
        $this->click($this->config['add']);
        $this->waitForVisibleCss($this->config['addModal'] . '.show');
        $this->fillFields($this->config['addForm'], $data, $this->config['fields'] ?? []);
        $this->click($this->config['addForm'] . ' button[type="submit"]');
        $this->waitModalClosed($this->config['addModal']);
        $this->waitForToast();
        $this->waitForDataTable($this->config['table']);
    }

    public function edit(string $searchText, array $data): void
    {
        $row = $this->tableRowWithText($this->config['table'], $searchText);
        $this->clickIn($row, '.btn-edit');
        $this->waitForVisibleCss($this->config['editModal'] . '.show');
        $this->fillFields($this->config['editForm'], $data, $this->config['editFields'] ?? []);
        $this->click($this->config['editForm'] . ' button[type="submit"]');
        $this->waitModalClosed($this->config['editModal']);
        $this->waitForToast();
        $this->waitForDataTable($this->config['table']);
    }

    public function delete(string $searchText): void
    {
        $row = $this->tableRowWithText($this->config['table'], $searchText);
        $this->clickIn($row, '.btn-delete');
        $this->waitForSwal();
        $this->click('.swal2-confirm');
        $this->waitForToast();
        $this->waitForDataTable($this->config['table']);
    }

    public function assertExists(string $text): void
    {
        $this->assertRowExists($this->config['table'], $text);
    }

    public function assertMissing(string $text): void
    {
        $this->assertRowMissing($this->config['table'], $text);
    }

    public function row(string $text): WebDriverElement
    {
        return $this->tableRowWithText($this->config['table'], $text);
    }

    private function fillFields(string $form, array $data, array $types): void
    {
        foreach ($data as $name => $value) {
            $type = $types[$name] ?? 'text';
            $selector = $form . ' [name="' . $name . '"]';
            if ($type === 'select_value') {
                $this->selectValue($selector, (string) $value);
                continue;
            }
            if ($type === 'select') {
                if ($value === null || $value === '') {
                    $this->selectFirstOption($selector);
                } else {
                    $this->selectOption($selector, (string) $value);
                }
                continue;
            }
            if ($type === 'date') {
                $this->setValue($selector, (string) $value);
                continue;
            }
            if ($type === 'checkbox') {
                $element = $this->waitForCss($selector);
                if (!$element->isSelected()) {
                    $element->click();
                }
                continue;
            }
            $this->fill($selector, $value);
        }
    }

    private function waitModalClosed(string $modal): void
    {
        $this->wait(15)->until(function () use ($modal): bool {
            $elements = $this->driver->findElements(WebDriverBy::cssSelector($modal . '.show'));
            return $elements === [];
        });
    }
}

