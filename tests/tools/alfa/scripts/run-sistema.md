# `run-sistema` — sistema

Prueba alfa independiente del grupo `sistema`. Verifica usuarios, roles,
respaldos y el control de acceso del módulo.

- **Linux/macOS:** `run-sistema.sh`
- **Windows:** `run-sistema.bat`
- **Núcleo:** `run-alfa.php --test=sistema`

## Uso

```bash
./tests/tools/alfa/scripts/run-sistema.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-sistema.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Usuarios: listado | HTTP 200 con `success:true`. |
| 2 | Roles: listado | HTTP 200 con `success:true`. |
| 3 | Respaldos: listado | HTTP 200 con `success:true`. |
| 4 | Vista de usuarios | HTTP 200 con su título. |
| 5 | Usuarios: datos obligatorios | HTTP 500 con el mensaje de validación. |
| 6 | Roles: nombre vacío | HTTP 500 con el mensaje de validación. |
| 7 | Usuarios sin sesión | HTTP 401 con `success:false`. |

Las comprobaciones 5 y 6 no crean usuarios ni roles.

## Resultado

Genera `formatos/resultados/junit-sistema.xml` y `sistema-<fecha>.log`.
