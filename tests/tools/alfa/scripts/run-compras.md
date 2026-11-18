# `run-compras` — compras

Prueba alfa independiente del grupo `compras`. Verifica proveedores, compras y
cuentas por pagar.

- **Linux/macOS:** `run-compras.sh`
- **Windows:** `run-compras.bat`
- **Núcleo:** `run-alfa.php --test=compras`

## Uso

```bash
./tests/tools/alfa/scripts/run-compras.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-compras.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Proveedores: listado | HTTP 200 con `success:true`. |
| 2 | Compras: listado | HTTP 200 con `success:true`. |
| 3 | Cuentas por pagar: listado | HTTP 200 con `success:true`. |
| 4 | Vista de compras | HTTP 200 con su título. |
| 5 | Compras: proveedor obligatorio | HTTP 500 con el mensaje de validación. |
| 6 | Proveedores: nombre vacío | HTTP 500 con el mensaje de validación. |

Las comprobaciones 5 y 6 no insertan registros.

## Resultado

Genera `formatos/resultados/junit-compras.xml` y `compras-<fecha>.log`.
