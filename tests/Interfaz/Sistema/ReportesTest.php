<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Sistema;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class ReportesTest extends InterfazTestCase
{
    #[Test]
    public function genera_un_reporte_de_un_modulo(): void
    {
        $this->visit('dashboard/reports');
        $this->waitForSelectOptions('#reportModuleSelect', 25);
        $this->selectFirstAvailable('#reportModuleSelect');
        $this->waitForVisibleCss('#reportActions', 30);
        $this->waitForVisibleCss('#summaryBar', 30);
        $this->assertPageContains('Reportes del Sistema');
        $this->assertPageContains('registros');
    }
}
