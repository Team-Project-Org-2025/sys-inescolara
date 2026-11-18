# `run-alfa` — ejecutor general de pruebas alfa

Ejecuta los grupos funcionales alfa de Sys Inescolara. No invoca las suites
`Unit`, `Integration`, `Smoke`, `Interfaz` ni `Aceptación`: cada grupo es una
prueba independiente que se valida por HTTP contra la aplicación real.

- **Linux/macOS:** `run-alfa.sh`
- **Windows:** `run-alfa.bat`
- **Núcleo multiplataforma:** `run-alfa.php`

## Uso

```bash
./tests/tools/alfa/scripts/run-alfa.sh [grupo|all|core] [opciones]
```

```bat
tests\tools\alfa\scripts\run-alfa.bat [grupo|all|core] [opciones]
```

Siempre desde la raíz del proyecto.

## Grupos

| Grupo | Qué valida |
| --- | --- |
| `auth` | Páginas públicas, login, sesión, rutas protegidas y 404. |
| `catalogo` | Especies, plantas, ubicaciones, lotes, precios y unidades. |
| `inventario` | Inventario consolidado, insumos y herramientas. |
| `comercial` | Clientes, ventas, cuentas por cobrar y ornatos. |
| `compras` | Proveedores, compras y cuentas por pagar. |
| `operaciones` | Tareas, trazabilidad, mermas, ampliación y recolección. |
| `reportes` | Dashboard, reportes y bitácora. |
| `sistema` | Usuarios, roles y respaldos. |

## Selección

| Modo | Comando | Ejecuta |
| --- | --- | --- |
| Todo | `run-alfa` o `run-alfa all` | Los ocho grupos. |
| Núcleo | `run-alfa core` | `auth` + `catalogo` + `inventario`. |
| Individual | `run-alfa <grupo>` | Solo ese grupo. |
| Por opción | `run-alfa --test=<grupo>` | Solo ese grupo. |
| Manual | `run-alfa --manual` | Menú para elegir uno o varios. |
| Listado | `run-alfa --list` | Grupos, núcleo y cantidad de casos. |

## Opciones

| Opción | Qué hace |
| --- | --- |
| `--test=NOMBRE` / `--prueba=NOMBRE` | Equivale a pasar el grupo como argumento. |
| `--manual` | Menú interactivo de selección. |
| `--list` | Lista los grupos y sus casos. |
| `--dry-run` | Muestra rutas y estados esperados sin arrancar el servidor. |
| `--verbose` | Añade la URL de cada comprobación al log. |
| `--no-junit` | No genera los JUnit XML. |
| `--results-dir=RUTA` | Cambia el directorio de logs y XML. |
| `--port=N` | Fija el puerto del servidor embebido (por defecto, uno libre). |
| `--timeout=SEG` | Espera máxima a que arranque el servidor (por defecto 20). |
| `--skip-seed` | No crea/actualiza `alfa_admin`. |
| `--keep-server` | Deja el servidor en marcha al terminar (depuración). |
| `-h`, `--help` | Muestra la ayuda. |

## Ejemplos

```bash
# Todo
./tests/tools/alfa/scripts/run-alfa.sh

# Núcleo
./tests/tools/alfa/scripts/run-alfa.sh core

# Un grupo
./tests/tools/alfa/scripts/run-alfa.sh catalogo

# Menú
./tests/tools/alfa/scripts/run-alfa.sh --manual

# Simulación
./tests/tools/alfa/scripts/run-alfa.sh --dry-run
```

## Cómo se ejecuta

1. Verifica `curl`, `pdo_mysql` y los archivos del servidor alfa.
2. Crea o actualiza el usuario `alfa_admin` con rol 1
   (`scripts/seed-alfa.php`), salvo con `--skip-seed`.
3. Busca un puerto libre y arranca
   `php -S 127.0.0.1:<puerto> -t <raíz> tests/tools/alfa/server/router.php`.
4. Inyecta la sesión de `alfa_admin` en las peticiones que llevan la cabecera
   `X-Alfa-Token` con el token aleatorio de la corrida.
5. Ejecuta los grupos seleccionados y escribe JUnit + log por grupo.
6. Detiene el servidor (salvo `--keep-server`).

El servidor de pruebas es un archivo aparte (`server/index.php`). No se modifica
`index.php`, `app/` ni `.env`.

## Resultados

```text
tests/tools/alfa/formatos/resultados/
├── junit-<grupo>.xml          ← JUnit del grupo
├── <grupo>-<fecha>.log        ← detalle HTTP
├── server-<fecha>.log         ← salida del servidor
├── server-<fecha>.err.log     ← errores del servidor
├── seed-alfa-<fecha>.log      ← salida del sembrador
└── cookies-<fecha>.txt        ← cookies temporales
```

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todas las comprobaciones pasaron. |
| `1` | Alguna comprobación falló. |
| `2` | Opción o grupo no válido. |
| `3` | Faltan requisitos del entorno. |

## Requisitos

- PHP 8.2+ con `curl` y `pdo_mysql`.
- MySQL/MariaDB iniciado con las bases de `.env`.
- `alfa_admin` (lo crea el ejecutor) o `--skip-seed` con un usuario existente.

## Ver también

- [`../README.md`](../README.md) — panorama de las pruebas alfa.
- [`seed-alfa.php`](seed-alfa.php) — sembrador del usuario de pruebas.
- [`run-auth.md`](run-auth.md) y demás guías por grupo.
