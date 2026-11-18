# `run-inventario` — inventario

Prueba alfa independiente del grupo `inventario`. Verifica el inventario
consolidado, los insumos y las herramientas.

- **Linux/macOS:** `run-inventario.sh`
- **Windows:** `run-inventario.bat`
- **Núcleo:** `run-alfa.php --test=inventario`

## Uso

```bash
./tests/tools/alfa/scripts/run-inventario.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-inventario.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Inventario consolidado | HTTP 200 con `success:true`. |
| 2 | Vista de inventario | HTTP 200 con su título. |
| 3 | Insumos: listado | HTTP 200 con `success:true`. |
| 4 | Herramientas: listado | HTTP 200 con `success:true`. |
| 5 | Herramientas: usos sin ID | HTTP 400 con el mensaje de validación. |
| 6 | Insumos: nombre vacío | HTTP 500 con el mensaje de validación. |
| 7 | Herramientas: nombre vacío | HTTP 500 con el mensaje de validación. |
| 8 | Lotes: planta vacía | HTTP 500 con el mensaje de validación. |

Las comprobaciones 5 a 8 no crean ni modifican registros.

## Resultado

Genera `formatos/resultados/junit-inventario.xml` y
`inventario-<fecha>.log`.
