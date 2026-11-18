# Pruebas ALFA — Sys Inescolara

Las **pruebas alfa** son verificaciones funcionales propias del equipo de
desarrollo. Se ejecutan por HTTP contra la aplicación real que el propio
ejecutor levanta con el servidor embebido de PHP.

> **Importante:** estos scripts **no ejecutan** las suites `Unit`, `Integration`,
> `Smoke`, `Interfaz` ni `Aceptación`. Cada tipo de prueba es independiente y se
> automatiza con su propia herramienta. Las pruebas alfa solo corren sus propios
> grupos funcionales.

```text
tests/tools/alfa/
├── README.md
├── server/
│   ├── index.php                       ← entrada de pruebas (sesión + reCAPTCHA de prueba)
│   └── router.php                      ← router del servidor embebido
├── scripts/
│   ├── run-alfa.php / .sh / .bat / .md ← ejecutor general
│   ├── run-auth.sh / .bat / .md        ← autenticación y acceso
│   ├── run-catalogo.sh / .bat / .md    ← catálogo
│   ├── run-inventario.sh / .bat / .md  ← inventario
│   ├── run-comercial.sh / .bat / .md   ← comercial
│   ├── run-compras.sh / .bat / .md     ← compras
│   ├── run-operaciones.sh / .bat / .md ← operaciones
│   ├── run-reportes.sh / .bat / .md    ← reportes
│   ├── run-sistema.sh / .bat / .md     ← sistema
│   └── seed-alfa.php                   ← crea/actualiza el usuario alfa_admin
└── formatos/
    ├── plantillas/junit-reporte.xml
    └── resultados/                     ← JUnit, logs y cookies (ignorados por git)
```

## Qué prueba cada script

| Grupo | Script Linux/macOS | Script Windows | Qué valida |
| --- | --- | --- | --- |
| `auth` | `run-auth.sh` | `run-auth.bat` | Páginas públicas, login, sesión, rutas protegidas y 404. |
| `catalogo` | `run-catalogo.sh` | `run-catalogo.bat` | Especies, plantas, ubicaciones, lotes, precios y unidades. |
| `inventario` | `run-inventario.sh` | `run-inventario.bat` | Inventario consolidado, insumos y herramientas. |
| `comercial` | `run-comercial.sh` | `run-comercial.bat` | Clientes, ventas, cuentas por cobrar y ornatos. |
| `compras` | `run-compras.sh` | `run-compras.bat` | Proveedores, compras y cuentas por pagar. |
| `operaciones` | `run-operaciones.sh` | `run-operaciones.bat` | Tareas, trazabilidad, mermas, ampliación y recolección. |
| `reportes` | `run-reportes.sh` | `run-reportes.bat` | Dashboard, reportes y bitácora. |
| `sistema` | `run-sistema.sh` | `run-sistema.bat` | Usuarios, roles y respaldos. |

Cada script individual equivale a `run-alfa` con `--test=<grupo>` y acepta las
mismas opciones (`--dry-run`, `--verbose`, `--no-junit`, etc.).

## Requisitos

- PHP 8.2+ con las extensiones `curl` y `pdo_mysql`.
- MySQL/MariaDB iniciado y las bases configuradas en `.env`
  (`DB_*` y `DB_SEC_*`).
- El usuario de pruebas `alfa_admin`. El ejecutor lo crea o actualiza antes de
  arrancar el servidor; use `--skip-seed` si ya existe y no quiere tocarlo.
- No hace falta Apache: el ejecutor levanta `php -S 127.0.0.1:<puerto libre>`.

Para preparar el usuario manualmente:

```bash
php tests/tools/alfa/scripts/seed-alfa.php
```

## Ejecución rápida

Siempre desde la raíz del proyecto.

### Linux / macOS

```bash
# Los ocho grupos
./tests/tools/alfa/scripts/run-alfa.sh

# Núcleo: auth + catalogo + inventario
./tests/tools/alfa/scripts/run-alfa.sh core

# Un solo grupo
./tests/tools/alfa/scripts/run-auth.sh
./tests/tools/alfa/scripts/run-catalogo.sh

# Menú para elegir uno o varios grupos
./tests/tools/alfa/scripts/run-alfa.sh --manual

# Ver el plan sin arrancar el servidor
./tests/tools/alfa/scripts/run-alfa.sh --dry-run
```

### Windows

```bat
rem Los ocho grupos
tests\tools\alfa\scripts\run-alfa.bat

rem Núcleo
tests\tools\alfa\scripts\run-alfa.bat core

rem Un solo grupo
tests\tools\alfa\scripts\run-auth.bat
tests\tools\alfa\scripts\run-catalogo.bat

rem Menú interactivo
tests\tools\alfa\scripts\run-alfa.bat --manual
```

## Modos de ejecución

| Modo | Comando | Ejecuta |
| --- | --- | --- |
| Todo | `run-alfa` sin argumentos o `run-alfa all` | Los ocho grupos. |
| Núcleo | `run-alfa core` | `auth` + `catalogo` + `inventario`. |
| Individual | `run-alfa <grupo>` o un script `run-<grupo>` | Solo ese grupo. |
| Manual (menú) | `run-alfa --manual` | Pregunta qué grupos ejecutar. |
| Simulación | `run-alfa --dry-run` | Muestra rutas y estados esperados sin arrancar el servidor. |
| Listado | `run-alfa --list` | Grupos, núcleo y cantidad de casos. |

## Cómo funciona la sesión

Las pruebas necesitan un usuario autenticado, pero el login real exige Google
reCAPTCHA. Para que la suite sea determinista y funcione sin red:

1. `seed-alfa.php` crea/actualiza `alfa_admin` con rol 1 (Administrador).
2. `run-alfa.php` arranca el servidor con un token aleatorio en
   `ALFA_SESSION_TOKEN`.
3. `server/index.php` inyecta la sesión de `alfa_admin` solo si la petición trae
   la cabecera `X-Alfa-Token` con ese token.
4. Las comprobaciones sin sesión usan otro cookie jar y omiten el token, de modo
   que el control de acceso se prueba de verdad (401 y redirecciones).

Nada de esto toca `index.php`, `app/` ni `.env`: el servidor de pruebas alfa es
un archivo aparte que solo usa el runner. En producción no existe esa ruta.

## Qué modifica y qué no

- **No modifica datos de negocio.** Las comprobaciones de escritura envían
  peticiones incompletas para validar los mensajes de error; no llegan a insertar
  ni actualizar registros.
- **Sí escribe** el usuario `alfa_admin` en la base de seguridad (salvo con
  `--skip-seed`), los JUnit y logs en `formatos/resultados/`, y las cookies de
  sesión temporales.
- No ejecuta `tests/phpunit.xml`, `tests/Interfaz`, `tests/aceptacion` ni la
  colección Postman. Esas suites se lanzan con sus propios scripts.

## Resultados

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/junit-<grupo>.xml` | JUnit del grupo (`auth`, `catalogo`, ...). |
| `formatos/resultados/<grupo>-<fecha>.log` | Detalle de cada comprobación HTTP. |
| `formatos/resultados/server-<fecha>.log` | Salida del servidor PHP embebido. |
| `formatos/resultados/server-<fecha>.err.log` | Errores del servidor embebido. |
| `formatos/resultados/seed-alfa-<fecha>.log` | Salida del sembrador de usuario. |
| `formatos/resultados/cookies-<fecha>.txt` | Cookies de sesión temporales. |

Los resultados están ignorados por git; las plantillas y este README se
versionan.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todas las comprobaciones ejecutadas pasaron. |
| `1` | Alguna comprobación falló. |
| `2` | Error de uso: opción o grupo no válido. |
| `3` | Faltan requisitos: curl, pdo_mysql, MySQL, servidor o sembrador. |

## Relación con las otras pruebas

| Nivel | Herramienta | Alcance |
| --- | --- | --- |
| Unit / Integration / Smoke | `tests/tools/phpunit/` | Lógica y SQL de los modelos. |
| Interfaz | `tests/tools/interfaz/` | Flujos E2E con Selenium. |
| Aceptación | `tests/tools/aceptacion/` | Recorrido funcional con Codeception. |
| **Alfa** | **`tests/tools/alfa/`** | **Grupos funcionales por módulo, por HTTP local.** |
| Beta | `tests/tools/beta/` | Humo, API y checklist sobre staging/producción controlada. |

Las pruebas alfa son independientes: puedes ejecutar solo `run-catalogo` sin
correr ninguna otra suite.
