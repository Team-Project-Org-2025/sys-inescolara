# Pruebas de interfaz (Selenium)

Suite E2E con `php-webdriver` + ChromeDriver que recorre la aplicación real en
XAMPP (`http://localhost/sys-inescolara/`).

> Guías de los scripts: [`../tools/interfaz/scripts/setup-interfaz.md`](../tools/interfaz/scripts/setup-interfaz.md) y
> [`../tools/interfaz/scripts/run-interfaz.md`](../tools/interfaz/scripts/run-interfaz.md).

## Requisitos

- XAMPP con **Apache** y **MySQL** iniciados.
- PHP 8.2+ (`php -v`).
- Google Chrome, Chromium o Brave. El ChromeDriver compatible lo instala
  `setup-interfaz` en `tests/Interfaz/bin/` (`chromedriver.exe` en Windows,
  `chromedriver` en Linux/macOS).
- Dependencias de desarrollo instaladas (`vendor/php-webdriver/webdriver`).
- Claves de prueba de reCAPTCHA en el `.env` (ver
  [setup-interfaz.md](../tools/interfaz/scripts/setup-interfaz.md#recaptcha-en-las-pruebas)).
- Usuario de prueba `e2e_admin` / `E2eAdmin123!` (lo crea el seed).

## Preparación

```powershell
composer install
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1
```

El script verifica PHP, PHPUnit y php-webdriver, **descarga ChromeDriver si
falta** (según el navegador detectado), crea las carpetas de resultados y
ejecuta la semilla interna
`tests/tools/interfaz/scripts/lib/seed-interfaz.php`. Para omitir la semilla:

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\setup-interfaz.ps1 -SkipSeed
```

En Linux/macOS:

```bash
composer install
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --skip-seed

# Aplicar las claves de prueba de reCAPTCHA (y revertirlas)
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --test-recaptcha
./tests/tools/interfaz/scripts/linux/setup-interfaz.sh --restore-recaptcha
```

## Ejecución

```powershell
# Toda la suite
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1

# Una suite (Autenticacion, Navegacion, Publico, Catalogo, Inventario,
# Comercial, Operaciones, Sistema)
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1 -Suite Inventario

# Un archivo o prueba concreta
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1 -Filter VentasTest

# Sin ventana de Chrome
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1 -Headless

# Sin volver a crear el usuario
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\windows\run-interfaz.ps1 -SkipSeed
```

En Linux/macOS:

```bash
./tests/tools/interfaz/scripts/linux/run-interfaz.sh
./tests/tools/interfaz/scripts/linux/run-interfaz.sh --suite Inventario
./tests/tools/interfaz/scripts/linux/run-interfaz.sh --filter VentasTest
./tests/tools/interfaz/scripts/linux/run-interfaz.sh --headless
./tests/tools/interfaz/scripts/linux/run-interfaz.sh --skip-seed
```

También se puede invocar PHPUnit directamente:

```powershell
php vendor\phpunit\phpunit\phpunit -c tests\Interfaz\phpunit.xml --testsuite Comercial
```

Los JUnit quedan en `tests/tools/interfaz/formatos/resultados/junit-*.xml`. Las capturas de los
fallos se guardan en `tests/tools/interfaz/formatos/resultados/screenshots/`.

## Configuración

`Support\Config` lee las variables del `.env` de la raíz y permite
sobrescribirlas con variables de entorno:

| Variable | Valor por defecto |
| --- | --- |
| `INTERFAZ_BASE_URL` | `http://localhost/sys-inescolara/` |
| `INTERFAZ_DRIVER` | `tests/Interfaz/bin/chromedriver` (`chromedriver.exe` en Windows) |
| `INTERFAZ_BROWSER_BIN` | detectado: Chrome, Chromium o Brave |
| `INTERFAZ_HEADLESS` | `0` |
| `INTERFAZ_USER` | `e2e_admin` |
| `INTERFAZ_PASSWORD` | `E2eAdmin123!` |
| `INTERFAZ_RESULTS` | `tests/tools/interfaz/formatos/resultados` |
| `DB_*` / `DB_SEC_*` | Valores del `.env` |

## Cobertura

| Suite | Pruebas | Qué cubre |
| --- | ---: | --- |
| Autenticacion | 6 | Login, credenciales inválidas, logout, ruta protegida y recuperación por token |
| Navegacion | 31 | Carga de los 31 módulos del sidebar sin 404 ni errores de tabla |
| Publico | 5 | Inicio, catálogo, servicios, nosotros y contacto |
| Catalogo | 3 | CRUD de especies, ubicaciones y plantas |
| Inventario | 6 | CRUD de unidades, herramientas, insumos y lotes; cuarentena y mermas |
| Comercial | 9 | Clientes, proveedores, precios, compras, ornatos, ampliación, ventas y cuentas por cobrar/pagar |
| Operaciones | 2 | Asignación/completado de tareas y recolección de semillas |
| Sistema | 5 | Roles, usuarios, perfil, respaldos y generación de reportes |

## Estructura

```text
tests/Interfaz/
├── Autenticacion/     Login, logout y recuperación
├── Navegacion/        Recorrido de los 31 módulos
├── Publico/           Páginas públicas
├── Catalogo/          CRUD de catálogo
├── Inventario/        CRUD y flujos de inventario
├── Comercial/         CRUD y flujos comerciales
├── Operaciones/       Tareas y recolección
├── Sistema/           Seguridad, respaldos y reportes
├── Pages/             Page objects (LoginPage, CrudPage, BasePage)
└── Support/           Config, driver, sesión, fixtures, DB y helpers
```

## Notas

- Cada ejecución usa un `INTERFAZ_RUN_ID` único para no chocar con datos
  existentes. Los fixtures insertan datos directamente en MySQL y los flujos
  usan la interfaz para crearlos.
- Las pruebas escriben en la base de datos de desarrollo (CRUD, ventas,
  compras, pagos, cuarentenas, etc.). No apunte esta suite a producción.
- `BackupsTest` crea un respaldo real y lo elimina al final.
- Si ChromeDriver no coincide con Chrome, actualícelo desde
  <https://googlechromelabs.github.io/chrome-for-testing/>.
