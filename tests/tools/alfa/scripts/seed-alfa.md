# `seed-alfa.php` — usuario de pruebas alfa

Crea o actualiza el usuario `alfa_admin` en la base de datos de seguridad. Lo
usa [`run-alfa`](run-alfa.md) antes de arrancar el servidor de pruebas.

## Uso

```bash
php tests/tools/alfa/scripts/seed-alfa.php
php tests/tools/alfa/scripts/seed-alfa.php --usuario=otro_alfa --email=otro@example.test
```

## Qué hace

1. Garantiza que exista el rol `1` (Administrador). `Auth::isAdmin()` compara el
   rol con `1`, así que el usuario de pruebas necesita ese ID.
2. Crea `alfa_admin` si no existe, o actualiza su correo, contraseña, rol y
   estado si ya existe.
3. Deja el usuario `Activo` y con `intentos_fallidos = 0`.

No imprime contraseñas. La sesión de las pruebas se inyecta desde
`server/index.php`, no se obtiene con un login real.

## Requisitos

- PHP con `pdo_mysql`.
- MySQL/MariaDB iniciado.
- Variables `DB_SEC_*` (o `DB_*`) en `.env`.

## Resultado

| Código | Significado |
| ---: | --- |
| `0` | Usuario creado o actualizado. |
| `3` | Faltan `pdo_mysql`, conexión o credenciales. |

El ejecutor guarda la salida en
`formatos/resultados/seed-alfa-<fecha>.log`.
