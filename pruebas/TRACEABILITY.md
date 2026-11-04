# Matriz de Trazabilidad — Pruebas de API con Postman

Generada: `2026-11-04` · Ámbito: endpoints JSON (no vistas HTML) · Orden: flujo de ejecución (`auth → GET → POST`)

> Esta matriz refleja la corrida verificada del **2026-11-04** sobre
> `http://localhost/sys-inescolara`, con las llaves de prueba de reCAPTCHA y
> `pruebas/data/plantas.csv` (3 filas). Los comandos para reproducirla están en
> [`COMANDOS.md`](COMANDOS.md).

## Resumen

| Requisitos cubiertos | Casos Postman | Casos PASS | Casos FAIL | Casos SKIP | No ejecutados |
| ---: | ---: | ---: | ---: | ---: | ---: |
| 7 de 31 | 12 | 12 | 0 | 0 | 0 |

Pruebas individuales (assertions) ejecutadas: **186** · PASS: **186** · FAIL: **0** · SKIP: **0**.

Requests ejecutados: **36** (12 requests × 3 iteraciones del CSV).
Cobertura completa del CRUD: **1 requisito** (RF-01 Plantas). Cobertura parcial de solo lectura o autenticación: **6 requisitos**.

## Resumen por módulo

| # | Módulo | Modelo | Endpoint(s) | Casos | Assertions / iteración | Total (3 iteraciones) | Estado |
| ---: | --- | --- | --- | ---: | ---: | ---: | --- |
| 01 | Administrar planta | `Planta` | `/plantas/get_plants`, `/plantas/add_ajax`, `/plantas/edit_ajax`, `/plantas/delete_ajax` | 4 | 21 | 63 | ✅ PASS |
| 02 | Administrar especie | `Especie` | `/especies/get_species` | 1 | 4 | 12 | ✅ PASS |
| 05 | Lote | `Lote` | `/lotes/get_batches` | 1 | 7 | 21 | ✅ PASS |
| 07 | Insumos | `Insumo` | `/insumos/get_supplies` | 1 | 6 | 18 | ✅ PASS |
| 13 | Procesar venta | `Venta` | `/ventas/listar` | 1 | 6 | 18 | ✅ PASS |
| 15 | Gestionar cliente | `Cliente` | `/clientes/get_clients` | 1 | 6 | 18 | ✅ PASS |
| 26 | Usuario | `Usuario` | `/login/logout`, `/login` (GET y POST) | 3 | 12 | 36 | ✅ PASS |
| **Total** | **7 módulos** | — | — | **12** | **62** | **186** | **✅ PASS** |

## Matriz detallada

| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-API-01 | API GET | Listar plantas y validar el contrato real `{ success, plantas, count }`. | Status 200, JSON válido, `plantas` es array no vacío y responde en menos de 1000 ms. | ✅ PASS | `10 · GET Plantas` (7/7 PASS · 3 iteraciones) |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-API-02 | API POST | Crear plantas desde las 3 filas de `plantas.csv` con nombre y especie válidos. | Status 200/201, `success: true`, `message` presente, `plant.id > 0` y guardado en `createdPlantId`. | ✅ PASS | `20 · POST Plantas — crear (CSV)` (6/6 PASS · 3 iteraciones) |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-API-03 | API POST | Editar la planta creada en la misma iteración. | Status 200, `success: true`, mensaje de actualización y `plant.id` igual al creado. | ✅ PASS | `21 · POST Plantas — editar` (4/4 PASS · 3 iteraciones) |
| RF-01 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica las plantas del vivero. | CP-API-04 | API POST | Dar de baja lógica (soft delete) la planta creada. | Status 200, `success: true`, mensaje de desactivación y `plantId` igual al creado. | ✅ PASS | `22 · POST Plantas — eliminar (limpieza)` (4/4 PASS · 3 iteraciones) |
| RF-02 | El sistema debe permitir administrar el catálogo de especies botánicas asociadas a las plantas. | CP-API-05 | API GET | Listar especies y detectar una especie válida para la creación de plantas. | Status 200, `especies` es array no vacío y se guarda `especieId`. | ✅ PASS | `09 · GET Especies (setup de especie_id)` (4/4 PASS · 3 iteraciones) |
| RF-05 | El sistema debe permitir administrar lotes con su estado, categoría, origen y existencias. | CP-API-06 | API GET | Listar los lotes registrados. | Status 200, JSON válido, `lotes` es array, `success: true` y tiempo < 1000 ms. | ✅ PASS | `13 · GET Lotes` (7/7 PASS · 3 iteraciones) |
| RF-07 | El sistema debe permitir administrar insumos, su unidad de medida y sus existencias. | CP-API-07 | API GET | Listar los insumos registrados. | Status 200, JSON válido, `supplies` es array, `success: true` y tiempo < 1000 ms. | ✅ PASS | `12 · GET Insumos` (6/6 PASS · 3 iteraciones) |
| RF-13 | El sistema debe permitir procesar ventas de contado o crédito con detalles y pagos. | CP-API-08 | API GET | Listar las ventas registradas. | Status 200, JSON válido, `ventas` es array, `success: true` y tiempo < 1000 ms. | ✅ PASS | `14 · GET Ventas` (6/6 PASS · 3 iteraciones) |
| RF-15 | El sistema debe permitir registrar, consultar, editar y dar de baja lógica a los clientes. | CP-API-09 | API GET | Listar los clientes registrados. | Status 200, JSON válido, `clientes` es array, `success: true` y tiempo < 1000 ms. | ✅ PASS | `11 · GET Clientes` (6/6 PASS · 3 iteraciones) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-API-10 | API GET | Cerrar cualquier sesión previa y limpiar el estado de la corrida. | Status 302 hacia `/login` (o 200 si el navegador sigue la redirección). | ✅ PASS | `00 · GET /login/logout — limpiar sesión anterior` (2/2 PASS · 3 iteraciones) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-API-11 | API GET | Obtener una sesión anónima y extraer el token CSRF del formulario de login. | Status 200, HTML con `#loginForm`, `_csrf_token` de 64 hex y `PHPSESSID` capturada. | ✅ PASS | `01 · GET /login — sesión y CSRF` (6/6 PASS · 3 iteraciones) |
| RF-26 | El sistema debe permitir autenticar usuarios y administrar sus datos, roles y permisos. | CP-API-12 | API POST | Autenticar con usuario, contraseña y reCAPTCHA; capturar la nueva cookie de sesión. | Status 302 hacia `/dashboard`, sin formulario de login y nueva `PHPSESSID` guardada. | ✅ PASS | `02 · POST /login — autenticación` (4/4 PASS · 3 iteraciones) |

## Cobertura de los 31 requisitos

> `✅ PASS` = cubierto y verificado por la suite Postman.
> `➖ N/A` = sin endpoint en la suite Postman; **no es un fallo**: su cobertura
> está en la suite PHPUnit (`tests/TRACEABILITY.md`).

| RF | Módulo | Cobertura Postman | Casos | Evidencia / cobertura alternativa | Estado |
| --- | --- | --- | --- | --- | --- |
| RF-01 | Administrar planta | **Completa** (CRUD) | CP-API-01…04 | Postman | ✅ PASS |
| RF-02 | Administrar especie | Parcial (solo lectura) | CP-API-05 | Postman | ✅ PASS |
| RF-03 | Administrar ubicación | No cubierta | — | PHPUnit (`RF-03`) | ➖ N/A |
| RF-04 | Inventario | No cubierta | — | PHPUnit (`RF-04`) | ➖ N/A |
| RF-05 | Lote | Parcial (solo lectura) | CP-API-06 | Postman | ✅ PASS |
| RF-06 | Trazabilidad | No cubierta | — | PHPUnit (`RF-06`) | ➖ N/A |
| RF-07 | Insumos | Parcial (solo lectura) | CP-API-07 | Postman | ✅ PASS |
| RF-08 | Herramientas | No cubierta | — | PHPUnit (`RF-08`) | ➖ N/A |
| RF-09 | Unidad de medida | No cubierta | — | PHPUnit (`RF-09`) | ➖ N/A |
| RF-10 | Mermas | No cubierta | — | PHPUnit (`RF-10`) | ➖ N/A |
| RF-11 | Registro de insumos (soporte) | No cubierta | — | PHPUnit (`RF-11`) | ➖ N/A |
| RF-12 | Uso de herramientas (soporte) | No cubierta | — | PHPUnit (`RF-12`) | ➖ N/A |
| RF-13 | Procesar venta | Parcial (solo listado) | CP-API-08 | Postman | ✅ PASS |
| RF-14 | Gestionar precio | No cubierta | — | PHPUnit (`RF-14`) | ➖ N/A |
| RF-15 | Gestionar cliente | Parcial (solo lectura) | CP-API-09 | Postman | ✅ PASS |
| RF-16 | Cuenta por cobrar | No cubierta | — | PHPUnit (`RF-16`) | ➖ N/A |
| RF-17 | Cuentas por pagar | No cubierta | — | PHPUnit (`RF-17`) | ➖ N/A |
| RF-18 | Gestionar compra | No cubierta | — | PHPUnit (`RF-18`) | ➖ N/A |
| RF-19 | Gestionar ornatos | No cubierta | — | PHPUnit (`RF-19`) | ➖ N/A |
| RF-20 | Gestionar ampliación | No cubierta | — | PHPUnit (`RF-20`) | ➖ N/A |
| RF-21 | Gestionar proveedor | No cubierta | — | PHPUnit (`RF-21`) | ➖ N/A |
| RF-22 | Asignar tarea | No cubierta | — | PHPUnit (`RF-22`) | ➖ N/A |
| RF-23 | Gestionar recolección | No cubierta | — | PHPUnit (`RF-23`) | ➖ N/A |
| RF-24 | Reportes | No cubierta | — | PHPUnit (`RF-24`) | ➖ N/A |
| RF-25 | Inicio / Dashboard | No cubierta | — | PHPUnit (`RF-25`) | ➖ N/A |
| RF-26 | Usuario | Parcial (login/logout; no CRUD de usuarios) | CP-API-10…12 | Postman + PHPUnit (`RF-26`) | ✅ PASS |
| RF-27 | Roles | No cubierta | — | PHPUnit (`RF-27`) | ➖ N/A |
| RF-28 | Bitácora | No cubierta | — | PHPUnit (`RF-28`) | ➖ N/A |
| RF-29 | Respaldo | No cubierta | — | PHPUnit (`RF-29`) | ➖ N/A |
| RF-30 | Notificaciones (soporte) | No cubierta | — | PHPUnit (`RF-30`) | ➖ N/A |
| RF-31 | Recuperación de contraseña (soporte) | No cubierta | — | PHPUnit (`RF-31`) | ➖ N/A |

## Pruebas omitidas (SKIP)

No hay pruebas omitidas dentro de la suite Postman.

Los 24 requisitos marcados `➖ N/A` no son omisiones de ejecución: simplemente no
tienen un caso en la suite de API. Su trazabilidad completa está en
[`../tests/TRACEABILITY.md`](../tests/TRACEABILITY.md).

## Observaciones de contrato

1. **Los listados no usan formato DataTables.** Devuelven
   `{ success, <colección>, count }`. La suite valida ese contrato y activa una
   comprobación de `data`/`recordsTotal`/`recordsFiltered` solo si el endpoint
   migra a DataTables.
2. **`GET /clientes/get_clients` devuelve `count: 0` fijo.** Los casos no usan
   `count` como fuente de verdad.
3. **CSRF no se exige en AJAX** (`checkCsrf()` retorna antes). La suite envía
   `X-CSRF-Token` y `_csrf_token` de forma preventiva.
4. **Un error de validación en un POST a ruta amigable responde 404 HTML**, no
   JSON 500, porque la excepción llega al `FrontController`. Los casos POST usan
   datos válidos y verifican el camino feliz.
5. **El login requiere reCAPTCHA.** La corrida automatizada local usa las llaves
   de prueba de Google; con llaves reales hay que reutilizar una sesión del
   navegador. Ver [`COMANDOS.md`](COMANDOS.md).
6. **`plantas` valida nombres con `/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/`.** El CSV
   respeta esa regla.

## Cómo leer esta matriz

- **PASS:** el caso se ejecutó en la corrida verificada y todas sus assertions
  pasaron.
- **FAIL:** al menos una assertion falló. Revisa la salida de
  `pruebas/scripts/run.sh` o del Collection Runner.
- **SKIP:** no aplica a esta suite; no hay casos omitidos.
- **N/A:** el requisito no tiene cobertura en Postman. No implica que el sistema
  falle: su cobertura está en PHPUnit.
- **Iteración:** cada fila de `pruebas/data/plantas.csv` ejecuta la colección
  completa. Con 3 filas se ejecutan 12 requests × 3 = 36 requests.
- **Assertions:** comprobaciones individuales dentro de los scripts `pm.test()`.
  La corrida verificada ejecutó 62 assertions por iteración (186 en total).
