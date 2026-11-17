# `generate_traceability.php` — regenerar la matriz PHPUnit

> **Tipo:** reporte · PHP
>
> **Atajo:** lee los JUnit de las tres suites y reescribe
> `tests/tools/reportes/formatos/resultados/TRACEABILITY.md` y `tests/tools/reportes/formatos/resultados/traceability.json`.

## Qué hace

1. Lee, si existen:
   - `tests/tools/phpunit/formatos/resultados/junit-unit.xml`
   - `tests/tools/phpunit/formatos/resultados/junit-integration.xml`
   - `tests/tools/phpunit/formatos/resultados/junit-smoke.xml`
2. Clasifica cada uno de los 31 módulos en Unit, Integration y Smoke.
3. Calcula `PASS`, `FAIL`, `SKIP` y `NO EJECUTADO`.
4. Reescribe, respetando las plantillas de
   `tests/tools/reportes/formatos/plantillas/`:
   - `tests/tools/reportes/formatos/resultados/TRACEABILITY.md` (tabla legible).
   - `tests/tools/reportes/formatos/resultados/traceability.json` (datos que consume `run-module.ps1`).

Si falta un JUnit, la suite correspondiente queda como `NO EJECUTADO` /
`Sin log JUnit`; no inventa resultados.

> **Importante:** el generador solo lee `junit-unit.xml`, `junit-integration.xml`
> y `junit-smoke.xml`. Los `module-<Modelo>-*.xml` de `run-module.ps1` **no**
> se usan aquí.

## Requisitos

- PHP 8.2+.
- Haber ejecutado las tres suites completas con `--log-junit` en los nombres
  exactos indicados.
- No necesita MySQL, Apache ni navegador.

## Uso

### 1. Generar los tres JUnit

```powershell
# Crear la carpeta si no existe
New-Item -ItemType Directory -Force tests\tools\phpunit\formatos\resultados | Out-Null

php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Unit        --log-junit tests\tools\phpunit\formatos\resultados\junit-unit.xml        --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Integration --log-junit tests\tools\phpunit\formatos\resultados\junit-integration.xml --do-not-cache-result
php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml --testsuite Smoke       --log-junit tests\tools\phpunit\formatos\resultados\junit-smoke.xml       --do-not-cache-result
```

### 2. Regenerar la matriz

```powershell
php tests\tools\reportes\scripts\generate_traceability.php
```

## Salida esperada

```text
Matriz generada: 31 requisitos, 93 casos (93 PASS, 0 FAIL, 0 SKIP, 0 sin ejecutar).
```

- **93 casos** = 31 módulos × 3 suites.
- **945 pruebas de modelo** = 415 unitarias + 468 de integración + 62 de humo.
- Las **7 omitidas** son limitaciones documentadas de SQLite frente a MySQL.

## Qué cambia en el repositorio

| Archivo | Cambio |
| --- | --- |
| `tests/tools/reportes/formatos/resultados/TRACEABILITY.md` | Se sobrescribe con el estado real de cada caso. |
| `tests/tools/reportes/formatos/resultados/traceability.json` | Se sobrescribe con los conteos y metadatos. |

`tests/tools/reportes/formatos/resultados/traceability.json` es la fuente que usa
[`run-module.ps1`](../../phpunit/scripts/run-module.md) para comparar los conteos. Después de agregar
o quitar pruebas, regenera la matriz para que el script no marque
`NO COINCIDE`.

## Cuándo ejecutarlo

- Después de cambiar pruebas unitarias, de integración o de humo.
- Después de agregar un modelo o un caso `CP-SMOKE-NN`.
- Cuando `run-module.ps1` diga que falta `traceability.json`.
- Antes de cerrar una entrega, para dejar la matriz al día.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| Todo aparece `NO EJECUTADO` | No existen los `junit-*.xml` o tienen otro nombre. | Ejecuta los tres comandos del paso 1. |
| `run-module.ps1` dice `NO COINCIDE` | La matriz quedó desactualizada. | Vuelve a generar los JUnit y la matriz. |
| Los conteos no cuadran | Se ejecutó solo una suite o se usaron `module-*.xml`. | Usa los nombres `junit-unit`, `junit-integration`, `junit-smoke`. |
| `PHP Parse error` | Versión de PHP demasiado antigua. | Usa PHP 8.2+. |

## Ver también

- Ejecutar las tres suites: [`run-module.md`](../../phpunit/scripts/run-module.md)
- Comandos de las suites:
  [`GUIA_PRUEBAS.md#8-regenerar-la-matriz-de-trazabilidad`](../../../GUIA_PRUEBAS.md#8-regenerar-la-matriz-de-trazabilidad)
- Matriz actual: [`TRACEABILITY.md`](../formatos/resultados/TRACEABILITY.md)
