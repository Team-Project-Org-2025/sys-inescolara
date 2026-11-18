# `run-catalogo` — catálogo

Prueba alfa independiente del grupo `catalogo`. Verifica los listados y la vista
de gestión del catálogo: especies, plantas, ubicaciones, lotes, precios y
unidades de medida.

- **Linux/macOS:** `run-catalogo.sh`
- **Windows:** `run-catalogo.bat`
- **Núcleo:** `run-alfa.php --test=catalogo`

## Uso

```bash
./tests/tools/alfa/scripts/run-catalogo.sh [opciones]
```

```bat
tests\tools\alfa\scripts\run-catalogo.bat [opciones]
```

## Comprobaciones

| # | Comprobación | Espera |
| ---: | --- | --- |
| 1 | Especies: listado AJAX | HTTP 200 con `success:true`. |
| 2 | Plantas: listado AJAX | HTTP 200 con `success:true`. |
| 3 | Ubicaciones: listado AJAX | HTTP 200 con `success:true`. |
| 4 | Lotes: listado AJAX | HTTP 200 con `success:true`. |
| 5 | Precios: listado AJAX | HTTP 200 con `success:true`. |
| 6 | Unidades de medida: listado AJAX | HTTP 200 con `success:true`. |
| 7 | Especies: vista de gestión | HTTP 200 con título y encabezado. |
| 8 | Especies: nombre vacío | HTTP 500 con el mensaje de validación. |
| 9 | Plantas: nombre vacío | HTTP 500 con el mensaje de validación. |
| 10 | Acción AJAX inválida | HTTP 400. |

Las comprobaciones 8 y 9 envían peticiones incompletas: validan el mensaje de
error sin crear registros.

## Resultado

Genera `formatos/resultados/junit-catalogo.xml` y `catalogo-<fecha>.log`.
