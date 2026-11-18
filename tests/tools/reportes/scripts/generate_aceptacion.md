# `generate_aceptacion` — regenerar la matriz de aceptación

> **Tipo:** reporte · PowerShell y Bash
>
> **Ejecutores:** `generate_aceptacion.ps1` (Windows) y
> `generate_aceptacion.sh` (Linux/macOS)
>
> **Motor interno:** `lib/generate_aceptacion.php`
>
> **Atajo:** lee el JUnit de Codeception y reescribe
> `tests/tools/reportes/formatos/resultados/ACEPTACION.md`.

## Qué hace

1. Lee `tests/tools/aceptacion/formatos/resultados/report.xml` (reporte JUnit de
   Codeception).
2. Cruza cada caso `CP-ACE-*` con su requisito `RF-01`…`RF-31` usando
   `tests/aceptacion/_support/ModuleCatalog.php`.
3. Calcula `PASS`, `FAIL`, `SKIP` y `NO EJECUTADO`.
4. Reescribe `tests/tools/reportes/formatos/resultados/ACEPTACION.md` usando
   `tests/tools/reportes/formatos/plantillas/ACEPTACION.plantilla.md` como
   formato obligatorio.

Si no existe el reporte, la matriz se genera con todos los casos como
`NO EJECUTADO`; no inventa resultados.

## Requisitos

- PHP 8.2+.
- Dependencias instaladas (`composer install`): usa `vendor/autoload.php`.
- Haber ejecutado la suite de aceptación para tener un `report.xml` real.
- PowerShell 5.1+ en Windows, o Bash en Linux/macOS.

## Uso

### 1. Ejecutar la suite de aceptación

```bash
# Linux/macOS
./tests/tools/aceptacion/scripts/run-aceptacion.sh
```

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\aceptacion\scripts\run-aceptacion.ps1
```

El ejecutor descarga ChromeDriver si falta, levanta la aplicación y el driver,
crea el usuario `e2e_admin`, ejecuta Codeception y guarda
`tests/tools/aceptacion/formatos/resultados/report.xml`.

### 2. Regenerar la matriz

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\reportes\scripts\generate_aceptacion.ps1
```

```bash
# Linux/macOS
./tests/tools/reportes/scripts/generate_aceptacion.sh
```

El motor también puede invocarse directamente:

```bash
php tests/tools/reportes/scripts/lib/generate_aceptacion.php
```

## Salida esperada

```text
Matriz generada: 45 casos (45 PASS, 0 FAIL, 0 SKIP, 0 sin ejecutar)
C:\xampp\htdocs\sys-inescolara\tests\tools\reportes\formatos\resultados\ACEPTACION.md
```

El número exacto depende de los casos definidos en ese momento (31 de módulos
+ los casos explícitos de autenticación, portal público y CRUD).

## Qué cambia en el repositorio

| Archivo | Cambio |
| --- | --- |
| `tests/tools/reportes/formatos/resultados/ACEPTACION.md` | Se sobrescribe con el estado real de cada caso. |

`tests/tools/aceptacion/formatos/resultados/` está ignorado por git;
`ACEPTACION.md` sí se versiona.

## Cuándo ejecutarlo

- Después de cambiar o agregar escenarios en `tests/aceptacion/Acceptance/`.
- Después de una corrida de aceptación.
- Antes de cerrar una entrega, para que la matriz refleje la última corrida.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| Todo aparece `NO EJECUTADO` | No existe `tests/tools/aceptacion/formatos/resultados/report.xml`. | Ejecuta el ejecutor de aceptación. |
| `Class "ModuleCatalog" not found` | Falta `composer install` o el archivo de soporte. | Ejecuta `composer install` y revisa `_support/ModuleCatalog.php`. |
| `Could not open input file` | Ruta incorrecta. | Ejecuta desde la raíz del proyecto. |
| Los casos no coinciden con los RF | Cambió el catálogo de módulos. | Revisa `_support/ModuleCatalog.php` y vuelve a ejecutar. |

## Ver también

- Suite de aceptación: [`aceptacion/README.md`](../../../aceptacion/README.md)
- Matriz actual: [`reportes/formatos/resultados/ACEPTACION.md`](../formatos/resultados/ACEPTACION.md)
- Matriz PHPUnit: [`generate_traceability.md`](generate_traceability.md)
