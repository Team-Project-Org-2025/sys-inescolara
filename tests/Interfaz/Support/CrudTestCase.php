<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Support;

use SysInescolara\Tests\Interfaz\Pages\CrudPage;

abstract class CrudTestCase extends InterfazTestCase
{
    protected function runCrud(
        array $config,
        array $createData,
        array $editData,
        string $createdText,
        string $editedText
    ): void {
        $page = new CrudPage($this->driver(), $config);
        $page->openModule();
        $page->create($createData);
        $page->assertExists($createdText);
        $page->edit($createdText, $editData);
        $page->assertExists($editedText);
        $page->delete($editedText);
        $page->assertMissing($editedText);
    }
}
