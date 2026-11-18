# `run-integration-doc` — integración + formato Word

> **Tipo:** PHPUnit · Word (Open XML) · PowerShell y Bash
>
> **Ejecutores:** `windows/run-integration-doc.ps1` (Windows) y
> `linux/run-integration-doc.sh` (Linux/macOS)
>
> **Atajo:** ejecuta las 31 pruebas de integración, genera una captura nueva por
> módulo y llena una **copia** del formato Word. El documento original nunca se
> modifica.
>
> **Formato original:** [`../formatos/plantillas/Formato-Prueba-Integración.docx`](../formatos/plantillas/Formato-Prueba-Integración.docx)
>
> **Copia llena:** [`../formatos/resultados/`](../formatos/resultados/)

## Qué hace

Para cada uno de los 31 módulos (RF-01 a RF-31):

1. Ejecuta su archivo `tests/Integration/**/…RepositoryTest.php` con PHPUnit.
2. Guarda el JUnit de la corrida en una carpeta temporal y lee los conteos
   reales (`tests`, `assertions`, `failures`, `errors`, `skipped`, `time`).
3. Genera una captura PNG de la salida real de consola en modo `--testdox`
   (con el nombre de cada caso ejecutado y su resultado) en
   `formatos/resultados/capturas/`. La captura omite el banner de PHPUnit y
   el marco del cuadro se ajusta a la proporción real de la imagen para no
   deformarla.
4. Copia el formato Word original y, sobre la **copia**, rellena cada cuadro:
   - **Objetivos de la prueba** y **Técnicas**: se conservan del formato.
   - **Código involucrado**: se conserva del formato.
   - **Caso de prueba**: se actualiza el total de casos ejecutados.
   - **Resultado**: se reescribe la línea `PASS/FAIL` con los conteos y el
     tiempo reales, y se reemplaza la captura por la nueva.
   - **Observaciones**: se conservan; si la corrida trae fallos, se añade la
     nota correspondiente.
5. Actualiza el resumen general (suite, resultado global, tiempo total,
   cobertura y observación general).
6. Guarda la copia en `formatos/resultados/` y un manifiesto JSON con el
   detalle de la ejecución.

No requiere Microsoft Word instalado: el `.docx` se edita como paquete Open
XML (ZIP + XML). La evidencia muestra el resultado de los casos, no el banner
de presentación de PHPUnit.

## Requisitos

- PHP 8.2 o superior en el `PATH`, con `dom`, `mbstring`, `zlib` y `gd`
  (con FreeType) para dibujar las capturas.
- PHPUnit instalado en `vendor/` (`composer install`).
- Driver `pdo_sqlite` habilitado.
- Al menos una fuente monoespaciada instalada (DejaVu Sans Mono, Liberation
  Mono, Noto Sans Mono, Menlo…). No hace falta navegador.
- El formato original en
  `tests/tools/integration/formatos/plantillas/`.
- PowerShell 5.1+ en Windows, o Bash en Linux/macOS.

El ejecutor Bash no necesita `zip` ni `unzip`: el motor
`scripts/lib/integration-doc.php` lee y escribe el `.docx` con un lector/escritor
ZIP propio sobre `zlib`.

Las capturas se dibujan con GD dentro del propio motor (en Windows,
`System.Drawing`). No se lanza Chrome ni ningún otro navegador: un Chrome
headless con perfil nuevo puede quedarse descargando componentes y no terminar
nunca, dejando la corrida colgada.

## Uso

### Windows (PowerShell)

```powershell
# Los 31 módulos (recomendado)
powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1

# Un módulo por número o por nombre
powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -Module 29
powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -Module Backup

# Elegir la ruta de salida
powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -Module PasswordReset -Output C:\temp\cuadro-31.docx

# Conservar los JUnit temporales
powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -KeepWorking
```

### Linux / macOS (Bash)

```bash
# Los 31 módulos (recomendado)
./tests/tools/integration/scripts/linux/run-integration-doc.sh

# Un módulo por número o por nombre
./tests/tools/integration/scripts/linux/run-integration-doc.sh --module=29
./tests/tools/integration/scripts/linux/run-integration-doc.sh --module=Backup

# Elegir la ruta de salida
./tests/tools/integration/scripts/linux/run-integration-doc.sh --module=PasswordReset --output=/tmp/cuadro-31.docx

# Conservar los JUnit temporales
./tests/tools/integration/scripts/linux/run-integration-doc.sh --keep-working
```

Si el sistema no tiene una fuente monoespaciada conocida, indícala:

```bash
CAPTURE_FONT=/ruta/a/DejaVuSansMono.ttf ./tests/tools/integration/scripts/linux/run-integration-doc.sh
```

Cada módulo tiene un límite de 300 s para que una prueba colgada no detenga la
corrida. Se puede cambiar:

```bash
INTEGRATION_TIMEOUT=600 ./tests/tools/integration/scripts/linux/run-integration-doc.sh
```

## Parámetros

| PowerShell | Bash | Valor por defecto | Descripción |
| --- | --- | --- | --- |
| `-Module` | `--module` | `all` | `all`, un número `01`-`31` o parte del nombre del modelo. |
| `-Output` | `--output` | `formatos/resultados/Formato-Prueba-Integración-<fecha>.docx` | Ruta del `.docx` de salida. |
| `-KeepWorking` | `--keep-working` | desactivado | Conserva los JUnit temporales de la corrida. |

## Qué genera

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/Formato-Prueba-Integración-<fecha>.docx` | Copia llena del formato. |
| `formatos/resultados/capturas/captura-NN-<Modelo>.png` | Captura nueva de cada módulo. |
| `formatos/resultados/ultima-ejecucion.json` | Conteos, tiempos, estado y ruta de cada captura. |

Los resultados están ignorados por git. El formato original y su plantilla sí
se versionan.

## Resultado esperado

En una corrida completa y sana:

```text
Módulos ejecutados : 31 de 31
Casos              : 468
Aserciones         : 9.920
Omitidos           : 5
Módulos con fallos : 0
```

Los 5 casos omitidos son los mismos que documenta
[`../../../GUIA_PRUEBAS.md`](../../../GUIA_PRUEBAS.md): SQL o constructores
exclusivos de MySQL que no son portables a SQLite.

## Modo parcial

Si eliges un módulo, el script solo ejecuta y actualiza los cuadros elegidos. El
resumen general se marca como **parcial** y la cobertura indica cuántos módulos
se ejecutaron. Los cuadros no elegidos conservan los datos del formato.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Copia generada sin fallos. |
| `1` | Hay módulos con fallos; revisa el JUnit y las observaciones. |
| `2` | El filtro de módulo no coincide con ninguno. |
| `3` | Error de entorno: falta PHP, PHPUnit, GD o el formato original. |

## Solución de problemas

| Síntoma | Causa | Solución |
| --- | --- | --- |
| `no se encontró php en el PATH` | PHP no está en el `PATH`. | Abre una consola nueva o agrega `C:\xampp\php`. |
| `No se encontró una fuente monoespaciada` | No hay DejaVu/Liberation/Noto instaladas (Linux). | Define `CAPTURE_FONT` con la ruta de un `.ttf` monoespaciado. |
| `La extensión GD de PHP no está disponible` | PHP se instaló sin `php-gd` o sin FreeType. | Instala `php-gd` (`sudo apt install php8.2-gd`). |
| `PHPUnit (módulo NN) no terminó en 300 s` | Una prueba quedó bloqueada. | Revisa ese test; si la máquina es lenta, sube `INTEGRATION_TIMEOUT`. |
| `falta el formato original` | El `.docx` no está en `plantillas/`. | Restaura el archivo original en esa ruta. |
| `No se puede tener acceso al archivo` al guardar | La copia está abierta en Word. | Ciérrala y vuelve a ejecutar. |
| Todos los cuadros salen `NO EJECUTADO` | No se ejecutó PHPUnit. | Revisa la salida y el `--log-junit` temporal. |
| La captura no muestra una prueba | El módulo falló antes de generar el JUnit. | Ejecuta PHPUnit del módulo directamente y revisa el error. |

## Ver también

- Índice de ejecutores: [`../../README.md`](../../README.md)
- Guía conceptual de las suites: [`../../../GUIA_PRUEBAS.md`](../../../GUIA_PRUEBAS.md)
- Comandos manuales por módulo: [`../../../COMANDOS_RAPIDOS.md`](../../../COMANDOS_RAPIDOS.md)
