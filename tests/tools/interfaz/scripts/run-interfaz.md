# `run-interfaz` — pruebas E2E con Selenium

> **Tipo:** Selenium + php-webdriver · PowerShell y Bash
>
> **Ejecutores:** `run-interfaz.ps1` (Windows) y `run-interfaz.sh` (Linux/macOS)
>
> **Atajo:** ejecuta la suite de interfaz completa, una suite concreta o un
> filtro de PHPUnit, y guarda el JUnit y las capturas de fallos.

## Qué hace

1. Si no se indica omitir la semilla, ejecuta `lib/seed-interfaz.php` para
   crear/actualizar `e2e_admin`.
2. Crea `tests/tools/interfaz/formatos/resultados/`.
3. Ejecuta PHPUnit con `tests/Interfaz/phpunit.xml`.
4. Guarda el JUnit en `tests/tools/interfaz/formatos/resultados/junit-<nombre>.xml`.
5. Devuelve el código de salida de PHPUnit.

Las pruebas recorren la aplicación real en
`http://localhost/sys-inescolara/` y **escriben en la base de datos de
desarrollo** (CRUD, ventas, compras, pagos, respaldos, etc.). No la apuntes a
producción.

## Requisitos

- XAMPP con **Apache** y **MySQL** iniciados.
- PHP 8.2+ y dependencias de Composer (`composer install`).
- Google Chrome y un ChromeDriver compatible en
  `tests/Interfaz/bin/chromedriver.exe` (Windows) o
  `tests/Interfaz/bin/chromedriver` (Linux/macOS).
- Usuario de prueba `e2e_admin` / `E2eAdmin123!`.
- Entorno preparado con [`setup-interfaz`](setup-interfaz.md).

Si es la primera vez:

```powershell
# Windows
composer install
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1
```

```bash
# Linux/macOS
composer install
./tests/tools/interfaz/scripts/setup-interfaz.sh
```

## Uso

### Windows (PowerShell)

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 [-Suite <nombre>] [-Filter <patrón>] [-Headless] [-SkipSeed]
```

### Linux / macOS (Bash)

```bash
./tests/tools/interfaz/scripts/run-interfaz.sh [--suite <nombre>] [--filter <patrón>] [--headless] [--skip-seed]
```

## Parámetros

| PowerShell | Bash | Valor por defecto | Descripción |
| --- | --- | --- | --- |
| `-Suite` | `--suite` | `all` | Suite a ejecutar: `Autenticacion`, `Navegacion`, `Publico`, `Catalogo`, `Inventario`, `Comercial`, `Operaciones`, `Sistema`. |
| `-Filter` | `--filter` | vacío | Patrón `--filter` de PHPUnit: clase o `Clase::metodo`. Tiene prioridad sobre la suite. |
| `-Headless` | `--headless` | desactivado | Ejecuta Chrome sin ventana (`INTERFAZ_HEADLESS=1`). |
| `-SkipSeed` | `--skip-seed` | desactivado | No vuelve a crear el usuario de prueba. |

## Ejemplos

### Toda la suite

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/run-interfaz.sh
```

### Una suite

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -Suite Inventario
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -Suite Comercial -Headless
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/run-interfaz.sh --suite Inventario
./tests/tools/interfaz/scripts/run-interfaz.sh --suite Comercial --headless
```

### Un archivo o una prueba concreta

```powershell
# Windows: una clase y un método
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -Filter VentasTest
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -Filter 'VentasTest::registra_una_venta_de_contado_con_pago_completo'
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/run-interfaz.sh --filter VentasTest
./tests/tools/interfaz/scripts/run-interfaz.sh --filter 'VentasTest::registra_una_venta_de_contado_con_pago_completo'
```

### Sin recrear el usuario

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1 -SkipSeed
```

```bash
# Linux/macOS
./tests/tools/interfaz/scripts/run-interfaz.sh --skip-seed
```

## Cobertura por suite

| Suite | Pruebas | Qué cubre |
| --- | ---: | --- |
| `Autenticacion` | 6 | Login, credenciales inválidas, logout, ruta protegida y recuperación. |
| `Navegacion` | 31 | Carga de los 31 módulos del sidebar sin 404 ni errores de tabla. |
| `Publico` | 5 | Inicio, catálogo, servicios, nosotros y contacto. |
| `Catalogo` | 3 | CRUD de especies, ubicaciones y plantas. |
| `Inventario` | 6 | CRUD de unidades, herramientas, insumos y lotes; cuarentena y mermas. |
| `Comercial` | 9 | Clientes, proveedores, precios, compras, ornatos, ampliación, ventas y cuentas. |
| `Operaciones` | 2 | Tareas y recolección de semillas. |
| `Sistema` | 5 | Roles, usuarios, perfil, respaldos y reportes. |

## Qué genera

| Archivo | Contenido |
| --- | --- |
| `tests/tools/interfaz/formatos/resultados/junit-all.xml` | JUnit de toda la suite. |
| `tests/tools/interfaz/formatos/resultados/junit-<suite>.xml` | JUnit de una suite. |
| `tests/tools/interfaz/formatos/resultados/junit-filter-<patrón>.xml` | JUnit de un filtro. |
| `tests/tools/interfaz/formatos/resultados/screenshots/` | Capturas de los fallos. |

## Códigos de salida

Devuelve el código de PHPUnit: `0` si todo pasó, distinto de `0` si hubo
fallos o errores de configuración.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `No se pudo preparar el usuario de prueba` | MySQL apagado o credenciales incorrectas. | Inicia MySQL y revisa `.env`. |
| Chrome no abre | ChromeDriver no coincide con Chrome. | Actualiza el binario en `tests/Interfaz/bin/`. |
| La app devuelve 404 | Apache apagado o URL incorrecta. | Inicia Apache y revisa `INTERFAZ_BASE_URL`. |
| El login falla | Usuario no creado o contraseña cambiada. | Ejecuta [`setup-interfaz`](setup-interfaz.md) sin omitir la semilla. |
| Quiero ver el navegador | `-Headless` / `--headless` activo. | Quita la opción. |

## Ver también

- Preparar el entorno: [`setup-interfaz.md`](setup-interfaz.md)
- Documentación de la suite: [`Interfaz/README.md`](../../../Interfaz/README.md)
- Código y Page Objects: `tests/Interfaz/`
