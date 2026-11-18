# `run-operaciones` — operaciones

Prueba alfa independiente del grupo `operaciones`. Verifica tareas,
trazabilidad, mermas, ampliación y recolección de semillas.

- **Linux/macOS:** `run-operaciones.sh`
- **Windows:** `run-operaciones.bat`
- **Núcleo:** `run-alfa.php --test=operaciones`

## Uso

```bash
./tests/tools/alfa/scripts/run-operaciones.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-operaciones.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Tareas: asignaciones | HTTP 200 con `success:true`. |
| 2 | Trazabilidad: listado | HTTP 200 con `success:true`. |
| 3 | Trazabilidad: lotes | HTTP 200 con `success:true`. |
| 4 | Mermas: listado | HTTP 200 con `success:true`. |
| 5 | Mermas: cuarentena | HTTP 200 con `success:true`. |
| 6 | Ampliación: intercambios | HTTP 200 con `success:true`. |
| 7 | Ampliación: lotes | HTTP 200 con `success:true`. |
| 8 | Recolección: listado | HTTP 200 con `success:true`. |
| 9 | Vista de tareas | HTTP 200 con su título. |
| 10 | Mermas: validación | HTTP 500 sin seleccionar cuarentena. |

La comprobación 10 no registra la merma.

## Resultado

Genera `formatos/resultados/junit-operaciones.xml` y
`operaciones-<fecha>.log`.
