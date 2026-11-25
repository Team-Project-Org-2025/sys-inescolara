# Pruebas automatizadas · SYSINECOLARA (Postman)

Carpeta única para las pruebas de API del proyecto. Todo lo necesario para
ejecutarlas está aquí:

```text
pruebas/
├── collections/
│   ├── SYSINECOLARA API Tests.postman_collection.json   suite monolítica anterior
│   ├── inventario/                                      una colección por módulo
│   │   ├── Plantas.postman_collection.json
│   │   ├── Especies.postman_collection.json
│   │   ├── Ubicaciones.postman_collection.json
│   │   ├── UnidadesMedida.postman_collection.json
│   │   ├── Insumos.postman_collection.json
│   │   ├── Herramientas.postman_collection.json
│   │   ├── Lotes.postman_collection.json
│   │   ├── Precios.postman_collection.json
│   │   ├── Inventario.postman_collection.json
│   │   ├── Trazabilidad.postman_collection.json
│   │   └── Mermas.postman_collection.json
│   ├── comercial/                                       una colección por módulo
│   │   ├── Clientes.postman_collection.json
│   │   ├── Ventas.postman_collection.json
│   │   ├── Ornatos.postman_collection.json
│   │   ├── CuentasCobrar.postman_collection.json
│   │   └── Ampliacion.postman_collection.json
│   ├── compras/                                         una colección por módulo
│   │   ├── Proveedores.postman_collection.json
│   │   ├── Compras.postman_collection.json
│   │   └── CuentasPagar.postman_collection.json
│   ├── operaciones/                                     una colección por módulo
│   │   ├── Tareas.postman_collection.json
│   │   └── Recoleccion.postman_collection.json
│   ├── sistema/                                         una colección por módulo
│   │   ├── Usuarios.postman_collection.json
│   │   ├── Roles.postman_collection.json
│   │   ├── Auditlog.postman_collection.json
│   │   ├── Backups.postman_collection.json
│   │   ├── Reports.postman_collection.json
│   │   ├── Notifications.postman_collection.json
│   │   └── Dashboard.postman_collection.json
│   └── publico/                                         smoke test de vistas
│       └── Publico.postman_collection.json
├── environments/
│   └── SysInescolara Local.postman_environment.json
├── data/
│   ├── plantas.csv
│   ├── seed.sql             datos mínimos para los CRUD (catálogos, planta y lote base)
│   └── seed-seguridad.sql   usuario postman_viewer para los casos 403
├── scripts/
│   ├── run.sh               corre las 29 colecciones (o una sola)
│   ├── run-all.sh           corre todo y exporta JUnit (Linux/macOS)
│   └── run-all.ps1          corre todo y exporta JUnit (Windows)
├── README.md          ← este documento (conceptos y scripts copiables)
├── COMANDOS.md        ← guía paso a paso con todos los comandos
└── TRACEABILITY.md    ← matriz de trazabilidad de la suite
```

**Suite por módulos (actual).** Cada colección de `collections/<dominio>/`
cubre el CRUD completo del módulo más los negativos `401/403/400`, y se
corresponde con una ficha en `docs/api/<dominio>/`. Antes de ejecutarla importa
`pruebas/data/seed.sql`.

**Dominio Comercial.** Ventas y Ornatos usan `?accion=`; el resto, `?action=`.
Cuentas por cobrar se sirve en `/cuentas_cobrar` (guion bajo). Las colecciones
de Ventas, Cuentas por cobrar y Ampliación crean su propio lote y lo desactivan
al final para no alterar el seed.

**Suite monolítica (anterior).** `SYSINECOLARA API Tests` cubre login con sesión
PHP + CSRF, listados de **Plantas, Especies, Clientes, Insumos, Lotes y Ventas**,
y el CRUD de **Plantas** con CSV. Se conserva como referencia.

### Documentos relacionados

| Documento | Para qué sirve |
| --- | --- |
| [`COMANDOS.md`](COMANDOS.md) | Todos los comandos y pasos listos para copiar y pegar: requisitos, servidor, CLI, GUI, diagnóstico y limpieza. |
| [`TRACEABILITY.md`](TRACEABILITY.md) | Matriz de trazabilidad: casos de prueba, cobertura de los 31 requisitos y observaciones de contrato. |
| [`../tests/tools/reportes/formatos/resultados/TRACEABILITY.md`](../tests/tools/reportes/formatos/resultados/TRACEABILITY.md) | Matriz de la suite PHPUnit, complementaria a esta. |
| [`../tests/tools/README.md`](../tests/tools/README.md) | Índice de ejecutores de pruebas (PHPUnit, Selenium, aceptación y estos scripts de API). |

---

## 1. Qué debes saber antes de correr

### 1.1 La mayoría de los listados no devuelve formato DataTables

Salvo `cuentas_cobrar?action=obtener_lista`, los endpoints de listado devuelven
el contrato real del backend:

```json
{ "success": true, "plantas": [ ... ], "count": 1 }
```

No devuelven `{ data, recordsTotal, recordsFiltered }`. Las pruebas validan el
contrato real y, además, incluyen una comprobación que se activa sola si algún
día migras el endpoint a DataTables server-side.

### 1.2 El login tiene reCAPTCHA v2

`POST /login` exige `g-recaptcha-response` y lo verifica contra Google. Un
Collection Runner **no puede resolver el captcha**. Tienes dos caminos:

**A. Automatización local (recomendado).** Usa las llaves de prueba oficiales
de Google en el `.env` local. Con la *secret* de prueba, Google acepta
cualquier respuesta no vacía:

```dotenv
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

El environment ya trae `recaptchaToken=PASSED`. PHP lee el `.env` en cada
petición, no hace falta reiniciar Apache.

> Nunca uses estas llaves en producción. En Render mantén tus llaves reales.

**B. Contra un entorno con llaves reales.** Inicia sesión una vez en el
navegador, copia la cookie `PHPSESSID` desde DevTools → Application → Cookies y
pégala en la variable `authCookie` del environment. Luego ejecuta solo la
carpeta `10 · GET (listados)` y `20 · POST (escritura y limpieza)`
(omite `00 · Autenticación`). Un token de reCAPTCHA real caduca en ~2 minutos,
así que no sirve para una corrida completa.

### 1.3 CSRF

El backend exige CSRF solo en POST **no AJAX** (`checkCsrf()` retorna antes si
la petición es AJAX). La colección envía igualmente `X-CSRF-Token` y
`_csrf_token` en los POST para dejar la prueba lista para cuando se endurezca
esa validación. El token se extrae del formulario de login.

### 1.4 Validación de nombres

`Planta` valida `nombre_comun` y `nombre_tecnico` con
`/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/`. Por eso el CSV usa solo letras y espacios.
Un nombre con números, paréntesis o guiones devuelve error (hoy como HTML 404;
ver “Hallazgos”).

---

## 2. Importar en Postman (GUI)

1. **Import** → arrastra:
   - la colección del módulo que quieras ejecutar (por ejemplo
     `pruebas/collections/inventario/Plantas.postman_collection.json`), o
   - `pruebas/collections/SYSINECOLARA API Tests.postman_collection.json`
   - `pruebas/environments/SysInescolara Local.postman_environment.json`
2. Selecciona el environment **SysInescolara Local** (arriba a la derecha).
3. Edita `usuario` y `password` con un usuario administrador local.
4. Abre **Runner** → selecciona la colección → activa el environment.
5. Opcional: en **Data** selecciona `pruebas/data/plantas.csv` y 3 iteraciones.
6. **Run**.

El orden importa: `00` cierra cualquier sesión anterior, `01` obtiene la
cookie anónima y el CSRF, y `02` autentica y guarda la nueva `PHPSESSID`.

---

## 3. Ejecutar por CLI (Newman)

```bash
# Requisito
npm install -g newman

# Correr todas las colecciones por módulo
POSTMAN_PASSWORD='Admin123!' POSTMAN_VIEWER_PASSWORD='Postman123!' \
  ./pruebas/scripts/run.sh

# Correr una sola colección
POSTMAN_PASSWORD='Admin123!' POSTMAN_VIEWER_PASSWORD='Postman123!' \
  ./pruebas/scripts/run.sh "pruebas/collections/inventario/Plantas.postman_collection.json"

# Contra otro host (Render, staging, etc.)
POSTMAN_PASSWORD='Admin123!' POSTMAN_VIEWER_PASSWORD='Postman123!' \
  POSTMAN_BASE_URL='https://tu-app.onrender.com' \
  ./pruebas/scripts/run.sh
```

Comando equivalente sin el helper:

```bash
newman run \
  "pruebas/collections/inventario/Plantas.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "baseUrl=http://localhost/sys-inescolara" \
  --env-var "password=Admin123!" \
  --env-var "viewerPassword=Postman123!"
```

Correr todo y exportar JUnit (para CI o reportes):

```bash
# Linux / macOS
POSTMAN_PASSWORD='Admin123!' POSTMAN_VIEWER_PASSWORD='Postman123!' \
  ./pruebas/scripts/run-all.sh

# Windows (PowerShell)
$env:POSTMAN_PASSWORD='Admin123!'; $env:POSTMAN_VIEWER_PASSWORD='Postman123!'
pwsh -File pruebas\scripts\run-all.ps1
```

El environment versionado deja `password` y `viewerPassword` vacíos: pásalos
como `--env-var` (o edítalos en Postman) para no commitear secretos.

`newman` devuelve **exit code ≠ 0** si alguna aserción falla. Úsalo en CI tal
cual.

---

## 4. Variables del environment

| Variable | Ejemplo | Uso |
|---|---|---|
| `baseUrl` | `http://localhost/sys-inescolara` | Raíz de la app (sin `/` final) |
| `authCookie` | *(se llena sola)* | Valor de `PHPSESSID` |
| `csrfToken` | *(se llena sola)* | `_csrf_token` del formulario |
| `usuario` | `admin` | Usuario o correo |
| `password` | *(secreto)* | Contraseña del administrador |
| `viewerUsuario` | `postman_viewer` | Usuario sin permisos para el caso 403 |
| `viewerPassword` | *(secreto)* | Contraseña del usuario viewer |
| `recaptchaToken` | `PASSED` | Respuesta de reCAPTCHA (llaves de prueba) |
| `createdPlantId` | *(se llena sola)* | ID de la planta de la iteración |
| `especieId` | *(se llena sola)* | Primera especie válida detectada |
| `maxResponseTime` | `1000` | Umbral de latencia en ms |
| `seededCollections` | `plantas,lotes,especies` | Módulos que deben traer datos |
| `seedPlantaId`, `seedUbicacionId` | `1` | IDs de los registros base de `seed.sql` |
| `seedEstadoId`, `seedEstadoCuarentenaId` | `5`, `6` | Estados `vivo` y `cuarentena` |
| `seedCategoriaId`, `seedOrigenId` | `1`, `1` | Catálogos de lote (`germinado`, `Siembra`) |
| `seedLoteId`, `seedTrazabilidadId` | `1`, `1` | Lote y cuarentena base |

`authCookie`, `csrfToken` y `password` están marcadas como **secret** en
Postman. No commitees el environment con valores reales; el archivo versionado
debe quedar con los secretos vacíos.

### ¿Qué hacer si mi BD no tiene clientes/insumos/ventas?

Hoy esas tablas están vacías en local, así que la prueba solo exige que el
array exista. Cuando siembres datos, agrega la clave a `seededCollections`:

```text
plantas,lotes,especies,clientes,supplies,ventas
```

---

## 5. Scripts, listos para copiar

### 5.1 Pre-request del login: limpiar estado

```js
// Limpieza de estado de ejecuciones anteriores.
['authCookie', 'csrfToken', 'createdPlantId', 'loginOk'].forEach((key) => {
  pm.environment.unset(key);
});

// Vaciar el cookie jar para no arrastrar una sesión autenticada.
try {
  pm.cookies.jar().clear(pm.request.url, function () {});
} catch (e) {
  console.log('No se pudo limpiar el cookie jar:', e.message);
}

pm.request.headers.remove('Cookie');
```

### 5.2 Post-response del `GET /login`: capturar CSRF y cookie

```js
const html = pm.response.text();

pm.test('Status 200', () => pm.response.to.have.status(200));

const csrfMatch = html.match(/name="_csrf_token"\s+value="([a-f0-9]{32,})"/i);
pm.test('Token CSRF presente', () => pm.expect(csrfMatch).to.not.be.null);

if (csrfMatch) {
  pm.environment.set('csrfToken', csrfMatch[1]);
}

// La PHPSESSID anónima es la dueña del token CSRF: hay que conservarla.
let sessionId = null;
try {
  sessionId = pm.cookies.get('PHPSESSID');
} catch (e) {
  sessionId = null;
}

if (!sessionId) {
  const raw = pm.response.headers.get('Set-Cookie') || '';
  const match = raw.match(/PHPSESSID=([^;]+)/);
  if (match) sessionId = match[1];
}

pm.test('PHPSESSID capturada', () => {
  pm.expect(sessionId).to.be.a('string').and.not.empty;
});

if (sessionId) {
  pm.environment.set('authCookie', sessionId);
}
```

### 5.3 Post-response del `POST /login`: guardar la cookie autenticada

```js
const location = pm.response.headers.get('Location') || '';
const loginOk = pm.response.code === 302 && location.includes('/dashboard');

pm.test('Login OK (302 a /dashboard)', () => {
  pm.expect(loginOk, 'Revisa usuario/password/reCAPTCHA').to.equal(true);
});

let sessionId = null;
try {
  sessionId = pm.cookies.get('PHPSESSID');
} catch (e) {
  sessionId = null;
}

if (!sessionId) {
  const raw = pm.response.headers.get('Set-Cookie') || '';
  const match = raw.match(/PHPSESSID=([^;]+)/);
  if (match) sessionId = match[1];
}

pm.test('Nueva PHPSESSID capturada', () => {
  pm.expect(sessionId).to.be.a('string').and.not.empty;
});

// session_regenerate_id(true) cambia el ID: guarda el NUEVO.
if (loginOk && sessionId) {
  pm.environment.set('authCookie', sessionId);
}
```

### 5.4 Tests de un GET (`get_plants`)

Requiere el header `X-Requested-With: XMLHttpRequest` para que los errores de
sesión lleguen como JSON 401 y no como redirect HTML.

```js
let body = null;
try { body = pm.response.json(); } catch (e) { body = null; }

const limit = Number(pm.environment.get('maxResponseTime') || 1000);

pm.test('a) Status 200', () => pm.response.to.have.status(200));
pm.test(`b) Tiempo < ${limit} ms`, () => {
  pm.expect(pm.response.responseTime).to.be.below(limit);
});
pm.test('c) Content-Type JSON', () => {
  pm.expect(pm.response.headers.get('Content-Type') || '').to.include('application/json');
});
pm.test('c) JSON válido', () => {
  pm.expect(body).to.be.an('object');
  pm.expect(body).to.not.be.null;
});
pm.test('d) plantas es un array con datos', () => {
  pm.expect(body).to.have.property('plantas').that.is.an('array');
  pm.expect(body.plantas.length).to.be.above(0);
});

// Si migras a DataTables server-side, esto se activa solo:
if (body && ('recordsTotal' in body || 'data' in body)) {
  pm.test('d) Estructura DataTables', () => {
    pm.expect(body).to.have.property('data').that.is.an('array');
    pm.expect(body).to.have.property('recordsTotal').that.is.a('number');
    pm.expect(body).to.have.property('recordsFiltered').that.is.a('number');
  });
}
```

Variante **estricta** para pegar cuando el endpoint ya devuelva DataTables:

```js
pm.test('d) Respuesta DataTables completa', () => {
  pm.expect(body).to.have.all.keys(
    'draw', 'data', 'recordsTotal', 'recordsFiltered'
  );
  pm.expect(body.data).to.be.an('array').that.is.not.empty;
  pm.expect(body.recordsTotal).to.be.a('number').and.to.be.at.least(body.data.length);
  pm.expect(body.recordsFiltered).to.be.a('number');
});
```

### 5.5 Tests de un POST (`add_ajax` de Plantas)

Configura el header dinámico en la pestaña **Headers**:

| Key | Value |
|---|---|
| `Content-Type` | `application/x-www-form-urlencoded` |
| `X-Requested-With` | `XMLHttpRequest` |
| `X-CSRF-Token` | `{{csrfToken}}` |

```js
let body = null;
try { body = pm.response.json(); } catch (e) { body = null; }

pm.test('Status 200 o 201', () => {
  pm.expect([200, 201]).to.include(pm.response.code);
});
pm.test('Content-Type JSON', () => {
  pm.expect(pm.response.headers.get('Content-Type') || '').to.include('application/json');
});
pm.test('success === true', () => {
  pm.expect(body).to.have.property('success', true);
});
pm.test('Mensaje de éxito', () => {
  pm.expect(body.message).to.be.a('string').and.not.empty;
});
pm.test('ID de la planta creada', () => {
  pm.expect(body).to.have.nested.property('plant.id');
  pm.expect(body.plant.id).to.be.a('number').and.to.be.above(0);
});
```

### 5.6 Inyectar cookie y CSRF en todas las peticiones

Pre-request **a nivel de colección**:

```js
const requestName = pm.info.requestName || '';
const isSessionBootstrap = requestName.startsWith('00') || requestName.startsWith('01');

if (!isSessionBootstrap) {
  const sessionId = pm.environment.get('authCookie');
  if (sessionId) {
    pm.request.headers.upsert({ key: 'Cookie', value: 'PHPSESSID=' + sessionId });
  }

  const csrf = pm.environment.get('csrfToken');
  if (csrf) {
    pm.request.headers.upsert({ key: 'X-CSRF-Token', value: csrf });
  }
}
```

---

## 6. Data-Driven Testing (`plantas.csv`)

```csv
nombre_comun,nombre_tecnico,especie_id,precio
Rosa de Prueba Uno,Rosa canina,1,15.50
Ixora de Prueba Dos,Ixora coccinea,1,20.00
Jazmin de Prueba Tres,Jasminum officinale,1,12.75
```

- `nombre_comun` → campo real del endpoint.
- `especie_id` → FK de `especie`; debe existir.
- `precio` → **no lo consume** `plantas/add_ajax`. El precio vive en
  `registro_insumo`/`lotes` (módulo Precios). Se deja como dato de ejemplo.
- Cada fila ejecuta **toda** la colección: login, GETs, crear, editar y
  eliminar. El `createdPlantId` se guarda y se limpia por iteración.

Si ejecutas el request suelto (sin CSV), el pre-request usa valores por
defecto válidos (`Planta Postman`, especie detectada).

---

## 7. Hallazgos detectados al escribir las pruebas

1. **`get_plants` y compañía no devuelven DataTables.** Devuelven
   `{ success, plantas, count }`. Cualquier test que espere `data`/
   `recordsTotal`/`recordsFiltered` fallará.
2. **`get_clients` devuelve `count: 0` fijo**, aunque traiga clientes
   (`ClientesController::clients_getClientsAjax`). No se debe usar `count`
   como fuente de verdad.
3. **Los POST a rutas amigables no manejan errores como JSON.** Si una
   validación falla en `/plantas/add_ajax`, la excepción sube al
   `FrontController` y responde **404 HTML** en vez de JSON 500. El flujo
   `index() + ?action=add_ajax` sí devuelve JSON. Conviene unificar el manejo
   de errores en el router.
4. **CSRF no se valida en AJAX.** `checkCsrf()` sale antes cuando
   `X-Requested-With: XMLHttpRequest`. El header enviado es preventivo.
5. **Validación de nombres:** solo letras/espacios (2–50). Afecta los datos
   del CSV.
6. **El login depende de reCAPTCHA**, por lo que la corrida headless exige
   llaves de prueba en local.
7. **Compras vía `?action=` no valida permisos de escritura.** El despachador
   llama a los manejadores internos; solo la ruta por segmento
   (`/compras/agregar_ajax`) ejecuta `checkPermisoOrFail`.
8. **`add_ajax` de Usuarios y Roles devuelve el id de auditoría**, no el id del
   registro, por leer `lastInsertId()` tras escribir el log.
9. **`compras?action=agregar_planta_rapido` está roto** (404: `Planta::add()`
   no existe).
10. **`tareas?action=complete_ajax`** exige `consumptions`/`tool_estados` como
    arrays; con form-urlencoded se pasan como string y lanza `TypeError`.

---

## 8. CI (GitHub Actions)

El pipeline está en [`.github/workflows/api-tests.yml`](../.github/workflows/api-tests.yml).
Incluye tres jobs:

1. **`api`** — levanta MariaDB 10.4, carga esquema + migraciones + seed, arranca
   la app con `php -S` (router de aceptación, que fuerza las llaves reCAPTCHA de
   prueba) y ejecuta las 29 colecciones con `newman`, exportando `results/*.xml`.
2. **`phpunit`** — ejecuta `vendor/bin/phpunit`.
3. **`lint`** — `php -l` sobre `app/` e `index.php`.

### Secretos requeridos

| Secreto | Descripción |
|---|---|
| `POSTMAN_ADMIN_PASSWORD` | Contraseña del usuario `admin` (p. ej. `Admin123!`) |
| `POSTMAN_VIEWER_PASSWORD` | Contraseña de `postman_viewer` (p. ej. `Postman123!`) |

Configúralos en **Settings → Secrets and variables → Actions**.

### Cómo funciona la corrida

```bash
# Esquema + migraciones + seed (mismo orden que ejecuta CI)
mysql -h127.0.0.1 -uroot -proot < database/sysinescolara_vacia.sql
php scripts/migrate.php
mysql -h127.0.0.1 -uroot -proot < pruebas/data/seed.sql
mysql -h127.0.0.1 -uroot -proot < pruebas/data/seed-seguridad.sql

# Servidor embebido con router de aceptación (fuerza reCAPTCHA de prueba)
php -S 127.0.0.1:8000 tests/aceptacion/server/router.php

# Correr todo
POSTMAN_BASE_URL='http://127.0.0.1:8000' \
POSTMAN_PASSWORD='Admin123!' POSTMAN_VIEWER_PASSWORD='Postman123!' \
  ./pruebas/scripts/run-all.sh
```

> `postman_viewer` lo crea `pruebas/data/seed-seguridad.sql` (rol Trabajador, sin
> permisos individuales) para que los casos `403` funcionen en un entorno recién
> creado.
