<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Sistema;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Config;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class UsuariosTest extends InterfazTestCase
{
    #[Test]
    public function crea_edita_y_desactiva_un_usuario(): void
    {
        $name = $this->unique('Usuario', 50);
        $edited = mb_substr($name . ' edit', 0, 50);
        $email = strtolower(str_replace(' ', '', $name)) . '@example.test';
        $editedEmail = strtolower(str_replace(' ', '', $edited)) . '@example.test';
        $password = 'E2ePass123!';

        $this->visit('dashboard/usuarios');
        $this->waitForDataTable('usuariosTable');
        $this->click('#btnAddUser');
        $this->waitForVisibleCss('#addUserModal.show');

        $this->fill('#addUserForm [name="nombre_usuario"]', $name);
        $this->fill('#addUserForm [name="correo_electronico"]', $email);
        $this->fill('#addUserForm [name="password"]', $password);
        $this->selectFirstAvailable('#addUserRole');
        $this->click('#addUserForm button[type="submit"]');

        $this->waitUntilGone('#addUserModal.show');
        $this->assertToastContains('Usuario agregado');
        $this->waitForDataTable('usuariosTable');
        $this->assertRowExists('usuariosTable', $name);

        $row = $this->tableRowWithText('usuariosTable', $name);
        $this->clickIn($row, '.btn-edit');
        $this->waitForVisibleCss('#editUserModal.show');
        $this->fill('#editUserForm [name="nombre_usuario"]', $edited);
        $this->fill('#editUserForm [name="correo_electronico"]', $editedEmail);
        $this->click('#editUserForm button[type="submit"]');

        $this->waitUntilGone('#editUserModal.show');
        $this->assertToastContains('Usuario actualizado');
        $this->waitForDataTable('usuariosTable');
        $this->assertRowExists('usuariosTable', $edited);

        $row = $this->tableRowWithText('usuariosTable', $edited);
        $this->clickIn($row, '.btn-delete');
        $this->waitForSwal();
        $this->fill('#swal-delete-password', Config::password());
        $this->click('.swal2-confirm');
        $this->assertToastContains('Usuario eliminado');
        $this->assertRowMissing('usuariosTable', $edited);
    }
}
