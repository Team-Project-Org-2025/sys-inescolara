<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Sistema;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class BackupsTest extends InterfazTestCase
{
    #[Test]
    public function crea_y_elimina_un_respaldo(): void
    {
        $this->visit('dashboard/backups');
        $this->waitForDataTable('backupsTable');
        $this->click('#createBackupBtn');

        $this->assertToastContains('Respaldo completo creado', 90);
        $this->waitForDataTable('backupsTable', 30);

        $this->waitForCss('#backupsTable tbody tr .btn-delete-backup', 30);
        $this->driver()->executeScript(
            "const btn = document.querySelector('#backupsTable tbody tr .btn-delete-backup');"
            . "if (!btn) { throw new Error('No apareció el respaldo recién creado.'); }"
            . "btn.click();"
        );
        $this->waitForSwal();
        $this->click('.swal2-confirm');
        $this->assertToastContains('Respaldo eliminado');
    }
}
