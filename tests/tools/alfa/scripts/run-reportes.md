# `run-reportes` — reportes

Prueba alfa independiente del grupo `reportes`. Verifica el dashboard, el módulo
de reportes y la bitácora.

- **Linux/macOS:** `run-reportes.sh`
- **Windows:** `run-reportes.bat`
- **Núcleo:** `run-alfa.php --test=reportes`

## Uso

```bash
./tests/tools/alfa/scripts/run-reportes.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-reportes.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Reportes: módulos | HTTP 200 con `success:true` y `plantas`. |
| 2 | Reportes: filtros de plantas | HTTP 200 con `success:true`. |
| 3 | Reportes: datos de plantas | HTTP 200 con `success:true`. |
| 4 | Vista de reportes | HTTP 200 con su título. |
| 5 | Bitácora: listado | HTTP 200 con `success:true`. |
| 6 | Dashboard de inventario | HTTP 200 con su título. |
| 7 | Dashboard de ventas | HTTP 200. |
| 8 | Dashboard de clientes | HTTP 200. |

## Resultado

Genera `formatos/resultados/junit-reportes.xml` y `reportes-<fecha>.log`.
