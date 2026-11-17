# `seed-interfaz.php` — usuario de prueba E2E

> **Tipo:** preparación · PHP
>
> **Atajo:** crea o actualiza el rol y el usuario `e2e_admin` en la base de
> seguridad. Es idempotente: se puede ejecutar tantas veces como haga falta.

## Qué hace

1. Busca el primer rol de la tabla `roles`.
2. Si no existe ninguno, crea el rol `Administrador`.
3. Busca el usuario `e2e_admin` (o el definido en `INTERFAZ_USER`).
4. Si no existe, lo inserta con correo `e2e_admin@example.test`, contraseña
   hasheada, rol y estado `Activo`.
5. Si existe, actualiza correo, contraseña, rol y estado.
6. Imprime el usuario, el rol asignado y las credenciales.

La contraseña y el usuario salen de `tests/Interfaz/Support/Config`, que lee el
`.env` de la raíz y permite sobrescribirlos con variables de entorno:

| Variable | Valor por defecto |
| --- | --- |
| `INTERFAZ_USER` | `e2e_admin` |
| `INTERFAZ_PASSWORD` | `E2eAdmin123!` |

## Cuándo ejecutarlo

- Antes de la primera corrida de `run-interfaz.ps1`.
- Después de reiniciar o recrear la base de datos.
- Si el login E2E falla por credenciales.
- Antes de `tests/tools/aceptacion/scripts/run-aceptacion.sh` (aunque ese script ya lo ejecuta).

`setup-interfaz.ps1` y `run-interfaz.ps1` lo ejecutan automáticamente salvo que
uses `-SkipSeed`.

## Requisitos

- PHP 8.2+.
- MySQL iniciado.
- Bases `sysinescolara` y `SysInescolara-Seguridad` creadas y migradas.
- `.env` con las credenciales de conexión (`DB_*` y `DB_SEC_*`).

## Uso

```powershell
php tests\tools\interfaz\scripts\seed-interfaz.php
```

No recibe argumentos. Configura el usuario y la contraseña por variables de
entorno si lo necesitas:

```powershell
$env:INTERFAZ_USER = 'e2e_admin'
$env:INTERFAZ_PASSWORD = 'OtraClave123!'
php tests\tools\interfaz\scripts\seed-interfaz.php
```

En Linux/macOS:

```bash
INTERFAZ_USER=e2e_admin INTERFAZ_PASSWORD='OtraClave123!' php tests/tools/interfaz/scripts/seed-interfaz.php
```

## Salida esperada

```text
Usuario de prueba creado: e2e_admin (id 12)
Rol asignado: 1
Credenciales: e2e_admin / E2eAdmin123!
```

En ejecuciones posteriores cambia la primera línea por
`Usuario de prueba actualizado: e2e_admin (id 12)`.

## Advertencias

- **Solo para desarrollo.** Escribe en la base real configurada en `.env`.
- No lo ejecutes contra producción ni con credenciales reales de usuarios.
- Si cambias `INTERFAZ_PASSWORD`, vuelve a ejecutar el seed para actualizar el
  hash.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| Error de conexión PDO | MySQL apagado o `.env` incorrecto. | Inicia MySQL y revisa `DB_*`/`DB_SEC_*`. |
| `Table 'roles' doesn't exist` | Base sin migrar. | Ejecuta las migraciones del proyecto. |
| El login sigue fallando | Se usó `-SkipSeed` o cambió la contraseña. | Vuelve a ejecutar el seed. |
| El usuario no tiene permisos | El primer rol no es administrador. | Revisa la tabla `roles` o asigna el rol correcto. |

## Ver también

- Preparar el entorno completo: [`setup-interfaz.md`](setup-interfaz.md)
- Ejecutar la suite E2E: [`run-interfaz.md`](run-interfaz.md)
- Documentación de la suite: [`Interfaz/README.md`](../../../Interfaz/README.md)
