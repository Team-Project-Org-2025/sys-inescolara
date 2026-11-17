# `run-module.ps1` — pruebas de un módulo completo

> **Tipo:** PHPUnit · Unit + Integration + Smoke · PowerShell
>
> **Atajo:** ejecuta las **tres suites** de uno o de los 31 módulos y compara los
> conteos contra `tests/tools/reportes/formatos/resultados/traceability.json`.
>
> **Referencia manual comando por comando:**
> [`COMANDOS_RAPIDOS.md`](../../../COMANDOS_RAPIDOS.md)

## Qué hace

Para cada módulo ejecuta, en este orden:

1. Su archivo unitario (`tests/Unit/<Dominio>/<Modelo>Test.php`).
2. Su archivo de integración
   (`tests/Integration/<Dominio>/<Modelo>RepositoryTest.php`).
3. Su caso de humo (`--testsuite Smoke --filter CP-SMOKE-NN`).

Guarda un JUnit por suite y compara `tests`, `pass`, `fail` y `skip` con la
matriz. Si todo coincide, termina con código `0`.

En total cubre **93 casos**: 31 módulos × 3 suites. Al ejecutar `all` se
ejecutan **945 pruebas de modelo** (938 PASS y 7 SKIP documentados).

## Requisitos

- PHP 8.2 o superior en el `PATH`.
- PHPUnit instalado en `vendor/` (`composer install`).
- Driver `pdo_sqlite` habilitado.
- PowerShell 5.1 o superior (Windows PowerShell o PowerShell 7).
- `tests/tools/reportes/formatos/resultados/traceability.json` existente. Si falta, regénéralo con
  [`generate_traceability.md`](../../reportes/scripts/generate_traceability.md).

No hace falta MySQL, Apache ni XAMPP.

## Uso

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 <módulo>
```

El argumento es **uno solo** y acepta cualquiera de estas formas:

| Forma | Ejemplo | Módulo |
| --- | --- | --- |
| Número con cero | `05` | Lote |
| Número sin cero | `5` | Lote |
| Nombre del modelo | `Lote` | Lote |
| Nombre del sidebar | `Cuenta por cobrar` | CuentaCobrar |
| Requisito | `RF-05` | Lote |
| Todos | `all`, `todos`, `todo`, `*` | Los 31 |
| Sin argumento | *(nada)* | Los 31 |

## Ejemplos

### Un módulo

```powershell
# Por número
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 05

# Por nombre
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 Lote

# Por requisito
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 RF-05
```

Salida esperada (resumida):

```text
>>> Lote :: tests\Unit\Catalogo\LoteTest.php
OK (18 tests, 83 assertions)
>>> Lote :: tests\Integration\Catalogo\LoteRepositoryTest.php
OK (20 tests, 1155 assertions)
>>> Lote :: --filter CP-SMOKE-05
OK (2 tests, 24 assertions)

Modulo: Lote (RF-05) - Lote
Suite       Ejecutadas Esperadas Pass Fail Skip Aserciones Estado
Unitaria            18        18   18    0    0         83 COINCIDE
Integracion         20        20   20    0    0       1155 COINCIDE
Humo                 2         2    2    0    0         24 COINCIDE

Modulos: 1 | COINCIDE: 1 | NO COINCIDE: 0
RESULTADO: COINCIDE con tests/tools/reportes/formatos/resultados/traceability.json
```

### Los 31 módulos

```powershell
# Explícito
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1 all

# O sin argumento: hace exactamente lo mismo
powershell -NoProfile -ExecutionPolicy Bypass -File tests\tools\phpunit\scripts\run-module.ps1
```

Salida esperada al final:

```text
[01/31] Planta           COINCIDE
...
[31/31] PasswordReset    COINCIDE

Modulos: 31 | COINCIDE: 31 | NO COINCIDE: 0
Pruebas: 945 | Aserciones: ... | FAIL: 0 | SKIP: 7
RESULTADO: COINCIDE con tests/tools/reportes/formatos/resultados/traceability.json
```

Tarda alrededor de 40 segundos. En modo `all` no muestra la salida completa de
PHPUnit; solo la muestra si un módulo falla.

## Qué genera

| Archivo | Contenido |
| --- | --- |
| `tests/tools/phpunit/formatos/resultados/module-<Modelo>-unit.xml` | JUnit de la suite unitaria. |
| `tests/tools/phpunit/formatos/resultados/module-<Modelo>-integration.xml` | JUnit de la suite de integración. |
| `tests/tools/phpunit/formatos/resultados/module-<Modelo>-smoke.xml` | JUnit del caso de humo. |

Estos XML **no** alimentan `generate_traceability.php`; ese generador usa
`tests/tools/phpunit/formatos/resultados/junit-{unit,integration,smoke}.xml`. Consulta
[`generate_traceability.md`](../../reportes/scripts/generate_traceability.md).

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todos los módulos comprobados coinciden con la matriz. |
| `1` | Al menos una suite no coincide o PHPUnit devolvió error. Al final se listan los módulos afectados. |

## Interpretar el resultado

- `COINCIDE`: los conteos del JUnit son idénticos a `traceability.json`.
- `NO COINCIDE`: cambió el número de pruebas, falló alguna o cambió el número
  de omitidas. Revisa el `Log:` que imprime el script.
- `SKIP` no es un fallo, pero el número debe coincidir con la matriz.
- Los mensajes `Error al guardar ...` que aparecen en Unit provienen de
  `error_log()` del modelo probando el camino sin base de datos; **no son
  fallos**.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `No se encontro el modulo '...'` | El argumento no coincide con ninguna forma válida. | Usa número, modelo, sidebar, `RF-NN` o `all`. |
| `No existe ...traceability.json` | Falta la matriz. | Ejecuta [`generate_traceability.php`](../../reportes/scripts/generate_traceability.md). |
| `No se pudo extraer el ID de humo` | El caso de humo de la matriz no tiene el formato `CP-SMOKE-NN`. | Regenera la matriz. |
| Un módulo da `NO COINCIDE` sin fallos | Cambió el número de pruebas u omitidas. | Regenera la matriz o revisa el test modificado. |
| Fallan todas las integraciones | Falta `pdo_sqlite`. | Habilita la extensión o usa `run-tests.php --extension=...`. |
| Los acentos se ven raros | PowerShell 5.1 leyó el `.ps1` como ANSI. | El script está en ASCII a propósito; no lo guardes como UTF-8 sin BOM. |

## Ver también

- Referencia manual de los 93 casos:
  [`COMANDOS_RAPIDOS.md`](../../../COMANDOS_RAPIDOS.md)
- Integración + humo multiplataforma: [`run-tests.md`](run-tests.md)
- Recetas para una sola suite, archivo o método:
  [`README.md#recetas-phpunit`](../../README.md#recetas-phpunit)
- Explicación de cada suite: [`GUIA_PRUEBAS.md`](../../../GUIA_PRUEBAS.md)
- Matriz de trazabilidad: [`TRACEABILITY.md`](../../reportes/formatos/resultados/TRACEABILITY.md)
