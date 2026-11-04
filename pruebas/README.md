# Pruebas automatizadas · SYSINECOLARA (Postman)

Carpeta única para las pruebas de API del proyecto. Todo lo necesario para
ejecutarlas está aquí:

```text
pruebas/
├── collections/
│   └── SYSINECOLARA API Tests.postman_collection.json
├── environments/
│   └── SysInescolara Local.postman_environment.json
├── data/
│   └── plantas.csv
├── scripts/
│   └── run.sh
├── README.md          ← este documento (conceptos y scripts copiables)
├── COMANDOS.md        ← guía paso a paso con todos los comandos
└── TRACEABILITY.md    ← matriz de trazabilidad de la suite
```

La colección cubre: login con sesión PHP + CSRF, listados de **Plantas,
Especies, Clientes, Insumos, Lotes y Ventas**, y el CRUD de **Plantas**
(crear con CSV, editar y soft delete).

### Documentos relacionados

| Documento | Para qué sirve |
| --- | --- |
| [`COMANDOS.md`](COMANDOS.md) | Todos los comandos y pasos listos para copiar y pegar: requisitos, servidor, CLI, GUI, diagnóstico y limpieza. |
| [`TRACEABILITY.md`](TRACEABILITY.md) | Matriz de trazabilidad: casos de prueba, cobertura de los 31 requisitos y observaciones de contrato. |
| [`../tests/TRACEABILITY.md`](../tests/TRACEABILITY.md) | Matriz de la suite PHPUnit, complementaria a esta. |

---

## 1. Qué debes saber antes de correr

### 1.1 La API no devuelve formato DataTables (todavía)

Los endpoints de listado devuelven el contrato real del backend:

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

## 3. Ejecutar por CLI

```bash
# Una corrida simple (sin CSV)
POSTMAN_USUARIO=admin POSTMAN_PASSWORD='tu-password' \
  ./pruebas/scripts/run.sh

# Data-driven: 3 filas = 3 iteraciones
POSTMAN_USUARIO=admin POSTMAN_PASSWORD='tu-password' \
  ./pruebas/scripts/run.sh --csv

# Contra otro host (Render, staging, etc.)
POSTMAN_USUARIO=admin POSTMAN_PASSWORD='tu-password' \
POSTMAN_BASE_URL='https://tu-app.onrender.com' \
  ./pruebas/scripts/run.sh
```

Comando equivalente sin el helper:

```bash
postman collection run \
  "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  -d "pruebas/data/plantas.csv"
```

El CLI devuelve **exit code ≠ 0** si alguna aserción falla. Úsalo en CI tal cual.

---

## 4. Variables del environment

| Variable | Ejemplo | Uso |
|---|---|---|
| `baseUrl` | `http://localhost/sys-inescolara` | Raíz de la app (sin `/` final) |
| `authCookie` | *(se llena sola)* | Valor de `PHPSESSID` |
| `csrfToken` | *(se llena sola)* | `_csrf_token` del formulario |
| `usuario` | `admin` | Usuario o correo |
| `password` | *(secreto)* | Contraseña |
| `recaptchaToken` | `PASSED` | Respuesta de reCAPTCHA (llaves de prueba) |
| `createdPlantId` | *(se llena sola)* | ID de la planta de la iteración |
| `especieId` | *(se llena sola)* | Primera especie válida detectada |
| `maxResponseTime` | `1000` | Umbral de latencia en ms |
| `seededCollections` | `plantas,lotes,especies` | Módulos que deben traer datos |

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

---

## 8. CI (GitHub Actions)

```yaml
name: API tests
on: [push, pull_request]
jobs:
  postman:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: npm install -g postman-cli
      - name: Run collection
        env:
          POSTMAN_USUARIO: ${{ secrets.POSTMAN_USUARIO }}
          POSTMAN_PASSWORD: ${{ secrets.POSTMAN_PASSWORD }}
          POSTMAN_BASE_URL: ${{ secrets.POSTMAN_BASE_URL }}
        run: ./pruebas/scripts/run.sh --csv
```

Para CI, el entorno destino debe tener las llaves de reCAPTCHA de prueba o un
usuario/sesión válidos. Los secretos van en GitHub Secrets, nunca en el repo.
