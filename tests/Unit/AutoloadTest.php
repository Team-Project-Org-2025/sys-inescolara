<?php

namespace SysInescolara\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\helpers\Csrf;

final class AutoloadTest extends TestCase
{
    #[Test]
    public function project_classes_autoload_correctly(): void
    {
        $this->assertTrue(class_exists(Csrf::class));
        $this->assertTrue(trait_exists(\SysInescolara\traits\ValidationTrait::class));
    }
}
