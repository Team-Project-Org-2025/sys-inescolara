# `run-comercial` — comercial

Prueba alfa independiente del grupo `comercial`. Verifica clientes, ventas,
cuentas por cobrar y ornatos.

- **Linux/macOS:** `run-comercial.sh`
- **Windows:** `run-comercial.bat`
- **Núcleo:** `run-alfa.php --test=comercial`

## Uso

```bash
./tests/tools/alfa/scripts/run-comercial.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-comercial.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Clientes: listado | HTTP 200 con `success:true`. |
| 2 | Ventas: listado | HTTP 200 con `success:true`. |
| 3 | Cuentas por cobrar: listado | HTTP 200 con formato DataTables (`recordsTotal`). |
| 4 | Cuentas por cobrar: estadísticas | HTTP 200 con `success:true`. |
| 5 | Ornatos: listado | HTTP 200 con `success:true`. |
| 6 | Vista de ventas | HTTP 200 con su título. |
| 7 | Ventas: cliente obligatorio | HTTP 500 con el mensaje de validación. |
| 8 | Clientes: nombre vacío | HTTP 500 con el mensaje de validación. |
| 9 | Ventas: búsqueda de clientes | HTTP 200 con `success:true`. |
| 10 | Ventas: trabajadores activos | HTTP 200 con `success:true`. |

Ventas y ornatos usan el parámetro `accion` (no `action`); el script ya envía la
variante correcta. Las comprobaciones 7 y 8 no registran datos.

## Resultado

Genera `formatos/resultados/junit-comercial.xml` y `comercial-<fecha>.log`.
